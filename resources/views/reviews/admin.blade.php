@extends('layouts.app')

@section('title', 'Manage Reviews | Admin')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-star"></i> Review Management</h1>
        <p>Approve or reject customer reviews</p>
    </div>

    <div class="reviews-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <span class="stat-number">{{ $reviews->where('is_approved', false)->count() }}</span>
                <span class="stat-label">Pending Reviews</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <span class="stat-number">{{ $reviews->where('is_approved', true)->count() }}</span>
                <span class="stat-label">Approved Reviews</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-info">
                <span class="stat-number">{{ number_format($reviews->avg('rating'), 1) }}</span>
                <span class="stat-label">Average Rating</span>
            </div>
        </div>
    </div>

    <div class="reviews-table-container">
        <table class="reviews-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr class="review-row {{ $review->is_approved ? 'approved' : 'pending' }}">
                    <td>
                        <div class="customer-info">
                            <strong>{{ $review->name }}</strong>
                            <small>{{ $review->email }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="rating-display">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $review->rating ? 'active' : '' }}"></i>
                            @endfor
                            <span class="rating-number">({{ $review->rating }})</span>
                        </div>
                    </td>
                    <td>
                        <div class="review-text">
                            {{ Str::limit($review->review_text, 100) }}
                            @if(strlen($review->review_text) > 100)
                                <button class="expand-btn" onclick="toggleReview({{ $review->id }})">
                                    <i class="fas fa-expand-alt"></i>
                                </button>
                            @endif
                        </div>
                        <div class="full-review" id="full-review-{{ $review->id }}" style="display: none;">
                            {{ $review->review_text }}
                        </div>
                    </td>
                    <td>
                        <div class="date-info">
                            <strong>{{ $review->created_at->format('M d, Y') }}</strong>
                            <small>{{ $review->created_at->format('h:i A') }}</small>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge {{ $review->is_approved ? 'approved' : 'pending' }}">
                            <i class="fas {{ $review->is_approved ? 'fa-check-circle' : 'fa-clock' }}"></i>
                            {{ $review->is_approved ? 'Approved' : 'Pending' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            @if(!$review->is_approved)
                                <button class="approve-btn" onclick="approveReview({{ $review->id }})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            @endif
                            <button class="reject-btn" onclick="rejectReview({{ $review->id }})">
                                <i class="fas fa-times"></i> {{ $review->is_approved ? 'Remove' : 'Reject' }}
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-reviews">
                        <i class="fas fa-star"></i>
                        <p>No reviews found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $reviews->links() }}
    </div>
</div>

<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.admin-header {
    text-align: center;
    margin-bottom: 2rem;
}

.admin-header h1 {
    color: #1a2332;
    margin-bottom: 0.5rem;
}

.admin-header h1 i {
    color: #ff9800;
    margin-right: 0.5rem;
}

.reviews-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #ff9800;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #1a2332;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.reviews-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.reviews-table {
    width: 100%;
    border-collapse: collapse;
}

.reviews-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #1a2332;
    border-bottom: 2px solid #e0e0e0;
}

.reviews-table td {
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
}

.review-row.pending {
    background: #fff9e6;
}

.review-row.approved {
    background: #f0f9f0;
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-info strong {
    color: #1a2332;
    margin-bottom: 0.25rem;
}

.customer-info small {
    color: #666;
    font-size: 0.85rem;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.rating-display .fas.fa-star {
    color: #ddd;
    font-size: 0.9rem;
}

.rating-display .fas.fa-star.active {
    color: #ff9800;
}

.rating-number {
    margin-left: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.review-text {
    max-width: 300px;
    position: relative;
}

.expand-btn {
    background: none;
    border: none;
    color: #ff9800;
    cursor: pointer;
    margin-left: 0.5rem;
    font-size: 0.8rem;
}

.full-review {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 0.9rem;
}

.date-info {
    display: flex;
    flex-direction: column;
}

.date-info strong {
    color: #1a2332;
    margin-bottom: 0.25rem;
}

.date-info small {
    color: #666;
    font-size: 0.85rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.approved {
    background: #d1edff;
    color: #0c5460;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.approve-btn,
.reject-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.3s ease;
}

.approve-btn {
    background: #28a745;
    color: white;
}

.approve-btn:hover {
    background: #218838;
}

.reject-btn {
    background: #dc3545;
    color: white;
}

.reject-btn:hover {
    background: #c82333;
}

.no-reviews {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-reviews i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
    display: block;
}

.pagination-container {
    display: flex;
    justify-content: center;
}

@media (max-width: 768px) {
    .admin-container {
        padding: 1rem;
    }
    
    .reviews-table-container {
        overflow-x: auto;
    }
    
    .reviews-table {
        min-width: 800px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function toggleReview(reviewId) {
    const fullReview = document.getElementById('full-review-' + reviewId);
    const expandBtn = event.target.closest('.expand-btn');
    
    if (fullReview.style.display === 'none') {
        fullReview.style.display = 'block';
        expandBtn.innerHTML = '<i class="fas fa-compress-alt"></i>';
    } else {
        fullReview.style.display = 'none';
        expandBtn.innerHTML = '<i class="fas fa-expand-alt"></i>';
    }
}

function approveReview(reviewId) {
    if (confirm('Are you sure you want to approve this review?')) {
        fetch(`/api/reviews/${reviewId}/approve`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error approving review');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error approving review');
        });
    }
}

function rejectReview(reviewId) {
    if (confirm('Are you sure you want to reject/remove this review? This action cannot be undone.')) {
        fetch(`/api/reviews/${reviewId}/reject`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error rejecting review');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error rejecting review');
        });
    }
}
</script>
@endsection