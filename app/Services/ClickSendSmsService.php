<?php

namespace App\Services;

use App\Contracts\SmsProvider;
use App\Models\Business;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickSendSmsService implements SmsProvider
{
    public static function isConfigured(): bool
    {
        return filled(config('services.clicksend.username'))
            && filled(config('services.clicksend.api_key'));
    }

    public function send(string $to, string $message): void
    {
        $response = Http::withBasicAuth(
            config('services.clicksend.username'),
            config('services.clicksend.api_key'),
        )->post('https://rest.clicksend.com/v3/sms/send', [
            'messages' => [
                [
                    'to'   => $to,
                    'body' => $message,
                    'from' => config('services.clicksend.from', 'ReviewMate'),
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('ClickSend SMS failed', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }

    public function sendReviewRequest(Business $business, Customer $customer): void
    {
        if (! $customer->phone) {
            return;
        }

        $url     = $business->googleReviewUrl();
        $message = "Hi {$customer->name}, {$business->name} would love a Google review! "
            . "It only takes a minute: {$url}";

        $this->send($customer->phone, $message);
    }

    public function sendFollowUp(Business $business, Customer $customer): void
    {
        if (! $customer->phone) {
            return;
        }

        $url     = $business->googleReviewUrl();
        $message = "Hi {$customer->name}, just a friendly reminder from {$business->name} — "
            . "we'd really appreciate your Google review: {$url}";

        $this->send($customer->phone, $message);
    }
}
