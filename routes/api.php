<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController;
use App\Http\Controllers\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication API
Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    if ($user->isLocked()) {
        return response()->json([
            'message' => 'Account is temporarily locked'
        ], 423);
    }

    $token = $user->createApiToken('api-access');
    $user->updateLoginInfo($request->ip());

    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]
    ]);
});

Route::middleware('auth:sanctum')->post('/auth/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();
    $kyc = $user->kyc;
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role,
        'kyc_verified' => $user->isKycVerified(),
        'kyc_status' => $kyc ? $kyc->status : 'not_submitted',
    ]);
});

// Health check
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'message' => 'MAM Tours API is running',
        'timestamp' => now(),
        'cars_count' => \App\Models\Car::count(),
        'bookings_count' => \App\Models\Booking::count(),
    ]);
});

// Images
Route::get('/images', [CarController::class, 'getImages']);

// Date-based availability and a customer-facing quotation.
Route::get('/availability', [\App\Http\Controllers\AvailabilityController::class, 'check']);

// Public Cars API (read-only)
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{id}', [CarController::class, 'show']);

// Protected Cars API (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/cars', [CarController::class, 'store']);
    Route::put('/cars/{id}', [CarController::class, 'update']);
    Route::delete('/cars/{id}', [CarController::class, 'destroy']);
});

// Protected Bookings API (authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/reserve', [BookingController::class, 'reserve']);
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    
    // Fleet staff manage physical vehicle handover and return.
    Route::middleware('role:fleet')->group(function () {
        Route::post('/bookings/{id}/check-out', [BookingController::class, 'checkout']);
        Route::put('/bookings/{id}/return', [BookingController::class, 'returnVehicle']);
    });
});

// Public Bookings API (unauthenticated users can submit bookings)
Route::post('/bookings', [BookingController::class, 'store']);
Route::middleware(['auth:sanctum', 'admin'])->get('/admin/bookings/{id}/identity-document', [BookingController::class, 'downloadIdentityDocument']);

// Payment endpoints are deliberately unavailable until a real provider workflow
// verifies amounts, currency, ownership, provider signatures, and final status.
Route::post('/payments/intent', function () {
    return response()->json([
        'message' => 'Payment processing is not configured. No payment was taken.',
        'status' => 'unavailable',
    ], 501);
});
Route::post('/payments/capture/{id}', function () {
    return response()->json([
        'message' => 'Payment capture is not configured. No payment was captured.',
        'status' => 'unavailable',
    ], 501);
});
Route::post('/payments/refund/{id}', function () {
    return response()->json([
        'message' => 'Refund processing is not configured. No refund was issued.',
        'status' => 'unavailable',
    ], 501);
});

// Staff operations: least-privilege access by role.
Route::middleware(['auth:sanctum', 'role:fleet'])->prefix('operations')->group(function () {
    Route::get('/maintenance', [\App\Http\Controllers\OperationsController::class, 'maintenanceIndex']);
    Route::post('/maintenance', [\App\Http\Controllers\OperationsController::class, 'maintenanceStore']);
    Route::put('/maintenance/{id}', [\App\Http\Controllers\OperationsController::class, 'maintenanceUpdate']);
});

Route::middleware(['auth:sanctum', 'role:finance'])->prefix('operations')->group(function () {
    Route::get('/reports/financial-summary', [\App\Http\Controllers\OperationsController::class, 'financialSummary']);
    Route::get('/reports/bookings', [\App\Http\Controllers\OperationsController::class, 'bookingsReport']);
    Route::get('/reports/management-summary', [\App\Http\Controllers\OperationsController::class, 'managementSummary']);
    Route::post('/bookings/{id}/refund', [\App\Http\Controllers\OperationsController::class, 'refundBooking']);
    Route::get('/transactions', [\App\Http\Controllers\OperationsController::class, 'transactionsIndex']);
    Route::get('/transactions/{id}/receipt', [\App\Http\Controllers\OperationsController::class, 'transactionReceipt']);
    Route::post('/transactions', [\App\Http\Controllers\OperationsController::class, 'recordFinancialTransaction']);
});

Route::middleware(['auth:sanctum', 'admin'])->put('/admin/users/{id}/role', [\App\Http\Controllers\OperationsController::class, 'updateStaffRole']);

// Protected Reports (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/reports/bookings.csv', function() {
        $bookings = \App\Models\Booking::with('car')->get();
        $csv = "id,carId,customerName,startDate,endDate,status,total,createdAt\n";
        foreach($bookings as $booking) {
            $total = $booking->pricing['total'] ?? '';
            $csv .= implode(',', [
                $booking->id,
                $booking->car_id,
                '"' . str_replace('"', '""', $booking->customerName) . '"',
                $booking->startDate,
                $booking->endDate,
                $booking->status,
                $total,
                $booking->created_at
            ]) . "\n";
        }
        return response($csv)->header('Content-Type', 'text/csv');
    });

    Route::get('/reports/fleet.csv', function() {
        $cars = \App\Models\Car::all();
        $csv = "id,brand,model,numberPlate,dailyRate,seats,category,isAvailable\n";
        foreach($cars as $car) {
            $csv .= implode(',', [
                $car->id,
                '"' . str_replace('"', '""', $car->brand) . '"',
                '"' . str_replace('"', '""', $car->model) . '"',
                $car->numberPlate,
                $car->dailyRate,
                $car->seats,
                $car->category ?? '',
                $car->isAvailable ? 1 : 0
            ]) . "\n";
        }
        return response($csv)->header('Content-Type', 'text/csv');
    });
});

// API v1 Routes
Route::prefix('v1')->middleware('api.version:v1')->group(function () {
    Route::get('/cars', [\App\Http\Controllers\Api\V1\CarApiController::class, 'index']);
    Route::get('/cars/{id}', [\App\Http\Controllers\Api\V1\CarApiController::class, 'show']);
    Route::get('/cars/search', [\App\Http\Controllers\Api\V1\CarApiController::class, 'search']);
});

// API v2 Routes (Enhanced with more features)
Route::prefix('v2')->middleware('api.version:v2')->group(function () {
    Route::get('/cars', [\App\Http\Controllers\Api\V2\CarApiController::class, 'index']);
    Route::get('/cars/{id}', [\App\Http\Controllers\Api\V2\CarApiController::class, 'show']);
    Route::get('/cars/search', [\App\Http\Controllers\Api\V2\CarApiController::class, 'search']);
});

// Stripe's provider-signed webhook is public but validates Stripe-Signature.
Route::post('/webhooks/stripe', [\App\Http\Controllers\PaymentController::class, 'stripeWebhook']);
// IntaSend callbacks validate the dashboard-configured challenge value.
Route::post('/webhooks/intasend', [\App\Http\Controllers\PaymentController::class, 'intaSendWebhook']);

// Webhook Routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/webhooks/register', [\App\Http\Controllers\WebhookController::class, 'register']);
    Route::get('/webhooks/deliveries/{id}', [\App\Http\Controllers\WebhookController::class, 'status']);
});

Route::post('/webhooks/receive', [\App\Http\Controllers\WebhookController::class, 'receive']);

// API Documentation
Route::get('/docs/spec', [\App\Http\Controllers\ApiDocController::class, 'spec']);

// Public test-upload endpoint removed; uploads must use authenticated workflows.

// KYC API v2 Routes
Route::prefix('v2')->middleware('api.version:v2')->group(function () {
    // User KYC endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/kyc/status', [\App\Http\Controllers\Api\V2\KycApiController::class, 'status']);
        Route::post('/kyc/submit', [\App\Http\Controllers\Api\V2\KycApiController::class, 'submit']);
        Route::get('/kyc/audit-trail', [\App\Http\Controllers\Api\V2\KycApiController::class, 'auditTrail']);
    });

    // Admin KYC endpoints
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/kyc')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminList']);
        Route::get('/statistics', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminStatistics']);
        Route::post('/{id}/verify', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminVerify']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminReject']);
        Route::post('/{id}/request-additional', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminRequestAdditional']);
        Route::get('/{id}/document/{type}', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminViewDocument']);
        Route::get('/{id}/audit-trail', [\App\Http\Controllers\Api\V2\KycApiController::class, 'adminAuditTrail']);
    });
});
