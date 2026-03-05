<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailFlowController;
use App\Http\Controllers\GoogleBusinessController;
use App\Http\Controllers\Integrations\ClinikoController;
use App\Http\Controllers\Integrations\HalaxyController;
use App\Http\Controllers\Integrations\IncomingWebhookController;
use App\Http\Controllers\Integrations\ServiceM8Controller;
use App\Http\Controllers\Integrations\SimproController;
use App\Http\Controllers\Integrations\TimelyController;
use App\Http\Controllers\Integrations\XeroController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QuickSendController;
use App\Http\Controllers\ReplyTemplateController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReviewRequestController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\WidgetSettingsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::get('/', [WaitlistController::class, 'index'])->name('home');
Route::post('/waitlist', [WaitlistController::class, 'store'])->name('waitlist.store');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');
Route::get('/features', [PublicController::class, 'features'])->name('features');
Route::get('/changelog', [PublicController::class, 'changelog'])->name('changelog');
Route::get('/docs', [PublicController::class, 'docs'])->name('docs');

// API documentation (Swagger UI)
Route::get('/api/docs', function () {
    return view('api-docs');
})->name('api.docs');

// Serve the OpenAPI YAML spec
Route::get('/docs/openapi.yaml', function () {
    $path = base_path('docs/openapi.yaml');

    return response()->file($path, ['Content-Type' => 'application/yaml']);
})->name('api.openapi');
Route::get('/r/{token}', [ReviewRequestController::class, 'track'])->name('review-requests.track');
Route::get('/r/ref/{token}', [ReferralController::class, 'track'])->name('referrals.track');
Route::get('/unsubscribe/{token}', [CustomerController::class, 'unsubscribe'])->name('customers.unsubscribe');

// Stripe webhook (must be outside auth middleware, CSRF excluded by Cashier)
Route::post('stripe/webhook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')
    ->name('cashier.webhook');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    // Dashboard
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Analytics
    Route::get('analytics', AnalyticsController::class)->name('analytics');

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
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
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

    // Reply Templates
    Route::get('settings/reply-templates', [ReplyTemplateController::class, 'index'])->name('settings.reply-templates');
    Route::post('settings/reply-templates', [ReplyTemplateController::class, 'store'])->name('settings.reply-templates.store');
    Route::put('settings/reply-templates/{replyTemplate}', [ReplyTemplateController::class, 'update'])->name('settings.reply-templates.update');
    Route::delete('settings/reply-templates/{replyTemplate}', [ReplyTemplateController::class, 'destroy'])->name('settings.reply-templates.destroy');

    // Widget Settings
    Route::get('settings/widget', [WidgetSettingsController::class, 'index'])->name('settings.widget');
    Route::put('settings/widget', [WidgetSettingsController::class, 'update'])->name('settings.widget.update');

    // Referrals
    Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');

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

    // Integrations settings page
    Route::get('settings/integrations', function () {
        $business = Auth::user()->currentBusiness();

        return Inertia::render('settings/integrations', [
            'servicem8Connected' => $business?->servicem8_access_token !== null,
            'servicem8AutoSend' => $business?->servicem8_auto_send_reviews ?? true,
            'webhookUrl' => $business?->uuid
                ? route('webhooks.servicem8', ['business' => $business->uuid])
                : null,
            'xeroConnected' => $business?->xero_access_token !== null,
            'xeroAutoSend' => $business?->xero_auto_send_reviews ?? true,
            'xeroWebhookUrl' => $business?->uuid
                ? route('webhooks.xero', ['business' => $business->uuid])
                : null,
            'clinikoConnected' => $business?->cliniko_api_key !== null,
            'clinikoAutoSend' => $business?->cliniko_auto_send_reviews ?? true,
            'timelyConnected' => $business?->timely_access_token !== null,
            'timelyAutoSend' => $business?->timely_auto_send_reviews ?? true,
            'timelyWebhookUrl' => $business?->uuid
                ? route('webhooks.timely', ['business' => $business->uuid])
                : null,
            'simproConnected' => $business?->simpro_access_token !== null,
            'simproAutoSend' => $business?->simpro_auto_send_reviews ?? true,
            'simproWebhookUrl' => $business?->uuid
                ? route('webhooks.simpro', ['business' => $business->uuid])
                : null,
            'halaxyConnected' => $business?->halaxy_api_key !== null,
            'halaxyAutoSend' => $business?->halaxy_auto_send_reviews ?? true,
            // Generic incoming webhook
            'incomingWebhookToken' => $business?->webhook_token,
            'incomingWebhookUrl' => $business?->webhook_token
                ? route('webhooks.incoming', ['token' => $business->webhook_token])
                : null,
        ]);
    })->name('settings.integrations');

    // ServiceM8 OAuth flow
    Route::prefix('integrations/servicem8')->name('integrations.servicem8.')->group(function () {
        Route::get('connect', [ServiceM8Controller::class, 'connect'])->name('connect');
        Route::get('callback', [ServiceM8Controller::class, 'callback'])->name('callback');
        Route::post('disconnect', [ServiceM8Controller::class, 'disconnect'])->name('disconnect');
        Route::post('toggle-auto-send', [ServiceM8Controller::class, 'toggleAutoSend'])->name('toggle-auto-send');
    });

    // Xero OAuth flow
    Route::prefix('integrations/xero')->name('integrations.xero.')->group(function () {
        Route::get('connect', [XeroController::class, 'connect'])->name('connect');
        Route::get('callback', [XeroController::class, 'callback'])->name('callback');
        Route::post('disconnect', [XeroController::class, 'disconnect'])->name('disconnect');
        Route::post('toggle-auto-send', [XeroController::class, 'toggleAutoSend'])->name('toggle-auto-send');
    });

    // Cliniko integration (API key, no OAuth)
    Route::prefix('integrations/cliniko')->name('integrations.cliniko.')->group(function () {
        Route::post('connect', [ClinikoController::class, 'store'])->name('store');
        Route::post('disconnect', [ClinikoController::class, 'disconnect'])->name('disconnect');
        Route::post('toggle-auto-send', [ClinikoController::class, 'toggleAutoSend'])->name('toggle-auto-send');
    });

    // Timely OAuth flow
    Route::prefix('integrations/timely')->name('integrations.timely.')->group(function () {
        Route::get('connect', [TimelyController::class, 'connect'])->name('connect');
        Route::get('callback', [TimelyController::class, 'callback'])->name('callback');
        Route::post('disconnect', [TimelyController::class, 'disconnect'])->name('disconnect');
        Route::post('toggle-auto-send', [TimelyController::class, 'toggleAutoSend'])->name('toggle-auto-send');
    });

    // Simpro OAuth flow
    Route::prefix('integrations/simpro')->name('integrations.simpro.')->group(function () {
        Route::post('connect', [SimproController::class, 'connect'])->name('connect');
        Route::get('callback', [SimproController::class, 'callback'])->name('callback');
        Route::post('disconnect', [SimproController::class, 'disconnect'])->name('disconnect');
        Route::post('toggle-auto-send', [SimproController::class, 'toggleAutoSend'])->name('toggle-auto-send');
    });

    // Halaxy integration (API key, no OAuth)
    Route::prefix('integrations/halaxy')->name('integrations.halaxy.')->group(function () {
        Route::post('connect', [HalaxyController::class, 'store'])->name('store');
        Route::post('disconnect', [HalaxyController::class, 'disconnect'])->name('disconnect');
        Route::post('toggle-auto-send', [HalaxyController::class, 'toggleAutoSend'])->name('toggle-auto-send');
    });

    // Generic incoming webhook — token regeneration (auth required)
    Route::post('settings/integrations/webhook/regenerate', [IncomingWebhookController::class, 'regenerate'])
        ->name('integrations.webhook.regenerate');
});

// ServiceM8 webhook — public, no auth, each business identified by UUID in URL
Route::post('webhooks/servicem8/{business:uuid}', [ServiceM8Controller::class, 'webhook'])
    ->name('webhooks.servicem8');

// Xero webhook — public, no auth, each business identified by UUID in URL
Route::post('webhooks/xero/{business:uuid}', [XeroController::class, 'webhook'])
    ->name('webhooks.xero');

// Timely webhook — public, no auth, each business identified by UUID in URL
Route::post('webhooks/timely/{business:uuid}', [TimelyController::class, 'webhook'])
    ->name('webhooks.timely');

// Simpro webhook — public, no auth, each business identified by UUID in URL
Route::post('webhooks/simpro/{business:uuid}', [SimproController::class, 'webhook'])
    ->name('webhooks.simpro');

// Generic incoming webhook — authenticated by secret token in URL, no session required
Route::post('webhooks/incoming/{token}', [IncomingWebhookController::class, 'handle'])
    ->name('webhooks.incoming');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
