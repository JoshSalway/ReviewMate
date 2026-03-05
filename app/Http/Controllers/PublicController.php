<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function pricing(): Response
    {
        return Inertia::render('pricing');
    }

    public function features(): Response
    {
        return Inertia::render('features');
    }

    public function changelog(): Response
    {
        return Inertia::render('changelog');
    }

    public function docs(): Response
    {
        return Inertia::render('docs');
    }
}
