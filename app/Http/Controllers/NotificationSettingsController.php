<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/notifications', [
            'preferences' => [
                'weekly_digest'    => $user->notificationPreference('weekly_digest'),
                'new_review_alert' => $user->notificationPreference('new_review_alert'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weekly_digest'    => ['required', 'boolean'],
            'new_review_alert' => ['required', 'boolean'],
        ]);

        $request->user()->update(['notification_preferences' => $validated]);

        return back()->with('success', 'Notification preferences saved.');
    }
}
