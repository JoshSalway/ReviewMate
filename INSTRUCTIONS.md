# ReviewMate — Agent Instructions

> Last updated: 2026-03-04
> Completion: ~100% code — deploy only remaining

## ✅ CODE COMPLETE — READY TO DEPLOY

All features are built and tested (104 tests passing, CI passing). The only remaining work is
production deployment and acquiring first paying customers.

---

## 🤖 AGENT TASK — DO THIS NOW

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md for full conventions.

The app is code-complete. Your task is to add Terms of Service and Privacy Policy pages — required for Stripe and user trust.

1. TERMS OF SERVICE — GET /terms
   - Add route in routes/web.php (no auth required)
   - Create resources/js/pages/terms.tsx (follow existing page patterns)
   - Write real Terms of Service for a review management SaaS for local businesses. Include:
     acceptance, service description (SMS/email review requests, Google integration), user
     responsibilities (must have customer consent before sending SMS/email), Twilio SMS usage,
     Stripe payment terms, cancellation policy, IP ownership, limitation of liability,
     governing law: Queensland, Australia.

2. PRIVACY POLICY — GET /privacy
   - Same pattern as terms
   - Cover: data collected (business info, customer contact details, Google Business data),
     how used, third parties (WorkOS, Stripe, Twilio SMS, Google Business Profile API, Mailgun,
     Anthropic AI), data retention, SMS compliance (SPAM Act 2003 Australia), contact email.

3. FOOTER
   - Add footer to main authenticated layout and landing page: "© 2025 ReviewMate · Terms · Privacy"

4. AUTH PAGES
   - Add "By signing up you agree to our Terms and Privacy Policy" with links on register page.

Write Pest tests confirming GET /terms and GET /privacy return 200.
Run ./vendor/bin/pest --parallel before finishing.
```

---

## Copy and paste this prompt to start a general session:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md for full conventions.

The app is code-complete (104 tests passing, CI passing). Your only task is production deployment:

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
| Google Place ID auto-discovery (onboarding) | ✅ Done |
| Branded error pages (403, 404, 500) | ✅ Done |
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
