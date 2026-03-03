# ReviewMate — Agent Instructions

> Last updated: 2026-03-04
> Completion: ~95% code — 2 fixes needed, then deploy

## ⚠️ TWO FIXES BEFORE DEPLOY

A code audit found 2 UX/polish issues. Fix these before deploying.

---

## Copy and paste this prompt to fix the pre-deploy issues:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md for full conventions.

Two issues were found in a code audit. Fix both:

1. GOOGLE PLACE ID UX
   - During onboarding step 2, users connect their Google Business Profile via OAuth, but then
     are still required to manually find and enter their Place ID — this feels broken and confusing.
   - After a successful OAuth connection, attempt to auto-discover the Place ID from the Google
     Business Profile API using the authenticated token. If exactly one location is found,
     pre-fill the Place ID field automatically. If multiple locations are found, show a dropdown
     of their locations to choose from rather than a blank text field.
   - If the API call fails or returns no locations, fall back gracefully to the existing manual
     text input with a help link explaining how to find a Place ID.
   - The Google Business Profile API endpoint for listing locations is:
     GET https://mybusinessbusinessinformation.googleapis.com/v1/accounts/{accountId}/locations
     The accountId is already stored after OAuth — use it here.

2. MISSING BRANDED ERROR PAGES
   - No custom error pages exist — users see raw Laravel error screens.
   - Create resources/views/errors/403.blade.php, 404.blade.php, and 500.blade.php.
   - Match the app's existing design (use Tailwind classes consistent with the app's style).
   - Each page: app name/logo at top, friendly error title, short message, link back to dashboard.

Write tests for any new controller logic. Run ./vendor/bin/pest --parallel before finishing.
```

---

## Copy and paste this prompt to start a general session:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md for full conventions.

The app is code-complete (99 tests passing, CI passing). Your only task is production deployment:

1. Follow the checklist in DEPLOYMENT.md
2. Run: php artisan test — confirm 99 tests pass
3. Deploy to Laravel Cloud or Forge
4. Make yourself an admin user (see DEPLOYMENT.md)
5. Create Stripe products and register webhook
6. Update Google OAuth redirect URI to production URL
7. Ship it
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
| Google Place ID input in settings | ✅ Done |
| Email unsubscribe (CAN-SPAM compliance) | ✅ Done |
| 30-day resend guard | ✅ Done |
| Security audit (all routes scoped) | ✅ Done |
| CI passing (PHP lint + TypeScript) | ✅ Done |
| **Deployed to production** | ❌ **#1 priority — see DEPLOYMENT.md** |

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
- [ ] First deploy run → 99 tests pass

---

## After Deploy — First Customers (Owner)

The app charges money — but only if people use it. After deploying:

1. **Use Outpost** to find local businesses (cafes, tradies, salons, gyms) → cold email them
2. **Pricing:** Offer first 5 customers a discounted rate ($29/mo instead of $49) for feedback
3. **Onboard manually** — jump on a call, connect their Google Business Profile for them
4. **Target:** 10 paying customers = ~$490/month. Enough to validate before scaling.

---

## Test Commands

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
composer run dev

./vendor/bin/pest --parallel
```
