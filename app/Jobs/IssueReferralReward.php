<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IssueReferralReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Referral $referral,
        public readonly User $referredUser,
    ) {}

    public function handle(): void
    {
        // Mark referral as converted
        $this->referral->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        // Give the referrer (business owner) 1 month free
        $referrerBusiness = $this->referral->referrerBusiness()->with('user')->first();
        if ($referrerBusiness && $referrerBusiness->user) {
            $referrerUser = $referrerBusiness->user;
            $currentTrialEnd = $referrerUser->trial_ends_at;

            if ($currentTrialEnd && $currentTrialEnd->isFuture()) {
                // Extend existing trial by 1 month
                $referrerUser->update(['trial_ends_at' => $currentTrialEnd->addMonth()]);
            } else {
                // Start a new 1-month trial from today
                $referrerUser->update(['trial_ends_at' => now()->addMonth()]);
            }
        }

        // Mark reward as issued
        $this->referral->update(['reward_issued_at' => now()]);
    }
}
