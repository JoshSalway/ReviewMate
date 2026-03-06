<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessIncomingWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public array $data,
    ) {}

    public function handle(): void
    {
        $email = $this->data['email'] ?? null;
        $phone = $this->data['phone'] ?? null;
        $name = $this->data['name'] ?? 'Customer';

        $customer = Customer::firstOrCreate(
            ['business_id' => $this->business->id, 'email' => $email],
            ['name' => $name, 'phone' => $phone]
        );

        // Update phone if we now have it and didn't before
        if ($phone && ! $customer->phone) {
            $customer->update(['phone' => $phone]);
        }

        // Skip unsubscribed customers
        if ($customer->isUnsubscribed()) {
            return;
        }

        // 90-day dedup
        if ($customer->reviewRequests()->where('created_at', '>=', now()->subDays(90))->exists()) {
            return;
        }

        $channel = $phone ? 'sms' : 'email';

        // Create the review request record first (it generates the tracking token)
        $reviewRequest = ReviewRequest::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'channel' => $channel,
            'status' => 'sent',
            'source' => 'webhook',
            'sent_at' => now(),
        ]);

        // Send via SMS if phone and SMS is configured
        if ($phone && SmsService::isConfigured()) {
            rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $customer));
        }

        // Send via email if email address available
        if ($email) {
            Mail::to($email, $name)
                ->queue(new ReviewRequestMail($this->business, $customer, $reviewRequest));
        }
    }
}
