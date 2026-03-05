<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewRequestResource;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewRequestController extends Controller
{
    /**
     * List review requests for a business.
     */
    public function index(Request $request, Business $business): AnonymousResourceCollection|JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $requests = $business->reviewRequests()
            ->with('customer')
            ->latest()
            ->paginate(50);

        return ReviewRequestResource::collection($requests);
    }

    /**
     * Create a review request for a customer.
     */
    public function store(Request $request, Business $business): ReviewRequestResource|JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Enforce 10 requests/month limit on free plan
        if ($request->user()->onFreePlan()) {
            $monthlyCount = $business->reviewRequests()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            if ($monthlyCount >= 10) {
                return response()->json(['message' => 'Free plan allows 10 review requests per month. Upgrade for unlimited.'], 422);
            }
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'channel' => ['required', 'in:email,sms,both'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        if ($customer->business_id !== $business->id) {
            return response()->json(['message' => 'Customer does not belong to this business.'], 422);
        }

        if ($customer->isUnsubscribed()) {
            return response()->json(['message' => 'Customer is unsubscribed.'], 422);
        }

        if (ReviewRequest::hasRecentRequest($business->id, $customer->id)) {
            return response()->json(['message' => 'A review request was already sent to this customer in the last 30 days.'], 422);
        }

        $reviewRequest = ReviewRequest::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'channel' => $validated['channel'],
            'status' => 'sent',
            'source' => 'api',
        ]);

        return new ReviewRequestResource($reviewRequest);
    }
}
