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

class QuickSendController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $recentlySent = $business->reviewRequests()
            ->with('customer')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($req) => [
                'id' => $req->id,
                'customer_name' => $req->customer->name,
                'customer_email' => $req->customer->email,
                'status' => $req->status,
                'sent_at' => $req->sent_at?->diffForHumans(),
            ]);

        return Inertia::render('quick-send', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'recentlySent' => $recentlySent,
        ]);
    }

    public function send(Request $request): RedirectResponse
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'channel' => ['required', 'in:email,sms,both'],
        ]);

        $business = $user->currentBusiness();

        $customer = $business->customers()->firstOrCreate(
            ['email' => $validated['email'] ?? null, 'phone' => $validated['phone'] ?? null],
            ['name' => $validated['name']]
        );

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

        return back()->with('success', "Request sent to {$customer->name}!");
    }
}
