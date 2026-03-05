<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\HalaxyService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PollHalaxyAppointments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Business $business) {}

    public function handle(): void
    {
        $integration = $this->business->integration('halaxy');

        if (! $integration?->api_key || ! $integration?->auto_send_reviews) {
            return;
        }

        $since   = $integration->last_polled_at ?? now()->subDay();
        $service = new HalaxyService($this->business);

        $appointments = $service->getCompletedAppointmentsSince($since);

        foreach ($appointments as $appointment) {
            $patientId = $appointment['patient_id'] ?? null;

            if (! $patientId) {
                continue;
            }

            $patient = $service->getPatient((string) $patientId);

            if (! $patient) {
                continue;
            }

            $name  = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
            $email = $patient['email'] ?? null;
            $phone = $patient['mobile'] ?? null;

            if (! $email && ! $phone) {
                Log::info('Halaxy: patient has no email or phone', ['patient_id' => $patientId]);
                continue;
            }

            $customer = Customer::firstOrCreate(
                ['business_id' => $this->business->id, 'email' => $email],
                ['name' => $name ?: 'Patient', 'phone' => $phone]
            );

            // Skip unsubscribed customers
            if ($customer->isUnsubscribed()) {
                continue;
            }

            // Skip if already sent within 90 days
            if ($customer->reviewRequests()->where('created_at', '>=', now()->subDays(90))->exists()) {
                Log::info('Halaxy: skipping — recent request exists', ['customer_id' => $customer->id]);
                continue;
            }

            $channel = $phone ? 'sms' : 'email';

            // Create the review request record first (it generates the tracking token)
            $reviewRequest = ReviewRequest::create([
                'business_id' => $this->business->id,
                'customer_id' => $customer->id,
                'status'      => 'sent',
                'channel'     => $channel,
                'source'      => 'halaxy',
                'sent_at'     => now(),
            ]);

            // Send via SMS if phone and SMS is configured
            if ($phone && SmsService::isConfigured()) {
                rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $customer));
            }

            // Send via email if email address available
            if ($email) {
                Mail::to($email, $name ?: 'Patient')
                    ->queue(new ReviewRequestMail($this->business, $customer, $reviewRequest));
            }
        }

        $integration->update(['last_polled_at' => now()]);
    }
}
