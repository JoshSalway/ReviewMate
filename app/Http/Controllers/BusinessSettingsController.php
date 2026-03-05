<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();
        $user = $request->user();

        return Inertia::render('settings/business', [
            'business' => [
                'id' => $business?->id,
                'name' => $business?->name,
                'type' => $business?->type,
                'google_place_id' => $business?->google_place_id,
                'owner_name' => $business?->owner_name,
                'phone' => $business?->phone,
                'is_google_connected' => $business?->isGoogleConnected() ?? false,
                'google_account_id' => $business?->integration('google')?->getMeta('account_id'),
                'google_location_id' => $business?->integration('google')?->getMeta('location_id'),
                'facebook_page_url' => $business?->facebook_page_url,
                'follow_up_enabled' => $business?->follow_up_enabled ?? true,
                'follow_up_days' => $business?->follow_up_days ?? 3,
                'follow_up_channel' => $business?->follow_up_channel ?? 'same',
            ],
            'isProPlan' => ! $user->onFreePlan(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:tradie,cafe,salon,healthcare,real_estate,retail,pet_services,fitness,other'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'facebook_page_url' => ['nullable', 'url', 'max:500'],
            'follow_up_enabled' => ['sometimes', 'boolean'],
            'follow_up_days' => ['sometimes', 'integer', 'in:2,3,5,7'],
            'follow_up_channel' => ['sometimes', 'string', 'in:same,sms,email'],
        ]);

        $business = $request->user()->currentBusiness();

        if ($business) {
            $business->update($validated);
        }

        return back()->with('success', 'Business settings updated.');
    }
}
