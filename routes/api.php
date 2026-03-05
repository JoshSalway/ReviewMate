<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ReviewRequestController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

// Public widget endpoint — no auth required, rate limited 60/min per IP
Route::middleware('throttle:60,1')
    ->get('widget/{slug}', [WidgetController::class, 'show'])
    ->name('widget.show');

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api/v1 and protected by Sanctum.
| Issue a token at POST /api/v1/auth/token (requires web session auth).
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Auth — issue tokens (requires session auth)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/token', [AuthController::class, 'token'])->name('auth.token');
        Route::delete('auth/tokens', [AuthController::class, 'revokeAll'])->name('auth.tokens.revoke');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Businesses
        Route::get('businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::get('businesses/{business:id}', [BusinessController::class, 'show'])->name('businesses.show');

        // Customers (scoped to business)
        Route::get('businesses/{business:id}/customers', [CustomerController::class, 'index'])->name('businesses.customers.index');

        // Review requests (scoped to business)
        Route::get('businesses/{business:id}/review-requests', [ReviewRequestController::class, 'index'])->name('businesses.review-requests.index');
        Route::post('businesses/{business:id}/review-requests', [ReviewRequestController::class, 'store'])->name('businesses.review-requests.store');

        // Reviews (scoped to business)
        Route::get('businesses/{business:id}/reviews', [ReviewController::class, 'index'])->name('businesses.reviews.index');
        Route::get('businesses/{business:id}/stats', [ReviewController::class, 'stats'])->name('businesses.stats');
    });

});
