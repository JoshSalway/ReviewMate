<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\HalaxyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HalaxyController extends Controller
{
    /**
     * Store Halaxy API key and test connection.
     * Halaxy uses API key auth (no OAuth) — user enters their API key from Halaxy Settings.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => 'required|string|min:10',
        ]);

        $business = Auth::user()->currentBusiness();

        // Temporarily set the value to test the connection
        $business->halaxy_api_key = $request->api_key;
        $service = new HalaxyService($business);

        if (! $service->testConnection()) {
            return back()->withErrors(['api_key' => 'Could not connect to Halaxy. Please check your API key.']);
        }

        $business->update([
            'halaxy_api_key' => $request->api_key,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Halaxy connected successfully!');
    }

    /**
     * Disconnect Halaxy integration.
     */
    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->update([
            'halaxy_api_key'        => null,
            'halaxy_last_polled_at' => null,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Halaxy disconnected.');
    }

    /**
     * Toggle auto-send setting.
     */
    public function toggleAutoSend(): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();
        $business->update(['halaxy_auto_send_reviews' => ! $business->halaxy_auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }
}
