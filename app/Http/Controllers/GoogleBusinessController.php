<?php

namespace App\Http\Controllers;

use App\Services\GoogleBusinessProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleBusinessController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/business.manage',
            ])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function callback(Request $request, GoogleBusinessProfileService $service): RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business) {
            return redirect()->route('dashboard')->with('error', 'No active business found.');
        }

        $googleUser = Socialite::driver('google')->user();

        $business->update([
            'google_access_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken,
            'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn - 60)->toDateTimeString(),
        ]);

        // Discover account and location IDs
        try {
            $service->discoverAccountAndLocation($business);
        } catch (\Throwable) {
            // Non-fatal — user can still use the connection
        }

        $redirectRoute = $business->isOnboardingComplete()
            ? 'settings.business'
            : 'onboarding.connect-google';

        return redirect()->route($redirectRoute)
            ->with('success', 'Google Business Profile connected successfully!');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if ($business) {
            $business->update([
                'google_access_token' => null,
                'google_refresh_token' => null,
                'google_token_expires_at' => null,
                'google_account_id' => null,
                'google_location_id' => null,
            ]);
        }

        return redirect()->route('settings.business')
            ->with('success', 'Google Business Profile disconnected.');
    }
}
