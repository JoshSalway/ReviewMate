# ReviewMate — Architecture

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.4, Laravel 12 |
| Frontend | React 19, Inertia.js v2 |
| Styling | Tailwind CSS v4 |
| Auth | WorkOS (passwordless, SSO-grade) |
| ORM | Eloquent, SQLite (dev) / PostgreSQL (prod) |
| Queue | Laravel database queue (dev), Redis-backed (prod) |
| Email | Resend/Mailgun via Laravel Mail |
| SMS | ClickSend (default), Twilio (fallback) |
| Billing | Stripe, Laravel Cashier v16 |
| AI | Anthropic Claude Haiku (via laravel/ai) |
| Google | Socialite + Google Business Profile API |
| API | Laravel Sanctum (personal access tokens) |
| Testing | Pest v4, PHPUnit v12 |
| Bundler | Vite, TypeScript |

## Directory Structure

```
app/
  Ai/Agents/          — AI agents (ReviewReplyAgent)
  Console/Commands/   — Artisan commands
  Http/
    Controllers/
      Api/V1/         — REST API controllers (Sanctum)
      Integrations/   — Integration webhook controllers
      Settings/       — Settings sub-controllers
    Middleware/       — EnsureSuperAdmin, HandleAppearance, etc.
    Resources/        — API JSON resources (BusinessResource, etc.)
  Jobs/               — Queued jobs (sync, follow-ups, onboarding emails)
  Listeners/          — Event listeners (SendOnboardingSequence)
  Mail/               — Mailable classes + Blade email templates
  Models/             — Eloquent models
  Providers/          — Service providers
  Services/           — Service layer (Google, SMS, integrations)

database/
  factories/          — Model factories (for tests)
  migrations/         — Database schema migrations (sequential)
  seeders/            — DatabaseSeeder, DemoSeeder

docs/
  internal/           — Internal docs (this file, DEPLOYMENT, WORKOS_SETUP, RUNBOOK)
  openapi.yaml        — OpenAPI 3.1 spec
  PRD.md              — Product requirements

mcp/
  src/index.ts        — TypeScript MCP server
  package.json        — MCP package manifest

resources/js/
  pages/              — Inertia page components (React)
  components/         — Shared UI components

routes/
  web.php             — Web routes (Inertia + webhooks)
  api.php             — REST API routes (Sanctum)
  auth.php            — WorkOS auth routes
  console.php         — Scheduled tasks

tests/
  Feature/            — Pest feature tests (218 tests)
```

## Data Model

```
User (WorkOS auth)
  └── has many Business
        ├── has many Customer
        │     ├── has many ReviewRequest (tracking: sent/opened/reviewed)
        │     └── has many Review
        ├── has many ReviewRequest
        ├── has many Review (Google reviews, synced via GBP API)
        ├── has many EmailTemplate
        └── has many ReplyTemplate
```

## Key Flows

### Review Request Flow
1. Customer added (manual or CSV or integration)
2. User triggers send (bulk, quick, or automated via integration)
3. `ReviewRequestMail` queued → sent via Resend/Mailgun
4. Customer clicks tracking link `/r/{token}` → status updated to `opened`
5. Customer clicks Google review link → they leave review
6. `SyncGoogleReviews` job (every 2h) fetches and upserts reviews

### Follow-up Flow
- `SendFollowUpRequests` job runs daily at 09:00
- Finds `sent` requests where `created_at` <= 5 days ago and `followed_up_at` is null
- Skips if customer has already reviewed (status `reviewed`)
- Sends `FollowUpMail` and updates `followed_up_at`

### Onboarding Drip Flow
- User registers → `Registered` event → `SendOnboardingSequence` listener
- Dispatches 5 `SendOnboardingEmail` jobs with delays (0, 3, 7, 14, 30 days)
- Day-3 email skips if business has already sent review requests

### Stripe Billing Flow
- User selects plan → `BillingController::subscribe` → Stripe Checkout
- Stripe webhook at `POST /stripe/webhook` → Cashier handles events
- `customer.subscription.deleted` → handled by `HandleStripeWebhook` listener
- Plan gate in controllers via `$user->onFreePlan()`

## Integration Architecture

All integrations follow the same pattern:
1. User connects via OAuth or API key in Settings → Integrations
2. Integration credentials stored (encrypted) on the `Business` model
3. When a trigger event fires (webhook or poll):
   - Integration controller validates the payload
   - `ProcessXxxJob` queued
   - Job creates or finds Customer, then calls `ReviewRequestController::store`
4. Toggle `auto_send_reviews` per integration

## Queue Jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `SyncGoogleReviews` | Every 2 hours | Fetch reviews from Google Business Profile API |
| `SendFollowUpRequests` | Daily 09:00 | Day-5 follow-up emails |
| `SendWeeklyDigests` | Mondays 08:00 | Weekly review digest email |
| `RefreshGoogleStats` | Every 6 hours | Update cached Google rating + review count |
| `PollClinikoAppointments` | Every 15 minutes | Cliniko appointment completion polling |
| `PollHalaxyAppointments` | Every 15 minutes | Halaxy appointment completion polling |
| `SendOnboardingEmail` | Dispatched with delay | Onboarding drip email |

## Security

- WorkOS handles auth (no passwords stored)
- Google OAuth tokens encrypted at rest (`encrypted` cast)
- Integration tokens encrypted at rest
- CSRF protection on all web routes (webhooks excluded)
- Sanctum tokens for API access
- Stripe webhook signature verification (Cashier handles)
- `EnsureSuperAdmin` middleware guards /admin routes
- Rate limiting on API routes (Laravel default)
