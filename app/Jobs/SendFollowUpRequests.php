<?php

namespace App\Jobs;

use App\Mail\FollowUpMail;
use App\Models\ReviewRequest;
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
        // Send to requests that are 5-6 days old (1-day window prevents duplicates)
        $windowStart = now()->subDays(6);
        $windowEnd = now()->subDays(5);

        $requests = ReviewRequest::query()
            ->with(['business', 'customer'])
            ->whereIn('status', ['sent', 'opened'])
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->whereNull('followed_up_at')
            ->whereNull('reviewed_at')
            ->get();

        foreach ($requests as $request) {
            $customer = $request->customer;
            $business = $request->business;

            if (! $customer?->email || ! $business) {
                continue;
            }

            Mail::to($customer->email, $customer->name)
                ->queue(new FollowUpMail($business, $customer));

            $request->update(['followed_up_at' => now()]);
        }
    }
}
