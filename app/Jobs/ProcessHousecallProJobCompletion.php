<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\HousecallProService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessHousecallProJobCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public array $jobData,
    ) {}

    public function handle(): void
    {
        // HCP webhook payload includes customer data directly — no extra API call needed
        $customer_data = $this->jobData['customer'] ?? null;

        if (! $customer_data) {
            // Fallback: fetch job from API if customer not in payload
            $jobId = $this->jobData['id'] ?? null;
            if (! $jobId) {
                Log::warning('HousecallPro: no job id in payload', ['business_id' => $this->business->id]);
                return;
            }

            $service  = new HousecallProService($this->business);
            $job      = $service->getJob($jobId);
            $customer_data = $job['customer'] ?? null;
        }

        if (! $customer_data) {
            Log::info('HousecallPro: no customer data in job', ['business_id' => $this->business->id]);
            return;
        }

        $firstName = $customer_data['first_name'] ?? '';
        $lastName  = $customer_data['last_name'] ?? '';
        $name      = trim("$firstName $lastName") ?: null;
        $email     = $customer_data['email'] ?? null;
        $phone     = $customer_data['mobile_number'] ?? $customer_data['home_number'] ?? null;

        if (! $email && ! $phone) {
            Log::info('HousecallPro: customer has no email or phone', ['business_id' => $this->business->id]);
            return;
        }

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

        if ($customer->isUnsubscribed()) {
            return;
        }

        $recentRequest = $customer->reviewRequests()
            ->where('created_at', '>=', now()->subDays(90))
            ->exists();

        if ($recentRequest) {
            Log::info('HousecallPro: skipping — recent request exists', ['customer_id' => $customer->id]);
            return;
        }

        $channel = $phone ? 'sms' : 'email';

        $reviewRequest = ReviewRequest::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'status'      => 'sent',
            'channel'     => $channel,
            'source'      => 'housecallpro',
            'sent_at'     => now(),
        ]);

        if ($phone && SmsService::isConfigured()) {
            rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $customer));
        }

        if ($email) {
            Mail::to($email, $name ?: 'Customer')
                ->queue(new ReviewRequestMail($this->business, $customer, $reviewRequest));
        }
    }
}
