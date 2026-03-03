<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business || ! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.business-type');
        }

        $stats = [
            'average_rating'  => $business->averageRating(),
            'total_reviews'   => $business->reviews()->count(),
            'requests_sent'   => $business->reviewRequests()->count(),
            'conversion_rate' => $business->conversionRate(),
            'pending_replies' => $business->reviews()
                ->whereNotNull('google_review_name')
                ->whereNull('google_reply')
                ->count(),
            'reviews_this_month' => $business->reviews()
                ->whereMonth('reviewed_at', now()->month)
                ->whereYear('reviewed_at', now()->year)
                ->count(),
            'requests_this_month' => $business->reviewRequests()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        $requestStats = [
            'sent' => $business->reviewRequests()->count(),
            'opened' => $business->reviewRequests()->where('status', 'opened')->count(),
            'reviewed' => $business->reviewRequests()->where('status', 'reviewed')->count(),
        ];

        $recentReviews = $business->reviews()
            ->with('customer')
            ->latest('reviewed_at')
            ->limit(5)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'reviewer_name' => $review->reviewer_name ?? $review->customer?->name ?? 'Anonymous',
                'reviewed_at' => $review->reviewed_at?->diffForHumans(),
                'via_review_mate' => $review->wasViaReviewMate(),
            ]);

        // Monthly review counts for the last 6 months for the chart
        $chartData = collect(range(5, 0))->map(function ($monthsAgo) use ($business) {
            $date = now()->subMonths($monthsAgo);

            return [
                'month' => $date->format('M'),
                'reviews' => $business->reviews()
                    ->whereYear('reviewed_at', $date->year)
                    ->whereMonth('reviewed_at', $date->month)
                    ->count(),
                'requests' => $business->reviewRequests()
                    ->whereYear('sent_at', $date->year)
                    ->whereMonth('sent_at', $date->month)
                    ->count(),
            ];
        })->values()->toArray();

        return Inertia::render('dashboard', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type,
                'google_review_url' => $business->googleReviewUrl(),
            ],
            'stats' => $stats,
            'requestStats' => $requestStats,
            'recentReviews' => $recentReviews,
            'chartData' => $chartData,
            'hasData' => $business->reviews()->exists() || $business->reviewRequests()->exists(),
        ]);
    }
}
