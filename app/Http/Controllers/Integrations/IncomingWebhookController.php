<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingWebhook;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IncomingWebhookController extends Controller
{
    /**
     * Accept a customer record and trigger a review request.
     *
     * POST /webhooks/incoming/{token}
     *
     * Payload (all fields optional except at least one of email/phone):
     * {
     *   "name": "Jane Smith",        // optional
     *   "email": "jane@example.com", // required if no phone
     *   "phone": "0412345678",       // required if no email
     *   "trigger": "job_completed"   // optional label for source tracking
     * }
     */
    public function handle(Request $request, string $token): JsonResponse
    {
        $business = Business::where('webhook_token', $token)->first();

        if (! $business) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $data = $request->validate([
            'name'    => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:30',
            'trigger' => 'nullable|string|max:100',
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json(['error' => 'At least one of email or phone is required'], 422);
        }

        ProcessIncomingWebhook::dispatch($business, $data);

        return response()->json(['status' => 'queued']);
    }

    /**
     * Regenerate the webhook token for a business.
     * POST /settings/integrations/webhook/regenerate
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        $business->update(['webhook_token' => Str::random(40)]);

        return back()->with('success', 'Webhook token regenerated.');
    }
}
