<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends Controller
{
    /**
     * List all businesses for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $businesses = $request->user()->businesses()->get();

        return BusinessResource::collection($businesses);
    }

    /**
     * Show a specific business.
     */
    public function show(Request $request, Business $business): BusinessResource|JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return new BusinessResource($business);
    }
}
