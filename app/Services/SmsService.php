<?php

namespace App\Services;

use App\Contracts\SmsProvider;

class SmsService
{
    public static function make(): SmsProvider
    {
        $driver = config('services.sms.driver', 'clicksend');

        return match ($driver) {
            'twilio' => new TwilioSmsService,
            'clicksend' => new ClickSendSmsService,
            default => new ClickSendSmsService,
        };
    }

    public static function isConfigured(): bool
    {
        $driver = config('services.sms.driver', 'clicksend');

        return match ($driver) {
            'twilio' => TwilioSmsService::isConfigured(),
            'clicksend' => ClickSendSmsService::isConfigured(),
            default => false,
        };
    }
}
