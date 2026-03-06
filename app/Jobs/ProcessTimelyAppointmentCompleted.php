<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\SmsService;
use App\Services\TimelyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessTimelyAppointmentCompleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public array $appointmentData,
    ) {}

    public function handle(): void
    {
        // Extract client info from webhook payload
        $client = $this->appointmentData['client'] ?? null;

        if (! $client) {
            // Fall back to fetching from API
            $clientId = $this->appointmentData['client_id'] ?? null;
            $accountId = $this->business->integration('timely')?->getMeta('account_id');

            if (! $clientId || ! $accountId) {
                Log::info('Timely: missing client_id or account_id in webhook payload');

                return;
            }

            $service = new TimelyService($this->business);
            $client = $service->getClient((int) $accountId, (int) $clientId);
        }

        if (! $client) {
            Log::info('Timely: could not fetch client data');

            return;
        }

        $name = trim(($client['first_name'] ?? '').' '.($client['last_name'] ?? ''));
        $email = $client['email'] ?? null;
        $phone = $client['mobile_phone'] ?? $client['phone'] ?? null;

        if (! $email && ! $phone) {
            Log::info('Timely: client has no email or phone');

            return;
        }

        $customer = Customer::firstOrCreate(
            ['business_id' => $this->business->id, 'email' => $email],
            ['name' => $name ?: 'Client', 'phone' => $phone]
        );

        // Skip unsubscribed customers
        if ($customer->isUnsubscribed()) {
            return;
        }

        // Skip if already sent within 90 days
        if ($customer->reviewRequests()->where('created_at', '>=', now()->subDays(90))->exists()) {
            Log::info('Timely: skipping — recent request exists', ['customer_id' => $customer->id]);

            return;
        }

        $channel = $phone ? 'sms' : 'email';

        // Create the review request record first (it generates the tracking token)
        $reviewRequest = ReviewRequest::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'channel' => $channel,
            'source' => 'timely',
            'sent_at' => now(),
        ]);

        // Send via SMS if phone and SMS is configured
        if ($phone && SmsService::isConfigured()) {
            rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $customer));
        }

        // Send via email if email address available
        if ($email) {
            Mail::to($email, $name ?: 'Client')
                ->queue(new ReviewRequestMail($this->business, $customer, $reviewRequest));
        }
    }
}
