<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WidgetSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $embedCode = $business?->slug
            ? '<script src="https://reviewmate.app/widget.js" data-business="'.$business->slug.'"></script>'
            : null;

        return Inertia::render('settings/widget', [
            'business' => [
                'slug' => $business?->slug,
                'widget_enabled' => $business?->widget_enabled ?? true,
                'widget_min_rating' => $business?->widget_min_rating ?? 4,
                'widget_max_reviews' => $business?->widget_max_reviews ?? 6,
                'widget_theme' => $business?->widget_theme ?? 'light',
            ],
            'embedCode' => $embedCode,
            'previewUrl' => $business?->slug
                ? url('/api/widget/'.$business->slug)
                : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'widget_enabled' => ['required', 'boolean'],
            'widget_min_rating' => ['required', 'integer', 'in:3,4,5'],
            'widget_max_reviews' => ['required', 'integer', 'in:3,6,9'],
            'widget_theme' => ['required', 'string', 'in:light,dark'],
        ]);

        $business = $request->user()->currentBusiness();

        if ($business) {
            $business->update($validated);
        }

        return back()->with('success', 'Widget settings updated.');
    }
}
