<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailFlowController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        if (! $business || ! $business->isOnboardingComplete()) {
            return redirect()->route('onboarding.business-type');
        }

        return Inertia::render('email-flow', [
            'business' => [
                'name' => $business->name,
                'type' => $business->type,
            ],
        ]);
    }
}
