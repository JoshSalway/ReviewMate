# ReviewMate — Agent Instructions

## Copy and paste this prompt to start a session:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md in this repo for full context on conventions and current state.

Current completion: ~90%. Core product is fully functional. Remaining tasks are polish/UX.

Your priority tasks in order:

1. REVIEW TRACKING LINK — embed a unique token in review request emails
   - Add `tracking_token` (uuid) to `review_requests` table (migration)
   - Generate token in ReviewRequestController::store (Str::uuid())
   - Add route: GET `/r/{token}` → ReviewRequestController::track
     - Find ReviewRequest by token, update status to 'opened', redirect to Google review link
   - Embed link in email templates: `{{ url('/r/' . $reviewRequest->tracking_token) }}`

2. GOOGLE PLACE ID INPUT — let businesses enter their Google Place ID in settings
   - Already have `google_place_id` column on businesses table
   - Add a text input to `resources/js/pages/settings/business.tsx` for Place ID
   - Update BusinessSettingsController to accept and save it
   - Show a helper link: "Find your Place ID at developers.google.com/maps/documentation/places/web-service/place-id"

3. EXPORT CUSTOMERS TO CSV
   - Add route: GET `/customers/export` → CustomerController::export
   - Stream CSV with headers: Name, Email, Phone, Created At, Last Request Sent, Reviews Left
   - Use Laravel's StreamedResponse

4. PAGINATE REVIEWS INDEX
   - `reviews/index.tsx` currently loads all reviews — add server-side pagination
   - Use Laravel's `paginate(20)` and Inertia's built-in pagination links component

Write Pest tests for all new controller actions. Follow existing code conventions exactly.
```

---

## App Overview

**What it does:** Helps local businesses get more Google reviews. Sends review request emails/SMS to customers, tracks who responded, lets owners reply to reviews with AI-suggested responses posted directly to Google.

**Who it's for:** Tradies, cafes, salons, gyms, healthcare, real estate — any local business that relies on Google reviews.

**Pricing:** Free (1 location, 50 customers, 10 req/mo), Starter $49/mo (unlimited), Pro $99/mo (5 locations).

---

## Current State (as of 2026-03-03)

| Area | Status |
|------|--------|
| Auth (WorkOS) | ✅ Complete |
| Business / Customer models | ✅ Complete |
| Review request sending (email + SMS) | ✅ Complete |
| AI reply suggestions (Claude) | ✅ Complete |
| CSV customer import | ✅ Complete |
| QR code page | ✅ Complete |
| Quick send | ✅ Complete |
| Email template editor | ✅ Complete |
| Onboarding wizard | ✅ Complete |
| Google Business Profile OAuth | ✅ Complete |
| Auto-sync reviews from Google | ✅ Complete |
| Post AI replies to Google | ✅ Complete |
| Follow-up automation (day 5) | ✅ Complete |
| Stripe billing (Cashier) | ✅ Complete |
| Stripe webhook listeners | ✅ Complete |
| Notification settings | ✅ Complete |
| Multi-location analytics | ✅ Complete |
| Saved reply templates | ✅ Complete |
| Bulk send to multiple customers | ✅ Complete |
| Weekly digest email | ✅ Complete |
| Review → request status sync | ✅ Complete |
| Waitlist landing page | ✅ Complete |
| Review tracking link (open pixel) | ❌ Not built |
| Google Place ID input in settings | ❌ Not built |
| Export customers to CSV | ❌ Not built |
| Paginate reviews/index | ❌ Not built |

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

# Google Business Profile
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/google/callback"

# AI (for review reply suggestions)
ANTHROPIC_API_KEY=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_STARTER=
STRIPE_PRICE_PRO=

# Twilio (optional — SMS sending)
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=
```
