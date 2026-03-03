# ReviewMate — Agent Instructions

## Copy and paste this prompt to start a session:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md in this repo for full context on conventions and current state.

Current completion: ~35%. Core sending works. Google Business Profile integration is the biggest missing piece.

Your priority tasks in order:

1. GOOGLE BUSINESS PROFILE OAUTH — connect a real Google account to a business
   - Install `laravel/socialite` if not present
   - Scopes needed: `https://www.googleapis.com/auth/business.manage`
   - Routes: GET `google/connect`, GET `google/callback`, DELETE `google/disconnect`
   - Controller: `app/Http/Controllers/GoogleBusinessController.php`
   - On callback: store `google_access_token`, `google_refresh_token`, `google_token_expires_at` on Business model (add migration)
   - After token stored: call GBP API to get `account_id` and `location_id`, store on Business
   - Add to `.env.example`: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI
   - Add to `config/services.php`: google driver config

2. AUTO-SYNC REVIEWS — pull new reviews from Google every 2 hours
   - Create `app/Services/GoogleBusinessProfileService.php`
     - `fetchReviews(Business $business)` — GET from GBP API
     - `postReply(Business $business, string $reviewName, string $reply)` — PUT reply
     - `getAccessToken(Business $business)` — refresh if expired
   - Create `app/Jobs/SyncGoogleReviews.php`
     - Upsert into `reviews` table using `google_review_id` as unique key
     - Add columns to reviews: `google_review_id`, `google_review_name`, `google_reply`, `google_reply_posted_at`
   - Schedule: every 2 hours for businesses with `google_access_token` not null

3. AI REPLY POSTING UI — "Reviews" page with one-click approve + post to Google
   - The `ReviewReplyAgent` and `replySuggestions()` endpoint already exist and work
   - Build `resources/js/pages/reviews/index.tsx` with two sections:
     - "Needs Reply": reviews with no `google_reply` — show AI suggest button + editable textarea + Post button
     - "Replied": reviews with `google_reply` set — show collapsed reply
   - Add `POST reviews/{review}/reply` route → `ReviewController::postReply()`
     - Calls `GoogleBusinessProfileService::postReply()`
     - Updates `google_reply` and `google_reply_posted_at` on the review

4. FOLLOW-UP AUTOMATION — resend request to non-responders on day 5
   - Create `app/Jobs/SendFollowUpRequests.php`
   - Add `followed_up_at` timestamp to `review_requests` table
   - Query: status = sent/opened, created 5-6 days ago, followed_up_at = null
   - Send using the `followup` EmailTemplate
   - Schedule daily

5. STRIPE BILLING
   - Install `laravel/cashier`
   - Plans: Starter $49/mo (1 location, email requests), Pro $99/mo (5 locations, SMS)
   - Free plan: max 1 location, 50 customers, 10 requests/month
   - Add billing settings page with upgrade/manage buttons

Write Pest tests for all new controller actions. Follow existing code conventions exactly.
```

---

## App Overview

**What it does:** Helps local businesses get more Google reviews. Sends review request emails/SMS to customers, tracks who responded, lets owners reply to reviews with AI-suggested responses posted directly to Google.

**Who it's for:** Tradies, cafes, salons, gyms, healthcare, real estate — any local business that relies on Google reviews.

**Pricing:** Starter $49/mo (1 location), Pro $99/mo (up to 5 locations).

---

## Current State (as of 2026-03-03)

| Area | Status |
|------|--------|
| Auth (WorkOS) | ✅ Complete |
| Business / Customer models | ✅ Complete |
| Review request sending (email) | ✅ Complete |
| AI reply suggestions (Claude) | ✅ Complete — `ReviewReplyAgent` exists |
| CSV customer import | ✅ Complete |
| QR code page | ✅ Complete |
| Quick send | ✅ Complete |
| Email template editor | ✅ Complete |
| Onboarding wizard | ✅ Complete |
| Google Business Profile OAuth | ❌ Not built |
| Auto-sync reviews from Google | ❌ Not built |
| Post AI replies to Google | ❌ Not built |
| Follow-up automation (day 5) | ❌ Not built |
| Stripe billing | ❌ Not built |
| Landing page | ❌ Not built |

---

## What to Test Locally

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
composer run dev
```

1. Sign up → create a business → add customers
2. Send a review request → confirm email arrives
3. Open a review → click "Suggest Reply" → confirm 3 AI options appear
4. Import customers via CSV
5. Run tests: `./vendor/bin/pest --parallel`

---

## Environment Variables Needed

```env
# WorkOS (already set up for dev)
WORKOS_CLIENT_ID=
WORKOS_API_KEY=
WORKOS_REDIRECT_URL=

# Google Business Profile (needs setup)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/google/callback"

# AI (for review reply suggestions)
ANTHROPIC_API_KEY=

# Stripe (when billing is added)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_STARTER=
STRIPE_PRICE_PRO=
```
