<?php

namespace App\Http\Controllers;

use App\Mail\WaitlistApprovedMail;
use App\Mail\WaitlistConfirmationMail;
use App\Models\WaitlistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('welcome', [
            'count' => WaitlistEntry::count(),
        ]);
    }

    public function waitlistPage(): Response
    {
        return Inertia::render('waitlist', [
            'count' => WaitlistEntry::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('waitlist_entries', 'email')],
            'business_type' => ['nullable', 'string', 'max:100'],
        ]);

        $entry = WaitlistEntry::create($request->only('name', 'email', 'business_type'));

        Mail::to($entry->email)->queue(new WaitlistConfirmationMail($entry));

        return back()->with('waitlist_success', true);
    }

    public function approve(WaitlistEntry $waitlistEntry): RedirectResponse
    {
        if ($waitlistEntry->isApproved()) {
            return back()->with('info', 'Already approved.');
        }

        $waitlistEntry->update(['approved_at' => now()]);

        Mail::to($waitlistEntry->email)->queue(new WaitlistApprovedMail($waitlistEntry));

        return back()->with('success', "{$waitlistEntry->name} has been approved and notified.");
    }
}
