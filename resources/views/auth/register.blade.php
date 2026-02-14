@extends('layouts.app')

@section('title', 'Register | MAM TOURS')

@section('content')
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Create Account</h1>
                    <p class="auth-subtitle">Join MAM Tours and start booking today</p>
                </div>

                @if ($errors->any())
                    <div class="auth-alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.post') }}" class="auth-form">
                    @csrf

                    <div class="form-group">
                        <label for="name" class="form-label">
                            <span class="support-icon-small"><i class="fas fa-user"></i></span>
                            Full Name
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input @error('name') input-error @enderror"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            required
                        >
                        @error('name')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <span class="support-icon-small"><i class="fas fa-envelope"></i></span>
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input @error('email') input-error @enderror"
                            value="{{ old('email') }}"
                            placeholder="your@email.com"
                            required
                        >
                        @error('email')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <span class="support-icon-small"><i class="fas fa-phone"></i></span>
                            Phone Number
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input"
                            value="{{ old('phone') }}"
                            placeholder="+256 755 943973"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <span class="support-icon-small"><i class="fas fa-lock"></i></span>
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input @error('password') input-error @enderror"
                            placeholder="••••••••"
                            required
                        >
                        @error('password')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">
                            <span class="support-icon-small"><i class="fas fa-lock"></i></span>
                            Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="form-input"
                            placeholder="••••••••"
                            required
                        >
                    </div>

                    <button type="submit" class="auth-submit-btn">Create Account</button>
                </form>

                <div class="auth-divider">
                    <span>Already have an account?</span>
                </div>

                <a href="{{ route('login') }}" class="auth-secondary-btn">Sign In</a>

                <div class="auth-footer">
                    <p>By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></p>
                </div>
            </div>

            <div class="auth-info">
                <div class="info-item">
                    <div class="support-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Quick Setup</h3>
                    <p>Get started in seconds</p>
                </div>
                <div class="info-item">
                    <div class="support-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Manage Bookings</h3>
                    <p>Track all your reservations</p>
                </div>
                <div class="info-item">
                    <div class="support-icon"><i class="fas fa-gift"></i></div>
                    <h3>Exclusive Deals</h3>
                    <p>Get member-only discounts</p>
                </div>
            </div>
        </div>
    </section>
@endsection
