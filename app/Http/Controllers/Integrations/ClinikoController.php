<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\BusinessIntegration;
use App\Services\ClinikoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinikoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => 'required|string|min:10',
        ]);

        $apiKey = $request->api_key;
        $shard = ClinikoService::detectShard($apiKey);
        $business = Auth::user()->currentBusiness();

        // Build a temporary integration to test the connection without persisting
        $tempIntegration = new BusinessIntegration([
            'provider' => 'cliniko',
            'api_key' => $apiKey,
            'meta' => ['shard' => $shard],
        ]);
        $business->setRelation('integrations', collect([$tempIntegration]));
        $service = new ClinikoService($business);

        if (! $service->testConnection()) {
            return back()->withErrors(['api_key' => 'Could not connect to Cliniko. Please check your API key.']);
        }

        BusinessIntegration::updateOrCreate(
            ['business_id' => $business->id, 'provider' => 'cliniko'],
            ['api_key' => $apiKey, 'meta' => ['shard' => $shard]]
        );

        return redirect()->route('settings.integrations')
            ->with('success', 'Cliniko connected successfully!');
    }

    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'cliniko')->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Cliniko disconnected.');
    }

    public function toggleAutoSend(): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->integration('cliniko');
        $integration?->update(['auto_send_reviews' => ! $integration->auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }
}
