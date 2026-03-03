<?php

use App\Jobs\SendFollowUpRequests;
use App\Jobs\SendWeeklyDigests;
use App\Jobs\SyncGoogleReviews;
use App\Models\Business;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send follow-up emails daily at 9am (5-day window job)
Schedule::job(new SendFollowUpRequests)->dailyAt('09:00');

// Also run legacy artisan command for backward compatibility
Schedule::command('reviewmate:send-followups')->dailyAt('09:05');

// Send weekly digest every Monday at 08:00
Schedule::job(new SendWeeklyDigests)->weeklyOn(1, '08:00');

// Sync Google reviews every 2 hours for all connected businesses
Schedule::call(function () {
    Business::whereNotNull('google_access_token')
        ->whereNotNull('google_location_id')
        ->each(fn ($business) => SyncGoogleReviews::dispatch($business));
})->everyTwoHours();
