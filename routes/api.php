<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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

    $throttleKey = 'api-login:' . Str::lower($request->email) . '|' . $request->ip();

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        return response()->json([
            'message' => 'Too many login attempts. Please try again in ' . RateLimiter::availableIn($throttleKey) . ' seconds.',
        ], 429);
    }

    $user = \App\Models\User::where('email', $request->email)->first();

    // Always run a hash check so response time does not reveal whether the account exists.
    $passwordMatches = Hash::check(
        $request->password,
        $user->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    );

    if (!$user || !$passwordMatches) {
        RateLimiter::hit($throttleKey, 60);
        $user?->incrementFailedAttempts();

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    if ($user->isLocked()) {
        return response()->json([
            'message' => 'Account is temporarily locked'
        ], 423);
    }

    RateLimiter::clear($throttleKey);

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
})->middleware('throttle:20,1');

Route::middleware('auth:sanctum')->post('/auth/logout', function (Request $request) {
    $request->user()->currentAccessToken()?->delete();
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

// Liveness/readiness probe: no data leaked, 503 when the database is unreachable.
Route::get('/health', function () {
    try {
        DB::select('select 1');
    } catch (\Throwable $e) {
        report($e);

        return response()->json(['status' => 'error'], 503);
    }

    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Images
Route::get('/images', [CarController::class, 'getImages']);

// Public Cars API (read-only)
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{id}', [CarController::class, 'show']);

// Protected Cars API (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/cars', [CarController::class, 'store']);
    Route::put('/cars/{id}', [CarController::class, 'update']);
    Route::delete('/cars/{id}', [CarController::class, 'destroy']);
});

// Bookings API (authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/bookings/reserve', [BookingController::class, 'reserve'])->middleware('throttle:10,1');
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Admin-only booking operations
    Route::middleware('admin')->group(function () {
        Route::post('/bookings/{id}/check-out', [BookingController::class, 'checkout']);
        Route::put('/bookings/{id}/return', [BookingController::class, 'returnVehicle']);
    });
});

// Protected Reports (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/reports/bookings.csv', function () {
        $filename = 'bookings-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'carId', 'customerName', 'startDate', 'endDate', 'status', 'total', 'createdAt']);

            \App\Models\Booking::with('car')->lazyById(500)->each(function ($booking) use ($out) {
                fputcsv($out, [
                    $booking->id,
                    $booking->car_id,
                    $booking->customerName,
                    $booking->startDate,
                    $booking->endDate,
                    $booking->status,
                    $booking->pricing['total'] ?? '',
                    $booking->created_at,
                ]);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    });

    Route::get('/reports/fleet.csv', function () {
        $filename = 'fleet-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'brand', 'model', 'numberPlate', 'dailyRate', 'seats', 'category', 'isAvailable']);

            \App\Models\Car::query()->lazyById(500)->each(function ($car) use ($out) {
                fputcsv($out, [
                    $car->id,
                    $car->brand,
                    $car->model,
                    $car->numberPlate,
                    $car->dailyRate,
                    $car->seats,
                    $car->category ?? '',
                    $car->isAvailable ? 1 : 0,
                ]);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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

// Webhook Routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/webhooks/register', [\App\Http\Controllers\WebhookController::class, 'register']);
    Route::get('/webhooks/deliveries/{id}', [\App\Http\Controllers\WebhookController::class, 'status']);
});

Route::post('/webhooks/receive', [\App\Http\Controllers\WebhookController::class, 'receive']);

// API Documentation
Route::get('/docs/spec', [\App\Http\Controllers\ApiDocController::class, 'spec']);

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
