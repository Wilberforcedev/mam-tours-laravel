<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\AuditLog;
use App\Services\NotificationService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::with('car')->latest();
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
    }

    private function carUnavailableResponse()
    {
        return response()->json(['message' => 'Car is not available for the selected dates'], 409);
    }

    /**
     * Locks the car row so concurrent requests serialize, then rejects any overlapping active booking.
     */
    private function lockAvailableCar(int $carId, Carbon $start, Carbon $end): ?Car
    {
        $car = Car::whereKey($carId)->lockForUpdate()->first();

        if (!$car || !$car->isAvailable) {
            return null;
        }

        $conflict = Booking::active()->overlapping($car->id, $start, $end)->exists();

        return $conflict ? null : $car;
    }

    private function releaseCarIfIdle(Car $car, int $exceptBookingId): void
    {
        $stillBooked = Booking::active()
            ->where('car_id', $car->id)
            ->whereKeyNot($exceptBookingId)
            ->exists();

        if (!$stillBooked) {
            $car->update(['isAvailable' => true]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'carId' => 'required|exists:cars,id',
                'customerName' => 'required|string|max:200',
                'customerEmail' => 'required|email|max:200',
                'customerPhone' => 'required|string|max:20',
                'startDate' => 'required|date_format:Y-m-d|after_or_equal:today',
                'endDate' => 'required|date_format:Y-m-d|after:startDate',
                'paymentMethod' => 'required|in:stripe,mtn_mobile_money,airtel_money,bank_transfer,cash',
                'mobileMoneyNumber' => 'nullable|string|max:20',
                'idType' => 'nullable|in:nin,passport',
                'idNumber' => 'nullable|string|max:50',
                'idDocument' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            $user = $request->user();
            $isCashlessPayment = in_array($validated['paymentMethod'], ['stripe', 'bank_transfer']);

            if ($isCashlessPayment && !$user->id_document) {
                return response()->json([
                    'message' => 'ID verification required for cashless payments',
                    'errors' => ['paymentMethod' => ['Please upload your ID/passport in your profile to use cashless payments.']]
                ], 422);
            }

            $idDocumentPath = $request->hasFile('idDocument')
                ? $request->file('idDocument')->store('bookings/id-documents', 'private')
                : null;

            $start = Carbon::parse($validated['startDate']);
            $end = Carbon::parse($validated['endDate']);

            $booking = DB::transaction(function () use ($validated, $user, $idDocumentPath, $start, $end) {
                $car = $this->lockAvailableCar((int) $validated['carId'], $start, $end);
                if (!$car) {
                    return null;
                }

                $pricing = $this->computePrice($car, $validated['startDate'], $validated['endDate'], []);

                $booking = Booking::create([
                    'car_id' => $car->id,
                    'user_id' => $user->id,
                    'customerName' => $validated['customerName'],
                    'customerEmail' => $validated['customerEmail'],
                    'customerPhone' => $validated['customerPhone'],
                    'startDate' => $start,
                    'endDate' => $end,
                    'status' => 'pending',
                    'totalPrice' => $pricing['total'],
                    'pricing' => $pricing,
                    'payment_method' => $validated['paymentMethod'],
                    'payment_status' => 'pending',
                    'mobile_money_number' => $validated['mobileMoneyNumber'] ?? null,
                    'idType' => $validated['idType'] ?? null,
                    'idNumber' => $validated['idNumber'] ?? null,
                    'idDocument' => $idDocumentPath,
                ]);

                $car->update(['isAvailable' => false]);

                AuditLog::create([
                    'action' => 'booking.create',
                    'details' => [
                        'bookingId' => $booking->id,
                        'carId' => $booking->car_id,
                        'idType' => $validated['idType'] ?? null,
                        'idDocument' => $idDocumentPath,
                    ],
                    'at' => now(),
                ]);

                return $booking;
            });

            if (!$booking) {
                if ($idDocumentPath) {
                    Storage::disk('private')->delete($idDocumentPath);
                }
                return $this->carUnavailableResponse();
            }

            // Side effects must not turn a persisted booking into a 500.
            try {
                $this->analyticsService->trackBookingCreated($booking->id, [
                    'car_id' => $booking->car_id,
                    'paymentMethod' => $booking->payment_method,
                    'total_amount' => $booking->pricing['total'],
                    'days' => $booking->pricing['days'],
                ]);

                $this->notificationService->sendAdminBookingNotification($booking);
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'message' => 'Booking submitted successfully! Admin will confirm shortly.',
                'booking' => $booking,
                'redirect_url' => route('dashboard')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Booking creation error', ['exception' => $e]);
            return response()->json(['message' => 'Failed to submit booking. Please try again.'], 500);
        }
    }

    public function show($id)
    {
        $booking = Booking::with('car')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        $this->authorize('view', $booking);

        return response()->json($booking);
    }

    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'carId' => 'required|exists:cars,id',
            'customerName' => 'required|string|max:200',
            'startDate' => 'required|date|after_or_equal:today',
            'endDate' => 'required|date|after:startDate',
            'addOns' => 'nullable|array',
            'addOns.driver' => 'nullable|boolean',
            'addOns.childSeat' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $start = Carbon::parse($validated['startDate']);
        $end = Carbon::parse($validated['endDate']);

        $booking = DB::transaction(function () use ($validated, $user, $start, $end) {
            $car = $this->lockAvailableCar((int) $validated['carId'], $start, $end);
            if (!$car) {
                return null;
            }

            $addOns = $validated['addOns'] ?? [];
            $booking = Booking::create([
                'car_id' => $car->id,
                'user_id' => $user->id,
                'customerName' => $validated['customerName'],
                'startDate' => $start,
                'endDate' => $end,
                'status' => 'reserved',
                'pricing' => $this->computePrice($car, $validated['startDate'], $validated['endDate'], $addOns),
                'addOns' => $addOns,
                'expiresAt' => now()->addMinutes(30),
            ]);

            $car->update(['isAvailable' => false]);

            AuditLog::create([
                'action' => 'booking.reserve',
                'details' => ['bookingId' => $booking->id, 'carId' => $booking->car_id],
                'at' => now(),
            ]);

            return $booking;
        });

        if (!$booking) {
            return $this->carUnavailableResponse();
        }

        return response()->json(['message' => 'Booking reserved', 'booking' => $booking], 201);
    }

    public function confirm($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        $this->authorize('confirm', $booking);

        if (!in_array($booking->status, Booking::HOLD_STATUSES, true)) {
            return response()->json(['message' => 'Booking must be pending or reserved to confirm'], 400);
        }
        if ($booking->isExpiredHold()) {
            return response()->json(['message' => 'Booking has expired'], 400);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmedAt' => now(),
        ]);

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
            return response()->json(['message' => 'Booking not found'], 404);
        }
        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'Booking must be confirmed'], 400);
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
            return response()->json(['message' => 'Booking not found'], 404);
        }
        $this->authorize('cancel', $booking);

        if (!in_array($booking->status, ['pending', 'reserved', 'confirmed'], true)) {
            return response()->json(['message' => 'Cannot cancel'], 400);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'canceled',
                'canceledAt' => now(),
            ]);

            if ($booking->car) {
                $this->releaseCarIfIdle($booking->car, $booking->id);
            }
        });

        if ($booking->user_id) {
            $this->notificationService->sendBookingCancellation($booking, $booking->user);
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
            return response()->json(['message' => 'Booking not found'], 404);
        }
        if (!in_array($booking->status, ['in_use', 'confirmed'], true)) {
            return response()->json(['message' => 'Booking not in use'], 400);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'completed',
                'returnedAt' => now(),
            ]);

            if ($booking->car) {
                $this->releaseCarIfIdle($booking->car, $booking->id);
            }
        });

        if ($booking->user_id) {
            $this->notificationService->sendBookingCompletion($booking, $booking->user);
        }

        AuditLog::create([
            'action' => 'booking.return',
            'details' => ['bookingId' => $booking->id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking marked as returned successfully', 'booking' => $booking]);
    }
}
