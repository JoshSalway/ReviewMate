<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\ClinikoService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReviewRequestMail;

class PollClinikoAppointments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Business $business) {}

    public function handle(): void
    {
        if (! $this->business->cliniko_api_key || ! $this->business->cliniko_auto_send_reviews) {
            return;
        }

        $since   = $this->business->cliniko_last_polled_at ?? now()->subDay();
        $service = new ClinikoService($this->business);

        $appointments = $service->getCompletedAppointmentsSince($since);

        foreach ($appointments as $appointment) {
            $patientLinks = $appointment['patient']['links']['self'] ?? null;
            if (! $patientLinks) {
                continue;
            }

            // Extract patient ID from link URL
            preg_match('/patients\/(\d+)/', $patientLinks, $matches);
            $patientId = $matches[1] ?? null;
            if (! $patientId) {
                continue;
            }

            $patient = $service->getPatient($patientId);
            if (! $patient) {
                continue;
            }

            $name  = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
            $email = $patient['email'] ?? null;
            $phone = $patient['patient_phone_numbers'][0]['number'] ?? null;

            if (! $email && ! $phone) {
                Log::info('Cliniko: patient has no email or phone', ['patient_id' => $patientId]);
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
                Log::info('Cliniko: skipping — recent request exists', ['customer_id' => $customer->id]);
                continue;
            }

            $channel = $phone ? 'sms' : 'email';

            // Create the review request record first (it generates the tracking token)
            $reviewRequest = ReviewRequest::create([
                'business_id' => $this->business->id,
                'customer_id' => $customer->id,
                'status'      => 'sent',
                'channel'     => $channel,
                'source'      => 'cliniko',
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

        $this->business->update(['cliniko_last_polled_at' => now()]);
    }
}
