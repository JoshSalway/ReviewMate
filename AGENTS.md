# ReviewMate — Agent Task Instructions

You are working on **ReviewMate**, a Google review management SaaS for local businesses (tradies, cafes, salons, gyms). It sends review requests to customers via email/SMS and tracks who left a review. Built with Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth.

The app is at `/Users/joshsalway/development/ReviewMate`.

Read the codebase before making changes. Follow existing patterns exactly — check sibling files before creating anything new.

---

## Context: What's Already Built

- `Business`, `Customer`, `ReviewRequest`, `Review`, `EmailTemplate` models exist
- Customers can be imported via CSV or added manually
- Review requests are sent via email (and SMS stub)
- `ReviewController` has a `replySuggestions()` method that uses `ReviewReplyAgent` (Claude AI) to generate 3 draft reply options for a review — this already works
- `ReviewReplyAgent` at `app/Ai/Agents/ReviewReplyAgent.php` exists and uses Laravel AI SDK
- Onboarding flow: business type → connect Google → select template
- QR code page, quick send, email flow, template editor all exist

---

## Task 1 — Google Business Profile OAuth + Auto-Fetch Reviews (HIGHEST PRIORITY)

Right now reviews are created manually or via review requests. ReviewMate needs to **automatically pull real Google reviews** from the Google Business Profile API so owners can see all their reviews in one place.

### 1a. Add Google OAuth to Business model

Add a migration:
```php
$table->text('google_access_token')->nullable();
$table->text('google_refresh_token')->nullable();
$table->string('google_token_expires_at')->nullable();
$table->string('google_account_id')->nullable();   // e.g. "accounts/123456789"
$table->string('google_location_id')->nullable();  // e.g. "locations/987654321"
```

Update `Business::$fillable` and `Business::$casts` (cast `google_access_token` as `encrypted`).

### 1b. Add Google OAuth flow

Use Laravel Socialite with the `google` driver. Add `laravel/socialite` if not installed.

Scopes needed:
- `https://www.googleapis.com/auth/business.manage`

**Routes (add to `routes/web.php`):**
```php
Route::get('google/connect', [GoogleBusinessController::class, 'redirect'])->name('google.connect');
Route::get('google/callback', [GoogleBusinessController::class, 'callback'])->name('google.callback');
Route::delete('google/disconnect', [GoogleBusinessController::class, 'disconnect'])->name('google.disconnect');
```

**Create `app/Http/Controllers/GoogleBusinessController.php`:**

- `redirect()` — `Socialite::driver('google')->scopes([...])->redirect()`
- `callback()` — get user, store `access_token`, `refresh_token`, `expires_in` on the business
- After storing tokens, also call the GBP API to discover their `account_id` and `location_id` — store those on the business
- `disconnect()` — nullify all google fields on the business

**Add to `.env.example`:**
```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/google/callback"
```

**Add to `config/services.php`:**
```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

### 1c. Create `app/Services/GoogleBusinessProfileService.php`

This service wraps the Google Business Profile API:

```php
class GoogleBusinessProfileService
{
    public function fetchReviews(Business $business, int $pageSize = 50): array
    // GET https://mybusiness.googleapis.com/v4/{google_location_id}/reviews
    // Returns array of review data

    public function postReply(Business $business, string $reviewName, string $reply): void
    // PUT https://mybusiness.googleapis.com/v4/{reviewName}/reply
    // Body: {"comment": $reply}

    public function deleteReply(Business $business, string $reviewName): void
    // DELETE https://mybusiness.googleapis.com/v4/{reviewName}/reply

    private function getAccessToken(Business $business): string
    // Refresh the token if expired using the refresh_token
    // Update business.google_access_token and google_token_expires_at
}
```

Use Laravel's `Http` facade. Always check token expiry and refresh before each call.

### 1d. Create `app/Jobs/SyncGoogleReviews.php`

A queued job that fetches reviews for a business and upserts them into the `reviews` table.

For each review from the API:
- Map to `Review` fields: `rating`, `body` (from `comment`), `reviewer_name`, `reviewed_at`, `source = 'google'`
- Store the raw Google `reviewId` and `reviewName` (the full resource path, needed for posting replies) — add these columns via migration if not present:
  ```php
  $table->string('google_review_id')->nullable()->unique();
  $table->string('google_review_name')->nullable(); // e.g. "accounts/x/locations/y/reviews/z"
  $table->text('google_reply')->nullable();         // the posted reply, if any
  $table->timestamp('google_reply_posted_at')->nullable();
  ```
- `updateOrCreate(['google_review_id' => $reviewId], [...])`

**Schedule this job:** In `routes/console.php` or `app/Console/Kernel.php`, run `SyncGoogleReviews` for each business with a connected Google account every 2 hours:
```php
Schedule::call(function () {
    Business::whereNotNull('google_access_token')->each(
        fn ($business) => SyncGoogleReviews::dispatch($business)
    );
})->everyTwoHours();
```

---

## Task 2 — AI Review Response UI ("Pending Replies" page)

The AI suggestion endpoint already exists (`POST reviews/{review}/reply-suggestions`). Now build the UI to approve and post responses.

### 2a. Create `resources/js/pages/reviews/index.tsx`

A "Reviews" page showing all reviews fetched from Google, grouped into two sections:

**Needs Reply** (reviews with no `google_reply`)
- Show star rating, reviewer name, review text, how long ago
- [Suggest Reply] button → calls `POST reviews/{review}/reply-suggestions` → shows 3 AI options
- User picks one or edits it → [Post Reply] button → calls `POST reviews/{review}/reply`
- After posting, move to "Replied" section

**Replied**
- Show reviews that have `google_reply` set
- Show the posted reply in a collapsible section

### 2b. Add `POST reviews/{review}/reply` route and action

In `ReviewController`, add:

```php
public function postReply(Request $request, Review $review): RedirectResponse
{
    $request->validate(['reply' => 'required|string|max:4096']);

    $business = $request->user()->currentBusiness();
    abort_unless($review->business_id === $business?->id, 403);
    abort_unless($review->google_review_name, 422, 'This review is not linked to Google.');

    app(GoogleBusinessProfileService::class)->postReply(
        $business,
        $review->google_review_name,
        $request->input('reply')
    );

    $review->update([
        'google_reply' => $request->input('reply'),
        'google_reply_posted_at' => now(),
    ]);

    return back()->with('success', 'Reply posted to Google.');
}
```

Route: `POST reviews/{review}/reply` → `reviews.reply`

### 2c. Add reviews nav link

Add "Reviews" to the sidebar navigation, between Requests and Templates.

---

## Task 3 — Review Request Follow-up Automation

When a review request is sent and the customer doesn't respond within 5 days, automatically send a single follow-up email.

- Status on `review_requests` is already: `sent`, `opened`, `reviewed`, `no_response`
- Create `app/Jobs/SendFollowUpRequests.php` — queries `ReviewRequest` where:
  - `status = 'sent'` or `status = 'opened'`
  - `created_at` is between 5–6 days ago (1-day window to prevent duplicate sends)
  - No existing follow-up sent (add `followed_up_at` timestamp column to `review_requests`)
- Send using the `followup` email template from `EmailTemplate`
- Update `followed_up_at = now()`

Schedule daily in the console kernel.

---

## Task 4 — Add Stripe Billing

### 4a. Install Cashier

```bash
composer require laravel/cashier
php artisan cashier:install
```

The `User` model (or whichever model owns the subscription) should `use Billable`.

### 4b. Plans

**Starter — $49/month**
- 1 business location
- Unlimited customers
- Email review requests
- AI reply suggestions
- Google review sync

**Pro — $99/month**
- Up to 5 business locations
- SMS review requests (when SMS provider added)
- All Starter features
- Priority support

### 4c. Enforce limits

On the free plan (no subscription), limit to:
- 1 business location
- 50 customers
- 10 review requests per month

Show upgrade prompts when limits are hit.

### 4d. Add billing settings page

Under `settings/billing`, show current plan and a [Manage Subscription] or [Upgrade] button.

---

## Task 5 — Dashboard Real Data

The dashboard likely shows placeholder or empty stats. Wire it up:

- **Total reviews this month** — `Review::where('business_id', $business->id)->whereMonth('reviewed_at', now()->month)->count()`
- **Average rating** — `Review::where('business_id', $business->id)->avg('rating')`
- **Requests sent this month** — `ReviewRequest::where('business_id', $business->id)->whereMonth('created_at', now()->month)->count()`
- **Conversion rate** — reviews / requests sent * 100
- **Reviews needing reply** — reviews with no `google_reply`

Pass all of these from `DashboardController` and display on the dashboard page.

---

## Conventions

- Stack: Laravel 12, React 19, Inertia.js v2, Tailwind v4, WorkOS auth
- Multi-business: use `$request->user()->currentBusiness()` to get the active business — always scope to this
- Use `laravel/ai` SDK + Claude for AI features (see existing `ReviewReplyAgent` for the pattern)
- Write Pest feature tests for all new controller actions
- Never expose Google tokens to the frontend
