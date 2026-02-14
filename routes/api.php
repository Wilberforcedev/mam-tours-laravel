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
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/reserve', [BookingController::class, 'reserve']);
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    
    // Admin-only booking operations
    Route::middleware('admin')->group(function () {
        Route::post('/bookings/{id}/check-out', [BookingController::class, 'checkout']);
        Route::put('/bookings/{id}/return', [BookingController::class, 'returnVehicle']);
    });
});

// Payment stubs (for compatibility)
Route::post('/payments/intent', function(Request $request) {
    return response()->json(['message' => 'Payment authorized', 'status' => 'authorized']);
});
Route::post('/payments/capture/{id}', function($id) {
    return response()->json(['message' => 'Payment captured']);
});
Route::post('/payments/refund/{id}', function($id) {
    return response()->json(['message' => 'Payment refunded']);
});

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
