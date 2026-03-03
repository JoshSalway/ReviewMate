<?php

namespace App\Jobs;

use App\Mail\WeeklyDigestMail;
use App\Models\Business;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigests implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Business::with('user')
            ->whereHas('user')
            ->chunk(100, function ($businesses) {
                foreach ($businesses as $business) {
                    $user = $business->user;

                    if (! $user || ! $user->email) {
                        continue;
                    }

                    if (! $user->notificationPreference('weekly_digest')) {
                        continue;
                    }

                    Mail::to($user->email, $user->name)
                        ->queue(new WeeklyDigestMail($user, $business));
                }
            });
    }
}
