@extends('layouts.app')

@section('title', 'My Profile | MAM TOURS')

@section('content')
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title"><i class="fas fa-user-circle"></i> My Profile</h1>
            <p class="hero-subtitle">Manage your account and profile picture</p>
        </div>
    </section>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-wrapper">
                <!-- Profile Picture Section -->
                <div class="profile-picture-section">
                    <div class="profile-picture-container">
                        @if($user->profile_picture)
                            <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="{{ $user->name }}" class="profile-picture">
                        @else
                            <div class="profile-picture-placeholder">
                                <span class="placeholder-icon">👤</span>
                            </div>
                        @endif
                    </div>
                    <h2 class="profile-name">{{ $user->name }}</h2>
                    <p class="profile-role">{{ ucfirst($user->role) }}</p>
                </div>

        
                <div class="profile-form-section">
                    @if($errors->any())
                        <div class="alert alert-error">
                            <strong>Oops! Something went wrong:</strong>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form">
                        @csrf

                       
                        <div class="form-group">
                            <label for="profile_picture" class="form-label"><i class="fas fa-image"></i> Profile Picture</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="profile_picture" name="profile_picture" class="file-input" accept="image/*">
                                <label for="profile_picture" class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                    <span class="file-input-text">Click to upload or drag and drop</span>
                                    <span class="file-input-hint">PNG, JPG, GIF up to 2MB</span>
                                </label>
                            </div>
                            <small class="form-hint">Recommended size: 400x400px</small>
                        </div>

                        <!-- Name -->
                        <div class="form-group">
                            <label for="name" class="form-label"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label for="phone" class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}" placeholder="+256...">
                        </div>

                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Changes</button>
                            <a href="{{ url('/dashboard') }}" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section footer-brand">
                    <div class="footer-logo">
                        <div class="footer-logo-container">
                            <img src="{{ asset('images/MAM TOURS LOGO.jpg') }}" alt="MAM TOURS logo" class="footer-logo-img">
                            <div class="footer-logo-overlay">
                                <i class="fas fa-car"></i>
                            </div>
                        </div>
                        <div class="footer-logo-text">
                            <h3 class="footer-title">MAM TOURS</h3>
                            <span class="footer-subtitle">Car Rental</span>
                        </div>
                    </div>
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
                <div class="footer-bottom-logo">
                    <div class="footer-bottom-logo-container">
                        <img src="{{ asset('images/MAM TOURS LOGO.jpg') }}" alt="MAM TOURS" class="footer-bottom-logo-img">
                    </div>
                    <span>MAM TOURS</span>
                </div>
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
@endsection

@section('styles')
    <style>
        .profile-section {
            padding: 3rem 1rem;
            background: #f8f9fa;
            min-height: calc(100vh - 300px);
        }

        .profile-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 3rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .profile-picture-container {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 1.5rem;
            border: 4px solid #ff9800;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture-placeholder {
            font-size: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2332;
            margin: 0;
        }

        .profile-role {
            color: #ff9800;
            font-weight: 500;
            margin: 0.5rem 0 0 0;
        }

        .profile-form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-form {
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
            font-size: 0.95rem;
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

        .file-input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .file-input {
            display: none;
        }

        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            border: 2px dashed #ff9800;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff9f5;
        }

        .file-input-label:hover {
            background: #ffe8d6;
            border-color: #ff7c00;
        }

        .file-input-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .file-input-text {
            font-weight: 600;
            color: #1a2332;
        }

        .file-input-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .form-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .submit-btn {
            flex: 1;
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

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 152, 0, 0.3);
        }

        .cancel-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: #f0f0f0;
            color: #1a2332;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cancel-btn:hover {
            background: #e0e0e0;
            border-color: #999;
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
            .profile-wrapper {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .profile-picture-container {
                width: 150px;
                height: 150px;
            }

            .profile-picture-placeholder {
                font-size: 60px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
@endsection
