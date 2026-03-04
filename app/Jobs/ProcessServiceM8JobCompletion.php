<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Customer;
use App\Services\ServiceM8Service;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReviewRequestMail;
use App\Models\ReviewRequest;

class ProcessServiceM8JobCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public string $jobUuid,
    ) {}

    public function handle(): void
    {
        $service = new ServiceM8Service($this->business);

        // Fetch job details from ServiceM8
        $job = $service->getJob($this->jobUuid);

        if (empty($job)) {
            Log::warning('ServiceM8: job not found', ['uuid' => $this->jobUuid, 'business_id' => $this->business->id]);
            return;
        }

        // Fetch the job's primary contact
        $jobContact = $service->getJobContact($this->jobUuid);

        if (! $jobContact || empty($jobContact['contact_uuid'])) {
            Log::info('ServiceM8: no contact found for job', ['uuid' => $this->jobUuid]);
            return;
        }

        // Fetch full contact details
        $contact = $service->getContact($jobContact['contact_uuid']);

        $name  = trim(($contact['first'] ?? '') . ' ' . ($contact['last'] ?? ''));
        $email = $contact['email'] ?? null;
        $phone = $contact['mobile'] ?? $contact['phone'] ?? null;

        if (! $email && ! $phone) {
            Log::info('ServiceM8: contact has no email or phone', ['contact_uuid' => $jobContact['contact_uuid']]);
            return;
        }

        // Find or create the customer record
        $customer = Customer::firstOrCreate(
            [
                'business_id' => $this->business->id,
                'email'       => $email,
            ],
            [
                'name'  => $name ?: 'Customer',
                'phone' => $phone,
            ]
        );

        // Skip unsubscribed customers
        if ($customer->isUnsubscribed()) {
            return;
        }

        // Don't spam — skip if a review request was already sent within the last 90 days
        $recentRequest = $customer->reviewRequests()
            ->where('created_at', '>=', now()->subDays(90))
            ->exists();

        if ($recentRequest) {
            Log::info('ServiceM8: skipping — recent request exists', ['customer_id' => $customer->id]);
            return;
        }

        // Determine channel: prefer SMS if phone available, fall back to email
        $channel = $phone ? 'sms' : 'email';

        // Create the review request record first (it generates the tracking token)
        $reviewRequest = ReviewRequest::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'status'      => 'sent',
            'channel'     => $channel,
            'source'      => 'servicem8',
            'sent_at'     => now(),
        ]);

        // Send via SMS if phone and SMS is configured
        if ($phone && SmsService::isConfigured()) {
            rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $customer));
        }

        // Send via email if email address available
        if ($email) {
            Mail::to($email, $name ?: 'Customer')
                ->queue(new ReviewRequestMail($this->business, $customer, $reviewRequest));
        }
    }
}
