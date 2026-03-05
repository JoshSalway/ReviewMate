<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    /**
     * Public endpoint: store referral token in session and redirect to login/register.
     */
    public function track(Request $request, string $token): RedirectResponse
    {
        $business = Business::where('referral_token', $token)->first();

        if (! $business) {
            return redirect('/');
        }

        // Store the business's referral token in session for pickup after registration
        session(['referral_token' => $token]);

        return redirect()->route('login');
    }

    /**
     * Auth-required: referral dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $business = $user->currentBusiness();

        if (! $business) {
            return Inertia::render('referrals', [
                'referralLink' => null,
                'stats' => ['total' => 0, 'signed_up' => 0, 'converted' => 0],
                'rewardsEarned' => 0,
            ]);
        }

        $referrals = $business->referrals()->get();

        $stats = [
            'total' => $referrals->count(),
            'signed_up' => $referrals->whereIn('status', ['signed_up', 'converted'])->count(),
            'converted' => $referrals->where('status', 'converted')->count(),
        ];

        $rewardsEarned = $referrals->where('status', 'converted')->whereNotNull('reward_issued_at')->count();

        $shareMessage = "Hey! I use ReviewMate to automatically collect Google reviews from my customers. It's been great for my business. Get your first month free when you sign up: ".$business->referralUrl();

        return Inertia::render('referrals', [
            'referralLink' => $business->referralUrl(),
            'stats' => $stats,
            'rewardsEarned' => $rewardsEarned,
            'shareMessage' => $shareMessage,
        ]);
    }
}
