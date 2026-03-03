<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Enforce 1 business limit on free plan
        if ($user->onFreePlan() && $user->businesses()->count() >= 1) {
            return back()->with('error', 'Upgrade to add more business locations.');
        }

        // Pro plan: up to 5 businesses
        if (! $user->isAdmin() && $user->subscribed() && ! $user->subscribedToPrice(config('services.stripe.price_pro')) && $user->businesses()->count() >= 1) {
            return back()->with('error', 'Upgrade to Pro to add more business locations.');
        }

        if (! $user->isAdmin() && $user->subscribedToPrice(config('services.stripe.price_pro')) && $user->businesses()->count() >= 5) {
            return back()->with('error', 'Pro plan supports up to 5 business locations.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:tradie,cafe,salon,healthcare,real_estate,retail,pet_services,fitness,other'],
            'owner_name' => ['nullable', 'string', 'max:255'],
        ]);

        $business = $user->businesses()->create($validated);

        $request->user()->switchBusiness($business->id);

        return redirect()->route('onboarding.connect-google')
            ->with('success', 'New business created. Complete the setup to get started.');
    }

    public function switch(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->user_id === $request->user()->id, 403);

        $request->user()->switchBusiness($business->id);

        return redirect()->route('dashboard');
    }
}
