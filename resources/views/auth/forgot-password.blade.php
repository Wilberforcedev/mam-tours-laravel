@extends('layouts.app')

@section('title', 'Forgot Password | MAM TOURS')

@section('content')
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Reset Your Password</h1>
            <p class="hero-subtitle">Enter your email to receive a password reset link</p>
        </div>
    </section>

    <!-- Forgot Password Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2 class="auth-title"><i class="fas fa-key"></i> Forgot Password</h2>
                        <p class="auth-subtitle">We'll send you a link to reset your password</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-error">
                            <strong>Error:</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <span class="support-icon-small"><i class="fas fa-envelope"></i></span>
                                Email Address
                            </label>
                            <input type="email" id="email" name="email" class="form-input @error('email') input-error @enderror" 
                                   value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
                            @error('email')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="auth-btn">Send Reset Link</button>
                    </form>

                    <div class="auth-footer">
                        <p>Remember your password? <a href="{{ route('login') }}" class="auth-link">Login here</a></p>
                        <p>Don't have an account? <a href="{{ route('register') }}" class="auth-link">Register here</a></p>
                    </div>
                </div>

                <div class="auth-info">
                    <div class="info-card">
                        <span class="support-icon"><i class="fas fa-envelope"></i></span>
                        <h3>Check Your Email</h3>
                        <p>We'll send you a password reset link within minutes</p>
                    </div>
                    <div class="info-card">
                        <span class="support-icon"><i class="fas fa-link"></i></span>
                        <h3>Click the Link</h3>
                        <p>Follow the link in the email to reset your password</p>
                    </div>
                    <div class="info-card">
                        <span class="support-icon"><i class="fas fa-check-circle"></i></span>
                        <h3>Create New Password</h3>
                        <p>Set a strong new password and log in</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">MAM TOURS</h3>
                    <p class="footer-text">Your trusted partner for reliable and affordable car rental services.</p>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li><a href="{{ url('/about') }}">About Us</a></li>
                        <li><a href="{{ url('/contact') }}">Contact Us</a></li>
                        <li><a href="{{ url('/faqs') }}">FAQs</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Contact</h4>
                    <p class="footer-text">Get in touch with us for bookings and inquiries.</p>
                    <a href="{{ url('/contact') }}" class="footer-link">Contact Us →</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span class="current-year"></span> MAM TOURS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/256755943973?text=Hello%20MAM%20Tours%2C%20I%20would%20like%20to%20inquire%20about%20car%20rental%20services." target="_blank" rel="noopener" class="whatsapp-float" title="Chat with us on WhatsApp">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
    </a>

    <script>
    (function(){
      var y=new Date().getFullYear();
      document.querySelectorAll('.current-year').forEach(function(el){ el.textContent=y; });
    })();
    </script>

    @section('styles')
        <style>
            .auth-section {
                padding: 3rem 1rem;
                background: #f8f9fa;
                min-height: calc(100vh - 300px);
            }

            .auth-wrapper {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 3rem;
                max-width: 1000px;
                margin: 0 auto;
            }

            .auth-card {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .auth-header {
                margin-bottom: 2rem;
                text-align: center;
            }

            .auth-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1a2332;
                margin: 0 0 0.5rem 0;
            }

            .auth-subtitle {
                color: #666;
                margin: 0;
            }

            .auth-form {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .form-group {
                display: flex;
                flex-direction: column;
            }

            .form-label {
                font-weight: 600;
                color: #1a2332;
                margin-bottom: 0.5rem;
            }

            .form-input {
                padding: 0.75rem;
                border: 2px solid #ddd;
                border-radius: 8px;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .form-input:focus {
                outline: none;
                border-color: #ff9800;
                box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
            }

            .form-input.input-error {
                border-color: #e74c3c;
            }

            .error-message {
                color: #e74c3c;
                font-size: 0.85rem;
                margin-top: 0.25rem;
            }

            .auth-btn {
                padding: 0.75rem 1.5rem;
                background: linear-gradient(135deg, #ff9800 0%, #ff7c00 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 1rem;
            }

            .auth-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 16px rgba(255, 152, 0, 0.3);
            }

            .auth-footer {
                margin-top: 2rem;
                text-align: center;
                border-top: 1px solid #eee;
                padding-top: 1.5rem;
            }

            .auth-footer p {
                margin: 0.5rem 0;
                color: #666;
            }

            .auth-link {
                color: #ff9800;
                text-decoration: none;
                font-weight: 600;
            }

            .auth-link:hover {
                text-decoration: underline;
            }

            .auth-info {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .info-card {
                background: white;
                padding: 1.5rem;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                text-align: center;
            }

            .info-icon {
                font-size: 2rem;
                display: block;
                margin-bottom: 0.5rem;
            }

            .info-card h3 {
                font-size: 1rem;
                font-weight: 600;
                color: #1a2332;
                margin: 0.5rem 0;
            }

            .info-card p {
                color: #666;
                margin: 0;
                font-size: 0.9rem;
            }

            .alert {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
            }

            .alert-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .alert-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .alert ul {
                margin: 0.5rem 0 0 1.5rem;
                padding: 0;
            }

            .alert li {
                margin: 0.25rem 0;
            }

            @media (max-width: 768px) {
                .auth-wrapper {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }
            }
        </style>
    @endsection
@endsection
