# ReviewMate — Changelog

## Session 3 (Current)

### ✅ Completed

#### Google Business Profile OAuth (Task 1)
- **Migration**: Added `google_access_token`, `google_refresh_token`, `google_token_expires_at`, `google_account_id`, `google_location_id` to `businesses` table (access token encrypted at rest)
- **Migration**: Added `google_review_id`, `google_review_name`, `google_reply`, `google_reply_posted_at` to `reviews` table
- **Installed** `laravel/socialite`
- **`GoogleBusinessController`**: OAuth redirect → callback (stores tokens, discovers account/location IDs) → disconnect
- **`GoogleBusinessProfileService`**: wraps GBP API — `fetchReviews()`, `postReply()`, `deleteReply()`, auto-refreshes expired tokens
- **`SyncGoogleReviews` Job**: fetches reviews from GBP API, upserts into `reviews` table by `google_review_id`
- **Scheduled** sync every 2 hours in `routes/console.php`
- Routes: `GET /google/connect`, `GET /google/callback`, `DELETE /google/disconnect`
- Business Settings page: shows Connect/Disconnect Google button with connection status badge
- Added `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` to `.env.example`

#### AI Review Response UI (Task 2)
- **`ReviewController::index`**: returns `needsReply` (Google reviews without a reply), `replied`, and `allReviews` (non-Google reviews)
- **`ReviewController::postReply`**: validates reply, calls `GoogleBusinessProfileService::postReply()`, stores reply on review
- **`reviews/index.tsx`**: "Needs Reply" section with AI suggestion flow + post button, "Replied" section with collapsible reply view, "All Reviews" section for non-Google reviews
- Added "Reviews" to sidebar navigation (between Requests and Templates)
- Route: `GET /reviews`, `POST /reviews/{review}/reply`

#### Follow-up Automation Job (Task 3)
- **Migration**: Added `followed_up_at` to `review_requests`
- **`SendFollowUpRequests` Job** (`app/Jobs/`): queries requests 5–6 days old (1-day window), sends follow-up email, sets `followed_up_at`
- Scheduled daily at 09:00

#### Stripe Billing + Admin Bypass (Task 4)
- **Installed** `laravel/cashier`
- **Migration**: Cashier migrations (customer columns, subscriptions, subscription items)
- **Migration**: Added `is_admin` boolean to `users` table
- **`User::isAdmin()`**, **`User::onFreePlan()`** helpers
- **`User` uses `Billable`** trait
- **`BillingController`**: index (shows plan + Cashier checkout URLs), subscribe (Stripe Checkout), portal (Billing Portal)
- **`settings/billing.tsx`**: plan cards (Starter $49/mo, Pro $99/mo), current plan badge, manage subscription button
- Added "Billing" to sidebar footer navigation
- Plan limits enforced:
  - Free: 1 business, 50 customers, 10 requests/month
  - Starter: 1 business, unlimited customers/requests
  - Pro: 5 businesses, unlimited customers/requests
  - **Admin: no limits** (bypasses all checks)
- Added `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_STARTER`, `STRIPE_PRICE_PRO` to `.env.example`

#### Dashboard Real Data (Task 5)
- Added `pending_replies`, `reviews_this_month`, `requests_this_month` to `DashboardController` stats
- Dashboard cards updated: "Total Reviews" now shows this-month count, "Requests Sent" shows this-month count, 4th card replaced with "Pending Replies" (orange highlight when > 0)

#### Git Repository
- Initialized git repo, made initial commit (237 files)
- Remote: `https://github.com/JoshSalway/ReviewMate`

#### Tests
- Updated `MultiBusinessTest`: added `user can add a second business as admin` and `free plan user cannot add a second business` tests
- **67 tests, all passing**

---

## Session 2 (Previous)

### ✅ Completed
- Email sending: `ReviewRequestMail`, `FollowUpMail` (queued mailables using business templates)
- Automated follow-up scheduler: `SendFollowUpRequests` artisan command, scheduled daily at 09:05
- CSV customer import: `CustomerController::importCsv()` with flexible header matching
- QR code page: client-side with `qrcode.react`, size/style controls, PNG download
- Multi-business support: session-tracked `current_business_id`, `BusinessSwitcher` dropdown in sidebar
- Analytics charts: `recharts` LineChart on dashboard (6-month review + request counts)
- Email flow page: visual automation flow diagram
- AI provider: set Anthropic as default in `config/ai.php`
- 66 tests passing

---

## Session 1 (Initial)

### ✅ Completed
- Laravel 12 scaffolded with React Starter Kit + WorkOS + Pest + Boost
- SQLite database configured
- Migrations: businesses, customers, review_requests, reviews, email_templates
- Models: Business, Customer, ReviewRequest, Review, EmailTemplate, User (with multi-business)
- 3-step onboarding: business-type → connect-google → select-template
- All core controllers: Dashboard, Onboarding, Customer, ReviewRequest, Review, Template, QuickSend, BusinessSettings
- `ReviewReplyAgent` (Laravel AI SDK + Claude) — generates 3 reply suggestions
- `DefaultTemplateService` — business-type-aware default templates
- Frontend pages: dashboard, customers, requests, templates, quick-send, onboarding (3 steps), review show, business settings, welcome
- 46 tests passing

---

## 🔜 Remaining / Post-MVP

### High Priority
- [ ] **SMS sending** — Integrate Twilio for SMS channel (routes exist, just need provider)
- [ ] **Stripe Webhook handler** — Handle `customer.subscription.updated/deleted` events to update subscription status in DB
- [ ] **Review sync webhook** — Real-time Google review sync via GBP webhooks (currently polling every 2h)

### Medium Priority
- [ ] **WorkOS credentials** — Add `WORKOS_CLIENT_ID` and `WORKOS_API_KEY` to `.env` (required for auth to work in production)
- [ ] **Notification settings** — Toggle weekly digest, new review alerts per business
- [ ] **Multi-location analytics** — Aggregate stats across all businesses for Pro users

### Lower Priority
- [ ] **Review reply on show page** — Wire up the "Post Reply" button on `reviews/show.tsx` (currently only copies to clipboard)
- [ ] **Bulk review request send** — Select multiple customers → send at once
- [ ] **Review response templates** — Save favourite reply templates

---

## Setup Checklist (for new environments)

1. `composer install && npm install`
2. `cp .env.example .env && php artisan key:generate`
3. Set `.env` credentials:
   - `WORKOS_CLIENT_ID`, `WORKOS_API_KEY` — WorkOS dashboard
   - `ANTHROPIC_API_KEY` — Anthropic console
   - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` — Google Cloud Console (enable Business Profile API)
   - `STRIPE_KEY`, `STRIPE_SECRET` — Stripe dashboard
   - `STRIPE_PRICE_STARTER`, `STRIPE_PRICE_PRO` — Stripe product price IDs
4. `php artisan migrate`
5. `npm run build`
6. `php artisan serve`
7. `php artisan queue:work` — for email sending
8. `php artisan schedule:work` — for follow-ups + Google sync
