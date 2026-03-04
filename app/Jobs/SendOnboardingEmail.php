<?php

namespace App\Jobs;

use App\Mail\Onboarding\OnboardingDay14Mail;
use App\Mail\Onboarding\OnboardingDay30Mail;
use App\Mail\Onboarding\OnboardingDay3Mail;
use App\Mail\Onboarding\OnboardingDay7Mail;
use App\Mail\Onboarding\OnboardingWelcomeMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SendOnboardingEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $userId,
        public readonly string $step,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $mailable = $this->buildMailable($user);

        if (! $mailable) {
            return;
        }

        Mail::to($user->email, $user->name)->send($mailable);
    }

    protected function buildMailable(User $user): ?Mailable
    {
        return match ($this->step) {
            'welcome' => new OnboardingWelcomeMail($user),
            'day3' => $this->buildDay3Mail($user),
            'day7' => new OnboardingDay7Mail($user),
            'day14' => new OnboardingDay14Mail($user),
            'day30' => new OnboardingDay30Mail($user),
            default => null,
        };
    }

    protected function buildDay3Mail(User $user): ?OnboardingDay3Mail
    {
        $hasRequestsSent = $user->businesses()
            ->withCount('reviewRequests')
            ->get()
            ->sum('review_requests_count') > 0;

        if ($hasRequestsSent) {
            return null;
        }

        return new OnboardingDay3Mail($user);
    }
}
