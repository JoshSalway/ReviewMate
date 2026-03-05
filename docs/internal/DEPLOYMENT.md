# ReviewMate — Deployment Guide (Internal)

## Recommended: Laravel Cloud

Laravel Cloud is the simplest deployment target. ReviewMate is built for it.

### Required plan
**Growth plan** (~$20/month) — needed for always-on queue workers (Google sync, follow-ups, weekly digest).

---

## Step-by-step Deploy

### 1. Create a Laravel Cloud project

1. Go to [cloud.laravel.com](https://cloud.laravel.com)
2. Create a new project → link the GitHub repo (`JoshSalway/ReviewMate`)
3. Set environment to Production

### 2. Enable PostgreSQL

In the Laravel Cloud project → Databases → Add PostgreSQL. Laravel Cloud will auto-inject `DB_URL`.

### 3. Configure queue workers

In the project → Workers → enable always-on queue worker:
```
php artisan queue:work --sleep=3 --tries=3
```

### 4. Set all environment variables

Copy everything from `.env.example` and fill in production values. Required:

```env
APP_NAME=ReviewMate
APP_ENV=production
APP_KEY=                          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database — Laravel Cloud injects DB_URL automatically
DB_CONNECTION=pgsql

# Sessions/Cache/Queue
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Mail
MAIL_MAILER=resend               # or mailgun
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=hello@reviewmate.app
MAIL_FROM_NAME="ReviewMate"

# WorkOS
WORKOS_CLIENT_ID=client_...
WORKOS_API_KEY=sk_...
WORKOS_REDIRECT_URL=https://yourdomain.com/authenticate

# Google Business Profile OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback
GOOGLE_PLACES_API_KEY=...

# Anthropic (AI reply suggestions)
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_PROVIDER=anthropic

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_STARTER=price_...
STRIPE_PRICE_PRO=price_...

# SMS
SMS_DRIVER=clicksend
CLICKSEND_USERNAME=...
CLICKSEND_API_KEY=...
CLICKSEND_FROM=ReviewMate
```

### 5. Run migrations

```bash
php artisan migrate --force
```

### 6. Make Josh superadmin

```bash
php artisan tinker
User::where('email', 'josh@...')->first()->update(['is_admin' => true, 'role' => 'superadmin']);
```

### 7. Set up Stripe products

Create two products in Stripe dashboard:
- **Starter** — $49/month AUD recurring → copy price ID to `STRIPE_PRICE_STARTER`
- **Pro** — $99/month AUD recurring → copy price ID to `STRIPE_PRICE_PRO`

Register webhook at `https://yourdomain.com/stripe/webhook` with events:
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.paid`
- `payment_intent.succeeded`

### 8. Update OAuth redirect URIs

- WorkOS: Add `https://yourdomain.com/authenticate` to allowed redirects
- Google Cloud Console: Add `https://yourdomain.com/google/callback` to authorised redirect URIs

### 9. Health check

```bash
curl https://yourdomain.com/up
# Should return 200 OK

php artisan schedule:run
# Should run without errors

php artisan queue:work --once
# Should process any pending jobs
```

---

## Integration Webhook URLs

After deploy, share these URLs with integration partners:

| Integration | Webhook URL |
|-------------|-------------|
| ServiceM8 | `https://yourdomain.com/webhooks/servicem8/{business_uuid}` |
| Xero | `https://yourdomain.com/webhooks/xero/{business_uuid}` |
| Timely | `https://yourdomain.com/webhooks/timely/{business_uuid}` |
| Simpro | `https://yourdomain.com/webhooks/simpro/{business_uuid}` |
| Generic | `https://yourdomain.com/webhooks/incoming/{token}` |

---

## Monitoring

- Laravel Pail for logs (dev): `php artisan pail`
- Laravel Cloud dashboard shows queue depth, job failures, and worker health
- Set up email alerts in Laravel Cloud for job failures

---

## Rollback

If a deploy breaks things:
1. Laravel Cloud → deploy history → redeploy previous version
2. If migration broke the schema: `php artisan migrate:rollback`
