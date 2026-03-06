<?php

use App\Jobs\AutoReplyReviews;
use App\Jobs\PollClinikoAppointments;
use App\Jobs\PollHalaxyAppointments;
use App\Jobs\RefreshGoogleStats;
use App\Jobs\SendFollowUpRequests;
use App\Jobs\SendWeeklyDigests;
use App\Jobs\SyncGoogleReviews;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Timezone-aware helper: returns true when it is currently $targetHour (local time)
 * for a given business timezone. Runs on every tick of the scheduler (hourly).
 */
if (! function_exists('isLocalHour')) {
    function isLocalHour(string $timezone, int $targetHour): bool
    {
        try {
            return Carbon::now($timezone)->hour === $targetHour;
        } catch (\Throwable) {
            return Carbon::now('UTC')->hour === $targetHour;
        }
    }
}

// ---------------------------------------------------------------------------
// Follow-up review request emails — 9am in the business's local timezone
// Runs hourly; dispatches only for businesses where it is currently 9am local.
// ---------------------------------------------------------------------------
Schedule::call(function () {
    Business::with(['user', 'reviewRequests.customer'])
        ->get()
        ->each(function (Business $business) {
            if (isLocalHour($business->timezone ?? 'Australia/Sydney', 9)) {
                SendFollowUpRequests::dispatch($business);
            }
        });
})->hourly()->name('follow-up-requests');

// ---------------------------------------------------------------------------
// Sync Google reviews — every 2 hours for all connected businesses
// (not timezone-specific — always-on monitoring)
// ---------------------------------------------------------------------------
Schedule::call(function () {
    Business::with('integrations')
        ->get()
        ->filter(fn ($b) => $b->isGoogleConnected())
        ->each(fn ($business) => SyncGoogleReviews::dispatch($business));
})->everyTwoHours()->name('sync-google-reviews');

// ---------------------------------------------------------------------------
// Poll Cliniko — 8am in the business's local timezone
// ---------------------------------------------------------------------------
Schedule::call(function () {
    Business::whereNotNull('cliniko_api_key')
        ->where('cliniko_auto_send_reviews', true)
        ->get()
        ->each(function (Business $business) {
            if (isLocalHour($business->timezone ?? 'Australia/Sydney', 8)) {
                PollClinikoAppointments::dispatch($business);
            }
        });
})->hourly()->name('poll-cliniko-appointments');

// ---------------------------------------------------------------------------
// Poll Halaxy — 8am in the business's local timezone
// ---------------------------------------------------------------------------
Schedule::call(function () {
    Business::whereNotNull('halaxy_api_key')
        ->where('halaxy_auto_send_reviews', true)
        ->get()
        ->each(function (Business $business) {
            if (isLocalHour($business->timezone ?? 'Australia/Sydney', 8)) {
                PollHalaxyAppointments::dispatch($business);
            }
        });
})->hourly()->name('poll-halaxy-appointments');

// ---------------------------------------------------------------------------
// Refresh Google Places stats — 6am in the business's local timezone
// ---------------------------------------------------------------------------
Schedule::call(function () {
    Business::whereNotNull('google_place_id')
        ->get()
        ->each(function (Business $business) {
            if (isLocalHour($business->timezone ?? 'Australia/Sydney', 6)) {
                RefreshGoogleStats::dispatch($business);
            }
        });
})->hourly()->name('refresh-google-stats');

// ---------------------------------------------------------------------------
// AI auto-reply to Google reviews — 6pm in the business's local timezone
// Runs hourly; dispatches only for businesses where it is currently 6pm local.
// ---------------------------------------------------------------------------
Schedule::call(function () {
    Business::where('auto_reply_enabled', true)
        ->with(['integrations', 'user'])
        ->get()
        ->each(function (Business $business) {
            if (isLocalHour($business->timezone ?? 'Australia/Sydney', 18)) {
                AutoReplyReviews::dispatch($business);
            }
        });
})->hourly()->name('auto-reply-reviews');

// ---------------------------------------------------------------------------
// Weekly digest — Monday 8am in the business's local timezone
// ---------------------------------------------------------------------------
Schedule::call(function () {
    if (Carbon::now('UTC')->dayOfWeek !== Carbon::MONDAY) {
        return;
    }
    Business::with('user')
        ->get()
        ->each(function (Business $business) {
            if (isLocalHour($business->timezone ?? 'Australia/Sydney', 8)) {
                SendWeeklyDigests::dispatch($business);
            }
        });
})->hourly()->name('weekly-digests');
