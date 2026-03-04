# ReviewMate

ReviewMate is a **Google review management SaaS for local businesses** (cafes, tradies, salons, gyms, etc.). It automates the process of asking customers for Google reviews, tracks whether they left one, syncs reviews from Google Business Profile, and lets owners reply with AI-generated suggestions — all from a single dashboard.

**Target market:** Australian small businesses that want more Google reviews without the manual follow-up work.

**Pricing:**
- Free — 1 business, 50 customers, 10 review requests/month
- Starter ($49/mo) — 1 business, unlimited customers/requests
- Pro ($99/mo) — up to 5 businesses, unlimited everything

---

## How It Works

### Core Flow
1. **Owner adds customers** (manually, via CSV import, or QR code scan)
2. **Owner sends a review request** (email, SMS, or both) — ReviewMate emails/texts the customer with a personalised message and a tracking link
3. **Customer clicks the link** → ReviewMate marks the request as "opened" → redirects them to the Google review page
4. **If no response in 5 days** → automated follow-up email is sent
5. **Google reviews sync automatically every 2 hours** via the Google Business Profile API
6. **Owner replies to reviews** in ReviewMate — Claude AI generates 3 reply suggestions, owner picks one and posts it directly to Google

### Key Features
- Review request sending via email (queued) and SMS (Twilio)
- Tracking links to detect opens (CAN-SPAM compliant with unsubscribe support)
- 5-day automated follow-up with 30-day resend guard (won't re-send to same customer within 30 days)
- Google Business Profile OAuth — connects the business's Google account
- Auto-sync reviews from Google every 2 hours
- AI reply suggestions (Claude / Anthropic) — 3 options per review
- Post replies to Google directly from the app
- Customisable email templates per business type
- Saved reply templates for common responses
- CSV import/export of customers
- Bulk send to multiple customers at once
- QR code page customers can scan to leave a review directly
- Quick send (one-off send without adding customer first)
- Analytics dashboard — avg rating, conversion rate, requests sent/opened/reviewed
- Multi-location analytics for Pro users
- Weekly digest email to owners (Monday 08:00)
- Waitlist landing page at `/`

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend | React 19 + Inertia.js v2 + TypeScript |
| Styling | Tailwind CSS v4 + shadcn/ui |
| Auth | WorkOS (`laravel/workos`) |
| AI | Claude via `laravel/ai` (Anthropic) |
| Billing | Stripe via `laravel/cashier` |
| SMS | Twilio |
| Email | Mailgun / Resend / any Laravel mail driver |
| Google API | `laravel/socialite` for OAuth + raw GBP API calls |
| DB (dev) | SQLite |
| DB (prod) | PostgreSQL |
| Routes | Laravel Wayfinder (generates TypeScript route functions) |
| Testing | Pest 4 — 99 tests, all passing |

---

## Architecture

### Multi-tenancy
- `User` → has many `Business` models
- Current business tracked in session (`current_business_id`)
- All controllers scope every query to `request->user()->currentBusiness()`
- Pro plan allows up to 5 businesses; Free/Starter limited to 1

### Models
- `User` — WorkOS auth, `Billable` (Stripe), `is_admin` flag bypasses all plan limits
- `Business` — name, type, Google credentials (encrypted), Place ID, onboarding status
- `Customer` — belongs to Business; has `unsubscribed_at`, `unsubscribe_token`
- `ReviewRequest` — belongs to Business + Customer; tracks status (`sent` → `opened` → `reviewed`), channel, tracking token
- `Review` — synced from Google; has `google_review_name`, `google_reply`, `google_reply_posted_at`
- `EmailTemplate` — customisable per business, rendered with `{{variable}}` substitution
- `ReplyTemplate` — saved reply snippets for owners

### Key Services
- `app/Services/GoogleBusinessProfileService.php` — all GBP API calls (sync reviews, post replies, token refresh)
- `app/Services/TwilioSmsService.php` — SMS sending with `isConfigured()` guard
- `app/Services/DefaultTemplateService.php` — seeds default email templates by business type
- `app/Ai/Agents/ReviewReplyAgent.php` — prompts Claude to return 3 reply options as a JSON array

### Background Jobs (queue required)
| Job | Trigger |
|-----|---------|
| `ReviewRequestMail` | Queued immediately on send |
| `SyncGoogleReviews` | Scheduler — every 2 hours |
| `SendFollowUpRequests` | Scheduler — daily 09:00 |
| `SendWeeklyDigests` | Scheduler — Monday 08:00 |

### Onboarding (3-step wizard)
1. Business type selection (seeds default email template)
2. Connect Google Business Profile (OAuth)
3. Select/customise email template

All authenticated routes redirect to onboarding if `business.onboarding_completed_at` is null.

---

## Running Locally

### Prerequisites
- PHP 8.3+, Composer, Node.js 20+, SQLite

### Setup

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
composer run dev        # starts Laravel + Vite concurrently
```

App runs at `http://localhost:8000`.

### Tests

```bash
./vendor/bin/pest --parallel
```

### Queue worker (needed for email sending and Google sync)

```bash
php artisan queue:work
```

---

## Hosting

**Recommended: [Laravel Cloud](https://cloud.laravel.com)** — handles workers, scheduler, and PostgreSQL automatically.

Alternative: **Laravel Forge** on any VPS (DigitalOcean, Hetzner, etc.).

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the full deployment checklist including:
- All environment variables
- Stripe product + webhook setup
- Google Cloud Console OAuth configuration
- WorkOS redirect URI update
- Making yourself an admin user

### Required External Services

| Service | Purpose |
|---------|---------|
| WorkOS | Authentication (SSO, magic links) |
| Google Cloud Console | Google Business Profile OAuth + API |
| Anthropic | AI reply suggestions |
| Stripe | Subscriptions + billing |
| Mailgun / Resend | Transactional email |
| Twilio | SMS (optional) |

---

## Important Conventions for AI Agents

- **Business scoping** — every DB query must go through `$request->user()->currentBusiness()`. Never query `Review`, `Customer`, `ReviewRequest` etc. without scoping to the current business.
- **Admin users** — `User::isAdmin()` returns true when `is_admin = 1`. Admins bypass all plan limits. Never apply billing checks to admins.
- **Plan limits** — enforced in controllers, not middleware. Free plan: 1 business, 50 customers, 10 requests/month.
- **Google tokens** — stored encrypted on `Business`. Always use `GoogleBusinessProfileService` for API calls (handles token refresh).
- **Queue** — emails are always queued (`ShouldQueue`), never sent synchronously.
- **Wayfinder routes** — use generated TypeScript route functions from `resources/js/routes/` on the frontend instead of hardcoded URLs.
- **Onboarding gate** — `isOnboardingComplete()` check is in `ReviewController::index()` and `DashboardController`. If false, redirect to `onboarding.business-type`.
- **CAN-SPAM** — every outbound email must include the unsubscribe link. Check `ReviewRequestMail` and `FollowUpMail` for the pattern.
- **30-day guard** — `ReviewRequest::hasRecentRequest($businessId, $customerId)` prevents re-sending within 30 days. Always call this before sending.
