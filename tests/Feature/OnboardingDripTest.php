<?php

use App\Jobs\SendOnboardingEmail;
use App\Listeners\SendOnboardingSequence;
use App\Mail\Onboarding\OnboardingDay14Mail;
use App\Mail\Onboarding\OnboardingDay30Mail;
use App\Mail\Onboarding\OnboardingDay3Mail;
use App\Mail\Onboarding\OnboardingDay7Mail;
use App\Mail\Onboarding\OnboardingWelcomeMail;
use App\Models\Business;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

test('registered listener dispatches all five onboarding jobs', function () {
    Queue::fake();

    $user = User::factory()->create();
    $listener = new SendOnboardingSequence;
    $listener->handle(new Registered($user));

    Queue::assertPushed(SendOnboardingEmail::class, 5);
});

test('registered listener schedules jobs with correct steps', function () {
    Queue::fake();

    $user = User::factory()->create();
    $listener = new SendOnboardingSequence;
    $listener->handle(new Registered($user));

    Queue::assertPushed(fn (SendOnboardingEmail $job) => $job->step === 'welcome' && $job->userId === $user->id);
    Queue::assertPushed(fn (SendOnboardingEmail $job) => $job->step === 'day3');
    Queue::assertPushed(fn (SendOnboardingEmail $job) => $job->step === 'day7');
    Queue::assertPushed(fn (SendOnboardingEmail $job) => $job->step === 'day14');
    Queue::assertPushed(fn (SendOnboardingEmail $job) => $job->step === 'day30');
});

test('send onboarding email job sends welcome mail', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    (new SendOnboardingEmail($user->id, 'welcome'))->handle();

    Mail::assertSent(OnboardingWelcomeMail::class, fn ($mail) => $mail->hasTo('test@example.com'));
});

test('send onboarding email job sends day7 mail', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    (new SendOnboardingEmail($user->id, 'day7'))->handle();

    Mail::assertSent(OnboardingDay7Mail::class, fn ($mail) => $mail->hasTo('test@example.com'));
});

test('send onboarding email job sends day14 mail', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    (new SendOnboardingEmail($user->id, 'day14'))->handle();

    Mail::assertSent(OnboardingDay14Mail::class, fn ($mail) => $mail->hasTo('test@example.com'));
});

test('send onboarding email job sends day30 mail', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    (new SendOnboardingEmail($user->id, 'day30'))->handle();

    Mail::assertSent(OnboardingDay30Mail::class, fn ($mail) => $mail->hasTo('test@example.com'));
});

test('day3 mail is skipped when user has sent review requests', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);

    ReviewRequest::factory()->create(['business_id' => $business->id]);

    (new SendOnboardingEmail($user->id, 'day3'))->handle();

    Mail::assertNotSent(OnboardingDay3Mail::class);
});

test('day3 mail is sent when user has not sent any review requests', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);
    Business::factory()->onboarded()->create(['user_id' => $user->id]);

    (new SendOnboardingEmail($user->id, 'day3'))->handle();

    Mail::assertSent(OnboardingDay3Mail::class, fn ($mail) => $mail->hasTo('test@example.com'));
});

test('job silently exits when user no longer exists', function () {
    Mail::fake();

    (new SendOnboardingEmail(99999, 'welcome'))->handle();

    Mail::assertNothingSent();
});
