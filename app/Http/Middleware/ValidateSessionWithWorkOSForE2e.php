<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use Symfony\Component\HttpFoundation\Response;

/**
 * E2E-aware wrapper around ValidateSessionWithWorkOS.
 *
 * When APP_E2E=true (local only), the WorkOS token validation is bypassed
 * so that Playwright tests can log in via the /_e2e/login helper route
 * without needing real WorkOS OAuth tokens.
 */
class ValidateSessionWithWorkOSForE2e extends ValidateSessionWithWorkOS
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.e2e', false) && config('app.env') === 'local') {
            // If the user is already authenticated via the session (set by
            // the /_e2e/login route), skip all WorkOS token checks.
            if (auth()->check()) {
                return $next($request);
            }
        }

        return parent::handle($request, $next);
    }
}
