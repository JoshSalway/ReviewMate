<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\SimproService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessSimproJobComplete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public int $jobId,
    ) {}

    public function handle(): void
    {
        $service = new SimproService($this->business);

        // Fetch job details from Simpro
        $job = $service->getJob($this->jobId);

        if (empty($job)) {
            Log::warning('Simpro: job not found', ['job_id' => $this->jobId, 'business_id' => $this->business->id]);
            return;
        }

        // Extract customer ID from job
        $customerId = $job['Customer']['ID'] ?? $job['customerId'] ?? null;

        if (! $customerId) {
            Log::info('Simpro: no customer on job', ['job_id' => $this->jobId]);
            return;
        }

        // Fetch customer details
        $customer = $service->getCustomer((int) $customerId);

        if (! $customer) {
            Log::warning('Simpro: customer not found', ['customer_id' => $customerId, 'business_id' => $this->business->id]);
            return;
        }

        $name  = trim(($customer['GivenName'] ?? '') . ' ' . ($customer['FamilyName'] ?? ''));
        $email = $customer['Email'] ?? null;
        $phone = $customer['Mobile'] ?? $customer['Phone'] ?? null;

        if (! $email && ! $phone) {
            Log::info('Simpro: customer has no email or phone', ['customer_id' => $customerId]);
            return;
        }

        // Find or create the customer record
        $reviewCustomer = Customer::firstOrCreate(
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
        if ($reviewCustomer->isUnsubscribed()) {
            return;
        }

        // Don't spam — skip if a review request was already sent within the last 90 days
        $recentRequest = $reviewCustomer->reviewRequests()
            ->where('created_at', '>=', now()->subDays(90))
            ->exists();

        if ($recentRequest) {
            Log::info('Simpro: skipping — recent request exists', ['customer_id' => $reviewCustomer->id]);
            return;
        }

        // Determine channel: prefer SMS if phone available, fall back to email
        $channel = $phone ? 'sms' : 'email';

        // Create the review request record first (it generates the tracking token)
        $reviewRequest = ReviewRequest::create([
            'business_id' => $this->business->id,
            'customer_id' => $reviewCustomer->id,
            'status'      => 'sent',
            'channel'     => $channel,
            'source'      => 'simpro',
            'sent_at'     => now(),
        ]);

        // Send via SMS if phone and SMS is configured
        if ($phone && SmsService::isConfigured()) {
            rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $reviewCustomer));
        }

        // Send via email if email address available
        if ($email) {
            Mail::to($email, $name ?: 'Customer')
                ->queue(new ReviewRequestMail($this->business, $reviewCustomer, $reviewRequest));
        }
    }
}
