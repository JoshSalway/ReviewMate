<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ReviewReplyAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AutoReplySettingsController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $business = $user->currentBusiness();

        if (! $business || ! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.business-type');
        }

        return Inertia::render('settings/auto-reply', [
            'settings' => [
                'auto_reply_enabled' => $business->auto_reply_enabled ?? false,
                'auto_reply_min_rating' => $business->auto_reply_min_rating ?? 4,
                'auto_reply_tone' => $business->auto_reply_tone ?? 'friendly',
                'auto_reply_length' => $business->auto_reply_length ?? 'medium',
                'auto_reply_signature' => $business->auto_reply_signature ?? '',
                'auto_reply_custom_instructions' => $business->auto_reply_custom_instructions ?? '',
            ],
            'businessType' => $business->type,
            'businessName' => $business->name,
            'isGoogleConnected' => $business->isGoogleConnected(),
            'isProPlan' => ! $user->onFreePlan(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->onFreePlan()) {
            return back()->with('error', 'Auto-reply is a Pro plan feature. Upgrade to enable it.');
        }

        $validated = $request->validate([
            'auto_reply_enabled' => ['required', 'boolean'],
            'auto_reply_min_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'auto_reply_tone' => ['required', 'string', 'in:professional,friendly,casual,enthusiastic'],
            'auto_reply_length' => ['required', 'string', 'in:short,medium,long'],
            'auto_reply_signature' => ['nullable', 'string', 'max:200'],
            'auto_reply_custom_instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $business = $user->currentBusiness();

        if ($validated['auto_reply_enabled'] && ! $business->isGoogleConnected()) {
            return back()->with('error', 'Connect Google Business Profile before enabling auto-reply.');
        }

        $business->update($validated);

        return back()->with('success', 'Auto-reply settings saved.');
    }

    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->onFreePlan()) {
            return response()->json(['error' => 'Pro plan required.'], 403);
        }

        $business = $user->currentBusiness();

        $validated = $request->validate([
            'tone' => ['required', 'string', 'in:professional,friendly,casual,enthusiastic'],
            'length' => ['required', 'string', 'in:short,medium,long'],
            'signature' => ['nullable', 'string', 'max:200'],
            'custom_instructions' => ['nullable', 'string', 'max:1000'],
            'sample_review' => ['required', 'string', 'max:500'],
            'sample_rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $agent = new ReviewReplyAgent(
            businessName: $business->name,
            businessType: $business->type,
            ownerName: $business->owner_name ?? $user->name,
            tone: $validated['tone'],
            length: $validated['length'],
            signature: $validated['signature'] ?? null,
            customInstructions: $validated['custom_instructions'] ?? null,
            multipleOptions: false,
        );

        $response = $agent->prompt(
            "Generate a reply for this {$validated['sample_rating']}-star Google review:\n\n\"{$validated['sample_review']}\""
        );

        return response()->json(['preview' => trim($response->text)]);
    }
}
