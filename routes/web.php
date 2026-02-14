<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public routes
Route::get('/', function () {
    return view('home');
});

Route::get('/home', function () {
    return view('home');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/faqs', function () {
    return view('faqs');
});

// Public API routes
Route::get('/api/reviews', [ReviewController::class, 'getApprovedReviews']);

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password reset routes
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/kyc', [KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc', [KycController::class, 'store'])->name('kyc.store');

    // Review routes
    Route::get('/reviews/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // Payment routes
    Route::get('/payments/{booking}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{booking}/stripe', [PaymentController::class, 'processStripe'])->name('payments.stripe');
    Route::post('/payments/{booking}/confirm-stripe', [PaymentController::class, 'confirmStripe'])->name('payments.confirm-stripe');
    Route::post('/payments/{booking}/mobile-money', [PaymentController::class, 'processMobileMoney'])->name('payments.mobile-money');
    Route::get('/invoices/{booking}', [PaymentController::class, 'generateInvoice'])->name('invoices.download');

    // Webhook routes (outside auth middleware)
    Route::post('/webhooks/mobile-money', [PaymentController::class, 'mobileMoneyWebhook'])->name('webhooks.mobile-money');

    // Admin routes
    Route::middleware('admin')->group(function () {
        // KYC Admin routes
        Route::get('/admin/kyc', [KycController::class, 'adminIndex'])->name('kyc.admin');
        Route::get('/api/kyc', [KycController::class, 'adminList'])->name('kyc.list');
        Route::put('/api/kyc/{id}/verify', [KycController::class, 'verify'])->name('kyc.verify');
        Route::put('/api/kyc/{id}/reject', [KycController::class, 'reject'])->name('kyc.reject');
        Route::get('/api/kyc/{id}/document/{type}', [KycController::class, 'viewDocument'])->name('kyc.document');

        // Review Admin routes
        Route::get('/admin/reviews', [ReviewController::class, 'adminIndex'])->name('reviews.admin');
        Route::put('/api/reviews/{id}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::delete('/api/reviews/{id}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
    });

    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isAdmin()) {
            $totalCars = \App\Models\Car::count();
            $activeBookings = \App\Models\Booking::whereIn('status', ['confirmed', 'in_use'])->count();
            $completedBookings = \App\Models\Booking::whereIn('status', ['completed', 'returned'])->count();
            $availableCars = \App\Models\Car::where('isAvailable', true)->count();
            return view('dashboard', compact('totalCars', 'activeBookings', 'completedBookings', 'availableCars'));
        } else {
            $userBookings = \App\Models\Booking::where('customerName', $user->name)->get();
            return view('dashboard', compact('userBookings'));
        }
    });

    Route::get('/bookings', function () {
        // Prevent admin from accessing bookings page
        if (auth()->user()->isAdmin()) {
            return redirect('/admin')->with('error', 'Admins cannot book vehicles. Please use the admin panel to manage bookings.');
        }
        return view('bookings');
    });

    Route::get('/admin', function () {
        if (!auth()->user()->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }
        return view('admin');
    });
});

// Serve static files (for compatibility with existing JS)
Route::get('/Home.html', function () {
    return view('home');
});

Route::get('/About Us.html', function () {
    return view('about');
});

Route::get('/Bookings.html', function () {
    if (!auth()->check()) {
        return redirect('/login');
    }
    return view('bookings');
});

Route::get('/Contact Us.html', function () {
    return view('contact');
});

Route::get('/admin.html', function () {
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        return redirect('/login');
    }
    return view('admin');
});
