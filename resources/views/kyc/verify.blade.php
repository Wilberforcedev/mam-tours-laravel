@extends('layouts.app')

@section('title', 'KYC Verification | MAM TOURS')

@section('content')
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">KYC Verification</h1>
            <p class="hero-subtitle">Complete your identity verification to book vehicles</p>
        </div>
    </section>

    <!-- KYC Section -->
    <section class="kyc-section">
        <div class="container">
            <div class="kyc-wrapper">
                <!-- Status Card -->
                <div class="kyc-status-card">
                    @if($kyc)
                        @if($kyc->status === 'verified')
                            <div class="status-badge verified">
                                <span class="status-icon"><i class="fas fa-check-circle"></i></span>
                                <span class="status-text">Verified</span>
                            </div>
                            <p class="status-message">Your identity has been verified. You can now book vehicles.</p>
                        @elseif($kyc->status === 'pending')
                            <div class="status-badge pending">
                                <span class="status-icon">⏳</span>
                                <span class="status-text">Pending Review</span>
                            </div>
                            <p class="status-message">Your documents are under review. We'll notify you once verified.</p>
                        @elseif($kyc->status === 'rejected')
                            <div class="status-badge rejected">
                                <span class="status-icon">❌</span>
                                <span class="status-text">Rejected</span>
                            </div>
                            <p class="status-message">{{ $kyc->rejection_reason }}</p>
                            <p class="status-submessage">Please resubmit with correct documents.</p>
                        @endif
                    @else
                        <div class="status-badge unverified">
                            <span class="status-icon"><i class="fas fa-clipboard-list"></i></span>
                            <span class="status-text">Not Verified</span>
                        </div>
                        <p class="status-message">Please complete your KYC verification to book vehicles.</p>
                    @endif
                </div>

                <!-- KYC Form -->
                <div class="kyc-form-card">
                    @if($errors->any())
                        <div class="alert alert-error">
                            <strong>Error:</strong>
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

                    <form action="{{ route('kyc.store') }}" method="POST" enctype="multipart/form-data" class="kyc-form">
                        @csrf

                        <h3 class="form-section-title"><i class="fas fa-id-card"></i> Identity Information</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_type" class="form-label">ID Type</label>
                                <select id="id_type" name="id_type" class="form-input" required>
                                    <option value="">Select ID Type</option>
                                    <option value="nin" {{ old('id_type', $kyc->id_type ?? '') === 'nin' ? 'selected' : '' }}>NIN (National ID)</option>
                                    <option value="passport" {{ old('id_type', $kyc->id_type ?? '') === 'passport' ? 'selected' : '' }}>Passport</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" id="id_number" name="id_number" class="form-input" 
                                       value="{{ old('id_number', $kyc->id_number ?? '') }}" 
                                       placeholder="Enter your ID number" required>
                            </div>
                        </div>

                        <h3 class="form-section-title"><i class="fas fa-car"></i> Driving Permit</h3>

                        <div class="form-group">
                            <label for="permit_number" class="form-label">Permit/License Number</label>
                            <input type="text" id="permit_number" name="permit_number" class="form-input" 
                                   value="{{ old('permit_number', $kyc->permit_number ?? '') }}" 
                                   placeholder="Enter your driving permit number" required>
                        </div>

                        <h3 class="form-section-title"><i class="fas fa-file-upload"></i> Document Upload</h3>

                        <div class="form-group">
                            <label for="id_document" class="form-label"><i class="fas fa-camera"></i> ID Document Photo (PDF, JPG, PNG)</label>
                            <p class="form-hint">Upload a clear photo of your ID or Passport</p>
                            <div class="file-input-wrapper">
                                <input type="file" id="id_document" name="id_document" class="file-input" 
                                       accept=".pdf,.jpg,.jpeg,.png" required>
                                <label for="id_document" class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                    <span class="file-input-text">Click to upload or drag and drop</span>
                                    <span class="file-input-hint">PDF, JPG, PNG up to 5MB</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="id_original_document" class="form-label"><i class="fas fa-id-card"></i> Original ID/Passport Document (PDF, JPG, PNG)</label>
                            <p class="form-hint">Upload the original ID or Passport document (optional but recommended for faster verification)</p>
                            <div class="file-input-wrapper">
                                <input type="file" id="id_original_document" name="id_original_document" class="file-input" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <label for="id_original_document" class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                    <span class="file-input-text">Click to upload or drag and drop</span>
                                    <span class="file-input-hint">PDF, JPG, PNG up to 5MB (Optional)</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="permit_document" class="form-label"><i class="fas fa-camera"></i> Driving Permit Photo (PDF, JPG, PNG)</label>
                            <p class="form-hint">Upload a clear photo of your driving permit or license</p>
                            <div class="file-input-wrapper">
                                <input type="file" id="permit_document" name="permit_document" class="file-input" 
                                       accept=".pdf,.jpg,.jpeg,.png" required>
                                <label for="permit_document" class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                    <span class="file-input-text">Click to upload or drag and drop</span>
                                    <span class="file-input-hint">PDF, JPG, PNG up to 5MB</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="permit_original_document" class="form-label"><i class="fas fa-car"></i> Original Driving Permit Document (PDF, JPG, PNG)</label>
                            <p class="form-hint">Upload the original driving permit or license document (optional but recommended for faster verification)</p>
                            <div class="file-input-wrapper">
                                <input type="file" id="permit_original_document" name="permit_original_document" class="file-input" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <label for="permit_original_document" class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                    <span class="file-input-text">Click to upload or drag and drop</span>
                                    <span class="file-input-hint">PDF, JPG, PNG up to 5MB (Optional)</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="submit-btn"><i class="fas fa-check"></i> Submit for Verification</button>
                            <a href="{{ url('/dashboard') }}" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Section -->
            <div class="kyc-info-section">
                <h3>Why KYC Verification?</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-icon"><i class="fas fa-shield-alt"></i></span>
                        <h4>Security</h4>
                        <p>Protects both you and our vehicles</p>
                    </div>
                    <div class="info-item">
                        <span class="info-icon"><i class="fas fa-balance-scale"></i></span>
                        <h4>Legal Compliance</h4>
                        <p>Meets anti-money laundering regulations</p>
                    </div>
                    <div class="info-item">
                        <span class="info-icon"><i class="fas fa-check-circle"></i></span>
                        <h4>Trust</h4>
                        <p>Ensures verified customers only</p>
                    </div>
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

    <script>
    (function(){
      var y=new Date().getFullYear();
      document.querySelectorAll('.current-year').forEach(function(el){ el.textContent=y; });
    })();
    </script>

    @section('styles')
        <style>
            .kyc-section {
                padding: 3rem 1rem;
                background: #f8f9fa;
                min-height: calc(100vh - 300px);
            }

            .kyc-wrapper {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 2rem;
                max-width: 1000px;
                margin: 0 auto 3rem;
            }

            .kyc-status-card {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                height: fit-content;
            }

            .status-badge {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                font-weight: 600;
            }

            .status-badge.verified {
                background: #d4edda;
                color: #155724;
            }

            .status-badge.pending {
                background: #fff3cd;
                color: #856404;
            }

            .status-badge.rejected {
                background: #f8d7da;
                color: #721c24;
            }

            .status-badge.unverified {
                background: #e2e3e5;
                color: #383d41;
            }

            .status-icon {
                font-size: 1.5rem;
            }

            .status-message {
                color: #666;
                margin: 0;
                font-size: 0.95rem;
            }

            .status-submessage {
                color: #999;
                margin: 0.5rem 0 0 0;
                font-size: 0.85rem;
            }

            .kyc-form-card {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .kyc-form {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .form-section-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #1a2332;
                margin: 1rem 0 0.5rem 0;
                border-bottom: 2px solid #ff9800;
                padding-bottom: 0.5rem;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
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

            .form-hint {
                font-size: 0.85rem;
                color: #666;
                margin: -0.5rem 0 0.75rem 0;
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

            .kyc-info-section {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                max-width: 1000px;
                margin: 0 auto;
            }

            .kyc-info-section h3 {
                font-size: 1.3rem;
                font-weight: 600;
                color: #1a2332;
                margin-bottom: 1.5rem;
            }

            .info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .info-item {
                text-align: center;
            }

            .info-icon {
                font-size: 2.5rem;
                display: block;
                margin-bottom: 0.5rem;
            }

            .info-item h4 {
                font-size: 1.1rem;
                font-weight: 600;
                color: #1a2332;
                margin: 0.5rem 0;
            }

            .info-item p {
                color: #666;
                margin: 0;
                font-size: 0.95rem;
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
                .kyc-wrapper {
                    grid-template-columns: 1fr;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }

                .form-actions {
                    flex-direction: column;
                }
            }
        </style>
    @endsection
@endsection
