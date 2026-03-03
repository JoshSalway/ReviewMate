# ReviewMate - Development Progress

## Tech Stack
- **Framework**: Laravel 12
- **Auth**: WorkOS AuthKit via `laravel/workos`
- **Frontend**: React 19 + Inertia.js v2 + TypeScript + Tailwind CSS v4 + shadcn/ui
- **Routing**: Laravel Wayfinder (type-safe frontend route generation)
- **AI**: Laravel AI SDK (`laravel/ai`) — review reply suggestions via Anthropic
- **Dev Tool**: Laravel Boost (MCP server for AI-assisted development)
- **Testing**: Pest 4
- **Database**: SQLite (dev)

---

## ✅ Completed

### Infrastructure
- [x] Laravel 12 project scaffolded with React Starter Kit + WorkOS + Pest + Boost
- [x] Laravel AI SDK installed (`laravel/ai`) — default provider set to Anthropic
- [x] WorkOS authentication configured (login, logout, session validation)
- [x] SQLite database configured
- [x] `ANTHROPIC_API_KEY` documented in `.env.example`

### Database
- [x] `businesses` table — name, type, google_place_id, owner_name, phone, onboarding_completed_at
- [x] `customers` table — business_id, name, email, phone, notes
- [x] `review_requests` table — business_id, customer_id, status, channel, sent/opened/reviewed timestamps
- [x] `reviews` table — business_id, customer_id, review_request_id, rating, body, reviewer_name, source, reviewed_at
- [x] `email_templates` table — business_id, type (request/followup/sms), subject, body

### Models
- [x] `Business` — relationships, googleReviewUrl(), isOnboardingComplete(), averageRating(), conversionRate()
- [x] `Customer` — relationships, initials(), requestStatus()
- [x] `ReviewRequest` — relationships, markAsOpened(), markAsReviewed()
- [x] `Review` — relationships, stars(), wasViaReviewMate()
- [x] `EmailTemplate` — relationships, renderBody()
- [x] `User` — businesses(), currentBusiness() (session-tracked), switchBusiness()

### Backend
- [x] `DashboardController` — stats, recent reviews, 6-month chart data
- [x] `OnboardingController` — 3-step onboarding flow
- [x] `CustomerController` — index, store, update, destroy, importCsv (CSV bulk import)
- [x] `ReviewRequestController` — index, store (queues ReviewRequestMail)
- [x] `ReviewController` — show, replySuggestions (AI-powered)
- [x] `TemplateController` — index, update
- [x] `QuickSendController` — index, send (queues ReviewRequestMail)
- [x] `BusinessSettingsController` — index, update
- [x] `BusinessController` — store (create business), switch (multi-business session)
- [x] `QrCodeController` — QR code page
- [x] `EmailFlowController` — email automation flow visualizer
- [x] `ReviewReplyAgent` — Laravel AI agent for generating reply suggestions
- [x] `DefaultTemplateService` — business-type-aware default email templates (cafe, tradie, salon, + generic)
- [x] All routes registered and Wayfinder types generated

### Email & Automation
- [x] `ReviewRequestMail` — queued mailable using business template
- [x] `FollowUpMail` — queued mailable using follow-up template
- [x] `SendFollowUpRequests` artisan command — sends follow-ups to unanswered requests older than 3 days
- [x] Scheduled at `09:00 daily` in `routes/console.php`

### Frontend (React/Inertia)
- [x] `dashboard.tsx` — stat cards, 6-month recharts chart, recent reviews, Google link copy, empty state
- [x] `customers/index.tsx` — table, add customer dialog, CSV import button, status badges, delete confirm
- [x] `requests/index.tsx` — stat cards, request list with timeline
- [x] `templates/index.tsx` — tab editor (request/followup/sms), live preview, variable chips
- [x] `quick-send.tsx` — fast send form, channel selector, recently sent list
- [x] `onboarding/business-type.tsx` — step 1, 9 business type cards, name/owner inputs
- [x] `onboarding/connect-google.tsx` — step 2, place ID input, collapsible instructions
- [x] `onboarding/select-template.tsx` — step 3, template preview, confirm
- [x] `reviews/show.tsx` — review detail, AI reply suggestions, copy button
- [x] `settings/business.tsx` — business settings form
- [x] `welcome.tsx` — landing page with hero, features, CTA
- [x] `qr-code.tsx` — client-side QR code (qrcode.react), size/style controls, PNG download
- [x] `email-flow.tsx` — visual automation flow diagram (trigger → request email → wait → branch → follow-up → done)
- [x] `BusinessSwitcher` component — sidebar dropdown for switching between businesses
- [x] Sidebar navigation with all routes including QR Code and Email Flow

### Testing (66 tests, all passing)
- [x] DashboardTest — guest redirect, onboarding redirect, stats
- [x] CustomerTest — CRUD, scoping, authorization
- [x] ReviewRequestTest — listing, sending, stats scoping, authorization
- [x] OnboardingTest — full 3-step flow
- [x] TemplateTest — viewing, updating, authorization
- [x] QuickSendTest — sending, customer creation
- [x] BusinessSettingsTest — viewing, updating, validation
- [x] MailTest — mail queuing, template rendering
- [x] SendFollowUpRequestsTest — scheduling logic, no duplicates
- [x] CsvImportTest — CSV parsing, deduplication
- [x] MultiBusinessTest — business switching, scoping

---

## 🔜 To Do (Post-MVP)

### High Priority
- [ ] **SMS sending** — Integrate Twilio or similar for SMS channel
- [ ] **WorkOS credentials** — Add `WORKOS_CLIENT_ID` and `WORKOS_API_KEY` to `.env` (required for auth to work)
- [ ] **Review sync** — Webhook or polling to sync actual Google reviews back to ReviewMate

### Medium Priority
- [ ] **Sanctum API routes** — If building a mobile app or external integrations
- [ ] **Notification settings** — Toggle weekly digest, new review alerts, etc.
- [ ] **Billing/Subscription** — Stripe integration for $9/month plan

---

## Setup Instructions

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```

2. Set up environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Add WorkOS credentials to `.env`:
   ```
   WORKOS_CLIENT_ID=your-client-id
   WORKOS_API_KEY=your-api-key
   WORKOS_REDIRECT_URL="${APP_URL}/authenticate"
   ```

4. Add AI credentials to `.env` (for review reply suggestions):
   ```
   ANTHROPIC_API_KEY=your-anthropic-key
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Build assets:
   ```bash
   npm run dev
   ```

7. Start the app:
   ```bash
   php artisan serve
   ```

8. Run tests:
   ```bash
   php artisan test
   ```

9. Run the queue worker (for email sending):
   ```bash
   php artisan queue:work
   ```

10. Run the scheduler (for automated follow-ups):
    ```bash
    php artisan schedule:work
    ```
