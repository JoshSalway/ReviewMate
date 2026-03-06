<?php

use App\Services\ClickSendSmsService;
use App\Services\SmsService;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Http;

test('sms service resolves clicksend by default', function () {
    config(['services.sms.driver' => 'clicksend']);
    expect(SmsService::make())->toBeInstanceOf(ClickSendSmsService::class);
});

test('sms service resolves twilio when configured', function () {
    config(['services.sms.driver' => 'twilio']);
    expect(SmsService::make())->toBeInstanceOf(TwilioSmsService::class);
});

test('clicksend sends sms via rest api', function () {
    Http::fake([
        'rest.clicksend.com/*' => Http::response(['response_code' => 'SUCCESS'], 200),
    ]);

    config([
        'services.clicksend.username' => 'test_user',
        'services.clicksend.api_key' => 'test_key',
        'services.clicksend.from' => 'ReviewMate',
    ]);

    $service = new ClickSendSmsService;
    $service->send('+61400000000', 'Test message');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'rest.clicksend.com')
            && $request->data()['messages'][0]['to'] === '+61400000000'
            && $request->data()['messages'][0]['body'] === 'Test message';
    });
});

test('clicksend is configured when credentials present', function () {
    config([
        'services.clicksend.username' => 'user',
        'services.clicksend.api_key' => 'key',
    ]);
    expect(ClickSendSmsService::isConfigured())->toBeTrue();
});

test('clicksend is not configured when credentials missing', function () {
    config([
        'services.clicksend.username' => null,
        'services.clicksend.api_key' => null,
    ]);
    expect(ClickSendSmsService::isConfigured())->toBeFalse();
});

test('sms service is not configured when no driver credentials set', function () {
    config([
        'services.sms.driver' => 'clicksend',
        'services.clicksend.username' => null,
        'services.clicksend.api_key' => null,
    ]);
    expect(SmsService::isConfigured())->toBeFalse();
});
