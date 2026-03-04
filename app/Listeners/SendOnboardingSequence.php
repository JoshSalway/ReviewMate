<?php

namespace App\Listeners;

use App\Jobs\SendOnboardingEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class SendOnboardingSequence
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $userId = $event->user->id;

        SendOnboardingEmail::dispatch($userId, 'welcome');
        SendOnboardingEmail::dispatch($userId, 'day3')->delay(now()->addDays(3));
        SendOnboardingEmail::dispatch($userId, 'day7')->delay(now()->addDays(7));
        SendOnboardingEmail::dispatch($userId, 'day14')->delay(now()->addDays(14));
        SendOnboardingEmail::dispatch($userId, 'day30')->delay(now()->addDays(30));
    }
}
