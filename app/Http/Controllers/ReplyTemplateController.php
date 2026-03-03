<?php

namespace App\Http\Controllers;

use App\Models\ReplyTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReplyTemplateController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business || ! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.business-type');
        }

        $templates = $business->replyTemplates()->latest()->get(['id', 'name', 'body']);

        return Inertia::render('settings/reply-templates', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:4096'],
        ]);

        $business = $request->user()->currentBusiness();
        $business->replyTemplates()->create($validated);

        return back()->with('success', 'Reply template saved.');
    }

    public function update(Request $request, ReplyTemplate $replyTemplate): RedirectResponse
    {
        abort_unless($replyTemplate->business_id === $request->user()->currentBusiness()?->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:4096'],
        ]);

        $replyTemplate->update($validated);

        return back()->with('success', 'Reply template updated.');
    }

    public function destroy(Request $request, ReplyTemplate $replyTemplate): RedirectResponse
    {
        abort_unless($replyTemplate->business_id === $request->user()->currentBusiness()?->id, 403);

        $replyTemplate->delete();

        return back()->with('success', 'Reply template deleted.');
    }
}
