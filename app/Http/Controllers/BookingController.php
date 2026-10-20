<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\AuditLog;
use App\Services\NotificationService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    protected $notificationService;
    protected $analyticsService;

    public function __construct(NotificationService $notificationService, AnalyticsService $analyticsService)
    {
        $this->notificationService = $notificationService;
        $this->analyticsService = $analyticsService;
    }
    private function daysBetween($startISO, $endISO)
    {
        $start = new Carbon($startISO);
        $end = new Carbon($endISO);
        return max(1, ceil($end->diffInDays($start)));
    }

    private function computePrice($car, $startISO, $endISO, $addOns = [])
    {
        $days = $this->daysBetween($startISO, $endISO);
        $base = $car->dailyRate * $days;

        $start = new Carbon($startISO);
        $end = new Carbon($endISO);
        $weekendDays = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            if ($date->isWeekend()) {
                $weekendDays++;
            }
        }

        $base += round($car->dailyRate * 0.1 * $weekendDays);

        if ($days >= 7) {
            $base = round($base * 0.9);
        }

        $addOnTotal = 0;
        if ($addOns['driver'] ?? false) {
            $addOnTotal += 50000 * $days;
        }
        if ($addOns['childSeat'] ?? false) {
            $addOnTotal += 10000 * $days;
        }

        $subtotal = $base + $addOnTotal;
        $taxes = round($subtotal * 0.18);
        $deposit = round($subtotal * 0.2);
        $total = $subtotal + $taxes;

        return [
            'days' => $days,
            'base' => $base,
            'addOnTotal' => $addOnTotal,
            'subtotal' => $subtotal,
            'taxes' => $taxes,
            'deposit' => $deposit,
            'total' => $total,
        ];
    }

    public function index()
    {
        $user = auth()->user();
        $bookings = $user->isAdmin()
            ? Booking::with('car')->latest()->get()
            : Booking::with('car')->where('user_id', $user->id)->latest()->get();

        return response()->json($bookings);
    }

    private function findAccessibleBooking($id)
    {
        $query = Booking::query();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query->find($id);
    }

    private function hasOverlappingBooking($carId, $startDate, $endDate)
    {
        return Booking::where('car_id', $carId)
            ->whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('status', 'in_use')
                    ->orWhere(function ($dateRange) use ($startDate, $endDate) {
                        $dateRange
                            ->where('startDate', '<', Carbon::parse($endDate)->startOfDay()->toDateTimeString())
                            ->where('endDate', '>', Carbon::parse($startDate)->startOfDay()->toDateTimeString());
                    });
            })
            ->where(function ($query) {
                $query->where('status', '!=', 'reserved')
                    ->orWhereNull('expiresAt')
                    ->orWhere('expiresAt', '>', now());
            })
            ->exists();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'carId' => 'required|exists:cars,id',
                'customerName' => 'required|string|max:200',
                'customerEmail' => 'required|email|max:200',
                'customerPhone' => 'required|string|max:20',
                'startDate' => 'required|date_format:Y-m-d',
                'endDate' => 'required|date_format:Y-m-d|after:startDate',
                'paymentMethod' => 'required|in:stripe,mtn_mobile_money,airtel_money,bank_transfer,cash',
                'mobileMoneyNumber' => 'nullable|string|max:20',
                'idType' => 'nullable|in:nin,passport',
                'idNumber' => 'nullable|string|max:50',
                'idDocument' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            // Check if user is trying to use cashless payment without ID verification
            $user = auth()->user();
            $isCashlessPayment = in_array($validated['paymentMethod'], ['stripe', 'bank_transfer']);
            
            if ($isCashlessPayment && $user && !$user->id_document) {
                return response()->json([
                    'message' => 'ID verification required for cashless payments',
                    'errors' => ['paymentMethod' => ['Please upload your ID/passport in your profile to use cashless payments.']]
                ], 422);
            }

            $car = Car::findOrFail($validated['carId']);
            $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], []);

            // Handle file upload
            $idDocumentPath = null;
            try {
                if ($request->hasFile('idDocument')) {
                    $file = $request->file('idDocument');
                    \Log::info('File upload attempt', [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'path' => $file->getRealPath()
                    ]);
                    
                    // Ensure directory exists
                    $storagePath = storage_path('app/public/bookings/id-documents');
                    if (!is_dir($storagePath)) {
                        mkdir($storagePath, 0755, true);
                        \Log::info('Created storage directory: ' . $storagePath);
                    }
                    
                    // Try to store in public disk
                    $idDocumentPath = $file->store('bookings/id-documents', 'public');
                    \Log::info('File uploaded successfully: ' . $idDocumentPath);
                } else {
                    \Log::warning('No file in request');
                }
            } catch (\Exception $fileError) {
                \Log::error('File upload error: ' . $fileError->getMessage() . ' | ' . $fileError->getFile() . ':' . $fileError->getLine());
                // Continue without file if upload fails - don't throw
                $idDocumentPath = null;
            }

            $bookingData = [
                'car_id' => $validated['carId'],
                'customerName' => $validated['customerName'],
                'customerEmail' => $validated['customerEmail'],
                'customerPhone' => $validated['customerPhone'],
                'startDate' => $validated['startDate'],
                'endDate' => $validated['endDate'],
                'status' => 'pending',
                'totalPrice' => $pricing['total'],
                'pricing' => json_encode($pricing),
                'payment_method' => $validated['paymentMethod'],
                'payment_status' => 'pending',
                'mobile_money_number' => $validated['mobileMoneyNumber'] ?? null,
                'idType' => $validated['idType'] ?? null,
                'idNumber' => $validated['idNumber'] ?? null,
                'idDocument' => $idDocumentPath,
            ];

            // Add user_id if user is authenticated
            if ($user) {
                $bookingData['user_id'] = $user->id;
            }

            $booking = DB::transaction(function () use ($validated, &$pricing, &$car, &$bookingData) {
                $car = Car::whereKey($validated['carId'])->lockForUpdate()->firstOrFail();

                if (!$car->isAvailable || $this->hasOverlappingBooking(
                    $car->id,
                    $validated['startDate'],
                    $validated['endDate']
                )) {
                    return null;
                }

                $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], []);
                $bookingData['totalPrice'] = $pricing['total'];
                $bookingData['pricing'] = json_encode($pricing);

                return Booking::create($bookingData);
            });

            if (!$booking) {
                return response()->json(['message' => 'Car is not available for the requested dates'], 409);
            }

            // Track booking creation
            $this->analyticsService->trackBookingCreated($booking->id, [
                'car_id' => $booking->car_id,
                'paymentMethod' => $booking->paymentMethod,
                'total_amount' => $pricing['total'],
                'days' => $pricing['days'],
            ]);

            // Store audit log
            AuditLog::create([
                'action' => 'booking.create',
                'details' => [
                    'bookingId' => $booking->id,
                    'carId' => $booking->car_id,
                    'idType' => $validated['idType'] ?? null,
                    'idNumber' => $validated['idNumber'] ?? null,
                    'idDocument' => $idDocumentPath,
                ],
                'at' => now(),
            ]);

            // Send admin notification about new booking
            $this->notificationService->sendAdminBookingNotification($booking);

            return response()->json([
                'message' => 'Booking submitted successfully! Admin will confirm shortly.',
                'booking' => $booking,
                'redirect_url' => route('dashboard')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Booking creation error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Failed to submit booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $booking = $this->findAccessibleBooking($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        return response()->json($booking);
    }

    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'carId' => 'required|exists:cars,id',
            'customerName' => 'required|string|max:200',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'addOns' => 'nullable|array',
        ]);

        $booking = DB::transaction(function () use ($validated) {
            $car = Car::whereKey($validated['carId'])->lockForUpdate()->firstOrFail();

            if (!$car->isAvailable || $this->hasOverlappingBooking(
                $car->id,
                $validated['startDate'],
                $validated['endDate']
            )) {
                return null;
            }

            $pricing = $this->computePrice(
                $car,
                $validated['startDate'],
                $validated['endDate'],
                $validated['addOns'] ?? []
            );

            return Booking::create([
                'car_id' => $car->id,
                'user_id' => auth()->id(),
                'customerName' => $validated['customerName'],
                'startDate' => $validated['startDate'],
                'endDate' => $validated['endDate'],
                'status' => 'reserved',
                'pricing' => $pricing,
                'addOns' => $validated['addOns'] ?? [],
                'expiresAt' => now()->addMinutes(30),
            ]);
        });

        if (!$booking) {
            return response()->json(['error' => 'Car unavailable for the requested dates'], 409);
        }

        AuditLog::create([
            'action' => 'booking.reserve',
            'details' => ['bookingId' => $booking->id, 'carId' => $booking->car_id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking reserved', 'booking' => $booking], 201);
    }

    public function confirm($id)
    {
        $booking = $this->findAccessibleBooking($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        if (!in_array($booking->status, ['pending', 'reserved'])) {
            return response()->json(['error' => 'Booking must be pending or reserved to confirm'], 400);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmedAt' => now(),
        ]);

        // Send confirmation notification via email and SMS
        \App\Jobs\SendBookingConfirmationNotification::dispatch($booking, 'confirmed');

        AuditLog::create([
            'action' => 'booking.confirm',
            'details' => ['bookingId' => $booking->id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking confirmed', 'booking' => $booking]);
    }

    public function checkout($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        if ($booking->status !== 'confirmed') {
            return response()->json(['error' => 'Booking must be confirmed'], 400);
        }

        $booking->update([
            'status' => 'in_use',
            'checkedOutAt' => now(),
        ]);

        AuditLog::create([
            'action' => 'booking.checkout',
            'details' => ['bookingId' => $booking->id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Vehicle checked out', 'booking' => $booking]);
    }

    public function cancel($id)
    {
        $booking = $this->findAccessibleBooking($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        if (in_array($booking->status, ['completed', 'returned', 'canceled', 'expired'])) {
            return response()->json(['error' => 'Cannot cancel'], 400);
        }

        $booking->update([
            'status' => 'canceled',
            'canceledAt' => now(),
        ]);

        // Send cancellation notification if user exists
        if ($booking->user_id) {
            $user = $booking->user;
            $this->notificationService->sendBookingCancellation($booking, $user);
        }

        AuditLog::create([
            'action' => 'booking.cancel',
            'details' => ['bookingId' => $booking->id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking canceled', 'booking' => $booking]);
    }

    public function returnVehicle($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        if (!in_array($booking->status, ['in_use', 'confirmed'])) {
            return response()->json(['error' => 'Booking not in use'], 400);
        }

        $booking->update([
            'status' => 'completed',
            'returnedAt' => now(),
        ]);

        // Send completion notification if user exists
        if ($booking->user_id) {
            $user = $booking->user;
            $this->notificationService->sendBookingCompletion($booking, $user);
        }

        AuditLog::create([
            'action' => 'booking.return',
            'details' => ['bookingId' => $booking->id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking marked as returned successfully', 'booking' => $booking]);
    }
}
