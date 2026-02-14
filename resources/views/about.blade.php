@extends('layouts.app')

@section('title', 'About Us | MAM TOURS')

@section('content')
    <!-- Hero Section -->
    <section class="page-hero modern-page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title"><i class="fas fa-star"></i> About MAM TOURS</h1>
            <p class="hero-subtitle">Your Trusted Partner in Premium Car Rental</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="content-section">
        <div class="container">
            <div class="about-grid">
                <div class="about-card">
                    <h2 class="section-title"><i class="fas fa-users"></i> Who We Are</h2>
                    <p class="content-text">
                        We're more than just a car rental company - we're your travel companion. With a diverse fleet 
                        of premium vehicles and a passion for customer satisfaction, we've been serving travelers across 
                        Uganda with excellence and reliability.
                    </p>
                </div>
                <div class="about-card">
                    <h2 class="section-title"><i class="fas fa-bullseye"></i> Our Mission</h2>
                    <p class="content-text">
                        To make every journey memorable by providing hassle-free, affordable, and premium car rental 
                        experiences. We believe in transparency, reliability, and putting our customers first - always.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="content-section alt-bg">
        <div class="container">
            <h2 class="section-title text-center"><i class="fas fa-sparkles"></i> Why Choose MAM TOURS?</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-car"></i></div>
                    <h3>Premium Fleet</h3>
                    <p>Handpicked vehicles maintained to perfection</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-tag"></i></div>
                    <h3>Unbeatable Prices</h3>
                    <p>Best rates with zero hidden charges</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-shield"></i></div>
                    <h3>Full Coverage</h3>
                    <p>Comprehensive insurance for complete peace of mind</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Instant Booking</h3>
                    <p>Book in seconds, drive in minutes</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-headset"></i></div>
                    <h3>Always Available</h3>
                    <p>24/7 support when you need us most</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Trusted Service</h3>
                    <p>Thousands of happy customers, countless journeys</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title"><i class="fas fa-rocket"></i> Ready for Your Next Adventure?</h2>
                <p class="cta-description">Join thousands of satisfied travelers who trust MAM TOURS</p>
                <a href="{{ url('/bookings') }}" class="cta-button"><i class="fas fa-calendar-check"></i> Book Your Perfect Ride</a>
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
@endsection
