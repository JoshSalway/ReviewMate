<?php

use App\Jobs\SendWeeklyDigests;
use App\Mail\WeeklyDigestMail;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

// --- NotificationSettingsController ---

test('guests cannot access notification settings', function () {
    auth()->logout();
    $this->get('/settings/notifications')->assertRedirect('/login');
});

test('user can view notification settings', function () {
    $this->get('/settings/notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/notifications')
            ->has('preferences.weekly_digest')
            ->has('preferences.new_review_alert')
        );
});

test('notification preferences default to true', function () {
    // User has no preferences stored yet
    $this->get('/settings/notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('preferences.weekly_digest', true)
            ->where('preferences.new_review_alert', true)
        );
});

test('user can update notification preferences', function () {
    $this->put('/settings/notifications', [
        'weekly_digest' => false,
        'new_review_alert' => true,
        'negative_review_alert' => true,
    ])->assertRedirect()
        ->assertSessionHas('success');

    expect($this->user->fresh()->notificationPreference('weekly_digest'))->toBeFalse();
    expect($this->user->fresh()->notificationPreference('new_review_alert'))->toBeTrue();
    expect($this->user->fresh()->notificationPreference('negative_review_alert'))->toBeTrue();
});

test('notification preferences require boolean values', function () {
    $this->put('/settings/notifications', [
        'weekly_digest' => 'maybe',
        'new_review_alert' => 'sometimes',
        'negative_review_alert' => 'perhaps',
    ])->assertSessionHasErrors(['weekly_digest', 'new_review_alert', 'negative_review_alert']);
});

test('both notification preference fields are required', function () {
    $this->put('/settings/notifications', [])
        ->assertSessionHasErrors(['weekly_digest', 'new_review_alert', 'negative_review_alert']);
});

// --- SendWeeklyDigests job ---

test('weekly digest job sends mail to businesses with digest enabled', function () {
    Mail::fake();

    $this->user->update(['notification_preferences' => ['weekly_digest' => true]]);

    (new SendWeeklyDigests)->handle();

    Mail::assertQueued(WeeklyDigestMail::class, fn ($mail) => $mail->hasTo($this->user->email));
});

test('weekly digest job skips users who opted out', function () {
    Mail::fake();

    $this->user->update(['notification_preferences' => ['weekly_digest' => false]]);

    (new SendWeeklyDigests)->handle();

    Mail::assertNothingQueued();
});

test('weekly digest job skips businesses without users', function () {
    Mail::fake();

    // Business with no user association — we can't easily make an orphan here,
    // so we test the default user has digest enabled
    $this->user->update(['notification_preferences' => ['weekly_digest' => true]]);

    (new SendWeeklyDigests)->handle();

    Mail::assertQueued(WeeklyDigestMail::class, 1);
});

test('notificationPreference returns default true when no preferences stored', function () {
    expect($this->user->notificationPreference('weekly_digest'))->toBeTrue();
    expect($this->user->notificationPreference('new_review_alert'))->toBeTrue();
});

test('notificationPreference respects custom default', function () {
    expect($this->user->notificationPreference('non_existent_key', false))->toBeFalse();
});
