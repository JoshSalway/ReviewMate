# ReviewMate — Agent Instructions

> Last updated: 2026-03-03
> Completion: ~95%

## ⚠️ NEARLY DONE — ONE FEATURE THEN DEPLOY

One small feature remaining. After that: no more code. The goal is production deploy and first paying customer.

---

## Copy and paste this prompt to start a session:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md for full conventions.

One feature remaining, then production hardening:

1. GOOGLE PLACE ID INPUT — let businesses enter their Google Place ID manually in settings
   - The `google_place_id` column already exists on the `businesses` table
   - Add a labelled text input to `resources/js/pages/settings/business.tsx`
   - Update `BusinessSettingsController` to accept and save `google_place_id`
   - Show a helper link: "Find your Place ID → developers.google.com/maps/documentation/places/web-service/place-id"
   - This lets businesses link their Google listing without full OAuth if they prefer

2. PRODUCTION AUDIT — security and correctness check
   - Confirm all routes inside auth middleware scope to `$request->user()->currentBusiness()`
   - No business can access another business's customers, reviews, or requests
   - Check all FormRequests for authorization
   - Run: ./vendor/bin/pest --parallel — fix any failing tests

3. DEPLOY PREP
   - Review DEPLOYMENT.md — confirm deploy script is correct
   - Verify `.env.example` documents every variable the app uses
   - Confirm queue workers are needed (follow-up jobs, weekly digest, Google sync) and documented

Do not add new features. Write a Pest test for the Google Place ID update action.
```

---

## Current State

| Area | Status |
|------|--------|
| Auth (WorkOS) | ✅ Done |
| Business / Customer models | ✅ Done |
| Review request sending (email + SMS via Twilio) | ✅ Done |
| Review request tracking link (token → status update) | ✅ Done |
| AI reply suggestions (Claude) | ✅ Done |
| Post AI replies to Google | ✅ Done |
| Auto-sync reviews from Google (every 2hrs) | ✅ Done |
| Google Business Profile OAuth | ✅ Done |
| Follow-up automation (day 5 resend) | ✅ Done |
| CSV customer import | ✅ Done |
| CSV customer export | ✅ Done |
| QR code page | ✅ Done |
| Quick send | ✅ Done |
| Bulk send to multiple customers | ✅ Done |
| Email template editor | ✅ Done |
| Saved reply templates | ✅ Done |
| Onboarding wizard | ✅ Done |
| Multi-location analytics | ✅ Done |
| Weekly digest email | ✅ Done |
| Stripe billing + webhooks | ✅ Done |
| Waitlist landing page | ✅ Done |
| Reviews index pagination | ✅ Done |
| Google Place ID input in settings | ❌ Not built |
| **Deployed to production** | ❌ **Not yet — this is the #1 priority after Place ID** |

---

## Deploy Checklist (Owner — not agent)

- [ ] Laravel Cloud or Forge account set up
- [ ] Domain bought and pointing to host
- [ ] All env vars configured in production
- [ ] Google OAuth redirect URI updated to production URL
- [ ] Stripe products created → price IDs in env
- [ ] Stripe webhook registered → signing secret in env
- [ ] Twilio credentials configured (for SMS)
- [ ] Mailgun/Resend configured for email
- [ ] Queue workers configured (needed for Google sync + follow-ups + weekly digest)
- [ ] First deploy run

---

## Test Commands

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
composer run dev

./vendor/bin/pest --parallel
```
