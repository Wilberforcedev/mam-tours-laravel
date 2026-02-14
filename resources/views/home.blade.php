@extends('layouts.app')

@section('title', 'Home | MAM TOURS – Car Rental Uganda')

@section('content')
    <div class="promo-bar" id="promoBar">
        <span><i class="fas fa-map-marker-alt"></i> Best car rental rates in Uganda transparent UGX pricing, no hidden fees.</span>
        <button type="button" class="promo-bar-close" id="promoBarClose" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <!-- Hero Section -->
    <section class="hero-section modern-hero">
        <div class="hero-geometric-shapes">
            <div class="hero-shape hero-shape-1"></div>
            <div class="hero-shape hero-shape-2"></div>
            <div class="hero-shape hero-shape-3"></div>
            <div class="hero-shape hero-shape-4"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-content modern-hero-content">
            <div class="hero-text">
                <h1 class="hero-title">It's Travel, When <span class="highlight">Adventure Takes Shape</span></h1>
                <p class="hero-subtitle">Experience premium car rental services that transform your journey into an unforgettable adventure. Drive with confidence, travel with style.</p>
                <div class="hero-actions">
                    <a href="{{ url('/bookings') }}" class="hero-btn primary-btn"><i class="fas fa-calendar-check"></i> Book Your Car Now</a>
                    <a href="{{ url('/about') }}" class="hero-btn secondary-btn"><i class="fas fa-info-circle"></i> Learn More</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="car-icon-container">
                    <svg class="car-icon" viewBox="0 0 200 120" xmlns="http://www.w3.org/2000/svg">
                        <!-- Car body -->
                        <rect x="20" y="60" width="160" height="40" rx="5" fill="#D4A574"/>
                        <!-- Car roof -->
                        <path d="M 50 60 L 70 35 L 130 35 L 150 60 Z" fill="#D4A574"/>
                        <!-- Windows -->
                        <rect x="75" y="40" width="25" height="18" rx="2" fill="#2C3E50"/>
                        <rect x="105" y="40" width="25" height="18" rx="2" fill="#2C3E50"/>
                        <!-- Wheels -->
                        <circle cx="50" cy="100" r="15" fill="#2C3E50"/>
                        <circle cx="50" cy="100" r="8" fill="#555"/>
                        <circle cx="150" cy="100" r="15" fill="#2C3E50"/>
                        <circle cx="150" cy="100" r="8" fill="#555"/>
                        <!-- Headlights -->
                        <rect x="25" y="70" width="8" height="6" rx="2" fill="#FFD700"/>
                        <rect x="167" y="70" width="8" height="6" rx="2" fill="#FF6B6B"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Bar (Uganda) -->
    <section class="stats-bar">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number" id="statVehicles">—</span>
                    <span class="stat-label">Vehicles in fleet</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Support in Uganda</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">UGX</span>
                    <span class="stat-label">Transparent pricing</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><i class="fas fa-map-marked-alt"></i></span>
                    <span class="stat-label">Kampala & Entebbe</span>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-route"></i> How It Works</h2>
            <p class="section-description">Book a car in Uganda in four simple steps</p>
            <div class="steps-grid">
                <div class="step-card">
                    <span class="step-num">1</span>
                    <div class="step-icon"><i class="fas fa-car-side"></i></div>
                    <h3>Choose your car</h3>
                    <p>Browse our fleet from economy to 4x4 for safari. All vehicles meet Ugandan road standards.</p>
                </div>
                <div class="step-card">
                    <span class="step-num">2</span>
                    <div class="step-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Book & pay</h3>
                    <p>Select dates, pay in UGX via Mobile Money, bank transfer, or cash. Quick KYC for your safety.</p>
                </div>
                <div class="step-card">
                    <span class="step-num">3</span>
                    <div class="step-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h3>Pick up</h3>
                    <p>Collect your vehicle in Kampala or arrange Entebbe Airport pickup. We’ll confirm the details.</p>
                </div>
                <div class="step-card">
                    <span class="step-num">4</span>
                    <div class="step-icon"><i class="fas fa-umbrella-beach"></i></div>
                    <h3>Explore Uganda</h3>
                    <p>Hit the road city trips, national parks, or gorilla trekking. Return when your trip ends.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Benefits Section -->
    <section class="quick-benefits">
        <div class="container">
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-car-side"></i></div>
                    <h3>Wide Selection</h3>
                    <p>From economy to luxury, we have the perfect car for you</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-tag"></i></div>
                    <h3>Unbeatable Prices</h3>
                    <p>Best rates guaranteed with transparent pricing</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-headset"></i></div>
                    <h3>24/7 Support</h3>
                    <p>Always here when you need us - day or night</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Easy Booking</h3>
                    <p>Book in minutes, drive in hours it's that simple</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-concierge-bell"></i> Our Services</h2>
            <p class="section-description">Choose the rental option that fits your needs in Uganda</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Car Hire with Driver</h3>
                    <p>Sit back and relax with our professional chauffeur service. Perfect for business trips or sightseeing.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Professional drivers</li>
                        <li><i class="fas fa-check"></i> Local knowledge</li>
                        <li><i class="fas fa-check"></i> Flexible schedules</li>
                        <li><i class="fas fa-check"></i> Airport pickups</li>
                    </ul>
                    <a href="{{ url('/bookings') }}" class="service-btn">Book Now</a>
                </div>
                <div class="service-card featured">
                    <div class="featured-badge">Most Popular</div>
                    <div class="service-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3>Self Drive</h3>
                    <p>Take control of your journey. Drive yourself and explore Uganda at your own pace with full freedom.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Complete freedom</li>
                        <li><i class="fas fa-check"></i> Flexible itinerary</li>
                        <li><i class="fas fa-check"></i> 24/7 roadside support</li>
                        <li><i class="fas fa-check"></i> GPS included</li>
                    </ul>
                    <a href="{{ url('/bookings') }}" class="service-btn">Book Now</a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-plane"></i>
                    </div>
                    <h3>Airport Transfers</h3>
                    <p>Hassle-free pickup and drop-off at Entebbe Airport. Start your trip stress-free with reliable service.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Meet & greet service</li>
                        <li><i class="fas fa-check"></i> Flight tracking</li>
                        <li><i class="fas fa-check"></i> On-time guarantee</li>
                        <li><i class="fas fa-check"></i> Luggage assistance</li>
                    </ul>
                    <a href="{{ url('/bookings') }}" class="service-btn">Book Now</a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Long-term Rentals</h3>
                    <p>Need a car for weeks or months? Get special rates for extended rentals with flexible terms.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Weekly packages</li>
                        <li><i class="fas fa-check"></i> Monthly discounts</li>
                        <li><i class="fas fa-check"></i> Free maintenance</li>
                        <li><i class="fas fa-check"></i> Vehicle swap option</li>
                    </ul>
                    <a href="{{ url('/bookings') }}" class="service-btn">Book Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Perfect for Uganda -->
    <section class="perfect-for-uganda">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-leaf"></i> Perfect for Uganda</h2>
            <p class="section-description">Whether you’re in Kampala, heading to the parks, or flying into Entebbe</p>
            <div class="use-cases-grid">
                <a href="{{ url('/bookings') }}" class="use-case-card">
                    <div class="use-case-icon"><i class="fas fa-city"></i></div>
                    <h3>Kampala city trips</h3>
                    <p>Business or leisure, reliable wheels in the capital.</p>
                </a>
                <a href="{{ url('/bookings') }}" class="use-case-card">
                    <div class="use-case-icon"><i class="fas fa-tree"></i></div>
                    <h3>Safari & national parks</h3>
                    <p>Murchison Falls, Queen Elizabeth, Kidepo – 4x4 ready.</p>
                </a>
                <a href="{{ url('/bookings') }}" class="use-case-card">
                    <div class="use-case-icon"><i class="fas fa-plane-arrival"></i></div>
                    <h3>Entebbe Airport</h3>
                    <p>Pick-up or drop-off at EBB start your trip stress free.</p>
                </a>
                <a href="{{ url('/bookings') }}" class="use-case-card">
                    <div class="use-case-icon"><i class="fas fa-paw"></i></div>
                    <h3>Gorilla trekking</h3>
                    <p>Bwindi, Mgahinga ....sturdy vehicles for the mountains.</p>
                </a>
                <a href="{{ url('/bookings') }}" class="use-case-card">
                    <div class="use-case-icon"><i class="fas fa-glass-cheers"></i></div>
                    <h3>Events & weddings</h3>
                    <p>Comfort and style for your special day across Uganda.</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Fleet Showcase Section -->
    <section class="cars-section modern-section alt-bg" id="our-fleet">
        <div class="container">
            <div class="section-header modern-section-header">
                <h2 class="section-title"><i class="fas fa-car-garage"></i> Our Premium Fleet</h2>
                <p class="section-description">Handpicked vehicles maintained to the highest standards Uganda road ready</p>
            </div>
            
            <!-- Category Filters -->
            <div class="category-filters" id="categoryFilters"></div>
            
            <!-- Vehicles Grid -->
            <div id="vehiclesByCategory" class="vehicles-grid modern-vehicles-grid"></div>
        </div>
    </section>

    <!-- Testimonials (Uganda) -->
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-quote-left"></i> What Our Customers Say</h2>
            <p class="section-description">From Kampala to the parks trusted by travellers across Uganda</p>
            
            <div class="testimonials-actions">
                @auth
                    <a href="{{ route('reviews.create') }}" class="add-review-btn">
                        <i class="fas fa-plus"></i> Share Your Experience
                    </a>
                @else
                    <a href="{{ route('login') }}" class="add-review-btn">
                        <i class="fas fa-sign-in-alt"></i> Login to Leave a Review
                    </a>
                @endauth
            </div>
            
            <div class="testimonials-grid" id="testimonialsGrid">
                <!-- Default testimonials (will be replaced by dynamic content) -->
                <div class="testimonial-card">
                    <div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testimonial-text">"Smooth booking and a great SUV for our trip to Murchison Falls. MAM Tours made it easy from Kampala."</p>
                    <div class="testimonial-author">Mawejje, Kampala</div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testimonial-text">"Picked us up at Entebbe Airport on time. Car was clean and perfect for a week in Uganda. Will book again."</p>
                    <div class="testimonial-author">Sarah & Mike, UK</div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testimonial-text">"Used them for our wedding in Entebbe. Professional and reliable. Highly recommend for events in Uganda."</p>
                    <div class="testimonial-author">Okoth, Entebbe</div>
                </div>
            </div>
            
            <div class="testimonials-loading" id="testimonialsLoading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Loading reviews...
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="cta-section modern-cta">
        <div class="cta-geometric-shapes">
            <div class="cta-shape cta-shape-1"></div>
            <div class="cta-shape cta-shape-2"></div>
            <div class="cta-shape cta-shape-3"></div>
        </div>
        <div class="container">
            <div class="cta-content modern-cta-content">
                <h2 class="cta-title"><i class="fas fa-star"></i> Ready to Explore Uganda?</h2>
                <p class="cta-subtitle">Join travellers across the Pearl of Africa who trust MAM TOURS</p>
                <a href="{{ url('/bookings') }}" class="cta-button modern-cta-button"><i class="fas fa-rocket"></i> Start Booking Today</a>
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
                    <p class="footer-text">Your trusted car rental partner in Uganda – reliable, affordable, road ready across the Pearl of Africa.</p>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li><a href="{{ url('/about') }}">About Us</a></li>
                        <li><a href="{{ url('/bookings') }}">Book a car</a></li>
                        <li><a href="{{ url('/faqs') }}">FAQs</a></li>
                        <li><a href="{{ url('/contact') }}">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Contact – Uganda</h4>
                    <p class="footer-text">Kampala, Uganda</p>
                    <p class="footer-text"><a href="tel:+256755943973" class="footer-link">+256 755 943 973</a></p>
                    <a href="https://wa.me/256755943973" target="_blank" rel="noopener" class="footer-link">WhatsApp</a> ·
                    <a href="{{ url('/contact') }}" class="footer-link">Contact form</a>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-bottom-logo">
                    <div class="footer-bottom-logo-container">
                        <img src="{{ asset('images/MAM TOURS LOGO.jpg') }}" alt="MAM TOURS" class="footer-bottom-logo-img">
                    </div>
                    <span>MAM TOURS</span>
                </div>
                <p>&copy; <span class="current-year"></span> MAM TOURS. All rights reserved. Car rental in Uganda.</p>
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
    <script>
    (function(){
      var promoBar=document.getElementById('promoBar');
      var closeBtn=document.getElementById('promoBarClose');
      if(promoBar&&closeBtn){
        closeBtn.addEventListener('click',function(){ promoBar.style.display='none'; });
      }
      var statEl=document.getElementById('statVehicles');
      if(statEl){
        fetch('/api/health').then(function(r){ return r.json(); }).then(function(d){
          if(d&&typeof d.cars_count!=='undefined') statEl.textContent=d.cars_count;
        }).catch(function(){});
      }
      document.querySelectorAll('.hero-strip-btn, a[href="#our-fleet"]').forEach(function(a){
        a.addEventListener('click',function(e){ e.preventDefault(); var el=document.getElementById('our-fleet'); if(el) el.scrollIntoView({behavior:'smooth'}); });
      });
    })();
    </script>
    <script src="{{ asset('js/booking.js') }}"></script>
    <script>
    // Load dynamic reviews
    (function() {
        const testimonialsGrid = document.getElementById('testimonialsGrid');
        const testimonialsLoading = document.getElementById('testimonialsLoading');
        
        if (testimonialsGrid) {
            testimonialsLoading.style.display = 'block';
            
            fetch('/api/reviews')
                .then(response => response.json())
                .then(reviews => {
                    testimonialsLoading.style.display = 'none';
                    
                    if (reviews && reviews.length > 0) {
                        testimonialsGrid.innerHTML = '';
                        
                        reviews.forEach(review => {
                            const stars = Array.from({length: 5}, (_, i) => 
                                `<i class="fas fa-star${i < review.rating ? '' : ' inactive'}"></i>`
                            ).join('');
                            
                            const testimonialCard = document.createElement('div');
                            testimonialCard.className = 'testimonial-card';
                            testimonialCard.innerHTML = `
                                <div class="testimonial-stars">${stars}</div>
                                <p class="testimonial-text">"${review.review_text}"</p>
                                <div class="testimonial-author">${review.name}</div>
                            `;
                            
                            testimonialsGrid.appendChild(testimonialCard);
                        });
                    }
                })
                .catch(error => {
                    console.log('Using default testimonials');
                    testimonialsLoading.style.display = 'none';
                });
        }
    })();
    </script>
@endsection
