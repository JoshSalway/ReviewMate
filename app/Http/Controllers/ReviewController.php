<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ReviewReplyAgent;
use App\Models\Review;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business || ! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.business-type');
        }

        $needsReply = $business->reviews()
            ->whereNotNull('google_review_name')
            ->whereNull('google_reply')
            ->latest('reviewed_at')
            ->paginate(20, ['*'], 'page')
            ->withQueryString()
            ->through(fn ($review) => $this->formatReview($review));

        $replied = $business->reviews()
            ->whereNotNull('google_reply')
            ->latest('google_reply_posted_at')
            ->paginate(20, ['*'], 'repliedPage')
            ->withQueryString()
            ->through(fn ($review) => $this->formatReview($review));

        $allReviews = $business->reviews()
            ->whereNull('google_review_name')
            ->latest('reviewed_at')
            ->paginate(20, ['*'], 'allPage')
            ->withQueryString()
            ->through(fn ($review) => $this->formatReview($review));

        return Inertia::render('reviews/index', [
            'needsReply' => $needsReply,
            'replied' => $replied,
            'allReviews' => $allReviews,
            'isGoogleConnected' => $business->isGoogleConnected(),
        ]);
    }

    public function show(Request $request, Review $review): Response
    {
        $business = $request->user()->currentBusiness();
        abort_unless($review->business_id === $business?->id, 403);

        $templates = $business->replyTemplates()->latest()->get(['id', 'name', 'body']);

        return Inertia::render('reviews/show', [
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'reviewer_name' => $review->reviewer_name ?? $review->customer?->name ?? 'Anonymous',
                'reviewed_at' => $review->reviewed_at?->toISOString(),
                'via_review_mate' => $review->wasViaReviewMate(),
                'has_google_link' => $review->google_review_name !== null,
                'google_reply' => $review->google_reply,
            ],
            'replyTemplates' => $templates,
        ]);
    }

    public function postReply(Request $request, Review $review): RedirectResponse
    {
        $request->validate(['reply' => 'required|string|max:4096']);

        $business = $request->user()->currentBusiness();
        abort_unless($review->business_id === $business?->id, 403);
        abort_unless($review->google_review_name, 422, 'This review is not linked to Google.');

        app(GoogleBusinessProfileService::class)->postReply(
            $business,
            $review->google_review_name,
            $request->input('reply')
        );

        $review->update([
            'google_reply' => $request->input('reply'),
            'google_reply_posted_at' => now(),
        ]);

        return back()->with('success', 'Reply posted to Google.');
    }

    public function replySuggestions(Request $request, Review $review): JsonResponse
    {
        $business = $request->user()->currentBusiness();
        abort_unless($review->business_id === $business?->id, 403);
        abort_unless($review->body, 422, 'Review has no text to reply to.');

        $agent = new ReviewReplyAgent(
            businessName: $business->name,
            businessType: $business->type,
            ownerName: $business->owner_name ?? $request->user()->name,
        );

        $response = $agent->prompt(
            "Generate 3 reply options for this {$review->rating}-star Google review:\n\n\"{$review->body}\""
        );

        $suggestions = json_decode($response->text, true) ?? [];

        return response()->json(['suggestions' => $suggestions]);
    }

    private function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'body' => $review->body,
            'reviewer_name' => $review->reviewer_name ?? $review->customer?->name ?? 'Anonymous',
            'reviewed_at' => $review->reviewed_at?->toISOString(),
            'via_review_mate' => $review->wasViaReviewMate(),
            'google_reply' => $review->google_reply,
            'google_reply_posted_at' => $review->google_reply_posted_at?->toISOString(),
            'has_google_link' => $review->google_review_name !== null,
        ];
    }
}
