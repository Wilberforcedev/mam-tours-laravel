<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\AuditLog;
use App\Services\NotificationService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
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
        return app(\App\Services\PricingService::class)->quote($car, $startISO, $endISO, $addOns);
    }

    public function index()
    {
        $user = auth()->user();

        // Only administrators may list all customer bookings.
        $staffCanViewAll = $user && in_array($user->role, ['admin', 'booking', 'finance'], true);
        $bookings = $staffCanViewAll
            ? Booking::with('car')->latest()->get()
            : Booking::where('user_id', $user->id)->with('car')->latest()->get();

        return response()->json($bookings);
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
                'addOns' => 'nullable|array',
                'addOns.driver' => 'nullable|boolean',
                'addOns.childSeat' => 'nullable|boolean',
                'addOns.delivery' => 'nullable|boolean',
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

            // Hold a row lock on the vehicle until the booking is persisted. Concurrent
            // requests for this same vehicle serialize before checking date overlap.
            \Illuminate\Support\Facades\DB::beginTransaction();
            $car = Car::whereKey($validated['carId'])->lockForUpdate()->firstOrFail();
            $overlap = Booking::where('car_id', $car->id)
                ->whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])
                ->where('startDate', '<', $validated['endDate'])
                ->where('endDate', '>', $validated['startDate'])
                ->exists();
            $maintenanceBlock = \App\Models\VehicleMaintenanceRecord::where('car_id', $car->id)
                ->where('blocks_rental', true)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->where(function ($q) use ($validated) {
                    $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<', $validated['endDate']);
                })->exists();
            if ($overlap || $maintenanceBlock) {
                \Illuminate\Support\Facades\DB::rollBack();
                return response()->json(['message' => 'Vehicle is already booked for the selected dates.'], 409);
            }

            $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], $validated['addOns'] ?? []);

            // Handle file upload
            $idDocumentPath = null;
            try {
                if ($request->hasFile('idDocument')) {
                    $file = $request->file('idDocument');
                    // Avoid logging personal file metadata or temporary filesystem paths.
                    
                    // Ensure directory exists
                    $storagePath = storage_path('app/private/bookings/id-documents');
                    if (!is_dir($storagePath)) {
                        mkdir($storagePath, 0755, true);
                        // Directory created for private document storage.
                    }
                    
                    // Try to store in public disk
                    $idDocumentPath = $file->store('bookings/id-documents', 'private');
                    // Never log identity document paths.
                } else {
                    // Identity document upload is optional for this endpoint.
                }
            } catch (\Exception $fileError) {
                \Log::error('Identity document upload failed.');
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

            $booking = Booking::create($bookingData);
            \Illuminate\Support\Facades\DB::commit();

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
                    // Do not copy identity numbers or document paths into audit logs.
                    'identityDocumentProvided' => !empty($idDocumentPath),
                ],
                'at' => now(),
            ]);

            // Mark car as unavailable
            $car->update(['isAvailable' => false]);

            // Send admin notification about new booking
            $this->notificationService->sendAdminBookingNotification($booking);

            return response()->json([
                'message' => 'Booking submitted successfully! Admin will confirm shortly.',
                'booking' => [
                    'id' => $booking->id,
                    'car_id' => $booking->car_id,
                    'customerName' => $booking->customerName,
                    'startDate' => $booking->startDate,
                    'endDate' => $booking->endDate,
                    'status' => $booking->status,
                    'pricing' => $pricing,
                    'payment_status' => $booking->payment_status,
                ],
                'redirect_url' => route('dashboard')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) { \Illuminate\Support\Facades\DB::rollBack(); }
            \Log::warning('Booking validation failed.');
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) { \Illuminate\Support\Facades\DB::rollBack(); }
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) { \Illuminate\Support\Facades\DB::rollBack(); }
            \Log::error('Booking creation failed.', ['exception_class' => get_class($e)]);
            return response()->json(['message' => 'Failed to submit booking. Please try again or contact support.'], 500);
        }
    }

    public function show($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $user = auth()->user();
        if (!$user || (!in_array($user->role, ['admin', 'booking', 'finance'], true) && (int) $booking->user_id !== (int) $user->id)) {
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

        try {
            $booking = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
                $car = app(\App\Services\BookingAvailabilityService::class)->lockAndAssertAvailable(
                    (int) $validated['carId'], $validated['startDate'], $validated['endDate']
                );
                $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], $validated['addOns'] ?? []);

                return Booking::create([
                    'car_id' => $validated['carId'],
                    'customerName' => $validated['customerName'],
                    'startDate' => $validated['startDate'],
                    'endDate' => $validated['endDate'],
                    'status' => 'reserved',
                    'pricing' => $pricing,
                    'totalPrice' => $pricing['total'],
                    'addOns' => $validated['addOns'] ?? [],
                    'expiresAt' => now()->addMinutes(30),
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Car unavailable for those dates', 'errors' => $e->errors()], 409);
        }

        $car = $booking->car;
        $car->update(['isAvailable' => false]);

        AuditLog::create([
            'action' => 'booking.reserve',
            'details' => ['bookingId' => $booking->id, 'carId' => $booking->car_id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking reserved', 'booking' => $booking], 201);
    }

    public function confirm($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        $user = auth()->user();
        if (!$user || (!in_array($user->role, ['admin', 'booking'], true) && (int) $booking->user_id !== (int) $user->id)) {
            return response()->json(['error' => 'Forbidden'], 403);
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
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        $user = auth()->user();
        if (!$user || (!in_array($user->role, ['admin', 'booking'], true) && (int) $booking->user_id !== (int) $user->id)) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        if (in_array($booking->status, ['completed', 'returned', 'canceled', 'expired'])) {
            return response()->json(['error' => 'Cannot cancel'], 400);
        }

        $booking->update([
            'status' => 'canceled',
            'canceledAt' => now(),
        ]);

        $booking->car->update(['isAvailable' => true]);

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

        $booking->car->update(['isAvailable' => true]);

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
