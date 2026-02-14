@extends('layouts.app')

@section('title', 'Bookings | MAM TOURS AND TRAVEL AGENCY')

@section('content')
    <!-- Available Vehicles Section -->
    <section class="cars-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Choose Your Vehicle</h2>
                <p class="section-description">Select from our premium fleet and book instantly.</p>
            </div>
            
            <!-- Vehicles Grid -->
            <div id="vehiclesByCategory" class="vehicles-grid"></div>
        </div>
    </section>

    <section class="booking-section">
        <div class="container">
            <div class="booking-wrapper">
                <div class="booking-header">
                    <h2 class="section-title">Book Your Vehicle</h2>
                    <p class="section-description">Complete your booking in 3 simple steps.</p>
                </div>
                <form class="booking-form" id="bookingForm" enctype="multipart/form-data">
                    
                    <!-- Step 1: Vehicle & Dates -->
                    <div class="booking-step">
                        <h3 class="step-title"><i class="fas fa-car"></i> Step 1: Vehicle & Dates</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="carSelect" class="form-label">Selected Vehicle</label>
                                <input type="text" id="carSelect" name="carId" class="form-input" readonly placeholder="Select a vehicle above" required>
                                <input type="hidden" id="selectedCarId" name="selectedCarId">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" id="startDate" name="startDate" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" id="endDate" name="endDate" class="form-input" required>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Personal Information -->
                    <div class="booking-step">
                        <h3 class="step-title"><i class="fas fa-user"></i> Step 2: Your Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customerName" class="form-label">Full Name</label>
                                <input type="text" id="customerName" name="customerName" class="form-input" placeholder="Enter your full name" required>
                            </div>
                            <div class="form-group">
                                <label for="customerEmail" class="form-label">Email Address</label>
                                <input type="email" id="customerEmail" name="customerEmail" class="form-input" placeholder="Enter your email" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customerPhone" class="form-label">Phone Number</label>
                                <input type="tel" id="customerPhone" name="customerPhone" class="form-input" placeholder="+256 700 000 000" required>
                            </div>
                            <div class="form-group">
                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                <select id="paymentMethod" name="paymentMethod" class="form-input" required>
                                    <option value="">Select payment method</option>
                                    <option value="stripe">Credit/Debit Card (Stripe)</option>
                                    <option value="mtn_mobile_money">MTN Mobile Money</option>
                                    <option value="airtel_money">Airtel Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash on Pickup</option>
                                </select>
                            </div>
                            <div class="form-group" id="mobileMoneyDetails" style="display: none;">
                                <label for="mobileMoneyNumber" class="form-label">Mobile Money Number</label>
                                <input type="tel" id="mobileMoneyNumber" name="mobileMoneyNumber" class="form-input" placeholder="+256 700 000 000">
                                <small class="form-hint">Enter the number for MTN Mobile Money or Airtel Money</small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Identity Verification -->
                    <div class="booking-step">
                        <h3 class="step-title"><i class="fas fa-shield-alt"></i> Step 3: Identity Verification</h3>
                        <p class="step-description">Upload your ID and driving permit for verification (required for security)</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="idType" class="form-label">ID Type</label>
                                <select id="idType" name="idType" class="form-input" required>
                                    <option value="">Select ID type</option>
                                    <option value="nin">National ID (NIN)</option>
                                    <option value="passport">Passport</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="idNumber" class="form-label">ID Number</label>
                                <input type="text" id="idNumber" name="idNumber" class="form-input" placeholder="Enter your ID number" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="permitNumber" class="form-label">Driving Permit Number</label>
                                <input type="text" id="permitNumber" name="permitNumber" class="form-input" placeholder="Enter your permit number" required>
                            </div>
                        </div>

                        <!-- Document Upload -->
                        <div class="documents-upload">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="idDocument" class="form-label">Upload ID Document</label>
                                    <div class="file-upload-area" id="idUploadArea">
                                        <input type="file" id="idDocument" name="idDocument" accept="image/*,.pdf" required>
                                        <div class="upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click to upload ID document</p>
                                            <small>JPG, PNG, PDF (max 5MB)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="permitDocument" class="form-label">Upload Driving Permit</label>
                                    <div class="file-upload-area" id="permitUploadArea">
                                        <input type="file" id="permitDocument" name="permitDocument" accept="image/*,.pdf" required>
                                        <div class="upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click to upload permit document</p>
                                            <small>JPG, PNG, PDF (max 5MB)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pricing-summary" id="pricingSummary" style="display: none;">
                        <h3>Booking Summary</h3>
                        <div class="pricing-details" id="pricingDetails"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn" id="submitBooking">
                            <i class="fas fa-check-circle"></i>
                            <span class="btn-text">Complete Booking</span>
                            <span class="btn-loading" style="display: none;">Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <strong id="toastTitle"></strong>
            <p id="toastMessage"></p>
        </div>
    </div>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/256755943973?text=Hello%20MAM%20Tours%2C%20I%20would%20like%20to%20inquire%20about%20car%20rental%20services." target="_blank" rel="noopener" class="whatsapp-float" title="Chat with us on WhatsApp">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
    </a>
@endsection

@section('scripts')
    <script src="{{ asset('js/booking-enhanced.js') }}"></script>
@endsection
