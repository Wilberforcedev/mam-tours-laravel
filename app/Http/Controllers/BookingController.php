<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
        return response()->json(Booking::all());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'carId' => 'required|exists:cars,id',
                'customerName' => 'required|string|max:200',
                'startDate' => 'required|date_format:Y-m-d',
                'endDate' => 'required|date_format:Y-m-d|after:startDate',
                'payment_method' => 'required|in:stripe,mtn_mobile_money,airtel_money,bank_transfer,cash',
                'mobile_money_number' => 'nullable|string|max:20',
                'phone_number' => 'nullable|string|max:20',
                'id_type' => 'required|in:nin,passport',
                'id_number' => 'required|string|max:50',
                'permit_number' => 'required|string|max:50',
            ]);

            // Check if user is authenticated and has verified KYC
            $user = auth()->user();
            if ($user && !$user->isKycVerified()) {
                return response()->json([
                    'error' => 'KYC verification required',
                    'message' => 'Please complete your KYC verification before booking a vehicle.'
                ], 403);
            }

            $car = Car::find($validated['carId']);
            if (!$car->isAvailable) {
                return response()->json(['error' => 'Car unavailable'], 400);
            }

            $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], []);

            $bookingData = [
                'car_id' => $validated['carId'],
                'customerName' => $validated['customerName'],
                'startDate' => $validated['startDate'],
                'endDate' => $validated['endDate'],
                'status' => 'pending',
                'pricing' => $pricing,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'phone_number' => $validated['phone_number'] ?? null,
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
            ];

            // Add user_id and kyc_id if user is authenticated
            if ($user) {
                $bookingData['user_id'] = $user->id;
                if ($user->kyc) {
                    $bookingData['kyc_id'] = $user->kyc->id;
                }
            }

            $booking = Booking::create($bookingData);

            // Store ID and Permit information in a JSON field or create audit log
            AuditLog::create([
                'action' => 'booking.create',
                'details' => [
                    'bookingId' => $booking->id,
                    'carId' => $booking->car_id,
                    'idType' => $validated['id_type'],
                    'idNumber' => $validated['id_number'],
                    'permitNumber' => $validated['permit_number']
                ],
                'at' => now(),
            ]);

            $car->update(['isAvailable' => false]);

            // Send admin notification about new booking
            $this->notificationService->sendAdminBookingNotification($booking);

            // For web requests, redirect to payment page
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Booking created successfully', 
                    'booking' => $booking,
                    'redirect_url' => route('payments.show', $booking)
                ], 201);
            } else {
                return redirect()->route('payments.show', $booking)
                    ->with('success', 'Booking created! Please complete payment to confirm your reservation.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Booking creation error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create booking', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $booking = Booking::find($id);
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

        $car = Car::find($validated['carId']);
        if (!$car->isAvailable) {
            return response()->json(['error' => 'Car unavailable'], 400);
        }

        $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], $validated['addOns'] ?? []);

        $booking = Booking::create([
            'car_id' => $validated['carId'],
            'customerName' => $validated['customerName'],
            'startDate' => $validated['startDate'],
            'endDate' => $validated['endDate'],
            'status' => 'reserved',
            'pricing' => $pricing,
            'addOns' => $validated['addOns'] ?? [],
            'expiresAt' => now()->addMinutes(30),
        ]);

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
        if (!in_array($booking->status, ['pending', 'reserved'])) {
            return response()->json(['error' => 'Booking must be pending or reserved to confirm'], 400);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmedAt' => now(),
        ]);

        // Send confirmation notification if user exists
        if ($booking->user_id) {
            $user = $booking->user;
            $this->notificationService->sendBookingConfirmation($booking, $user);
        }

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
