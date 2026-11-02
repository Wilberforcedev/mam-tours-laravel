<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\AuditLog;
use App\Services\AnalyticsService;
use App\Services\BookingAvailabilityService;
use App\Services\NotificationService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BookingController extends Controller
{
    private const STAFF_ROLES = ['admin', 'booking', 'finance'];

    protected $notificationService;
    protected $analyticsService;

    public function __construct(NotificationService $notificationService, AnalyticsService $analyticsService)
    {
        $this->notificationService = $notificationService;
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::with('car')->latest();
        if (!in_array($user->role, self::STAFF_ROLES, true)) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
    }

    private function carUnavailableResponse()
    {
        return response()->json(['message' => 'Car is not available for the selected dates'], 409);
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
                'pickupTime' => 'nullable|date_format:H:i',
                'returnTime' => 'nullable|date_format:H:i',
                'paymentMethod' => 'required|in:stripe,mtn_mobile_money,airtel_money,bank_transfer,cash',
                'mobileMoneyNumber' => 'nullable|string|max:20',
                'idType' => 'nullable|in:nin,passport',
                'idNumber' => 'nullable|string|max:50',
                'idDocument' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'addOns' => 'nullable|array',
                'addOns.driver' => 'nullable|boolean',
                'addOns.childSeat' => 'nullable|boolean',
                'addOns.delivery' => 'nullable|boolean',
                'addOns.discountCode' => 'nullable|string|max:50',
            ]);
        } catch (ValidationException $e) {
            // This endpoint accepts multipart form posts, so return JSON explicitly
            // instead of the framework's redirect response.
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $user = $request->user();
        $isCashlessPayment = in_array($validated['paymentMethod'], ['stripe', 'bank_transfer'], true);

        if ($isCashlessPayment && !$user->id_document) {
            return response()->json([
                'message' => 'ID verification required for cashless payments',
                'errors' => ['paymentMethod' => ['Please upload your ID/passport in your profile to use cashless payments.']],
            ], 422);
        }

        $idDocumentPath = $request->hasFile('idDocument')
            ? $request->file('idDocument')->store('bookings/id-documents', 'private')
            : null;

        $pickupTime = $validated['pickupTime'] ?? config('rental.default_pickup_time', '09:00');
        $returnTime = $validated['returnTime'] ?? config('rental.default_return_time', '09:00');
        $pickupAt = Carbon::parse($validated['startDate'] . ' ' . $pickupTime);
        $returnAt = Carbon::parse($validated['endDate'] . ' ' . $returnTime);
        $holdMinutes = (int) config('rental.booking_hold_minutes', 30);

        try {
            $booking = DB::transaction(function () use ($validated, $user, $idDocumentPath, $pickupAt, $returnAt, $pickupTime, $returnTime, $holdMinutes) {
                try {
                    $car = app(BookingAvailabilityService::class)->lockAndAssertAvailable(
                        (int) $validated['carId'],
                        (string) $pickupAt,
                        (string) $returnAt
                    );
                } catch (ValidationException $e) {
                    return null;
                }

                $pricing = app(PricingService::class)->quote(
                    $car,
                    $validated['startDate'],
                    $validated['endDate'],
                    $validated['addOns'] ?? []
                );

                $booking = Booking::create([
                    'car_id' => $car->id,
                    'user_id' => $user->id,
                    'customerName' => $validated['customerName'],
                    'customerEmail' => $validated['customerEmail'],
                    'customerPhone' => $validated['customerPhone'],
                    'startDate' => $pickupAt,
                    'endDate' => $returnAt,
                    'pickup_time' => $pickupTime,
                    'return_time' => $returnTime,
                    'status' => 'pending',
                    'expiresAt' => now()->addMinutes($holdMinutes),
                    'totalPrice' => $pricing['total'],
                    'pricing' => $pricing,
                    'addOns' => $validated['addOns'] ?? [],
                    'payment_method' => $validated['paymentMethod'],
                    'payment_status' => 'pending',
                    'mobile_money_number' => $validated['mobileMoneyNumber'] ?? null,
                    'idType' => $validated['idType'] ?? null,
                    'idNumber' => $validated['idNumber'] ?? null,
                    'idDocument' => $idDocumentPath,
                ]);

                AuditLog::create([
                    'action' => 'booking.create',
                    'details' => [
                        'bookingId' => $booking->id,
                        'carId' => $booking->car_id,
                        'identityDocumentProvided' => !empty($idDocumentPath),
                    ],
                    'at' => now(),
                ]);

                return $booking;
            });
        } catch (ValidationException $e) {
            if ($idDocumentPath) {
                Storage::disk('private')->delete($idDocumentPath);
            }

            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            if ($idDocumentPath) {
                Storage::disk('private')->delete($idDocumentPath);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if ($idDocumentPath) {
                Storage::disk('private')->delete($idDocumentPath);
            }

            Log::error('Booking creation error', ['exception' => $e]);

            return response()->json(['message' => 'Failed to submit booking. Please try again.'], 500);
        }

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
            'redirect_url' => route('dashboard'),
        ], 201);
    }

    public function downloadIdentityDocument($id)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $booking = Booking::findOrFail($id);
        $path = $booking->idDocument;
        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json(['message' => 'Identity document not found'], 404);
        }
        return Storage::disk('private')->download($path);
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
            'carId' => 'required_without:car_id|exists:cars,id',
            'car_id' => 'required_without:carId|exists:cars,id',
            'customerName' => 'required|string|max:200',
            'startDate' => 'required|date|after_or_equal:today',
            'endDate' => 'required|date|after:startDate',
            'pickupTime' => 'nullable|date_format:H:i',
            'returnTime' => 'nullable|date_format:H:i',
            'addOns' => 'nullable|array',
            'addOns.driver' => 'nullable|boolean',
            'addOns.childSeat' => 'nullable|boolean',
            'addOns.delivery' => 'nullable|boolean',
            'addOns.discountCode' => 'nullable|string|max:50',
        ]);

        $carId = (int) ($validated['carId'] ?? $validated['car_id']);
        $pickupTime = $validated['pickupTime'] ?? config('rental.default_pickup_time', '09:00');
        $returnTime = $validated['returnTime'] ?? config('rental.default_return_time', '09:00');
        $pickupDate = Carbon::parse($validated['startDate'])->toDateString();
        $returnDate = Carbon::parse($validated['endDate'])->toDateString();
        $pickupAt = Carbon::parse($pickupDate . ' ' . $pickupTime);
        $returnAt = Carbon::parse($returnDate . ' ' . $returnTime);
        $holdMinutes = (int) config('rental.booking_hold_minutes', 30);

        try {
            $booking = DB::transaction(function () use ($validated, $carId, $pickupDate, $returnDate, $pickupAt, $returnAt, $pickupTime, $returnTime, $holdMinutes) {
                try {
                    $car = app(BookingAvailabilityService::class)->lockAndAssertAvailable(
                        $carId,
                        (string) $pickupAt,
                        (string) $returnAt
                    );
                } catch (ValidationException $e) {
                    return null;
                }

                $pricing = app(PricingService::class)->quote(
                    $car,
                    $pickupDate,
                    $returnDate,
                    $validated['addOns'] ?? []
                );

                return Booking::create([
                    'car_id' => $car->id,
                    'user_id' => auth()->id(),
                    'customerName' => $validated['customerName'],
                    'startDate' => $pickupAt,
                    'endDate' => $returnAt,
                    'pickup_time' => $pickupTime,
                    'return_time' => $returnTime,
                    'status' => 'reserved',
                    'expiresAt' => now()->addMinutes($holdMinutes),
                    'pricing' => $pricing,
                    'totalPrice' => $pricing['total'],
                    'addOns' => $validated['addOns'] ?? [],
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (!$booking) {
            return $this->carUnavailableResponse();
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
            'expiresAt' => null,
        ]);

        \App\Jobs\SendBookingConfirmationNotification::dispatch($booking, 'confirmed');

        AuditLog::create([
            'action' => 'booking.confirm',
            'details' => ['bookingId' => $booking->id],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Booking confirmed', 'booking' => $booking]);
    }

    public function checkout(Request $request, $id)
    {
        $validated = $request->validate([
            'pickup_odometer_km' => 'nullable|integer|min:0',
            'pickup_fuel_percent' => 'nullable|integer|min:0|max:100',
            'pickup_condition_notes' => 'nullable|string|max:5000',
        ]);
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'Booking must be confirmed'], 400);
        }

        $booking->update(array_merge($validated, [
            'status' => 'in_use',
            'checkedOutAt' => now(),
            'conditionReports' => array_merge($booking->conditionReports ?? [], [
                'pickup' => [
                    'odometer_km' => $validated['pickup_odometer_km'] ?? null,
                    'fuel_percent' => $validated['pickup_fuel_percent'] ?? null,
                    'notes' => $validated['pickup_condition_notes'] ?? null,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ]),
        ]));

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

    public function returnVehicle(Request $request, $id)
    {
        $validated = $request->validate([
            'return_odometer_km' => 'nullable|integer|min:0',
            'return_fuel_percent' => 'nullable|integer|min:0|max:100',
            'return_condition_notes' => 'nullable|string|max:5000',
        ]);
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        if (!in_array($booking->status, ['in_use', 'confirmed'], true)) {
            return response()->json(['message' => 'Booking not in use'], 400);
        }

        if (isset($validated['return_odometer_km'], $booking->pickup_odometer_km)
            && $validated['return_odometer_km'] < $booking->pickup_odometer_km) {
            return response()->json(['error' => 'Return odometer cannot be lower than pickup odometer.'], 422);
        }

        $booking->update(array_merge($validated, [
            'status' => 'completed',
            'returnedAt' => now(),
            'conditionReports' => array_merge($booking->conditionReports ?? [], [
                'return' => [
                    'odometer_km' => $validated['return_odometer_km'] ?? null,
                    'fuel_percent' => $validated['return_fuel_percent'] ?? null,
                    'notes' => $validated['return_condition_notes'] ?? null,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ]),
        ]));

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
