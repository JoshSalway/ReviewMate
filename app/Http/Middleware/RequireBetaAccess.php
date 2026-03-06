<?php

namespace App\Http\Middleware;

use App\Models\WaitlistEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBetaAccess
{
    /**
     * Handle an incoming request.
     *
     * When BETA_MODE is on, only superadmins and waitlist-approved users can
     * access the authenticated app. Everyone else is redirected to /waitlist.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.beta_mode', false)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Superadmins always have access.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if the user's email is on the approved waitlist.
        $approved = WaitlistEntry::where('email', $user->email)
            ->whereNotNull('approved_at')
            ->exists();

        if ($approved) {
            return $next($request);
        }

        return redirect()->route('waitlist');
    }
}
