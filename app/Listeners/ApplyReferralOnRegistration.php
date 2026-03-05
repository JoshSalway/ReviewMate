<?php

namespace App\Listeners;

use App\Models\Business;
use App\Models\Referral;
use Illuminate\Auth\Events\Registered;

class ApplyReferralOnRegistration
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Check if there's a referral token in the session
        $token = session('referral_token');

        if (! $token) {
            return;
        }

        // Find the referring business by their shareable referral token
        $referrerBusiness = Business::where('referral_token', $token)->first();

        if (! $referrerBusiness) {
            // Try treating the token as a Referral record token (for customer Flow A)
            $referral = Referral::where('referral_token', $token)
                ->where('status', 'pending')
                ->first();
        } else {
            // Flow B: business-to-business referral
            $referral = Referral::create([
                'referrer_business_id' => $referrerBusiness->id,
                'referral_token' => $token.'_'.$user->id, // unique sub-token
                'referral_type' => 'business',
                'status' => 'signed_up',
                'signed_up_at' => now(),
            ]);
        }

        if (! $referral) {
            session()->forget('referral_token');

            return;
        }

        // Update referral status if it was pending
        if ($referral->isPending()) {
            $referral->update([
                'status' => 'signed_up',
                'signed_up_at' => now(),
            ]);
        }

        // Link the user to this referral
        $user->update([
            'referral_id' => $referral->id,
            'trial_ends_at' => now()->addMonth(),
        ]);

        session()->forget('referral_token');
    }
}
