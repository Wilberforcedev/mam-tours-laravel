@extends('layouts.app')

@section('title', 'FAQs | MAM TOURS')

@section('content')
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Frequently Asked Questions</h1>
            <p class="hero-subtitle">Find answers to common questions about our car rental services</p>
        </div>
    </section>

    <!-- FAQs Section -->
    <section class="faqs-section">
        <div class="container">
            <div class="faqs-grid">
                <!-- Booking FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h3 class="faq-title">How do I book a car?</h3>
                    <p class="faq-text">
                        Booking is simple! Visit our Bookings page, select your vehicle type, choose your pickup and return dates, and complete the reservation form. You'll receive a confirmation email with all the details.
                    </p>
                </div>

                <!-- Payment FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-credit-card"></i></div>
                    <h3 class="faq-title">What payment methods do you accept?</h3>
                    <p class="faq-text">
                        We accept cash, bank transfers, and mobile money payments. Payment is required at the time of booking to secure your reservation.
                    </p>
                </div>

                <!-- Documents FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-file-alt"></i></div>
                    <h3 class="faq-title">What documents do I need?</h3>
                    <p class="faq-text">
                        You'll need a valid driver's license, national ID, and proof of address. International visitors should have an International Driving Permit along with their passport.
                    </p>
                </div>

                <!-- Age Requirements -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-user-check"></i></div>
                    <h3 class="faq-title">What's the minimum age to rent?</h3>
                    <p class="faq-text">
                        The minimum age is 18 years old with a valid driver's license. Some premium vehicles may require drivers to be 25 years or older.
                    </p>
                </div>

                <!-- Insurance FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="faq-title">Is insurance included?</h3>
                    <p class="faq-text">
                        Yes! All our rentals include comprehensive insurance coverage. Additional coverage options are available for an extra fee if you need extra protection.
                    </p>
                </div>

                <!-- Cancellation FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-times-circle"></i></div>
                    <h3 class="faq-title">Can I cancel my booking?</h3>
                    <p class="faq-text">
                        Yes, you can cancel up to 24 hours before your pickup time for a full refund. Cancellations within 24 hours may incur a cancellation fee.
                    </p>
                </div>

                <!-- Extension FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-hourglass-end"></i></div>
                    <h3 class="faq-title">Can I extend my rental?</h3>
                    <p class="faq-text">
                        Absolutely! You can extend your rental period anytime during your booking. Contact us or visit your dashboard to request an extension.
                    </p>
                </div>

                <!-- Fuel Policy -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-gas-pump"></i></div>
                    <h3 class="faq-title">What's your fuel policy?</h3>
                    <p class="faq-text">
                        All vehicles are provided with a full tank. You should return the car with a full tank as well. If not, you'll be charged for the fuel at current market rates.
                    </p>
                </div>

                <!-- Breakdown Support -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-tools"></i></div>
                    <h3 class="faq-title">What if the car breaks down?</h3>
                    <p class="faq-text">
                        We provide 24/7 roadside assistance. Call our support team immediately, and we'll arrange a replacement vehicle or repair service at no extra cost.
                    </p>
                </div>

                <!-- Mileage FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-road"></i></div>
                    <h3 class="faq-title">Is there a mileage limit?</h3>
                    <p class="faq-text">
                        No, our rentals come with unlimited mileage. Drive as much as you need without worrying about extra charges for distance traveled.
                    </p>
                </div>

                <!-- Damage FAQs -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3 class="faq-title">What if I damage the car?</h3>
                    <p class="faq-text">
                        Minor wear and tear is covered by insurance. For significant damage, your insurance deductible applies. Always report any damage immediately to our team.
                    </p>
                </div>

                <!-- Pickup/Return -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h3 class="faq-title">Where can I pick up and return the car?</h3>
                    <p class="faq-text">
                        We're located at Martin Road, Kampala. We also offer delivery and pickup services to your location for an additional fee. Contact us for details.
                    </p>
                </div>

                <!-- Special Requests -->
                <div class="faq-card">
                    <div class="faq-icon"><i class="fas fa-gift"></i></div>
                    <h3 class="faq-title">Do you offer special requests?</h3>
                    <p class="faq-text">
                        Yes! We can arrange child seats, GPS navigation, and other accessories. Contact us before your booking to arrange these services.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Didn't find your answer?</h2>
                <p class="cta-description">Our support team is here to help. Contact us anytime.</p>
                <div class="cta-buttons">
                    <a href="{{ url('/contact') }}" class="cta-button">Contact Us</a>
                    <a href="https://wa.me/256755943973" target="_blank" rel="noopener" class="cta-button secondary">WhatsApp Us</a>
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
@endsection

@section('scripts')
    <script>
    (function(){
      var y=new Date().getFullYear();
      document.querySelectorAll('.current-year').forEach(function(el){ el.textContent=y; });
    })();
    </script>
@endsection
