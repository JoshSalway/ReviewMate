<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * List reviews for a business.
     */
    public function index(Request $request, Business $business): AnonymousResourceCollection|JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $reviews = $business->reviews()
            ->latest()
            ->paginate(50);

        return ReviewResource::collection($reviews);
    }

    /**
     * Get review stats for a business.
     */
    public function stats(Request $request, Business $business): JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $totalReviews = $business->reviews()->count();
        $averageRating = $business->averageRating();
        $conversionRate = $business->conversionRate();
        $totalRequests = $business->reviewRequests()->count();
        $pendingReplies = $business->reviews()
            ->whereNotNull('google_review_id')
            ->whereNull('google_reply')
            ->count();

        return response()->json([
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'conversion_rate' => $conversionRate,
            'total_requests' => $totalRequests,
            'pending_replies' => $pendingReplies,
            'google_rating' => $business->integration('google')?->getMeta('rating'),
            'google_review_count' => $business->integration('google')?->getMeta('review_count'),
        ]);
    }
}
