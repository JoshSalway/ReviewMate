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

    public function __construct(public readonly ?Business $business = null) {}

    public function handle(): void
    {
        $businesses = $this->business
            ? collect([$this->business->loadMissing('user')])->filter()
            : Business::with('user')->whereHas('user')->lazy(100);

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
    }
}
