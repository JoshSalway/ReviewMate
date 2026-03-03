# ReviewMate — Agent Instructions

> Last updated: 2026-03-03
> Completion: ~100% code — deploy only remaining

## ✅ CODE COMPLETE — READY TO DEPLOY

All features are built and tested (99 tests passing). The only remaining task is production deployment. See DEPLOYMENT.md for the full checklist.

---

## Copy and paste this prompt to start a session:

```
You are working on ReviewMate — a Google review management SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, SQLite (dev).

Read AGENTS.md for full conventions.

The app is code-complete. Your only task is production deployment:

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
| **Deployed to production** | ❌ **This is the #1 priority — see DEPLOYMENT.md** |

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
