<?php

use App\Jobs\IssueReferralReward;
use App\Models\Business;
use App\Models\Referral;
use App\Models\User;

// ── IssueReferralReward edge-case tests ─────────────────────────────────────
//
// The happy-path tests (single reward, extending existing trial) live in
// ReferralTest.php. This file covers edge cases noted in NEXT_STEPS.md:
//
//  1. Already-rewarded referral — calling handle() twice keeps the referral
//     in a consistent state and does not throw.
//  2. Already-converted referral — handle() on a "converted" referral still
//     completes without error (idempotent write).
//  3. Referral where referrer has an existing future trial — trial is extended
//     by at least ~1 month beyond the pre-existing end date.
//  4. Fresh trial — referral reward starts a new 1-month trial from now.

test('issue referral reward can be called twice without throwing', function () {
    $referrerUser = User::factory()->create(['trial_ends_at' => null]);
    $referrerBusiness = Business::factory()->onboarded()->create(['user_id' => $referrerUser->id]);
    $referredUser = User::factory()->create();

    $referral = Referral::create([
        'referrer_business_id' => $referrerBusiness->id,
        'referral_token' => 'idempotent-token-001',
        'referral_type' => 'business',
        'status' => 'signed_up',
    ]);

    $job = new IssueReferralReward($referral, $referredUser);

    // First call — happy path
    $job->handle();

    $referrerUser->refresh();
    expect($referrerUser->trial_ends_at)->not->toBeNull();
    expect($referrerUser->trial_ends_at->isFuture())->toBeTrue();

    // Second call — should not throw even though status is already 'converted'
    expect(fn () => $job->handle())->not->toThrow(Throwable::class);

    // Final state: referral still marked converted, trial still in the future
    $referral->refresh();
    expect($referral->status)->toBe('converted');
    expect($referral->reward_issued_at)->not->toBeNull();

    $referrerUser->refresh();
    expect($referrerUser->trial_ends_at->isFuture())->toBeTrue();
});

test('issue referral reward on already-converted referral is idempotent', function () {
    $referrerUser = User::factory()->create(['trial_ends_at' => now()->addMonth()]);
    $referrerBusiness = Business::factory()->onboarded()->create(['user_id' => $referrerUser->id]);
    $referredUser = User::factory()->create();

    $referral = Referral::create([
        'referrer_business_id' => $referrerBusiness->id,
        'referral_token' => 'already-converted-002',
        'referral_type' => 'business',
        'status' => 'converted',
        'converted_at' => now()->subDay(),
        'reward_issued_at' => now()->subDay(),
    ]);

    // Calling handle on an already-converted referral should not throw
    expect(fn () => (new IssueReferralReward($referral, $referredUser))->handle())
        ->not->toThrow(Throwable::class);

    $referral->refresh();
    // Still converted
    expect($referral->status)->toBe('converted');
});

test('issue referral reward extends existing future trial by approximately one month', function () {
    // Referrer user has 10 days remaining on their trial.
    $referrerUser = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    $referrerBusiness = Business::factory()->onboarded()->create(['user_id' => $referrerUser->id]);
    $referredUser = User::factory()->create();

    // Snapshot the original end date before the job runs
    $originalEnd = $referrerUser->trial_ends_at->copy();

    $referral = Referral::create([
        'referrer_business_id' => $referrerBusiness->id,
        'referral_token' => 'extend-future-003',
        'referral_type' => 'business',
        'status' => 'signed_up',
    ]);

    (new IssueReferralReward($referral, $referredUser))->handle();

    $referrerUser->refresh();

    // Trial must be in the future
    expect($referrerUser->trial_ends_at->isFuture())->toBeTrue();

    // New end date must be after the original end date (was extended, not reset)
    expect($referrerUser->trial_ends_at->isAfter($originalEnd))->toBeTrue();

    // Extended by at least 25 days beyond the original end (generous lower bound for "1 month")
    $daysAdded = $originalEnd->diffInDays($referrerUser->trial_ends_at, true);
    expect($daysAdded)->toBeGreaterThanOrEqual(25);
});

test('issue referral reward starts fresh one-month trial when referrer has no existing trial', function () {
    $referrerUser = User::factory()->create(['trial_ends_at' => null]);
    $referrerBusiness = Business::factory()->onboarded()->create(['user_id' => $referrerUser->id]);
    $referredUser = User::factory()->create();

    $referral = Referral::create([
        'referrer_business_id' => $referrerBusiness->id,
        'referral_token' => 'fresh-trial-004',
        'referral_type' => 'business',
        'status' => 'signed_up',
    ]);

    (new IssueReferralReward($referral, $referredUser))->handle();

    $referrerUser->refresh();
    expect($referrerUser->trial_ends_at)->not->toBeNull();
    expect($referrerUser->trial_ends_at->isFuture())->toBeTrue();

    // Should be ~1 month from now — at least 25 days ahead
    $daysFromNow = now()->diffInDays($referrerUser->trial_ends_at, false);
    expect($daysFromNow)->toBeGreaterThanOrEqual(25);
});
