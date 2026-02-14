<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Show login form
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            if ($user->isAdmin()) {
                return redirect('/admin')->with('success', 'Welcome back, Admin!');
            }
            return redirect('/dashboard')->with('success', 'Login successful!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // Show registration form
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.register');
    }

    // Handle registration
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'customer', // Default role is customer
            'email_notifications' => true,
            'sms_notifications' => false,
        ]);

        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Account created successfully!');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out successfully!');
    }

    // Show dashboard
    public function dashboard()
    {
        $this->authorize('view', Auth::user());
        $user = Auth::user();
        $bookings = $user->bookings()->latest()->get();

        return view('dashboard', compact('user', 'bookings'));
    }

    // Admin: Show users management
    public function manageUsers()
    {
        $this->authorize('isAdmin', Auth::user());
        $users = User::all();

        return view('admin.users', compact('users'));
    }

    // Admin: Grant admin rights
    public function grantAdmin(Request $request, User $user)
    {
        $this->authorize('isAdmin', Auth::user());

        $user->update(['role' => 'admin']);

        return back()->with('success', "{$user->name} is now an admin!");
    }

    // Admin: Revoke admin rights
    public function revokeAdmin(Request $request, User $user)
    {
        $this->authorize('isAdmin', Auth::user());

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot revoke your own admin rights!']);
        }

        $user->update(['role' => 'customer']);

        return back()->with('success', "{$user->name} is now a customer!");
    }

    // Admin: Delete user
    public function deleteUser(Request $request, User $user)
    {
        $this->authorize('isAdmin', Auth::user());

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account!']);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully!');
    }

    // Update notification preferences
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
        ]);

        Auth::user()->update($validated);

        return back()->with('success', 'Notification preferences updated!');
    }
}
