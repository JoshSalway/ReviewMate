<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $subscription = $user->subscription();
        $plan = null;

        if ($user->isAdmin()) {
            $plan = 'admin';
        } elseif ($user->subscribed('default')) {
            $plan = $user->subscribedToPrice(config('services.stripe.price_pro')) ? 'pro' : 'starter';
        }

        return Inertia::render('settings/billing', [
            'plan'          => $plan,
            'isAdmin'       => $user->isAdmin(),
            'onFreePlan'    => $user->onFreePlan(),
            'subscription'  => $subscription ? [
                'status'        => $subscription->stripe_status,
                'ends_at'       => $subscription->ends_at?->toDateString(),
            ] : null,
            'prices' => [
                'starter' => config('services.stripe.price_starter'),
                'pro'     => config('services.stripe.price_pro'),
            ],
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate(['price' => 'required|string']);

        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts do not require a subscription.');
        }

        $checkoutUrl = $user->newSubscription('default', $request->input('price'))
            ->checkout([
                'success_url' => route('settings.billing') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('settings.billing'),
            ])
            ->url;

        return redirect($checkoutUrl);
    }

    public function portal(Request $request): RedirectResponse
    {
        return redirect(
            $request->user()->billingPortalUrl(route('settings.billing'))
        );
    }
}
