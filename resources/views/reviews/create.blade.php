@extends('layouts.app')

@section('title', 'Leave a Review | MAM TOURS')

@section('content')
<div class="container">
    <div class="review-form-container">
        <div class="review-form-header">
            <h1><i class="fas fa-star"></i> Share Your Experience</h1>
            <p>Help other travelers by sharing your experience with MAM TOURS</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('reviews.store') }}" method="POST" class="review-form">
            @csrf
            
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Your Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required>
                @error('name')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required>
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="rating"><i class="fas fa-star"></i> Rating</label>
                <div class="rating-input">
                    <div class="stars" id="ratingStars">
                        <span class="star" data-rating="1"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="2"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="3"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="4"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="5"><i class="fas fa-star"></i></span>
                    </div>
                    <input type="hidden" id="rating" name="rating" value="{{ old('rating', 5) }}" required>
                    <span class="rating-text">Excellent</span>
                </div>
                @error('rating')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="review_text"><i class="fas fa-comment"></i> Your Review</label>
                <textarea id="review_text" name="review_text" rows="5" placeholder="Tell us about your experience with MAM TOURS..." required>{{ old('review_text') }}</textarea>
                <div class="char-counter">
                    <span id="charCount">0</span>/1000 characters
                </div>
                @error('review_text')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
                <a href="{{ url('/') }}" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.review-form-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.review-form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.review-form-header h1 {
    color: #1a2332;
    margin-bottom: 0.5rem;
}

.review-form-header h1 i {
    color: #ff9800;
    margin-right: 0.5rem;
}

.review-form-header p {
    color: #666;
    font-size: 1.1rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #1a2332;
}

.form-group label i {
    color: #ff9800;
    margin-right: 0.5rem;
    width: 16px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ff9800;
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stars {
    display: flex;
    gap: 0.25rem;
}

.star {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
}

.star.active,
.star:hover {
    color: #ff9800;
}

.rating-text {
    font-weight: 600;
    color: #1a2332;
}

.char-counter {
    text-align: right;
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
}

.error-message {
    color: #dc3545;
    font-size: 0.9rem;
    margin-top: 0.25rem;
    display: block;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.submit-btn,
.cancel-btn {
    padding: 0.75rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.submit-btn {
    background: #ff9800;
    color: white;
    border: none;
    cursor: pointer;
}

.submit-btn:hover {
    background: #f57c00;
    transform: translateY(-2px);
}

.cancel-btn {
    background: #f5f5f5;
    color: #666;
    border: 2px solid #e0e0e0;
}

.cancel-btn:hover {
    background: #e0e0e0;
    color: #333;
}

@media (max-width: 768px) {
    .review-form-container {
        margin: 1rem;
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .submit-btn,
    .cancel-btn {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating');
    const ratingText = document.querySelector('.rating-text');
    const reviewText = document.getElementById('review_text');
    const charCount = document.getElementById('charCount');
    
    const ratingTexts = {
        1: 'Poor',
        2: 'Fair', 
        3: 'Good',
        4: 'Very Good',
        5: 'Excellent'
    };
    
    // Initialize rating
    let currentRating = parseInt(ratingInput.value) || 5;
    updateStars(currentRating);
    
    // Star rating functionality
    stars.forEach(star => {
        star.addEventListener('click', function() {
            currentRating = parseInt(this.dataset.rating);
            ratingInput.value = currentRating;
            updateStars(currentRating);
            ratingText.textContent = ratingTexts[currentRating];
        });
        
        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            updateStars(hoverRating);
        });
    });
    
    document.querySelector('.stars').addEventListener('mouseleave', function() {
        updateStars(currentRating);
    });
    
    function updateStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
    
    // Character counter
    reviewText.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count > 1000) {
            charCount.style.color = '#dc3545';
        } else if (count > 900) {
            charCount.style.color = '#ff9800';
        } else {
            charCount.style.color = '#666';
        }
    });
    
    // Initialize character count
    charCount.textContent = reviewText.value.length;
});
</script>
@endsection