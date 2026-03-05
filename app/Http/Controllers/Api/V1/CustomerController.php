<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    /**
     * List customers for a business.
     */
    public function index(Request $request, Business $business): AnonymousResourceCollection|JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $customers = $business->customers()
            ->orderBy('name')
            ->paginate(50);

        return CustomerResource::collection($customers);
    }
}
