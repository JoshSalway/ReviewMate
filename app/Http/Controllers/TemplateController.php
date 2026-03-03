<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $templates = $business->emailTemplates()
            ->get()
            ->keyBy('type')
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'subject' => $t->subject,
                'body' => $t->body,
            ]);

        return Inertia::render('templates/index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type,
            ],
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        abort_unless($emailTemplate->business_id === $request->user()->currentBusiness()?->id, 403);

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $emailTemplate->update($validated);

        return back()->with('success', 'Template saved successfully.');
    }
}
