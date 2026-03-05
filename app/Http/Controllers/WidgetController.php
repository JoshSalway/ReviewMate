<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\JsonResponse;

class WidgetController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('widget_enabled', true)
            ->firstOrFail();

        $minRating = $business->widget_min_rating ?? 4;
        $maxReviews = $business->widget_max_reviews ?? 6;

        $reviews = $business->reviews()
            ->whereNotNull('body')
            ->where('rating', '>=', $minRating)
            ->orderByDesc('reviewed_at')
            ->limit($maxReviews)
            ->get()
            ->map(fn ($review) => [
                'reviewer_name' => $review->reviewer_name ?? 'Anonymous',
                'rating' => $review->rating,
                'body' => $review->body,
                'reviewed_at' => $review->reviewed_at?->diffForHumans() ?? '',
                'source' => $review->source,
            ]);

        return response()->json([
            'business' => [
                'name' => $business->name,
                'rating' => (float) ($business->google_rating ?? $business->averageRating()),
                'review_count' => $business->google_review_count ?? $business->reviews()->count(),
            ],
            'reviews' => $reviews,
            'powered_by_url' => 'https://reviewmate.app?ref=widget',
        ]);
    }
}
