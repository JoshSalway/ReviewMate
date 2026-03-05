<?php

namespace App\Jobs;

use App\Mail\FollowUpMail;
use App\Models\ReviewRequest;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFollowUpRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $requests = ReviewRequest::query()
            ->with(['business.user', 'customer'])
            ->whereIn('status', ['sent', 'opened'])
            ->whereNull('followed_up_at')
            ->whereNull('reviewed_at')
            ->get();

        foreach ($requests as $request) {
            $customer = $request->customer;
            $business = $request->business;

            if (! $customer || ! $business) {
                continue;
            }

            // Skip if follow-up is disabled for this business
            if (! ($business->follow_up_enabled ?? true)) {
                continue;
            }

            // Check if the request is old enough based on per-business follow_up_days
            $followUpDays = $business->follow_up_days ?? 3;
            if ($request->sent_at === null || $request->sent_at->gt(now()->subDays($followUpDays))) {
                continue;
            }

            if ($customer->isUnsubscribed()) {
                continue;
            }

            // Determine which channel(s) to use
            $followUpChannel = $business->follow_up_channel ?? 'same';
            $originalChannel = $request->channel;

            $useEmail = match ($followUpChannel) {
                'email' => true,
                'sms'   => false,
                default => in_array($originalChannel, ['email', 'both']),
            };

            $useSms = match ($followUpChannel) {
                'sms'   => true,
                'email' => false,
                default => in_array($originalChannel, ['sms', 'both']),
            };

            if ($useEmail && $customer->email) {
                Mail::to($customer->email, $customer->name)
                    ->queue(new FollowUpMail($business, $customer, $request));
            }

            if ($useSms && $customer->phone && SmsService::isConfigured()) {
                rescue(fn () => SmsService::make()->sendFollowUp($business, $customer));
            }

            $request->update([
                'followed_up_at' => now(),
                'status'         => 'followed_up',
            ]);
        }
    }
}
