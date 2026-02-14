@extends('layouts.admin')

@section('title', 'Admin Dashboard | MAM Tours & Travel')

@section('content')
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Admin Dashboard</h1>
            <p class="dashboard-subtitle">Manage bookings and vehicles.</p>
        </div>
        <button class="add-vehicle-btn" id="addVehicleBtn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            + Add Vehicle
        </button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" data-tab="bookings">
            Bookings
        </button>
        <button class="tab" data-tab="vehicles">
            Vehicles (<span id="vehiclesCount">0</span>)
        </button>
        <button class="tab" data-tab="kyc">
            KYC Verification (<span id="kycCount">0</span>)
        </button>
        <button class="tab" data-tab="reports">
            Reports
        </button>
    </div>

    <!-- Bookings Container -->
    <div id="bookingsContainer" class="content-container">
        <div class="tabs-inner">
            <button class="tab-inner active" data-tab-inner="pending">
                Pending (<span id="pendingCount">0</span>)
            </button>
            <button class="tab-inner" data-tab-inner="active">
                Active Bookings (<span id="activeCount">0</span>)
            </button>
            <button class="tab-inner" data-tab-inner="completed">
                Completed (<span id="completedCount">0</span>)
            </button>
        </div>
        <div class="bookings-container">
            <div id="pendingBookings" class="bookings-list"></div>
            <div id="activeBookings" class="bookings-list" style="display: none;"></div>
            <div id="completedBookings" class="bookings-list" style="display: none;"></div>
        </div>
    </div>

    <!-- Vehicles Container -->
    <div id="vehiclesContainer" class="content-container" style="display: none;">
        <div class="vehicles-grid" id="vehiclesGrid">
            <div class="loading-state">Loading vehicles...</div>
        </div>
        
        <!-- Vehicle Pagination -->
        <div class="pagination-container" id="vehiclePaginationContainer" style="display: none;">
            <button class="pagination-btn" id="vehiclePrevBtn" disabled>Previous</button>
            <div class="pagination-info">
                <span id="vehiclePageInfo">Page 1 of 1</span>
            </div>
            <button class="pagination-btn" id="vehicleNextBtn" disabled>Next</button>
        </div>
    </div>

    <!-- KYC Container -->
    <div id="kycContainer" class="content-container" style="display: none;">
        <div class="tabs-inner">
            <button class="tab-inner active" data-tab-inner="pending">
                Pending (<span id="kycPendingCount">0</span>)
            </button>
            <button class="tab-inner" data-tab-inner="verified">
                Verified (<span id="kycVerifiedCount">0</span>)
            </button>
            <button class="tab-inner" data-tab-inner="rejected">
                Rejected (<span id="kycRejectedCount">0</span>)
            </button>
        </div>
        <div id="kycListContainer">
            <div id="kycPendingList" class="kyc-list"></div>
            <div id="kycVerifiedList" class="kyc-list" style="display: none;"></div>
            <div id="kycRejectedList" class="kyc-list" style="display: none;"></div>
        </div>
    </div>

    <!-- Reports Container -->
    <div id="reportsContainer" class="content-container" style="display: none;">
        <div class="reports-header">
            <h2>Booking Reports</h2>
            <p>Generate and view booking statistics</p>
        </div>

        <div class="reports-filters">
            <div class="filter-group">
                <label for="reportType">Report Type</label>
                <select id="reportType" class="filter-select">
                    <option value="weekly">Weekly Report</option>
                    <option value="monthly">Monthly Report</option>
                    <option value="custom">Custom Date Range</option>
                </select>
            </div>
            
            <div class="filter-group" id="customDateGroup" style="display: none;">
                <label for="startDate">Start Date</label>
                <input type="date" id="startDate" class="filter-input">
            </div>
            
            <div class="filter-group" id="customEndDateGroup" style="display: none;">
                <label for="endDate">End Date</label>
                <input type="date" id="endDate" class="filter-input">
            </div>
            
            <button class="generate-report-btn" id="generateReportBtn">
                <i class="fas fa-chart-bar"></i> Generate Report
            </button>
            
            <button class="export-report-btn" id="exportReportBtn">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>

        <div class="reports-stats" id="reportsStats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-content">
                    <h3 id="totalBookings">0</h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-content">
                    <h3 id="totalRevenue">UGX 0</h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-car"></i></div>
                <div class="stat-content">
                    <h3 id="mostBookedCar">-</h3>
                    <p>Most Booked Car</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-content">
                    <h3 id="avgBookingValue">UGX 0</h3>
                    <p>Avg Booking Value</p>
                </div>
            </div>
        </div>

        <div class="reports-table-container">
            <h3>Booking Details</h3>
            <table class="reports-table" id="reportsTable">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #999;">
                            Select a report type and click "Generate Report" to view data
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" id="paginationContainer" style="display: none;">
        <button class="pagination-btn" id="prevBtn" disabled>Previous</button>
        <div class="pagination-info">
            <span id="pageInfo">Page 1 of 1</span>
        </div>
        <button class="pagination-btn" id="nextBtn" disabled>Next</button>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Vehicle</h2>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <p class="modal-description">
                Add a new vehicle to the fleet. Ugandan number plates only (e.g., UAJ 979B or UG 32 00042).
            </p>
            <form id="addVehicleForm">
                <div class="form-group">
                            <label for="car_picture" class="form-label"><i class="fas fa-image"></i> Car Picture</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="car_picture" name="car_picture" class="file-input" accept="image/*">
                                <label for="car_picture" class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                    <span class="file-input-text">Click to upload or drag and drop</span>
                                    <span class="file-input-hint">PNG, JPG, GIF up to 2MB</span>
                                </label>
                            </div>
                </div>
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" required placeholder="e.g., Mercedes-Benz">
                </div>
                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" id="model" name="model" required placeholder="e.g., E350">
                </div>
                <div class="form-group">
                    <label for="numberPlate">Number Plate</label>
                    <input type="text" id="numberPlate" name="numberPlate" required placeholder="e.g., UAJ 979B, UBB 123C, UG 32 00042">
                    <small class="form-hint">Format: UAJ 979B (legacy) or UG 32 00042 (digital). Spaces are optional.</small>
                </div>
                <div class="form-group">
                    <label for="dailyRate">Daily Rate (UGX)</label>
                    <input type="number" id="dailyRate" name="dailyRate" required placeholder="e.g., 200000" min="0">
                </div>
                <div class="form-group">
                    <label for="seats">Seats</label>
                    <input type="number" id="seats" name="seats" required placeholder="e.g., 5" min="1" max="50">
                </div>
                <button type="submit" class="submit-btn">Add Vehicle</button>
            </form>
        </div>
    </div>

    <!-- KYC Detail Modal -->
    <div id="kycDetailModal" class="modal">
        <div class="modal-content kyc-modal-content">
            <div class="modal-header">
                <h2>KYC Details</h2>
                <button class="close-btn" id="closeKycModal">&times;</button>
            </div>
            <div class="kyc-detail-body">
                <div class="kyc-detail-section">
                    <h3>Customer Information</h3>
                    <div class="detail-row">
                        <label>Name:</label>
                        <span id="detailName"></span>
                    </div>
                    <div class="detail-row">
                        <label>Email:</label>
                        <span id="detailEmail"></span>
                    </div>
                    <div class="detail-row">
                        <label>Phone:</label>
                        <span id="detailPhone"></span>
                    </div>
                </div>

                <div class="kyc-detail-section">
                    <h3>Identity Information</h3>
                    <div class="detail-row">
                        <label>ID Type:</label>
                        <span id="detailIdType"></span>
                    </div>
                    <div class="detail-row">
                        <label>ID Number:</label>
                        <span id="detailIdNumber"></span>
                    </div>
                    <div class="detail-row">
                        <label>Permit Number:</label>
                        <span id="detailPermitNumber"></span>
                    </div>
                </div>

                <div class="kyc-detail-section">
                    <h3>Documents</h3>
                    <div class="document-row">
                        <div class="document-item">
                            <label>ID Document</label>
                            <button class="view-doc-btn" id="viewIdDocBtn" type="button">📄 View Document</button>
                        </div>
                        <div class="document-item">
                            <label>Permit Document</label>
                            <button class="view-doc-btn" id="viewPermitDocBtn" type="button">📄 View Document</button>
                        </div>
                    </div>
                </div>

                <div class="kyc-detail-section">
                    <h3>Status</h3>
                    <div class="detail-row">
                        <label>Current Status:</label>
                        <span id="detailStatus" class="status-badge"></span>
                    </div>
                    <div class="detail-row" id="rejectionReasonRow" style="display: none;">
                        <label>Rejection Reason:</label>
                        <span id="detailRejectionReason"></span>
                    </div>
                    <div class="detail-row" id="verifiedAtRow" style="display: none;">
                        <label>Verified At:</label>
                        <span id="detailVerifiedAt"></span>
                    </div>
                </div>

                <div class="kyc-detail-section" id="actionSection">
                    <h3>Actions</h3>
                    <div class="action-buttons">
                        <button class="approve-btn" id="approveBtn" type="button"><i class="fas fa-check"></i> Approve KYC</button>
                        <button class="reject-btn" id="rejectBtn" type="button"><i class="fas fa-times"></i> Reject KYC</button>
                    </div>
                </div>

                <div class="kyc-detail-section" id="rejectFormSection" style="display: none;">
                    <h3>Rejection Reason</h3>
                    <textarea id="rejectionReasonInput" class="form-input" placeholder="Enter reason for rejection..." maxlength="500"></textarea>
                    <div class="action-buttons">
                        <button class="submit-btn" id="submitRejectBtn" type="button">Submit Rejection</button>
                        <button class="cancel-btn" id="cancelRejectBtn" type="button">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <strong id="toastTitle"></strong>
            <p id="toastMessage"></p>
        </div>
    </div>

    <style>
        .kyc-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .kyc-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.3s ease;
        }

        .kyc-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #ff9800;
        }

        .kyc-card-info {
            display: grid;
            gap: 0.75rem;
        }

        .kyc-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .kyc-card-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a2332;
        }

        .kyc-card-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .kyc-card-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .kyc-card-status.verified {
            background: #d4edda;
            color: #155724;
        }

        .kyc-card-status.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .kyc-card-detail {
            font-size: 0.95rem;
            color: #666;
        }

        .kyc-card-detail label {
            font-weight: 600;
            color: #1a2332;
        }

        .kyc-card-actions {
            display: flex;
            gap: 0.75rem;
        }

        .kyc-view-btn {
            padding: 0.5rem 1rem;
            background: #ff9800;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .kyc-view-btn:hover {
            background: #ff7c00;
            transform: translateY(-2px);
        }

        .kyc-modal-content {
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .kyc-detail-body {
            padding: 1.5rem 0;
        }

        .kyc-detail-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .kyc-detail-section:last-child {
            border-bottom: none;
        }

        .kyc-detail-section h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a2332;
            margin-bottom: 1rem;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            margin-bottom: 0.75rem;
            align-items: center;
        }

        .detail-row label {
            font-weight: 600;
            color: #1a2332;
        }

        .detail-row span {
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.verified {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .document-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .document-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .document-item label {
            font-weight: 600;
            color: #1a2332;
            font-size: 0.9rem;
        }

        .view-doc-btn {
            padding: 0.75rem 1rem;
            background: #f0f0f0;
            color: #1a2332;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-doc-btn:hover {
            background: #e0e0e0;
            border-color: #ff9800;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .approve-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .approve-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .reject-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reject-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .submit-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #c82333;
        }

        .cancel-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: #f0f0f0;
            color: #1a2332;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background: #e0e0e0;
        }

        #rejectionReasonInput {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 1rem;
        }

        #rejectionReasonInput:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        }

        @media (max-width: 768px) {
            .kyc-card {
                grid-template-columns: 1fr;
            }

            .kyc-card-actions {
                flex-direction: column;
            }

            .document-row {
                grid-template-columns: 1fr;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        .verification-note {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            color: #1565c0;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .document-verification-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .document-verification-item {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .document-verification-item h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a2332;
            margin: 0;
        }

        .document-preview {
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .document-preview img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            cursor: zoom-in;
        }

        .document-preview iframe {
            width: 100%;
            height: 400px;
            border: none;
        }

        .preview-loading {
            color: #999;
            font-size: 0.9rem;
        }

        .document-actions {
            display: flex;
            gap: 0.5rem;
        }

        .download-btn {
            flex: 1;
            padding: 0.5rem 1rem;
            background: #2196f3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .document-verification-row {
                grid-template-columns: 1fr;
            }

            .document-preview {
                min-height: 250px;
            }

            .document-preview iframe {
                height: 300px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/admin.js') }}"></script>
@endsection