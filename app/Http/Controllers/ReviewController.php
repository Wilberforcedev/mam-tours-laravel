<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::approved()->latest()->paginate(10);
        return view('reviews.index', compact('reviews'));
    }

    public function create()
    {
        return view('reviews.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|min:10|max:1000'
        ]);

        Review::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'email' => $request->email,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'is_approved' => false
        ]);

        return redirect()->back()->with('success', 'Thank you for your review! It will be published after approval.');
    }

    public function adminIndex()
    {
        $reviews = Review::with('user')->latest()->paginate(15);
        return view('reviews.admin', compact('reviews'));
    }

    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);
        
        return response()->json(['success' => true, 'message' => 'Review approved successfully']);
    }

    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        
        return response()->json(['success' => true, 'message' => 'Review rejected and deleted']);
    }

    public function getApprovedReviews()
    {
        $reviews = Review::approved()->latest()->take(6)->get();
        return response()->json($reviews);
    }
}