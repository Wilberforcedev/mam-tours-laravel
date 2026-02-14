<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $this->ensureIsNotRateLimited($request);

        if (Auth::attempt($request->validated(), $request->remember)) {
            $request->session()->regenerate();
            
            RateLimiter::clear($this->throttleKey($request));
            
            $user = Auth::user();
            
            // Check if account is locked
            if ($user->isLocked()) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Account is temporarily locked due to too many failed attempts.',
                ]);
            }
            
            // Log successful login
            activity()
                ->causedBy($user)
                ->log('User logged in');
            
            // Redirect admin users to admin panel
            if ($user->isAdmin()) {
                return redirect('/admin')->with('success', 'Welcome back, Admin!');
            }
            
            // Redirect regular users to dashboard (not bookings for now)
            return redirect()->intended('/dashboard')->with('success', 'Login successful!');
        }

        RateLimiter::hit($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Log logout
        if ($user) {
            activity()
                ->causedBy($user)
                ->log('User logged out');
        }
        
        return redirect('/')->with('success', 'Logged out successfully!');
    }

    protected function ensureIsNotRateLimited(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }
    }

    protected function throttleKey(Request $request)
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }
}
