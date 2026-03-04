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

        return Inertia::render('settings/business', [
            'business' => [
                'id' => $business?->id,
                'name' => $business?->name,
                'type' => $business?->type,
                'google_place_id' => $business?->google_place_id,
                'owner_name' => $business?->owner_name,
                'phone' => $business?->phone,
                'is_google_connected' => $business?->isGoogleConnected() ?? false,
                'google_account_id' => $business?->google_account_id,
                'google_location_id' => $business?->google_location_id,
                'facebook_page_url' => $business?->facebook_page_url,
            ],
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
        ]);

        $business = $request->user()->currentBusiness();

        if ($business) {
            $business->update($validated);
        }

        return back()->with('success', 'Business settings updated.');
    }
}
