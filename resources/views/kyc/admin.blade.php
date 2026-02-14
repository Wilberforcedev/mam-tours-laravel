@extends('layouts.admin')

@section('title', 'KYC Management | MAM Tours Admin')

@section('content')
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title"><i class="fas fa-id-card"></i> KYC Verification Management</h1>
            <p class="dashboard-subtitle">Review and approve customer KYC submissions.</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" data-tab="pending">
            <i class="fas fa-hourglass-half"></i> Pending (<span id="pendingCount">0</span>)
        </button>
        <button class="tab" data-tab="verified">
            <i class="fas fa-check-circle"></i> Verified (<span id="verifiedCount">0</span>)
        </button>
        <button class="tab" data-tab="rejected">
            <i class="fas fa-times-circle"></i> Rejected (<span id="rejectedCount">0</span>)
        </button>
    </div>

    <!-- KYC Container -->
    <div id="kycContainer" class="content-container">
        <div id="pendingKyc" class="kyc-list"></div>
        <div id="verifiedKyc" class="kyc-list" style="display: none;"></div>
        <div id="rejectedKyc" class="kyc-list" style="display: none;"></div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" id="paginationContainer" style="display: none;">
        <button class="pagination-btn" id="prevBtn" disabled>Previous</button>
        <div class="pagination-info">
            <span id="pageInfo">Page 1 of 1</span>
        </div>
        <button class="pagination-btn" id="nextBtn" disabled>Next</button>
    </div>

    <!-- KYC Detail Modal -->
    <div id="kycDetailModal" class="modal">
        <div class="modal-content kyc-modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-id-card"></i> KYC Details</h2>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="kyc-detail-body">
                <div class="kyc-detail-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
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
                    <h3><i class="fas fa-passport"></i> Identity Information</h3>
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
                    <h3><i class="fas fa-file-alt"></i> Document Verification</h3>
                    <p class="verification-note"><i class="fas fa-info-circle"></i> Compare the information below with the uploaded documents to verify authenticity.</p>
                    
                    <div class="document-verification-row">
                        <div class="document-verification-item">
                            <h4><i class="fas fa-camera"></i> ID Document Photo</h4>
                            <div class="document-preview" id="idDocumentPreview">
                                <div class="preview-loading">Loading document...</div>
                            </div>
                            <div class="document-actions">
                                <button class="download-btn" id="downloadIdDocBtn" type="button"><i class="fas fa-download"></i> Download Full Size</button>
                            </div>
                        </div>
                        
                        <div class="document-verification-item">
                            <h4><i class="fas fa-camera"></i> Permit/License Document Photo</h4>
                            <div class="document-preview" id="permitDocumentPreview">
                                <div class="preview-loading">Loading document...</div>
                            </div>
                            <div class="document-actions">
                                <button class="download-btn" id="downloadPermitDocBtn" type="button"><i class="fas fa-download"></i> Download Full Size</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kyc-detail-section" id="originalDocumentsSection" style="display: none;">
                    <h3><i class="fas fa-file-pdf"></i> Original Documents (Uploaded)</h3>
                    <p class="verification-note"><i class="fas fa-info-circle"></i> Original documents uploaded by the customer for additional verification.</p>
                    
                    <div class="document-verification-row">
                        <div class="document-verification-item">
                            <h4><i class="fas fa-id-card"></i> Original ID/Passport Document</h4>
                            <div class="document-preview" id="idOriginalDocumentPreview">
                                <div class="preview-loading">No document uploaded</div>
                            </div>
                            <div class="document-actions">
                                <button class="download-btn" id="downloadIdOriginalDocBtn" type="button" style="display: none;"><i class="fas fa-download"></i> Download Full Size</button>
                            </div>
                        </div>
                        
                        <div class="document-verification-item">
                            <h4><i class="fas fa-car"></i> Original Driving Permit Document</h4>
                            <div class="document-preview" id="permitOriginalDocumentPreview">
                                <div class="preview-loading">No document uploaded</div>
                            </div>
                            <div class="document-actions">
                                <button class="download-btn" id="downloadPermitOriginalDocBtn" type="button" style="display: none;"><i class="fas fa-download"></i> Download Full Size</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kyc-detail-section">
                    <h3><i class="fas fa-flag"></i> Status</h3>
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
                    <h3><i class="fas fa-tasks"></i> Actions</h3>
                    <div class="action-buttons">
                        <button class="approve-btn" id="approveBtn"><i class="fas fa-check"></i> Approve KYC</button>
                        <button class="reject-btn" id="rejectBtn"><i class="fas fa-times"></i> Reject KYC</button>
                    </div>
                </div>

                <div class="kyc-detail-section" id="rejectFormSection" style="display: none;">
                    <h3><i class="fas fa-comment"></i> Rejection Reason</h3>
                    <textarea id="rejectionReasonInput" class="form-input" placeholder="Enter reason for rejection..." maxlength="500"></textarea>
                    <div class="action-buttons">
                        <button class="submit-btn" id="submitRejectBtn"><i class="fas fa-paper-plane"></i> Submit Rejection</button>
                        <button class="cancel-btn" id="cancelRejectBtn"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong id="toastTitle"></strong>
                <p id="toastMessage"></p>
            </div>
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

        .view-btn {
            padding: 0.5rem 1rem;
            background: #ff9800;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #999;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
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
    </style>
@endsection

@section('scripts')
    <script>
        const API_BASE = '/api';
        let allKyc = [];
        let currentTab = 'pending';
        let currentPage = 1;
        const itemsPerPage = 5;
        let currentKycId = null;

        const tabs = document.querySelectorAll('.tab');
        const kycContainer = document.getElementById('kycContainer');
        const pendingKycDiv = document.getElementById('pendingKyc');
        const verifiedKycDiv = document.getElementById('verifiedKyc');
        const rejectedKycDiv = document.getElementById('rejectedKyc');
        const modal = document.getElementById('kycDetailModal');
        const closeModal = document.getElementById('closeModal');
        const toast = document.getElementById('toast');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        const paginationContainer = document.getElementById('paginationContainer');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const pageInfo = document.getElementById('pageInfo');

        document.addEventListener('DOMContentLoaded', () => {
            loadKyc();
            setupEventListeners();
        });

        function setupEventListeners() {
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabName = tab.dataset.tab;
                    switchTab(tabName);
                });
            });

            closeModal.addEventListener('click', () => {
                modal.classList.remove('active');
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });

            document.getElementById('approveBtn').addEventListener('click', approveKyc);
            document.getElementById('rejectBtn').addEventListener('click', showRejectForm);
            document.getElementById('submitRejectBtn').addEventListener('click', submitReject);
            document.getElementById('cancelRejectBtn').addEventListener('click', hideRejectForm);

            document.getElementById('downloadIdDocBtn').addEventListener('click', () => {
                downloadDocument(currentKycId, 'id');
            });

            document.getElementById('downloadPermitDocBtn').addEventListener('click', () => {
                downloadDocument(currentKycId, 'permit');
            });

            document.getElementById('downloadIdOriginalDocBtn').addEventListener('click', () => {
                downloadDocument(currentKycId, 'id_original');
            });

            document.getElementById('downloadPermitOriginalDocBtn').addEventListener('click', () => {
                downloadDocument(currentKycId, 'permit_original');
            });

            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderKyc();
                }
            });

            nextBtn.addEventListener('click', () => {
                const filtered = filterKycByStatus(currentTab);
                const totalPages = Math.ceil(filtered.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderKyc();
                }
            });
        }

        async function loadKyc() {
            try {
                const response = await fetch(`${API_BASE}/kyc`);
                allKyc = await response.json();
                updateCounts();
                renderKyc();
            } catch (error) {
                console.error('Error loading KYC:', error);
                showToast('Error', 'Failed to load KYC verifications.');
            }
        }

        function switchTab(tabName) {
            currentTab = tabName;
            currentPage = 1;

            tabs.forEach(t => {
                if (t.dataset.tab === tabName) {
                    t.classList.add('active');
                } else {
                    t.classList.remove('active');
                }
            });

            pendingKycDiv.style.display = tabName === 'pending' ? 'flex' : 'none';
            verifiedKycDiv.style.display = tabName === 'verified' ? 'flex' : 'none';
            rejectedKycDiv.style.display = tabName === 'rejected' ? 'flex' : 'none';

            renderKyc();
        }

        function filterKycByStatus(status) {
            return allKyc.filter(kyc => kyc.status === status);
        }

        function renderKyc() {
            const filtered = filterKycByStatus(currentTab);
            const totalPages = Math.ceil(filtered.length / itemsPerPage);

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const paginatedKyc = filtered.slice(startIndex, endIndex);

            const html = paginatedKyc.length > 0
                ? paginatedKyc.map(kyc => createKycCard(kyc)).join('')
                : '<div class="empty-state"><p>No KYC submissions in this category.</p></div>';

            const targetDiv = currentTab === 'pending' ? pendingKycDiv : 
                             currentTab === 'verified' ? verifiedKycDiv : rejectedKycDiv;
            targetDiv.innerHTML = html;

            updatePagination(filtered.length, totalPages);

            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const kycId = parseInt(e.target.dataset.kycId);
                    openKycDetail(kycId);
                });
            });
        }

        function createKycCard(kyc) {
            const user = kyc.user || {};
            const statusClass = kyc.status;
            const statusText = kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1);

            return `
                <div class="kyc-card">
                    <div class="kyc-card-info">
                        <div class="kyc-card-header">
                            <span class="kyc-card-name">${user.name || 'Unknown'}</span>
                            <span class="kyc-card-status ${statusClass}">${statusText}</span>
                        </div>
                        <div class="kyc-card-detail">
                            <label>Email:</label> ${user.email || 'N/A'}
                        </div>
                        <div class="kyc-card-detail">
                            <label>ID Type:</label> ${kyc.id_type === 'nin' ? 'NIN' : 'Passport'} - ${kyc.id_number}
                        </div>
                        <div class="kyc-card-detail">
                            <label>Submitted:</label> ${new Date(kyc.created_at).toLocaleDateString()}
                        </div>
                    </div>
                    <div class="kyc-card-actions">
                        <button class="view-btn" data-kyc-id="${kyc.id}">View Details</button>
                    </div>
                </div>
            `;
        }

        function openKycDetail(kycId) {
            const kyc = allKyc.find(k => k.id === kycId);
            if (!kyc) return;

            currentKycId = kycId;
            const user = kyc.user || {};

            document.getElementById('detailName').textContent = user.name || 'N/A';
            document.getElementById('detailEmail').textContent = user.email || 'N/A';
            document.getElementById('detailPhone').textContent = user.phone || 'N/A';
            document.getElementById('detailIdType').textContent = kyc.id_type === 'nin' ? 'NIN (National ID)' : 'Passport';
            document.getElementById('detailIdNumber').textContent = kyc.id_number;
            document.getElementById('detailPermitNumber').textContent = kyc.permit_number;

            const statusBadge = document.getElementById('detailStatus');
            statusBadge.textContent = kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1);
            statusBadge.className = `status-badge ${kyc.status}`;

            const rejectionReasonRow = document.getElementById('rejectionReasonRow');
            if (kyc.status === 'rejected' && kyc.rejection_reason) {
                rejectionReasonRow.style.display = 'grid';
                document.getElementById('detailRejectionReason').textContent = kyc.rejection_reason;
            } else {
                rejectionReasonRow.style.display = 'none';
            }

            const verifiedAtRow = document.getElementById('verifiedAtRow');
            if (kyc.status === 'verified' && kyc.verified_at) {
                verifiedAtRow.style.display = 'grid';
                document.getElementById('detailVerifiedAt').textContent = new Date(kyc.verified_at).toLocaleString();
            } else {
                verifiedAtRow.style.display = 'none';
            }

            const actionSection = document.getElementById('actionSection');
            if (kyc.status === 'pending') {
                actionSection.style.display = 'block';
            } else {
                actionSection.style.display = 'none';
            }

            // Load document previews
            loadDocumentPreview('id', kyc.id_document_path);
            loadDocumentPreview('permit', kyc.permit_document_path);

            // Load original document previews if they exist
            const hasOriginalDocs = kyc.id_original_document_path || kyc.permit_original_document_path;
            const originalDocsSection = document.getElementById('originalDocumentsSection');
            
            if (hasOriginalDocs) {
                originalDocsSection.style.display = 'block';
                loadDocumentPreview('id_original', kyc.id_original_document_path);
                loadDocumentPreview('permit_original', kyc.permit_original_document_path);
                
                // Show download buttons if documents exist
                if (kyc.id_original_document_path) {
                    document.getElementById('downloadIdOriginalDocBtn').style.display = 'block';
                }
                if (kyc.permit_original_document_path) {
                    document.getElementById('downloadPermitOriginalDocBtn').style.display = 'block';
                }
            } else {
                originalDocsSection.style.display = 'none';
            }

            hideRejectForm();
            modal.classList.add('active');
        }

        function loadDocumentPreview(type, filePath) {
            let previewDiv;
            
            if (type === 'id') {
                previewDiv = document.getElementById('idDocumentPreview');
            } else if (type === 'permit') {
                previewDiv = document.getElementById('permitDocumentPreview');
            } else if (type === 'id_original') {
                previewDiv = document.getElementById('idOriginalDocumentPreview');
            } else if (type === 'permit_original') {
                previewDiv = document.getElementById('permitOriginalDocumentPreview');
            }
            
            if (!filePath) {
                previewDiv.innerHTML = '<div class="preview-loading">No document uploaded</div>';
                return;
            }

            const fileUrl = `/storage/${filePath}`;
            const fileExtension = filePath.split('.').pop().toLowerCase();

            // Check if it's an image or PDF
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                previewDiv.innerHTML = `<img src="${fileUrl}" alt="Document preview" />`;
            } else if (fileExtension === 'pdf') {
                previewDiv.innerHTML = `<iframe src="${fileUrl}"></iframe>`;
            } else {
                previewDiv.innerHTML = '<div class="preview-loading">Document format not supported for preview</div>';
            }
        }

        function downloadDocument(kycId, type) {
            const url = `${API_BASE}/kyc/${kycId}/document/${type}`;
            const link = document.createElement('a');
            link.href = url;
            link.download = `kyc_${type}_document`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        async function approveKyc() {
            if (!currentKycId) return;

            try {
                const response = await fetch(`${API_BASE}/kyc/${currentKycId}/verify`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    showToast('Success', 'KYC approved successfully.');
                    modal.classList.remove('active');
                    await loadKyc();
                } else {
                    const result = await response.json();
                    showToast('Error', result.message || 'Failed to approve KYC.');
                }
            } catch (error) {
                console.error('Error approving KYC:', error);
                showToast('Error', 'Failed to approve KYC.');
            }
        }

        function showRejectForm() {
            document.getElementById('actionSection').style.display = 'none';
            document.getElementById('rejectFormSection').style.display = 'block';
            document.getElementById('rejectionReasonInput').focus();
        }

        function hideRejectForm() {
            document.getElementById('actionSection').style.display = 'block';
            document.getElementById('rejectFormSection').style.display = 'none';
            document.getElementById('rejectionReasonInput').value = '';
        }

        async function submitReject() {
            if (!currentKycId) return;

            const reason = document.getElementById('rejectionReasonInput').value.trim();
            if (!reason) {
                showToast('Error', 'Please enter a rejection reason.');
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/kyc/${currentKycId}/reject`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason })
                });

                if (response.ok) {
                    showToast('Success', 'KYC rejected successfully.');
                    modal.classList.remove('active');
                    await loadKyc();
                } else {
                    const result = await response.json();
                    showToast('Error', result.message || 'Failed to reject KYC.');
                }
            } catch (error) {
                console.error('Error rejecting KYC:', error);
                showToast('Error', 'Failed to reject KYC.');
            }
        }

        function updateCounts() {
            document.getElementById('pendingCount').textContent = allKyc.filter(k => k.status === 'pending').length;
            document.getElementById('verifiedCount').textContent = allKyc.filter(k => k.status === 'verified').length;
            document.getElementById('rejectedCount').textContent = allKyc.filter(k => k.status === 'rejected').length;
        }

        function updatePagination(totalItems, totalPages) {
            if (totalItems === 0) {
                paginationContainer.style.display = 'none';
                return;
            }

            paginationContainer.style.display = 'flex';
            pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${totalItems} total)`;

            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;
        }

        function showToast(title, message) {
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
@endsection
