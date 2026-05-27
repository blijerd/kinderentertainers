<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function create(string $token): View
    {
        $review = Review::query()
            ->with('entertainer')
            ->where('token', $token)
            ->firstOrFail();

        return view('reviews.create', compact('review'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $review = Review::query()
            ->where('token', $token)
            ->firstOrFail();

        abort_if($review->isSubmitted(), 409);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ], attributes: [
            'rating' => 'beoordeling',
            'title' => 'titel',
            'body' => 'review',
        ]);

        $review->update([
            ...$validated,
            'status' => ReviewStatus::Pending,
            'submitted_at' => now(),
            'published_at' => null,
        ]);

        return redirect()->route('reviews.thanks');
    }

    public function thanks(): View
    {
        return view('reviews.thanks');
    }
}
