<?php

use App\Jobs\IssueReferralReward;
use App\Listeners\ApplyReferralOnRegistration;
use App\Mail\ReferralInviteMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\BusinessIntegration;
use App\Models\Referral;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Jobs\SyncGoogleReviews;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();

    $this->user = User::factory()->create([
        'notification_preferences' => ['new_review_alert' => false],
    ]);
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
    ]);
    BusinessIntegration::create([
        'business_id'  => $this->business->id,
        'provider'     => 'google',
        'access_token' => 'fake-token',
        'meta'         => ['location_id' => 'accounts/123/locations/456'],
    ]);
});

// ── Track endpoint ─────────────────────────────────────────────────────────

test('track endpoint stores referral token in session and redirects to login', function () {
    $response = $this->get('/r/ref/'.$this->business->referral_token);

    $response->assertRedirect('/login');
    $response->assertSessionHas('referral_token', $this->business->referral_token);
});

test('track endpoint redirects to home for unknown token', function () {
    $this->get('/r/ref/unknown-token-xyz')
        ->assertRedirect('/');
});

// ── Registration hook ──────────────────────────────────────────────────────

test('registration with referral token creates referral record and gives trial', function () {
    $newUser = User::factory()->create(['trial_ends_at' => null, 'referral_id' => null]);

    // Simulate the session having a referral token
    session(['referral_token' => $this->business->referral_token]);

    $listener = new ApplyReferralOnRegistration;
    $listener->handle(new Registered($newUser));

    $newUser->refresh();
    expect($newUser->trial_ends_at)->not->toBeNull();
    expect($newUser->trial_ends_at->isFuture())->toBeTrue();

    expect(Referral::where('referred_business_id', null)->count())->toBeGreaterThan(0);
});

test('registration without referral token does nothing', function () {
    session()->forget('referral_token');

    $newUser = User::factory()->create(['trial_ends_at' => null, 'referral_id' => null]);

    $listener = new ApplyReferralOnRegistration;
    $listener->handle(new Registered($newUser));

    $newUser->refresh();
    expect($newUser->trial_ends_at)->toBeNull();
    expect($newUser->referral_id)->toBeNull();
});

test('registration clears referral token from session', function () {
    session(['referral_token' => $this->business->referral_token]);

    $newUser = User::factory()->create(['trial_ends_at' => null, 'referral_id' => null]);

    $listener = new ApplyReferralOnRegistration;
    $listener->handle(new Registered($newUser));

    expect(session('referral_token'))->toBeNull();
});

// ── Customer referral after review ────────────────────────────────────────

test('sync google reviews sends referral invite to customer who reviewed on pro plan', function () {
    // Make user a pro (non-free) user
    $this->user->update(['trial_ends_at' => now()->addMonth()]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Happy Customer',
        'email' => 'happy@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(3),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([[
        'reviewId' => 'review-happy',
        'name' => 'accounts/123/locations/456/reviews/review-happy',
        'starRating' => 'FIVE',
        'comment' => 'Loved it!',
        'createTime' => now()->toIso8601String(),
        'reviewer' => ['displayName' => 'Happy Customer'],
    ]]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertQueued(ReferralInviteMail::class, fn ($mail) => $mail->hasTo('happy@example.com'));
});

test('sync does not send referral invite on free plan', function () {
    // User is on free plan (no subscription, no trial)
    expect($this->user->onFreePlan())->toBeTrue();

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Free Customer',
        'email' => 'free@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(3),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([[
        'reviewId' => 'review-free',
        'name' => 'accounts/123/locations/456/reviews/review-free',
        'starRating' => 'FIVE',
        'comment' => 'Good!',
        'createTime' => now()->toIso8601String(),
        'reviewer' => ['displayName' => 'Free Customer'],
    ]]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNotQueued(ReferralInviteMail::class);
});

test('sync does not send referral invite to customer without email', function () {
    $this->user->update(['trial_ends_at' => now()->addMonth()]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'No Email Customer',
        'email' => null,
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(3),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([[
        'reviewId' => 'review-noemail',
        'name' => 'accounts/123/locations/456/reviews/review-noemail',
        'starRating' => 'FIVE',
        'comment' => 'Good!',
        'createTime' => now()->toIso8601String(),
        'reviewer' => ['displayName' => 'No Email Customer'],
    ]]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNotQueued(ReferralInviteMail::class);
});

// ── IssueReferralReward job ────────────────────────────────────────────────

test('issue referral reward marks referral as converted and extends referrer trial', function () {
    Queue::fake([]); // don't fake — run synchronously

    $referrerUser = User::factory()->create(['trial_ends_at' => null]);
    $referrerBusiness = Business::factory()->onboarded()->create(['user_id' => $referrerUser->id]);

    $referredUser = User::factory()->create(['trial_ends_at' => null]);

    $referral = Referral::create([
        'referrer_business_id' => $referrerBusiness->id,
        'referral_token' => 'test-token-123',
        'referral_type' => 'business',
        'status' => 'signed_up',
        'signed_up_at' => now()->subDays(7),
    ]);

    (new IssueReferralReward($referral, $referredUser))->handle();

    $referral->refresh();
    expect($referral->status)->toBe('converted');
    expect($referral->converted_at)->not->toBeNull();
    expect($referral->reward_issued_at)->not->toBeNull();

    $referrerUser->refresh();
    expect($referrerUser->trial_ends_at)->not->toBeNull();
    expect($referrerUser->trial_ends_at->isFuture())->toBeTrue();
});

test('issue referral reward extends existing trial by 1 month', function () {
    $existingTrialEnd = now()->addDays(15);
    $referrerUser = User::factory()->create(['trial_ends_at' => $existingTrialEnd]);
    $referrerBusiness = Business::factory()->onboarded()->create(['user_id' => $referrerUser->id]);

    $referredUser = User::factory()->create();

    $referral = Referral::create([
        'referrer_business_id' => $referrerBusiness->id,
        'referral_token' => 'extend-token-456',
        'referral_type' => 'business',
        'status' => 'signed_up',
    ]);

    (new IssueReferralReward($referral, $referredUser))->handle();

    $referrerUser->refresh();
    // Trial should be extended by ~1 month from existing end date
    expect($referrerUser->trial_ends_at->gte($existingTrialEnd->addDays(25)))->toBeTrue();
});

// ── Referral dashboard ─────────────────────────────────────────────────────

test('referrals dashboard is accessible to authenticated users', function () {
    $this->actingAs($this->user)
        ->get('/referrals')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('referrals'));
});

test('referrals dashboard shows referral link', function () {
    $this->actingAs($this->user)
        ->get('/referrals')
        ->assertInertia(fn ($page) => $page
            ->has('referralLink')
            ->where('referralLink', fn ($link) => str_contains((string) $link, $this->business->referral_token))
        );
});

test('referrals dashboard shows correct stats', function () {
    Referral::create([
        'referrer_business_id' => $this->business->id,
        'referral_token' => 'stat-token-1',
        'referral_type' => 'business',
        'status' => 'converted',
        'reward_issued_at' => now(),
    ]);

    Referral::create([
        'referrer_business_id' => $this->business->id,
        'referral_token' => 'stat-token-2',
        'referral_type' => 'business',
        'status' => 'signed_up',
    ]);

    $this->actingAs($this->user)
        ->get('/referrals')
        ->assertInertia(fn ($page) => $page
            ->where('stats.total', 2)
            ->where('stats.signed_up', 2) // converted counts as signed_up too
            ->where('stats.converted', 1)
            ->where('rewardsEarned', 1)
        );
});

test('referrals dashboard is not accessible to guests', function () {
    $this->get('/referrals')->assertRedirect('/login');
});
