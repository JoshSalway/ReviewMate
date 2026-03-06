<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use App\Services\ClickSendSmsService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
        'google_place_id' => 'ChIJtest1234',
    ]);
    $this->actingAs($this->user);
});

// --- Business model helpers ---

test('facebookReviewUrl returns correct URL when page URL is set', function () {
    $this->business->facebook_page_url = 'https://www.facebook.com/mybusiness';

    expect($this->business->facebookReviewUrl())
        ->toBe('https://www.facebook.com/mybusiness/reviews');
});

test('facebookReviewUrl strips trailing slash before appending /reviews', function () {
    $this->business->facebook_page_url = 'https://www.facebook.com/mybusiness/';

    expect($this->business->facebookReviewUrl())
        ->toBe('https://www.facebook.com/mybusiness/reviews');
});

test('facebookReviewUrl returns null when page URL is not set', function () {
    $this->business->facebook_page_url = null;

    expect($this->business->facebookReviewUrl())->toBeNull();
});

test('hasFacebookReviews returns true when page URL is set', function () {
    $this->business->facebook_page_url = 'https://www.facebook.com/mybusiness';

    expect($this->business->hasFacebookReviews())->toBeTrue();
});

test('hasFacebookReviews returns false when page URL is not set', function () {
    $this->business->facebook_page_url = null;

    expect($this->business->hasFacebookReviews())->toBeFalse();
});

// --- ClickSend SMS includes Facebook link ---

test('clicksend sms includes facebook link when facebook_page_url set', function () {
    Http::fake([
        'rest.clicksend.com/*' => Http::response(['response_code' => 'SUCCESS'], 200),
    ]);

    config([
        'services.clicksend.username' => 'test_user',
        'services.clicksend.api_key' => 'test_key',
        'services.clicksend.from' => 'ReviewMate',
    ]);

    $this->business->facebook_page_url = 'https://www.facebook.com/mybusiness';
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane',
        'phone' => '+61400000000',
    ]);

    $service = new ClickSendSmsService;
    $service->sendReviewRequest($this->business, $customer);

    Http::assertSent(function ($request) {
        $body = $request->data()['messages'][0]['body'];

        return str_contains($body, 'Facebook: https://www.facebook.com/mybusiness/reviews')
            && str_contains($body, 'Google:');
    });
});

test('clicksend sms excludes facebook link when facebook_page_url not set', function () {
    Http::fake([
        'rest.clicksend.com/*' => Http::response(['response_code' => 'SUCCESS'], 200),
    ]);

    config([
        'services.clicksend.username' => 'test_user',
        'services.clicksend.api_key' => 'test_key',
        'services.clicksend.from' => 'ReviewMate',
    ]);

    $this->business->facebook_page_url = null;
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane',
        'phone' => '+61400000000',
    ]);

    $service = new ClickSendSmsService;
    $service->sendReviewRequest($this->business, $customer);

    Http::assertSent(function ($request) {
        $body = $request->data()['messages'][0]['body'];

        return ! str_contains($body, 'Facebook')
            && str_contains($body, 'Google review');
    });
});

// --- ClickSend follow-up includes Facebook link ---

test('clicksend follow-up includes facebook link when facebook_page_url set', function () {
    Http::fake([
        'rest.clicksend.com/*' => Http::response(['response_code' => 'SUCCESS'], 200),
    ]);

    config([
        'services.clicksend.username' => 'test_user',
        'services.clicksend.api_key' => 'test_key',
        'services.clicksend.from' => 'ReviewMate',
    ]);

    $this->business->facebook_page_url = 'https://www.facebook.com/mybusiness';
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane',
        'phone' => '+61400000000',
    ]);

    $service = new ClickSendSmsService;
    $service->sendFollowUp($this->business, $customer);

    Http::assertSent(function ($request) {
        $body = $request->data()['messages'][0]['body'];

        return str_contains($body, 'Facebook:');
    });
});

// --- Business settings: facebook_page_url is saved ---

test('facebook page url can be saved via business settings', function () {
    $response = $this->put('/settings/business', [
        'name' => $this->business->name,
        'type' => $this->business->type,
        'google_place_id' => $this->business->google_place_id,
        'owner_name' => $this->business->owner_name,
        'phone' => $this->business->phone,
        'facebook_page_url' => 'https://www.facebook.com/mybusiness',
    ]);

    $response->assertRedirect();

    $this->business->refresh();
    expect($this->business->facebook_page_url)->toBe('https://www.facebook.com/mybusiness');
});

test('facebook page url must be a valid url', function () {
    $response = $this->put('/settings/business', [
        'name' => $this->business->name,
        'type' => $this->business->type,
        'google_place_id' => $this->business->google_place_id,
        'owner_name' => $this->business->owner_name,
        'phone' => $this->business->phone,
        'facebook_page_url' => 'not-a-url',
    ]);

    $response->assertSessionHasErrors('facebook_page_url');
});

test('facebook page url is optional and can be null', function () {
    $response = $this->put('/settings/business', [
        'name' => $this->business->name,
        'type' => $this->business->type,
        'google_place_id' => $this->business->google_place_id,
        'owner_name' => $this->business->owner_name,
        'phone' => $this->business->phone,
        'facebook_page_url' => '',
    ]);

    $response->assertRedirect();

    $this->business->refresh();
    expect($this->business->facebook_page_url)->toBeNull();
});
