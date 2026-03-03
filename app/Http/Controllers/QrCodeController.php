<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QrCodeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        return Inertia::render('qr-code', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'google_review_url' => $business->googleReviewUrl(),
            ],
        ]);
    }
}
