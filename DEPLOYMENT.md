# ReviewMate — Deployment Guide

## Recommended Hosting

**Laravel Cloud** (simplest) or **Laravel Forge + any VPS**.

---

## Prerequisites

| Service | Purpose | Required |
|---------|---------|----------|
| WorkOS | Authentication | Yes |
| Google Cloud Console | Google Business Profile OAuth | Yes (for review sync) |
| Anthropic API | AI reply suggestions | Yes |
| Stripe | Billing/subscriptions | Yes |
| Mail (Mailgun / Resend / SES) | Transactional email | Yes |
| Twilio | SMS review requests | Optional |

---

## Environment Variables

Copy `.env.example` to `.env` and fill in every value:

```env
APP_NAME=ReviewMate
APP_ENV=production
APP_KEY=                          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (Laravel Cloud sets DB_URL automatically)
DB_CONNECTION=pgsql
DB_URL=postgresql://user:pass@host:5432/reviewmate

# Sessions/Cache/Queue — use database driver in production
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Mail — Mailgun example
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=key-...
MAIL_FROM_ADDRESS=hello@yourdomain.com
MAIL_FROM_NAME="ReviewMate"

# WorkOS
WORKOS_CLIENT_ID=client_...
WORKOS_API_KEY=sk_...
WORKOS_REDIRECT_URL=https://yourdomain.com/authenticate

# Google Business Profile OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback

# Anthropic (AI reply suggestions)
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_PROVIDER=anthropic

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_STARTER=price_...      # $49/mo product price ID
STRIPE_PRICE_PRO=price_...          # $99/mo product price ID

# Twilio (optional — SMS sending)
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=+1...
```

---

## Deploy Steps

```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Run migrations
php artisan migrate --force

# 3. Clear and warm caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Storage link (if using local disk)
php artisan storage:link
```

---

## Queue Workers

The following features require a queue worker running:

| Feature | Job/Mail | Trigger |
|---------|----------|---------|
| Review request emails | `ReviewRequestMail` | Sending a request |
| Follow-up emails | `FollowUpMail` | Sending a request |
| Weekly digest emails | `SendWeeklyDigests` | Scheduler — Mondays 08:00 |
| Google review sync | `SyncGoogleReviews` | Scheduler — every 2 hours |
| Follow-up automation | `SendFollowUpRequests` | Scheduler — daily 09:00 |
| Stripe confirmation emails | `SubscriptionConfirmedMail` | Stripe webhook |

**Start a worker:**

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

On Laravel Cloud, enable the worker in the dashboard.
On Forge, add a queue worker daemon pointing to the same command.

---

## Scheduler

The task scheduler must run every minute. Add to cron:

```cron
* * * * * cd /path/to/reviewmate && php artisan schedule:run >> /dev/null 2>&1
```

On Laravel Cloud, the scheduler runs automatically.

---

## Stripe Setup

1. Create two products in the Stripe dashboard:
   - **ReviewMate Starter** — $49/mo recurring → copy the Price ID to `STRIPE_PRICE_STARTER`
   - **ReviewMate Pro** — $99/mo recurring → copy to `STRIPE_PRICE_PRO`

2. Register a webhook in the Stripe dashboard pointing to:
   ```
   https://yourdomain.com/stripe/webhook
   ```
   Events to listen for:
   - `customer.subscription.created`
   - `customer.subscription.deleted`
   - `customer.subscription.updated`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`

3. Copy the webhook signing secret to `STRIPE_WEBHOOK_SECRET`.

---

## Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com) → APIs & Services → Credentials
2. Create an OAuth 2.0 Client ID (Web application)
3. Add authorised redirect URI: `https://yourdomain.com/google/callback`
4. Enable the **Google My Business API** and **Business Profile API** for the project
5. Copy Client ID and Secret to env

---

## WorkOS Setup

1. In the WorkOS dashboard, update the redirect URI to `https://yourdomain.com/authenticate`
2. Ensure the domain is verified

---

## First Admin User

After first deploy, make yourself an admin so you bypass billing restrictions:

```bash
php artisan tinker
>>> App\Models\User::where('email', 'you@example.com')->first()->update(['is_admin' => true]);
```

---

## Health Check

After deploying, verify:

- [ ] `GET /` returns the waitlist landing page
- [ ] Auth flow works (WorkOS login)
- [ ] Can create a business and add customers
- [ ] Can send a review request (email arrives)
- [ ] Google Business Profile OAuth connects successfully
- [ ] Stripe checkout works (use test mode first)
- [ ] `php artisan queue:work` processes queued jobs
- [ ] `php artisan schedule:run` runs without errors
