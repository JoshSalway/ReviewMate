<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use Twilio\Rest\Client;

class TwilioSmsService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token'),
        );
    }

    public static function isConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.from'));
    }

    public function send(string $to, string $message): void
    {
        $this->client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message,
        ]);
    }

    public function sendReviewRequest(Business $business, Customer $customer): void
    {
        if (! $customer->phone) {
            return;
        }

        $url = $business->googleReviewUrl();
        $message = "Hi {$customer->name}, {$business->name} would love a Google review! "
            ."It only takes a minute: {$url}";

        $this->send($customer->phone, $message);
    }

    public function sendFollowUp(Business $business, Customer $customer): void
    {
        if (! $customer->phone) {
            return;
        }

        $url = $business->googleReviewUrl();
        $message = "Hi {$customer->name}, just a friendly reminder from {$business->name} — "
            ."we'd really appreciate your Google review: {$url}";

        $this->send($customer->phone, $message);
    }
}
