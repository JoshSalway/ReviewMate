<?php

use App\Mail\FollowUpMail;
use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('review request mail is queued when email channel is selected', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    $this->post('/requests', [
        'customer_id' => $customer->id,
        'channel' => 'email',
    ]);

    Mail::assertQueued(ReviewRequestMail::class, fn ($mail) => $mail->hasTo('customer@example.com')
    );
});

test('no mail is queued when channel is sms only', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    $this->post('/requests', [
        'customer_id' => $customer->id,
        'channel' => 'sms',
    ]);

    Mail::assertNothingQueued();
});

test('review request mail uses business template if available', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $this->business->emailTemplates()->create([
        'type' => 'request',
        'subject' => 'Hi {customer_name}, how was your experience?',
        'body' => 'Hi {customer_name}, please review us: {review_link}',
    ]);

    $mail = new ReviewRequestMail($this->business, $customer);

    expect($mail->renderedSubject)->toContain('Jane Doe');
});

test('quick send queues mail when email address provided', function () {
    $this->post('/quick-send', [
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'channel' => 'email',
    ]);

    Mail::assertQueued(ReviewRequestMail::class);
});

test('follow up mail renders customer and business details', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'John Smith',
    ]);

    $mail = new FollowUpMail($this->business, $customer);

    expect($mail->renderedBody)->toBeString();
    expect($mail->renderedSubject)->toBeString();
});
