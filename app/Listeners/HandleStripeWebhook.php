<?php

namespace App\Listeners;

use App\Jobs\IssueReferralReward;
use App\Mail\SubscriptionCancelledMail;
use App\Mail\SubscriptionConfirmedMail;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events\WebhookHandled;

class HandleStripeWebhook
{
    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? null;

        match ($type) {
            'customer.subscription.created' => $this->onSubscriptionCreated($event->payload),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($event->payload),
            default => null,
        };

        // Check for pending referral reward whenever a subscription event occurs
        if ($type === 'customer.subscription.created') {
            $this->checkReferralReward($event->payload);
        }
    }

    protected function onSubscriptionCreated(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeCustomerId) {
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning("Stripe subscription.created: no user found for customer {$stripeCustomerId}");

            return;
        }

        Mail::to($user->email, $user->name)->queue(new SubscriptionConfirmedMail($user));
    }

    protected function checkReferralReward(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeCustomerId) {
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user || ! $user->referral_id) {
            return;
        }

        $referral = Referral::find($user->referral_id);

        if (! $referral || $referral->status === 'converted') {
            return;
        }

        IssueReferralReward::dispatch($referral, $user);
    }

    protected function onSubscriptionDeleted(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeCustomerId) {
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning("Stripe subscription.deleted: no user found for customer {$stripeCustomerId}");

            return;
        }

        Mail::to($user->email, $user->name)->queue(new SubscriptionCancelledMail($user));
    }
}
