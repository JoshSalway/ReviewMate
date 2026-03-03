<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmailFlowController;
use App\Http\Controllers\GoogleBusinessController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\QuickSendController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewRequestController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::inertia('/', 'welcome')->name('home');

// Stripe webhook (must be outside auth middleware, CSRF excluded by Cashier)
Route::post('stripe/webhook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')
    ->name('cashier.webhook');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    // Dashboard
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('business-type', [OnboardingController::class, 'businessType'])->name('business-type');
        Route::post('business-type', [OnboardingController::class, 'storeBusinessType'])->name('business-type.store');
        Route::get('connect-google', [OnboardingController::class, 'connectGoogle'])->name('connect-google');
        Route::post('connect-google', [OnboardingController::class, 'storeConnectGoogle'])->name('connect-google.store');
        Route::get('select-template', [OnboardingController::class, 'selectTemplate'])->name('select-template');
        Route::post('select-template', [OnboardingController::class, 'storeSelectTemplate'])->name('select-template.store');
    });

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('customers/import', [CustomerController::class, 'importCsv'])->name('customers.import');
    Route::post('customers/bulk-send', [CustomerController::class, 'bulkSend'])->name('customers.bulk-send');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Review Requests
    Route::get('requests', [ReviewRequestController::class, 'index'])->name('requests.index');
    Route::post('requests', [ReviewRequestController::class, 'store'])->name('requests.store');

    // Reviews
    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::post('reviews/{review}/reply-suggestions', [ReviewController::class, 'replySuggestions'])->name('reviews.reply-suggestions');
    Route::post('reviews/{review}/reply', [ReviewController::class, 'postReply'])->name('reviews.reply');

    // Templates
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::put('templates/{emailTemplate}', [TemplateController::class, 'update'])->name('templates.update');

    // Quick Send
    Route::get('quick-send', [QuickSendController::class, 'index'])->name('quick-send.index');
    Route::post('quick-send', [QuickSendController::class, 'send'])->name('quick-send.send');

    // Businesses (multi-business support)
    Route::post('businesses', [BusinessController::class, 'store'])->name('businesses.store');
    Route::post('businesses/{business}/switch', [BusinessController::class, 'switch'])->name('businesses.switch');

    // QR Code
    Route::get('qr-code', QrCodeController::class)->name('qr-code');

    // Email Flow
    Route::get('email-flow', EmailFlowController::class)->name('email-flow');

    // Business Settings
    Route::get('settings/business', [BusinessSettingsController::class, 'index'])->name('settings.business');
    Route::put('settings/business', [BusinessSettingsController::class, 'update'])->name('settings.business.update');

    // Notification Settings
    Route::get('settings/notifications', [NotificationSettingsController::class, 'index'])->name('settings.notifications');
    Route::put('settings/notifications', [NotificationSettingsController::class, 'update'])->name('settings.notifications.update');

    // Billing
    Route::get('settings/billing', [BillingController::class, 'index'])->name('settings.billing');
    Route::post('settings/billing/subscribe', [BillingController::class, 'subscribe'])->name('settings.billing.subscribe');
    Route::post('settings/billing/portal', [BillingController::class, 'portal'])->name('settings.billing.portal');

    // Google Business OAuth
    Route::get('google/connect', [GoogleBusinessController::class, 'redirect'])->name('google.connect');
    Route::get('google/callback', [GoogleBusinessController::class, 'callback'])->name('google.callback');
    Route::delete('google/disconnect', [GoogleBusinessController::class, 'disconnect'])->name('google.disconnect');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
