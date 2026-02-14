@extends('layouts.app')

@section('title', 'Contact Us | MAM TOURS')

@section('content')
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Get In Touch</h1>
            <p class="hero-subtitle">We're here to help you with all your car rental needs</p>
        </div>
    </section>

    <!-- Contact Support Section -->
    <section class="contact-support-section">
        <div class="container">
            <div class="support-intro">
                <h2>Need support or have a question about MAM Tours? We're here to help.</h2>
            </div>

            <div class="support-grid">
                <!-- Get Help Card -->
                <div class="support-card">
                    <div class="support-icon"><i class="fas fa-phone"></i></div>
                    <h3 class="support-title">Get Help</h3>
                    <p class="support-text">Speak to our team today and ask anything about our car rental services.</p>
                    <a href="tel:+256755943973" class="support-btn">Call Us</a>
                </div>

                <!-- WhatsApp Card -->
                <div class="support-card">
                    <div class="support-icon"><i class="fas fa-comments"></i></div>
                    <h3 class="support-title">Chat with Us</h3>
                    <p class="support-text">Get instant support through WhatsApp. We're available 24/7 for your queries.</p>
                    <a href="https://wa.me/256755943973" target="_blank" rel="noopener" class="support-btn">WhatsApp</a>
                </div>

                <!-- Email Card -->
                <div class="support-card">
                    <div class="support-icon"><i class="fas fa-envelope"></i></div>
                    <h3 class="support-title">Email Us</h3>
                    <p class="support-text">Send us an email with your questions and we'll respond within 24 hours.</p>
                    <a href="mailto:Comradesrentalandhire@gmail.com" class="support-btn">Send Email</a>
                </div>

                <!-- Bookings Card -->
                <div class="support-card">
                    <div class="support-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3 class="support-title">Make a Booking</h3>
                    <p class="support-text">Ready to rent a car? Visit our bookings page to reserve your vehicle today.</p>
                    <a href="{{ url('/bookings') }}" class="support-btn">Book Now</a>
                </div>

                <!-- FAQs Card -->
                <div class="support-card">
                    <div class="support-icon"><i class="fas fa-question-circle"></i></div>
                    <h3 class="support-title">Have a Question?</h3>
                    <p class="support-text">Check our frequently asked questions for quick answers to common queries.</p>
                    <a href="{{ url('/faqs') }}" class="support-btn">View FAQs</a>
                </div>

                <!-- Location Card -->
                <div class="support-card">
                    <div class="support-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h3 class="support-title">Visit Us</h3>
                    <p class="support-text">Come visit our office at Martin Road, Kampala. We're open Mon-Sat, 8am-6pm.</p>
                    <a href="https://www.google.com/maps?q=Martin%20Road%20Kampala" target="_blank" rel="noopener" class="support-btn">Get Directions</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Business Hours Section -->
    <section class="business-hours-section">
        <div class="container">
            <div class="hours-wrapper">
                <div class="hours-content">
                    <h2 class="hours-title">Business Hours</h2>
                    <ul class="hours-list">
                        <li>
                            <span class="day">Monday - Friday</span>
                            <span class="time">08:00 - 18:00</span>
                        </li>
                        <li>
                            <span class="day">Saturday</span>
                            <span class="time">09:00 - 16:00</span>
                        </li>
                        <li>
                            <span class="day">Sunday</span>
                            <span class="time">On Request</span>
                        </li>
                        <li>
                            <span class="day">Public Holidays</span>
                            <span class="time">On Request</span>
                        </li>
                    </ul>
                </div>

                <div class="contact-info">
                    <h2 class="info-title">Contact Information</h2>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                        <a href="tel:+256755943973">+256 755-943973</a>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                        <a href="mailto:Comradesrentalandhire@gmail.com">Comradesrentalandhire@gmail.com</a>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Address</span>
                        <p>Martin Road, Kampala, Uganda</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Book Your Vehicle?</h2>
                <p class="cta-description">Start your journey with MAM TOURS today</p>
                <a href="{{ url('/bookings') }}" class="cta-button">Book Now</a>
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
@endsection

@section('scripts')
    <script>
    (function(){
      var y=new Date().getFullYear();
      document.querySelectorAll('.current-year').forEach(function(el){ el.textContent=y; });
    })();
    </script>
@endsection
