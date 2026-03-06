# ReviewMate — Laravel Cloud Deployment Guide

> Last updated: 2026-03-06
> Stack: PHP 8.2+, Laravel 12, React + Inertia, PostgreSQL, database queue
> Tests: 229 passing — ready to deploy

---

## Prerequisites

Before starting, ensure you have accounts and credentials for:

| Service | Purpose | URL |
|---------|---------|-----|
| Laravel Cloud | Hosting | https://cloud.laravel.com |
| GitHub | Source code | https://github.com |
| WorkOS | Authentication (passwordless + SSO) | https://workos.com |
| Stripe | Billing (Cashier v16) | https://stripe.com |
| ClickSend | SMS — default, cheapest for AU | https://clicksend.com |
| Twilio | SMS — fallback provider | https://twilio.com |
| Resend or Mailgun | Transactional email | https://resend.com |
| Google Cloud Console | Business Profile OAuth + Places API | https://console.cloud.google.com |
| Anthropic | Claude Haiku (AI reply suggestions) | https://console.anthropic.com |

---

## Step 1: Create the Laravel Cloud Application

1. Go to [cloud.laravel.com](https://cloud.laravel.com) and sign in.
2. Click **New Application**.
3. Connect your GitHub account and select the `ReviewMate` repository.
4. Set branch to `main`.
5. Set environment to **Production**.
6. **Required plan: Growth** (~$20/month). The always-on queue worker is mandatory — scheduled jobs (Google sync every 2h, follow-ups daily, weekly digest) will not run on the Starter plan.

---

## Step 2: Configure the Database

1. In the Laravel Cloud project, go to **Databases** → **Add Database**.
2. Select **PostgreSQL**.
3. Recommended size: **1 vCPU / 1 GB RAM** for launch (upgrade as needed).
4. Laravel Cloud will automatically inject `DB_URL` into your environment — you do not need to set individual `DB_HOST`, `DB_PORT`, etc.
5. Set `DB_CONNECTION=pgsql` in environment variables (see Step 3).

---

## Step 3: Set Environment Variables

In the Laravel Cloud project → **Environment** → add all variables below.

### Core Application

```env
APP_NAME=ReviewMate
APP_ENV=production
APP_KEY=                          # Generate with: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_LOCALE=en

BCRYPT_ROUNDS=12
```

### Database

```env
DB_CONNECTION=pgsql
# DB_URL is injected automatically by Laravel Cloud — do not set manually
```

### Session, Cache, Queue

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

> Note: The app uses the database queue driver. Laravel Cloud's always-on worker will process the `default` queue. If you later switch to Redis, also update `QUEUE_CONNECTION=redis` and add Redis connection vars.

### Logging

```env
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Mail (choose Resend or Mailgun)

**Option A — Resend (recommended):**
```env
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=hello@reviewmate.app
MAIL_FROM_NAME="ReviewMate"
```

**Option B — Mailgun:**
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.reviewmate.app
MAILGUN_SECRET=key-...
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=hello@reviewmate.app
MAIL_FROM_NAME="ReviewMate"
```

### WorkOS (Authentication)

```env
WORKOS_CLIENT_ID=client_...         # From WorkOS dashboard → Applications → API Keys
WORKOS_API_KEY=sk_...               # From WorkOS dashboard → Applications → API Keys
WORKOS_REDIRECT_URL=https://yourdomain.com/authenticate
```

### Google (Business Profile OAuth + Places API)

```env
GOOGLE_CLIENT_ID=...                # From Google Cloud Console → OAuth 2.0 Client IDs
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback
GOOGLE_PLACES_API_KEY=...           # From Google Cloud Console → APIs & Services → Credentials
```

### Anthropic (AI reply suggestions)

```env
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_PROVIDER=anthropic
```

### Stripe (Billing)

```env
STRIPE_KEY=pk_live_...              # Publishable key
STRIPE_SECRET=sk_live_...           # Secret key
STRIPE_WEBHOOK_SECRET=whsec_...     # Set after creating webhook endpoint (see Step 7)
STRIPE_PRICE_STARTER=price_...      # $49/month AUD recurring price ID
STRIPE_PRICE_PRO=price_...          # $99/month AUD recurring price ID
```

### SMS — ClickSend (default, AU-optimised)

```env
SMS_DRIVER=clicksend
CLICKSEND_USERNAME=...              # ClickSend account username (email)
CLICKSEND_API_KEY=...               # From ClickSend dashboard → API Credentials
CLICKSEND_FROM=ReviewMate           # Sender ID (up to 11 alphanumeric chars for AU)
```

### SMS — Twilio (fallback, optional)

```env
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=+61...           # Your Twilio number in E.164 format
```

> To switch to Twilio as the primary provider, set `SMS_DRIVER=twilio`.

### Integration OAuth Credentials (optional — only needed if customers use these integrations)

```env
# ServiceM8 (OAuth — tradie job management)
SERVICEM8_CLIENT_ID=
SERVICEM8_CLIENT_SECRET=

# Xero (OAuth — invoice-paid triggers review request)
XERO_CLIENT_ID=
XERO_CLIENT_SECRET=
XERO_WEBHOOK_KEY=

# Timely (OAuth — salon appointment completion)
TIMELY_CLIENT_ID=
TIMELY_CLIENT_SECRET=

# Simpro (OAuth — large tradie businesses)
SIMPRO_CLIENT_ID=
SIMPRO_CLIENT_SECRET=

# Jobber (OAuth — field service management)
JOBBER_CLIENT_ID=
JOBBER_CLIENT_SECRET=

# Housecall Pro (OAuth — home service businesses)
HOUSECALLPRO_CLIENT_ID=
HOUSECALLPRO_CLIENT_SECRET=

# Cliniko (per-business API key, stored in DB — no app-level key needed)
# Halaxy (per-business API key, stored in DB — no app-level key needed)
```

### AWS S3 (optional — only if you enable file uploads)

```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-2
AWS_BUCKET=reviewmate-uploads
```

---

## Step 4: Configure Queue Workers

1. In Laravel Cloud → **Workers** → **Add Worker**.
2. Set the command:
   ```
   php artisan queue:work --sleep=3 --tries=3 --timeout=90
   ```
3. Set **Always On** — required so scheduled jobs can dispatch and process.
4. One worker is sufficient at launch. Add more if queue depth grows.

The app uses a single `default` queue. All jobs (Google sync, follow-ups, onboarding emails, integration processing) go to this queue.

---

## Step 5: Configure Scheduled Tasks

Laravel Cloud runs the scheduler automatically via its built-in cron. No additional configuration is needed beyond ensuring the scheduler is enabled.

Verify the schedule in `routes/console.php`:

| Job | Schedule | Purpose |
|-----|----------|---------|
| `SendFollowUpRequests` | Daily 09:00 | Day-5 follow-up emails to customers who haven't reviewed |
| `reviewmate:send-followups` | Daily 09:05 | Legacy command (backward compat) |
| `SendWeeklyDigests` | Mondays 08:00 | Weekly review digest to business owners |
| `SyncGoogleReviews` | Every 2 hours | Fetch new reviews from Google Business Profile API |
| `PollClinikoAppointments` | Daily 08:00 | Find completed Cliniko appointments → send review requests |
| `PollHalaxyAppointments` | Daily 08:00 | Find completed Halaxy appointments → send review requests |
| `RefreshGoogleStats` | Daily 06:00 | Update cached Google rating + review count for dashboard |

> All times are UTC. 09:00 UTC = 7pm AEST / 8pm AEDT.

---

## Step 6: Configure the Domain and SSL

1. In Laravel Cloud → **Domains** → **Add Domain**.
2. Enter your domain (e.g. `reviewmate.app` or `app.reviewmate.app`).
3. Follow the DNS instructions shown — typically a CNAME pointing to Laravel Cloud's edge.
4. SSL is provisioned automatically via Let's Encrypt.
5. Once the domain is verified, update `APP_URL` in environment variables.

---

## Step 7: Stripe Webhook Setup

### Create the webhook endpoint

1. Go to [Stripe Dashboard](https://dashboard.stripe.com) → **Developers** → **Webhooks** → **Add endpoint**.
2. Endpoint URL: `https://yourdomain.com/stripe/webhook`
3. Select these events:

   | Event | Purpose |
   |-------|---------|
   | `customer.subscription.created` | New subscription activated |
   | `customer.subscription.updated` | Plan change, renewal |
   | `customer.subscription.deleted` | Subscription cancelled |
   | `invoice.paid` | Payment confirmed |
   | `invoice.payment_failed` | Payment failure (Cashier handles retries) |
   | `payment_intent.succeeded` | One-off payment completed |

4. Click **Add endpoint**.
5. Copy the **Signing secret** (starts with `whsec_`).
6. Set `STRIPE_WEBHOOK_SECRET=whsec_...` in your Laravel Cloud environment variables.

> The webhook route `POST /stripe/webhook` is public (outside auth middleware) and CSRF-excluded by Cashier automatically.

### Create Stripe products and prices

1. In Stripe Dashboard → **Products** → **Add product**.
2. Create **ReviewMate Starter**:
   - Price: $49 AUD / month (recurring)
   - Copy the price ID (starts with `price_`)
   - Set `STRIPE_PRICE_STARTER=price_...`
3. Create **ReviewMate Pro**:
   - Price: $99 AUD / month (recurring)
   - Copy the price ID
   - Set `STRIPE_PRICE_PRO=price_...`

---

## Step 8: WorkOS Setup

### Configure redirect URIs

1. Log in to [WorkOS dashboard](https://dashboard.workos.com).
2. Go to **Applications** → select your ReviewMate application.
3. Under **Redirect URIs**, add:
   - `https://yourdomain.com/authenticate`
4. Remove any localhost URIs from production (or keep them for staging).

### Enable authentication methods

1. Go to **Authentication** → **AuthKit**.
2. Enable **Email magic links** (passwordless — recommended).
3. Optionally enable **Google OAuth**, **Apple**, or **Microsoft** SSO for user convenience.

### Get credentials

From **API Keys** in the WorkOS application:
- `WORKOS_CLIENT_ID` — starts with `client_`
- `WORKOS_API_KEY` — starts with `sk_`

---

## Step 9: Google Cloud Setup

### Google Business Profile OAuth (for syncing reviews)

1. Go to [Google Cloud Console](https://console.cloud.google.com) → **APIs & Services** → **Credentials**.
2. Create an **OAuth 2.0 Client ID** (Web application type).
3. Add authorised redirect URI: `https://yourdomain.com/google/callback`
4. Enable the **Google Business Profile API** in Library.
5. Copy `Client ID` → `GOOGLE_CLIENT_ID` and `Client Secret` → `GOOGLE_CLIENT_SECRET`.

### Google Places API (for stats and business search)

1. In the same project, go to **APIs & Services** → **Library**.
2. Enable **Places API**.
3. Create an API key (restrict to Places API for security).
4. Set `GOOGLE_PLACES_API_KEY=...`.

---

## Step 10: ClickSend SMS Setup

1. Sign up at [clicksend.com](https://clicksend.com).
2. Go to **Dashboard** → **API Credentials**.
3. Copy your **Username** (the email you signed up with) → `CLICKSEND_USERNAME`.
4. Copy your **API Key** → `CLICKSEND_API_KEY`.
5. Set `CLICKSEND_FROM=ReviewMate` — this is the sender ID shown to recipients.
   - For Australian numbers, alphanumeric sender IDs up to 11 characters are supported.
   - For international delivery, you may need a registered number instead.

ClickSend is ~35-40% cheaper than Twilio for Australian numbers (~$0.027/SMS AU vs ~$0.04).

---

## Post-Deploy Checklist

### Immediately after first deploy

```bash
# 1. Run migrations
php artisan migrate --force

# 2. Verify the app responds
curl https://yourdomain.com/up
# Expected: 200 OK

# 3. Test the queue worker is alive
php artisan queue:work --once
# Should process any pending jobs and exit

# 4. Test the scheduler
php artisan schedule:run
# Should output scheduled tasks without errors
```

### Grant superadmin access

After logging in via WorkOS for the first time:

```bash
php artisan tinker
User::where('email', 'your@email.com')->first()->update([
    'is_admin' => true,
    'role' => 'superadmin',
]);
```

The Filament admin panel is accessible at `/admin`.

### Verify key flows end-to-end

1. **Registration + Onboarding:** Register via WorkOS magic link → complete business type step → connect Google (or skip) → select email template → land on dashboard.
2. **Add a customer:** Go to Customers → Add Customer → fill in name + email/phone.
3. **Send a review request:** Quick Send → enter details → Send. Check email is received.
4. **Tracking link:** Click `/r/{token}` link in the email → confirm status changes to `opened`.
5. **Billing:** Go to Settings → Billing → Subscribe → complete Stripe Checkout → confirm subscription active.
6. **Stripe webhook:** In Stripe Dashboard → Webhooks → your endpoint → send a test event.
7. **Google sync (if connected):** Dispatch `SyncGoogleReviews` manually from Tinker and confirm reviews appear.

### Configure integration OAuth redirect URIs

For each OAuth integration you have credentials for, add the callback URL to the respective developer portal:

| Integration | Redirect URI |
|-------------|-------------|
| ServiceM8 | `https://yourdomain.com/integrations/servicem8/callback` |
| Xero | `https://yourdomain.com/integrations/xero/callback` |
| Timely | `https://yourdomain.com/integrations/timely/callback` |
| Simpro | `https://yourdomain.com/integrations/simpro/callback` |
| Jobber | `https://yourdomain.com/integrations/jobber/callback` |
| Housecall Pro | `https://yourdomain.com/integrations/housecallpro/callback` |
| Google Business | `https://yourdomain.com/google/callback` |
| WorkOS | `https://yourdomain.com/authenticate` |

### Public webhook URLs (share with integration partners / customers)

These are the inbound webhook endpoints businesses will configure in third-party systems. The `{business_uuid}` is each business's UUID from the `businesses` table.

| Integration | Webhook URL |
|-------------|-------------|
| ServiceM8 | `https://yourdomain.com/webhooks/servicem8/{business_uuid}` |
| Xero | `https://yourdomain.com/webhooks/xero/{business_uuid}` |
| Timely | `https://yourdomain.com/webhooks/timely/{business_uuid}` |
| Simpro | `https://yourdomain.com/webhooks/simpro/{business_uuid}` |
| Jobber | `https://yourdomain.com/webhooks/jobber/{business_uuid}` |
| Housecall Pro | `https://yourdomain.com/webhooks/housecallpro/{business_uuid}` |
| Generic webhook | `https://yourdomain.com/webhooks/incoming/{token}` |

The generic webhook token is auto-generated per business and visible in Settings → Integrations.

---

## Monitoring

### Laravel Cloud built-in

- **Logs:** Project → Logs tab. Filter by level (error, warning).
- **Queue health:** Project → Workers → shows queue depth and recent job throughput.
- **Deploy history:** Project → Deployments → redeploy any previous version in one click.
- **Alerts:** Set up email alerts in Laravel Cloud for worker failures and job exceptions.

### Laravel Nightwatch (optional)

If you want detailed error tracking and performance monitoring:

```env
NIGHTWATCH_TOKEN=...   # From Nightwatch dashboard
```

Add the Nightwatch package and configure per the Nightwatch docs.

### What to monitor

| Signal | Action |
|--------|--------|
| Queue depth growing | Increase worker count in Laravel Cloud |
| `SyncGoogleReviews` failing | Check Google OAuth token expiry; reconnect in Settings → Integrations |
| `SendFollowUpRequests` failing | Check mail config and RESEND_API_KEY / Mailgun credentials |
| Stripe webhook 400s | Verify `STRIPE_WEBHOOK_SECRET` matches Stripe dashboard |
| High SMS failure rate | Check ClickSend credit balance and sender ID approval |

### Manual health checks

```bash
# Confirm app is up
curl https://yourdomain.com/up

# Check queue
php artisan queue:failed        # List failed jobs
php artisan queue:retry all     # Retry all failed jobs
php artisan queue:flush         # Delete all failed jobs (after investigation)

# Force run the scheduler
php artisan schedule:run

# Force sync Google reviews for a specific business
php artisan tinker
App\Jobs\SyncGoogleReviews::dispatch(Business::find(1));

# Re-run follow-ups manually
php artisan tinker
App\Jobs\SendFollowUpRequests::dispatchSync();
```

---

## Rollback Procedure

### Code rollback

1. In Laravel Cloud → **Deployments** → find the last working deployment.
2. Click **Redeploy** on that version.
3. Laravel Cloud will redeploy that exact commit without running new migrations.

### Migration rollback

If a new migration broke the schema:

```bash
php artisan migrate:rollback      # Rolls back the most recent batch
php artisan migrate:rollback --step=2  # Rolls back 2 batches
```

> Warning: Rolling back migrations that dropped columns or tables can result in data loss. Always take a database snapshot before deploying schema changes.

### Database snapshot (before major deploys)

```bash
pg_dump $DB_URL > backup-$(date +%Y%m%d-%H%M).sql
```

Laravel Cloud also provides automated daily PostgreSQL backups. Check the database section of the dashboard to restore from a point-in-time backup if needed.

### Emergency: disable outgoing communication

If a bug is causing mass emails or SMS:

```env
# Disable all email sending (logs instead)
MAIL_MAILER=log

# Disable all SMS sending
SMS_DRIVER=log
```

Set these in Laravel Cloud environment variables and redeploy (or run `php artisan config:clear` if the worker picks up changes).

---

## Full Environment Variable Reference

The complete list of environment variables with descriptions:

```env
# ============================================================
# APPLICATION
# ============================================================
APP_NAME=ReviewMate
APP_ENV=production
APP_KEY=                          # Required. Run: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_LOCALE=en
BCRYPT_ROUNDS=12

# ============================================================
# DATABASE (Laravel Cloud injects DB_URL automatically)
# ============================================================
DB_CONNECTION=pgsql

# ============================================================
# SESSION / CACHE / QUEUE
# ============================================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

# ============================================================
# LOGGING
# ============================================================
LOG_CHANNEL=stack
LOG_LEVEL=error

# ============================================================
# MAIL
# ============================================================
MAIL_MAILER=resend                 # or: mailgun
RESEND_API_KEY=re_...
# MAILGUN_DOMAIN=
# MAILGUN_SECRET=
MAIL_FROM_ADDRESS=hello@reviewmate.app
MAIL_FROM_NAME="ReviewMate"

# ============================================================
# WORKOS AUTH
# ============================================================
WORKOS_CLIENT_ID=client_...
WORKOS_API_KEY=sk_...
WORKOS_REDIRECT_URL=https://yourdomain.com/authenticate

# ============================================================
# GOOGLE
# ============================================================
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback
GOOGLE_PLACES_API_KEY=

# ============================================================
# ANTHROPIC (AI reply suggestions)
# ============================================================
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_PROVIDER=anthropic

# ============================================================
# STRIPE
# ============================================================
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_STARTER=price_...    # $49/month AUD
STRIPE_PRICE_PRO=price_...        # $99/month AUD

# ============================================================
# SMS — CLICKSEND (default, AU-optimised)
# ============================================================
SMS_DRIVER=clicksend
CLICKSEND_USERNAME=
CLICKSEND_API_KEY=
CLICKSEND_FROM=ReviewMate

# ============================================================
# SMS — TWILIO (fallback, set SMS_DRIVER=twilio to use)
# ============================================================
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM_NUMBER=

# ============================================================
# INTEGRATIONS — OAUTH (optional, add as needed)
# ============================================================
SERVICEM8_CLIENT_ID=
SERVICEM8_CLIENT_SECRET=

XERO_CLIENT_ID=
XERO_CLIENT_SECRET=
XERO_WEBHOOK_KEY=

TIMELY_CLIENT_ID=
TIMELY_CLIENT_SECRET=

SIMPRO_CLIENT_ID=
SIMPRO_CLIENT_SECRET=

JOBBER_CLIENT_ID=
JOBBER_CLIENT_SECRET=

HOUSECALLPRO_CLIENT_ID=
HOUSECALLPRO_CLIENT_SECRET=

# Cliniko and Halaxy: per-business API keys stored in DB — no app-level keys needed

# ============================================================
# NIGHTWATCH (optional error tracking)
# ============================================================
NIGHTWATCH_TOKEN=
```
