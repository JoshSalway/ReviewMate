<?php

namespace App\Jobs;

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\SmsService;
use App\Services\XeroService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessXeroInvoicePaid implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public string $invoiceId,
    ) {}

    public function handle(): void
    {
        $service = new XeroService($this->business);
        $invoice = $service->getInvoice($this->invoiceId);

        if (! $invoice) {
            Log::info("Xero: invoice {$this->invoiceId} not found for business {$this->business->id}");

            return;
        }

        // Only process PAID invoices (Xero fires UPDATE for any invoice change)
        if (($invoice['Status'] ?? '') !== 'PAID') {
            return;
        }

        $contactId = $invoice['Contact']['ContactID'] ?? null;
        if (! $contactId) {
            return;
        }

        $contact = $service->getContact($contactId);
        if (! $contact) {
            return;
        }

        $name = $contact['Name'] ?? 'Customer';
        $email = $contact['EmailAddress'] ?? null;
        $phone = collect($contact['Phones'] ?? [])->firstWhere('PhoneType', 'MOBILE')['PhoneNumber']
            ?? $contact['Phones'][0]['PhoneNumber']
            ?? null;

        if (! $email && ! $phone) {
            Log::info("Xero: contact {$contactId} has no email or phone, skipping");

            return;
        }

        $customer = Customer::firstOrCreate(
            ['business_id' => $this->business->id, 'email' => $email],
            ['name' => $name, 'phone' => $phone],
        );

        if ($customer->isUnsubscribed()) {
            return;
        }

        // Skip if already sent within 90 days
        if (ReviewRequest::hasRecentRequest($this->business->id, $customer->id, 90)) {
            return;
        }

        $channel = ($phone && $email) ? 'both' : ($phone ? 'sms' : 'email');

        $reviewRequest = $customer->reviewRequests()->create([
            'business_id' => $this->business->id,
            'channel' => $channel,
            'source' => 'xero',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        if ($email) {
            Mail::to($email, $name)
                ->queue(new ReviewRequestMail($this->business, $customer, $reviewRequest));
        }

        if ($phone && SmsService::isConfigured()) {
            rescue(fn () => SmsService::make()->sendReviewRequest($this->business, $customer));
        }
    }
}
