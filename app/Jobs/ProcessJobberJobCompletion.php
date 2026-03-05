<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\JobberService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessJobberJobCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public string $jobId,
    ) {}

    public function handle(): void
    {
        $service = new JobberService($this->business);
        $job     = $service->getJob($this->jobId);

        if (empty($job)) {
            Log::warning('Jobber: job not found', ['jobId' => $this->jobId, 'business_id' => $this->business->id]);
            return;
        }

        // Only act on completed jobs
        if (($job['jobStatus'] ?? '') !== 'COMPLETED') {
            return;
        }

        $client = $job['client'] ?? [];
        $name   = $client['name'] ?? null;
        $email  = $client['email'] ?? null;

        // Find primary phone or fall back to first
        $phones = $client['phones'] ?? [];
        $phone  = null;
        foreach ($phones as $p) {
            if ($p['primary'] ?? false) {
                $phone = $p['number'];
                break;
            }
        }
        if (! $phone && ! empty($phones)) {
            $phone = $phones[0]['number'] ?? null;
        }

        if (! $email && ! $phone) {
            Log::info('Jobber: client has no email or phone', ['jobId' => $this->jobId]);
            return;
        }

        $customer = Customer::firstOrCreate(
            ['business_id' => $this->business->id, 'email' => $email],
            ['name' => $name ?: 'Customer', 'phone' => $phone],
        );

        if ($customer->isUnsubscribed()) {
            return;
        }

        $recentRequest = $customer->reviewRequests()
            ->where('created_at', '>=', now()->subDays(90))
            ->exists();

        if ($recentRequest) {
            Log::info('Jobber: skipping — recent request exists', ['customer_id' => $customer->id]);
            return;
        }

        $channel = $phone ? 'sms' : 'email';

        $reviewRequest = ReviewRequest::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'status'      => 'sent',
            'channel'     => $channel,
            'source'      => 'jobber',
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
