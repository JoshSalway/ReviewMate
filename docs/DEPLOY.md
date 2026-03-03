# Deploying ReviewMate to Laravel Cloud

## Prerequisites
- Account at [cloud.laravel.com](https://cloud.laravel.com)
- GitHub repo connected: `https://github.com/JoshSalway/ReviewMate`
- API keys ready (see below)

---

## Step 1 — Create the application on Laravel Cloud

1. Go to [cloud.laravel.com](https://cloud.laravel.com) → **New Application**
2. Connect your GitHub repo: `JoshSalway/ReviewMate`
3. Set **branch**: `main`
4. Set **PHP version**: `8.4`
5. Set **build command**: `npm ci && npm run build`
6. Set **deploy command**: `php artisan migrate --force`

---

## Step 2 — Add a Serverless Postgres database

In the app's **Resources** tab:
- Add **Serverless Postgres** (supports auto-hibernation → cheaper when idle)
- Laravel Cloud will inject `DB_URL` automatically — no manual DB config needed

---

## Step 3 — Enable Queue Worker + Scheduler

In the app's **Clusters** tab:
- **App cluster**: enable **Scheduler** toggle (runs `schedule:run` every minute)
- Add a **Worker cluster**: command `php artisan queue:work --tries=3`

---

## Step 4 — Set environment variables

Go to **Environment** → add these variables:

```
APP_NAME=ReviewMate
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.laravel.cloud   # update after first deploy

# Auth
WORKOS_CLIENT_ID=client_...
WORKOS_API_KEY=sk_...
WORKOS_REDIRECT_URL=https://your-app.laravel.cloud/authenticate

# AI
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_PROVIDER=anthropic

# Google Business Profile
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://your-app.laravel.cloud/google/callback

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_STARTER=price_...
STRIPE_PRICE_PRO=price_...

# Mail (use Resend, Postmark, or SES for production)
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=hello@yourdomain.com
MAIL_FROM_NAME=ReviewMate
```

> Laravel Cloud automatically sets `DB_URL`, `CACHE_URL`, and `QUEUE_CONNECTION` when you attach Postgres — you don't need to set those manually.

---

## Step 5 — Deploy

Click **Deploy** (or push to `main` — auto-deploy is enabled by default).

Laravel Cloud will:
1. Pull the repo
2. Run `composer install --optimize-autoloader`
3. Run `npm ci && npm run build`
4. Run `php artisan migrate --force`
5. Swap traffic with zero downtime

---

## Step 6 — After first deploy

1. **Get your app URL** from the Cloud dashboard (e.g. `https://reviewmate-abc123.laravel.cloud`)
2. Update `APP_URL` and `WORKOS_REDIRECT_URL` and `GOOGLE_REDIRECT_URI` with the real URL
3. Update WorkOS redirect URL in your WorkOS dashboard
4. Update Google OAuth redirect URI in Google Cloud Console
5. **Make yourself admin** — run in the Cloud console/terminal:
   ```bash
   php artisan tinker --execute="App\Models\User::where('email', 'YOUR_EMAIL')->update(['is_admin' => true])"
   ```

---

## Step 7 — Custom domain (optional)

In **Domains** tab → Add your domain → Follow DNS instructions → TLS auto-provisioned.

---

## Stripe Webhooks

In your Stripe dashboard → Webhooks → Add endpoint:
- URL: `https://your-domain.com/stripe/webhook`
- Events to listen for:
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`

Then set `STRIPE_WEBHOOK_SECRET` to the signing secret from Stripe.

---

## Local dev vs Production

| Setting | Local | Production (Cloud) |
|---------|-------|--------------------|
| Database | SQLite | Serverless Postgres |
| Queue | `sync` (tests) / `database` (dev) | Dedicated worker cluster |
| Cache | `database` | Postgres (auto-configured) |
| Sessions | `database` | Postgres (auto-configured) |
| Storage | `local` | S3-compatible object storage |
| Mail | `log` | Resend / Postmark / SES |
