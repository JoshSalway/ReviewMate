<?php

namespace App\Http\Controllers;

use App\Models\WaitlistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', Rule::unique('waitlist_entries', 'email')],
            'business_type' => ['nullable', 'string', 'max:100'],
        ]);

        WaitlistEntry::create($request->only('name', 'email', 'business_type'));

        return back()->with('waitlist_success', true);
    }
}
