<?php

namespace App\Http\Controllers;

use App\Mail\ReviewRequestMail;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\TwilioSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReviewRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $stats = [
            'sent' => $business->reviewRequests()->count(),
            'opened' => $business->reviewRequests()->where('status', 'opened')->count(),
            'reviewed' => $business->reviewRequests()->where('status', 'reviewed')->count(),
            'no_response' => $business->reviewRequests()->where('status', 'no_response')->count(),
        ];

        $requests = $business->reviewRequests()
            ->with('customer')
            ->latest()
            ->paginate(20)
            ->through(fn ($req) => [
                'id' => $req->id,
                'customer_name' => $req->customer->name,
                'customer_email' => $req->customer->email,
                'customer_initials' => $req->customer->initials(),
                'status' => $req->status,
                'channel' => $req->channel,
                'sent_at' => $req->sent_at?->diffForHumans(),
                'opened_at' => $req->opened_at?->diffForHumans(),
                'reviewed_at' => $req->reviewed_at?->diffForHumans(),
            ]);

        return Inertia::render('requests/index', [
            'stats' => $stats,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Enforce 10 requests/month limit on free plan
        if ($user->onFreePlan()) {
            $monthlyCount = $user->currentBusiness()
                ?->reviewRequests()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count() ?? 0;

            if ($monthlyCount >= 10) {
                return back()->with('error', 'Free plan allows 10 review requests per month. Upgrade for unlimited.');
            }
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'channel' => ['required', 'in:email,sms,both'],
        ]);

        $business = $user->currentBusiness();

        $customer = $business->customers()->findOrFail($validated['customer_id']);

        $reviewRequest = ReviewRequest::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'channel' => $validated['channel'],
            'sent_at' => now(),
        ]);

        if (in_array($validated['channel'], ['email', 'both']) && $customer->email) {
            Mail::to($customer->email, $customer->name)
                ->queue(new ReviewRequestMail($business, $customer, $reviewRequest));
        }

        if (in_array($validated['channel'], ['sms', 'both']) && $customer->phone && TwilioSmsService::isConfigured()) {
            rescue(fn () => app(TwilioSmsService::class)->sendReviewRequest($business, $customer));
        }

        return back()->with('success', "Review request sent to {$customer->name}.");
    }

    public function track(string $token): RedirectResponse
    {
        $reviewRequest = ReviewRequest::where('tracking_token', $token)->firstOrFail();

        if ($reviewRequest->status === 'sent') {
            $reviewRequest->markAsOpened();
        }

        $destination = $reviewRequest->business->googleReviewUrl()
            ?? 'https://search.google.com/local/writereview';

        return redirect()->away($destination);
    }
}
