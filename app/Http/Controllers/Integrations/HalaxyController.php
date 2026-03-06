<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\BusinessIntegration;
use App\Services\HalaxyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HalaxyController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => 'required|string|min:10',
        ]);

        $business = Auth::user()->currentBusiness();

        // Build a temporary integration to test the connection without persisting
        $tempIntegration = new BusinessIntegration([
            'provider' => 'halaxy',
            'api_key' => $request->api_key,
        ]);
        $business->setRelation('integrations', collect([$tempIntegration]));
        $service = new HalaxyService($business);

        if (! $service->testConnection()) {
            return back()->withErrors(['api_key' => 'Could not connect to Halaxy. Please check your API key.']);
        }

        BusinessIntegration::updateOrCreate(
            ['business_id' => $business->id, 'provider' => 'halaxy'],
            ['api_key' => $request->api_key]
        );

        return redirect()->route('settings.integrations')
            ->with('success', 'Halaxy connected successfully!');
    }

    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'halaxy')->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Halaxy disconnected.');
    }

    public function toggleAutoSend(): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->integration('halaxy');
        $integration?->update(['auto_send_reviews' => ! $integration->auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }
}
