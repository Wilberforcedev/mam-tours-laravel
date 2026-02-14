<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'customer',
            'email_notifications' => true,
            'sms_notifications' => false,
        ]);

        // Log registration
        activity()
            ->causedBy($user)
            ->log('User registered');

        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Account created successfully! Welcome to MAM Tours.');
    }
}
