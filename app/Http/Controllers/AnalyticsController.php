<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $business = $user->currentBusiness();

        if (! $business || ! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.business-type');
        }

        // Pro and admin users see all their businesses; others see just the current one
        $canSeeAll = $user->isAdmin() || $user->subscribed('default');

        $businesses = $canSeeAll
            ? $user->businesses()->with('reviews', 'reviewRequests')->get()
            : collect([$business->load('reviews', 'reviewRequests')]);

        $rows = $businesses->map(function ($biz) {
            $totalReviews = $biz->reviews()->count();
            $avgRating = round($biz->reviews()->avg('rating') ?? 0, 1);
            $requestsSent = $biz->reviewRequests()->count();
            $reviewedCount = $biz->reviewRequests()->where('status', 'reviewed')->count();
            $conversionRate = $requestsSent > 0
                ? round(($reviewedCount / $requestsSent) * 100, 1)
                : 0;
            $pendingReplies = $biz->reviews()
                ->whereNotNull('google_review_name')
                ->whereNull('google_reply')
                ->count();
            $thisMonthReviews = $biz->reviews()
                ->whereMonth('reviewed_at', now()->month)
                ->whereYear('reviewed_at', now()->year)
                ->count();

            return [
                'id' => $biz->id,
                'name' => $biz->name,
                'type' => $biz->type,
                'total_reviews' => $totalReviews,
                'avg_rating' => $avgRating,
                'requests_sent' => $requestsSent,
                'conversion_rate' => $conversionRate,
                'pending_replies' => $pendingReplies,
                'reviews_this_month' => $thisMonthReviews,
            ];
        });

        $totals = [
            'total_reviews' => $rows->sum('total_reviews'),
            'avg_rating' => $rows->avg('avg_rating') ? round($rows->avg('avg_rating'), 1) : 0,
            'requests_sent' => $rows->sum('requests_sent'),
            'conversion_rate' => $rows->avg('conversion_rate') ? round($rows->avg('conversion_rate'), 1) : 0,
            'pending_replies' => $rows->sum('pending_replies'),
            'reviews_this_month' => $rows->sum('reviews_this_month'),
        ];

        return Inertia::render('analytics', [
            'businesses' => $rows,
            'totals' => $totals,
            'can_see_all' => $canSeeAll,
        ]);
    }
}
