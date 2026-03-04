<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\ClinikoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinikoController extends Controller
{
    /**
     * Store Cliniko API key and test connection.
     * Cliniko uses API key auth (no OAuth) — user enters their API key.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => 'required|string|min:10',
        ]);

        $apiKey   = $request->api_key;
        $shard    = ClinikoService::detectShard($apiKey);
        $business = Auth::user()->currentBusiness();

        // Temporarily set the values to test the connection
        $business->cliniko_api_key = $apiKey;
        $business->cliniko_shard   = $shard;
        $service = new ClinikoService($business);

        if (! $service->testConnection()) {
            return back()->withErrors(['api_key' => 'Could not connect to Cliniko. Please check your API key.']);
        }

        $business->update([
            'cliniko_api_key' => $apiKey,
            'cliniko_shard'   => $shard,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Cliniko connected successfully!');
    }

    /**
     * Disconnect Cliniko integration.
     */
    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->update([
            'cliniko_api_key'        => null,
            'cliniko_shard'          => null,
            'cliniko_last_polled_at' => null,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Cliniko disconnected.');
    }

    /**
     * Toggle auto-send setting.
     */
    public function toggleAutoSend(): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();
        $business->update(['cliniko_auto_send_reviews' => ! $business->cliniko_auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }
}
