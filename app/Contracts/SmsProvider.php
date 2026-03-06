<?php

namespace App\Contracts;

use App\Models\Business;
use App\Models\Customer;

interface SmsProvider
{
    public function send(string $to, string $message): void;

    public function sendReviewRequest(Business $business, Customer $customer): void;

    public function sendFollowUp(Business $business, Customer $customer): void;

    public static function isConfigured(): bool;
}
