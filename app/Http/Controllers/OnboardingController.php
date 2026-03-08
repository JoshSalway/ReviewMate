<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\DefaultTemplateService;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function businessType(Request $request): Response|RedirectResponse
    {
        if ($request->user()->currentBusiness()?->isOnboardingComplete()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/business-type');
    }

    public function storeBusinessType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:tradie,cafe,salon,healthcare,real_estate,retail,pet_services,fitness,other'],
            'owner_name' => ['required', 'string', 'max:255'],
        ]);

        $business = $request->user()->businesses()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        session(['current_business_id' => $business->id]);

        return redirect()->route('onboarding.connect-google');
    }

    public function connectGoogle(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business) {
            return redirect()->route('onboarding.business-type');
        }

        $locations = [];

        if ($business->isGoogleConnected()) {
            try {
                $locations = app(GoogleBusinessProfileService::class)->listLocationsWithPlaceIds($business);
            } catch (\Throwable) {
                // Fall back to manual input
            }
        }

        return Inertia::render('onboarding/connect-google', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'google_place_id' => $business->google_place_id,
            ],
            'isGoogleConnected' => $business->isGoogleConnected(),
            'locations' => $locations,
        ]);
    }

    public function storeConnectGoogle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_place_id' => ['required', 'string', 'max:255'],
        ]);

        $business = $request->user()->currentBusiness();

        if (! $business) {
            return redirect()->route('onboarding.business-type');
        }

        $business->update($validated);

        return redirect()->route('onboarding.select-template');
    }

    public function selectTemplate(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business) {
            return redirect()->route('onboarding.connect-google');
        }

        $defaultTemplates = DefaultTemplateService::forBusinessType($business->type);

        return Inertia::render('onboarding/select-template', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type,
            ],
            'defaultTemplates' => $defaultTemplates,
        ]);
    }

    public function storeSelectTemplate(Request $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business) {
            return redirect()->route('onboarding.business-type');
        }

        $templates = DefaultTemplateService::forBusinessType($business->type);

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['business_id' => $business->id, 'type' => $template['type']],
                ['subject' => $template['subject'] ?? null, 'body' => $template['body']]
            );
        }

        $business->update(['onboarding_completed_at' => now()]);

        return redirect()->route('onboarding.complete');
    }

    public function complete(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business) {
            return redirect()->route('onboarding.business-type');
        }

        if (! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.select-template');
        }

        return Inertia::render('onboarding/complete', [
            'business' => [
                'name' => $business->name,
                'type' => $business->type,
            ],
        ]);
    }
}
