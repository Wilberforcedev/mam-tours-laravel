@extends('layouts.app')

@section('title', 'Login | MAM TOURS')

@section('content')
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to your MAM Tours account</p>
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

                <form method="POST" action="{{ route('login.post') }}" class="auth-form">
                    @csrf

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

                    <button type="submit" class="auth-submit-btn">Sign In</button>

                    <div class="forgot-password-link">
                        <a href="{{ route('password.request') }}"><i class="fas fa-key"></i> Forgot your password?</a>
                    </div>
                </form>

                <div class="auth-divider">
                    <span>New to MAM Tours?</span>
                </div>

                <a href="{{ route('register') }}" class="auth-secondary-btn">Create an Account</a>

                <div class="auth-footer">
                    <p>By signing in, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></p>
                </div>
            </div>

            <div class="auth-info">
                <div class="info-item">
                    <div class="support-icon"><i class="fas fa-car"></i></div>
                    <h3>Easy Booking</h3>
                    <p>Book your perfect car in minutes</p>
                </div>
                <div class="info-item">
                    <div class="support-icon"><i class="fas fa-coins"></i></div>
                    <h3>Best Prices</h3>
                    <p>Competitive rates guaranteed</p>
                </div>
                <div class="info-item">
                    <div class="support-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Secure</h3>
                    <p>Your data is safe with us</p>
                </div>
            </div>
        </div>
    </section>
@endsection
