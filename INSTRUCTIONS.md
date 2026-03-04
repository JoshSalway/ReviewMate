# ReviewMate — Agent Instructions

> Last updated: 2026-03-04
> Completion: ~100% code complete — deploy + marketing tasks remaining
> Priority: HIGH — first revenue target

---

## What Is ReviewMate?

ReviewMate is a Google review management SaaS for Australian local businesses (tradies, cafes, salons, gyms). It automatically sends personalised review request emails and SMS to customers after a job or sale, follows up 5 days later, syncs Google reviews every 2 hours, and provides AI-powered reply suggestions via Claude. It is a direct competitor to Birdeye and Podium at 5–10x lower price.

Pricing: Free (50 customers, 10 requests/mo) | Starter $49/mo (1 location, unlimited) | Pro $99/mo (5 locations).

---

## Stack

- **PHP 8.4 / Laravel 12** — backend, API, jobs, scheduler
- **React 19 + Inertia.js v2** — SPA frontend (no separate API layer)
- **Tailwind CSS v4** — styling
- **WorkOS** — authentication (SSO-grade, passwordless)
- **Laravel Cashier + Stripe** — subscriptions and billing
- **Twilio SDK** — SMS review requests
- **Laravel AI + Anthropic (Claude Haiku)** — AI reply suggestions
- **Laravel Socialite** — Google Business Profile OAuth
- **Laravel Wayfinder** — type-safe route helpers in TypeScript
- **Pest v4 + PHPUnit v12** — testing
- **SQLite** (dev) / **PostgreSQL** (production)
- **Radix UI + Recharts + qrcode.react + Headless UI** — frontend components

---

## How to Run Locally

```bash
# First time setup
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install

# Start dev server (Laravel + Vite + queue worker + log viewer concurrently)
composer run dev

# If using Laravel Herd (no php artisan serve needed)
composer run herd

# Run tests
./vendor/bin/pest --parallel

# Lint PHP
./vendor/bin/pint --dirty

# Lint TypeScript
npm run types:check

# Full CI check
composer run ci:check
```

Minimum required `.env` keys for local dev:

```env
APP_KEY=                    # php artisan key:generate
WORKOS_CLIENT_ID=           # from WorkOS dashboard
WORKOS_API_KEY=             # from WorkOS dashboard
WORKOS_REDIRECT_URL=http://localhost:8000/authenticate
GOOGLE_CLIENT_ID=           # Google Cloud Console
GOOGLE_CLIENT_SECRET=       # Google Cloud Console
GOOGLE_REDIRECT_URI=http://localhost:8000/google/callback
ANTHROPIC_API_KEY=          # Anthropic console
AI_DEFAULT_PROVIDER=anthropic
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_STARTER=price_...
STRIPE_PRICE_PRO=price_...
```

---

## Current State

| Feature | Status |
|---------|--------|
| Auth (WorkOS — passwordless/SSO) | ✅ DONE |
| User model with `is_admin` flag | ✅ DONE |
| Free plan limits (50 customers, 10 requests/mo) | ✅ DONE |
| Multi-business support (session-based switching) | ✅ DONE |
| Business model with Google OAuth fields (encrypted) | ✅ DONE |
| Customer CRUD (add, edit, delete, paginate) | ✅ DONE |
| CSV customer import (flexible column mapping) | ✅ DONE |
| CSV customer export (with review status) | ✅ DONE |
| Email unsubscribe (tokenised link, CAN-SPAM compliant) | ✅ DONE |
| 30-day resend guard (no duplicate requests) | ✅ DONE |
| Review request sending — email | ✅ DONE |
| Review request sending — SMS (Twilio) | ✅ DONE |
| Bulk send to multiple customers (email / SMS / both) | ✅ DONE |
| Quick send (one-off, no customer record needed) | ✅ DONE |
| Review request tracking link (`/r/{token}`) | ✅ DONE |
| Follow-up automation (day 5 resend if not reviewed) | ✅ DONE |
| Google Business Profile OAuth (Socialite) | ✅ DONE |
| Auto-sync reviews from Google (every 2 hours) | ✅ DONE |
| Review inbox with pagination (needs reply / replied / all) | ✅ DONE |
| AI reply suggestions (3 options via Claude) | ✅ DONE |
| Post AI reply back to Google via API | ✅ DONE |
| Saved reply templates (create, edit, delete) | ✅ DONE |
| Email template editor | ✅ DONE |
| QR code page (downloadable) | ✅ DONE |
| Email flow visualiser | ✅ DONE |
| Onboarding wizard (business type → Google → template) | ✅ DONE |
| Google Place ID auto-discovery in onboarding | ✅ DONE |
| Dashboard (stats, chart, recent reviews) | ✅ DONE |
| Multi-location analytics page | ✅ DONE |
| Weekly digest email (Mondays 08:00) | ✅ DONE |
| New review alert email | ✅ DONE |
| Notification preferences settings | ✅ DONE |
| Business settings (name, type, owner, place ID) | ✅ DONE |
| Stripe billing + Cashier subscriptions | ✅ DONE |
| Stripe billing portal (upgrade / cancel) | ✅ DONE |
| Stripe webhooks (subscription lifecycle) | ✅ DONE |
| Terms of Service + Privacy Policy pages | ✅ DONE |
| Branded error pages (403, 404, 500) | ✅ DONE |
| Waitlist landing page with signup form | ✅ DONE |
| CI passing (PHP lint + TypeScript type check) | ✅ DONE |
| 107 tests passing | ✅ DONE |
| **Deployed to production** | ❌ TOP PRIORITY |
| **Onboarding email drip sequence** | ❌ See Task 3 |
| **Cold email campaigns (via Outpost)** | ❌ See Task 1 |

---

## Agent Tasks (Priority Order)

---

### Task 1: Deploy to Laravel Cloud (Production)

**Owner task — not for agents unless Josh explicitly delegates it.**

Follow DEPLOYMENT.md exactly. Summary of steps:

1. Create Laravel Cloud project → link GitHub repo → set environment to Production
2. Enable PostgreSQL database on Laravel Cloud
3. Enable always-on queue worker (required for Google sync + follow-ups + weekly digest)
4. Configure all environment variables (see DEPLOYMENT.md for full list)
5. Run: `php artisan migrate --force`
6. Make Josh admin via tinker: `User::where('email', 'josh@...')->first()->update(['is_admin' => true])`
7. Set up Stripe products (Starter $49/mo + Pro $99/mo) and register webhook to `https://yourdomain.com/stripe/webhook`
8. Update WorkOS redirect URI to `https://yourdomain.com/authenticate`
9. Update Google OAuth redirect URI to `https://yourdomain.com/google/callback`
10. Verify health check (see DEPLOYMENT.md)

**Acceptance criteria:**
- `GET /` shows landing page
- WorkOS login works end-to-end
- Can send a review request email that arrives
- Google Business Profile OAuth connects successfully
- Stripe test checkout completes
- `php artisan queue:work` processes jobs
- `php artisan schedule:run` runs without errors

---

### Task 2: Cold Email Campaigns (via Outpost)

Write these as ready-to-use Outpost campaigns. Save each as a separate file in `/Users/joshsalway/development/ReviewMate/resources/email-campaigns/`.

---

#### Campaign A: AU Cafes and Restaurants (`campaign-au-cafes.md`)

**Target:** Cafe owners, restaurant managers, food business owners in Australia.
**Goal:** Book a demo or start a free trial of ReviewMate.
**Personalisation fields:** `{{first_name}}`, `{{business_name}}`, `{{city}}`

---

**Email 1 — Day 0 (Introduction)**

Subject line variants (A/B test):
- A: `{{first_name}}, how many Google reviews did {{business_name}} get this month?`
- B: `The #1 thing {{business_name}} customers check before walking in`
- C: `Quick question about {{business_name}}'s Google reviews`

Body:
```
Hi {{first_name}},

I was looking at cafes in {{city}} and noticed {{business_name}} — great spot.

Quick question: do you have a system for asking happy customers to leave Google reviews?

Most cafes get reviews randomly — when someone is annoyed, they find the time. The happy customers mean to leave one but never get around to it.

ReviewMate fixes that. After a customer visits, it automatically sends them a personalised review request. If they don't respond in 5 days, it follows up once. Most cafes we work with see their first new review within 48 hours.

It takes 10 minutes to set up. No tech skills needed.

Would it be okay to send you a 2-minute walkthrough?

Josh
ReviewMate
```

---

**Email 2 — Day 4 (Social Proof)**

Subject line variants:
- A: `How a {{city}} cafe got 23 new reviews in a month`
- B: `Re: {{business_name}}'s Google reviews`
- C: `The one thing separating 4.2-star and 4.8-star cafes`

Body:
```
Hi {{first_name}},

Following up on my last email — I know you're busy running {{business_name}}.

The difference between a 4.2-star and a 4.8-star cafe on Google isn't the food. It's who's asking for reviews systematically.

The cafes doing this well have one thing in common: they ask every customer, every time, without thinking about it. ReviewMate does that automatically — email and SMS, with a follow-up if they don't respond.

Pricing starts at $49/month. For most cafes, one extra table booking a week from improved Google visibility more than covers it.

Happy to give you a free 14-day trial — no credit card needed.

Worth 10 minutes to set up?

Josh
```

---

**Email 3 — Day 10 (Last Touch)**

Subject line variants:
- A: `Last email — free trial for {{business_name}}`
- B: `Before I stop following up...`
- C: `{{first_name}}, one last thing`

Body:
```
Hi {{first_name}},

Last email from me — I don't want to clutter your inbox.

If getting more Google reviews automatically isn't a priority for {{business_name}} right now, completely understand.

If timing changes: reviewmate.app — free trial, no card needed.

Either way, good luck with the cafe. Hope {{city}} is treating you well.

Josh
```

**Send timing:** Email 1 → Day 0. Email 2 → Day 4 if no reply. Email 3 → Day 10 if no reply.

---

#### Campaign B: AU Tradies — Plumbers, Electricians, Builders (`campaign-au-tradies.md`)

**Target:** Trade business owners and sole operators in Australia.
**Goal:** Start a free trial or book a demo.
**Personalisation fields:** `{{first_name}}`, `{{business_name}}`, `{{trade}}` (e.g. plumber, electrician), `{{city}}`

---

**Email 1 — Day 0 (Pain Point)**

Subject line variants:
- A: `{{first_name}}, how do {{city}} homeowners find a {{trade}} they trust?`
- B: `Most {{trade}}s lose jobs before they even quote`
- C: `The {{trade}} with the most Google reviews wins`

Body:
```
Hi {{first_name}},

When someone in {{city}} needs a {{trade}}, they open Google and call the first business with the most reviews and the best rating.

That's the whole decision.

The problem is most {{trade}}s don't have a system for collecting reviews. Happy customers go home and forget to write one. The one-star reviewer finds the time every time.

ReviewMate fixes this. After you finish a job, it automatically texts or emails your customer and asks for a Google review. If they don't respond in 5 days, it follows up once. Most tradies we work with see 5–10 new reviews in their first month.

Setup takes 10 minutes. Works on your phone.

Worth a look for {{business_name}}?

Josh
ReviewMate
```

---

**Email 2 — Day 5 (Objection handling)**

Subject line variants:
- A: `"I don't have time for this" — fair, so we automated it`
- B: `Re: Google reviews for {{business_name}}`
- C: `{{first_name}}, your {{trade}} competitors are doing this`

Body:
```
Hi {{first_name}},

Following up on my note about ReviewMate.

The most common thing I hear from tradies: "I know I should ask for reviews but I never remember after a job."

That's exactly why we built ReviewMate. You don't have to remember. You add a customer once (or import your list from a spreadsheet), and it sends the request automatically. You just do the job.

$49/month. Free trial to start, no card needed.

If you've got 10 minutes this week, I'd love to show you how a {{trade}} in {{city}} could look in 30 days.

Josh
```

---

**Email 3 — Day 12 (Final)**

Subject line variants:
- A: `Last one from me, {{first_name}}`
- B: `Free trial still open for {{business_name}}`
- C: `Before I stop following up`

Body:
```
Hi {{first_name}},

Last email — promise.

If getting more Google reviews on autopilot for {{business_name}} isn't the right time, no worries at all.

When it is: reviewmate.app — free 14-day trial, no card needed, takes 10 minutes to set up.

Hope the jobs in {{city}} are going well.

Josh
```

**Send timing:** Email 1 → Day 0. Email 2 → Day 5 if no reply. Email 3 → Day 12 if no reply.

---

#### Campaign C: AU Allied Health — Physios, Chiros, Gyms (`campaign-au-allied-health.md`)

**Target:** Physiotherapy clinics, chiropractic practices, gyms, and personal trainers in Australia.
**Goal:** Start a free trial.
**Personalisation fields:** `{{first_name}}`, `{{business_name}}`, `{{practice_type}}` (e.g. physio clinic, gym), `{{city}}`

---

**Email 1 — Day 0 (Hook)**

Subject line variants:
- A: `{{first_name}}, how are new patients finding {{business_name}}?`
- B: `The {{practice_type}} with the most Google reviews fills its books first`
- C: `Quick question about {{business_name}}'s online reputation`

Body:
```
Hi {{first_name}},

When someone in {{city}} types "{{practice_type}} near me" into Google, the practices with the most recent 5-star reviews get the calls.

Most clinics and gyms are sitting on a goldmine of happy patients and members who just never got around to writing a review. They meant to. Life got busy.

ReviewMate automates the ask. After a session or appointment, it sends a personalised review request by email or SMS — and follows up once if they don't respond. No awkward in-person asking required.

It takes 10 minutes to set up and integrates with your existing patient/member list (CSV upload).

Is this something that would help {{business_name}}?

Josh
ReviewMate
```

---

**Email 2 — Day 5 (Value + trust)**

Subject line variants:
- A: `Re: more Google reviews for {{business_name}}`
- B: `What the top-rated {{practice_type}}s in {{city}} are doing differently`
- C: `The one change that filled {{practice_type}} books in 30 days`

Body:
```
Hi {{first_name}},

Quick follow-up on ReviewMate.

Allied health is one of the highest-trust categories online. Patients choose a physio or chiropractor based almost entirely on reviews — they can't try before they buy.

The practices doing well on Google aren't getting lucky. They're systematically asking every patient at the right moment. ReviewMate does that automatically.

A few specifics:
- Sends personalised review requests after each appointment
- Follows up once if they haven't responded in 5 days
- Syncs your Google reviews so you can reply to all of them in one place
- AI-powered reply suggestions (professional, takes 30 seconds)

Free trial. No card needed. If it doesn't work, cancel in 2 clicks.

Worth trying for {{business_name}}?

Josh
```

---

**Email 3 — Day 12 (Final)**

Subject line variants:
- A: `Last email about ReviewMate, {{first_name}}`
- B: `Free trial still available for {{business_name}}`
- C: `Wrapping up — no more emails after this`

Body:
```
Hi {{first_name}},

Last one from me.

If getting more Google reviews automatically for {{business_name}} isn't a priority right now, completely fine — I'll leave you to it.

If the timing ever shifts: reviewmate.app — free 14-day trial, 10 minutes to set up.

Take care,
Josh
```

**Send timing:** Email 1 → Day 0. Email 2 → Day 5 if no reply. Email 3 → Day 12 if no reply.

---

### Task 3: Onboarding Email Drip Sequence (Code Task)

Build an automated 5-email onboarding sequence for new users. This is the highest-value automation to reduce churn and drive activation.

**Trigger:** New user registers (listens on `Registered` event or `User::created` model event).

**Tech stack:** Laravel Mail (Blade templates), Resend (MAIL_MAILER=resend), queued jobs with delays.

**Implementation plan:**

1. Create 5 Mailable classes (or one with a `$step` parameter) in `app/Mail/Onboarding/`:
   - `OnboardingWelcomeMail` — immediate
   - `OnboardingDay3Mail` — 3 days delay (only if no review request sent)
   - `OnboardingDay7Mail` — 7 days delay
   - `OnboardingDay14Mail` — 14 days delay
   - `OnboardingDay30Mail` — 30 days delay (upgrade prompt)

2. Create Blade email templates in `resources/views/emails/onboarding/`.

3. Create a `SendOnboardingSequence` listener on the `Registered` event that dispatches jobs with the correct `delay()`.

4. Each job should check the user still exists and hasn't unsubscribed before sending.

**Email content:**

---

**Email 1 — Welcome (Day 0)**
Subject: `Welcome to ReviewMate — here's how to get your first review today`

```
Hi {{name}},

Welcome to ReviewMate. Getting your first review is easier than you think — here's exactly what to do:

Step 1: Connect your Google Business Profile
Go to Settings → Business → Connect Google. This lets ReviewMate sync your reviews automatically and lets you reply without leaving the app.

Step 2: Import your customers
Go to Customers → Import CSV. Most businesses import 20–100 customers in their first session. No CSV? Add them manually — takes 30 seconds per customer.

Step 3: Send your first review request
Go to Customers → select a few customers → Bulk Send. Or use Quick Send for a one-off.

Most businesses get their first new review within 48 hours of sending their first request.

Any questions, reply to this email.

Josh
ReviewMate
```

---

**Email 2 — Day 3 Tip (only if zero review requests sent)**
Subject: `Quick tip — most businesses get their first review within 48 hours`

```
Hi {{name}},

Just checking in — have you sent your first review request yet?

If you haven't had a chance, here's the fastest way to start: use Quick Send.

Go to reviewmate.app/quick-send → enter a customer's name and email → send. Done in under 60 seconds.

No need to import a full customer list first. Just pick one happy customer you saw this week and send it now.

The hardest part is starting. Everything after that is automatic.

Josh
```

---

**Email 3 — Day 7 Check-in**
Subject: `How are your reviews going? (Week 1 check-in)`

```
Hi {{name}},

One week in — how's it going?

A few things worth knowing:

1. ReviewMate follows up automatically. If a customer doesn't respond in 5 days, we send a gentle reminder for you. You don't need to do anything.

2. Check your Reviews tab. If you connected Google, you should be seeing your existing reviews. You can reply to them with AI suggestions in seconds.

3. Your QR code is ready. Go to reviewmate.app/qr-code — print it and put it on your counter or include it in invoices.

Any questions, reply here.

Josh
```

---

**Email 4 — Day 14 Feature Spotlight**
Subject: `Did you know ReviewMate can reply to reviews for you?`

```
Hi {{name}},

Two weeks in — hope the reviews are rolling in.

One feature a lot of people miss: AI reply suggestions.

When a customer leaves a Google review, ReviewMate pulls it in automatically. Click on any review → click "Suggest a reply" → get 3 AI-written options → pick one and post it to Google without leaving ReviewMate.

Responding to reviews (especially negative ones) improves your Google ranking and shows potential customers you care. Most business owners know this but never find the time to do it.

ReviewMate makes it a 30-second task.

Try it on your next review.

Josh
```

---

**Email 5 — Day 30 Upgrade Prompt**
Subject: `{{name}}, you've been using ReviewMate for a month`

```
Hi {{name}},

It's been a month — hope ReviewMate has been earning its keep.

If you're on the free plan and running into limits (50 customers, 10 requests/month), upgrading to Starter ($49/month) removes all limits and unlocks unlimited customers and requests.

At $49/month, you need one extra customer a month from improved Google visibility to break even. Most businesses see far more than that.

Upgrade here: reviewmate.app/settings/billing

If the free plan is working fine for you, no pressure at all.

Thanks for being an early user.

Josh
```

**Acceptance criteria:**
- `php artisan test` still passes after implementation
- New user registration triggers the sequence
- Day 3 email only sends if `business->reviewRequests()->count() === 0`
- All emails have an unsubscribe link
- Emails use the same design as `ReviewRequestMail` for consistency

---

### Task 4: Landing Page Improvements (`welcome.tsx`)

The landing page currently shows a waitlist form. Once deployed, it should convert visitors into trial sign-ups. These changes should be made to `/Users/joshsalway/development/ReviewMate/resources/js/pages/welcome.tsx`.

**Changes needed:**

1. **Hero section** — replace "Coming soon — join the waitlist" pill with "Now open — free 14-day trial". Change CTA button from "Join the waitlist" to "Start your free trial — no credit card needed". Link to `/login` (WorkOS handles signup).

2. **Social proof section** — add between hero and "How it works". Show:
   - `"We got 14 new reviews in our first month. I didn't have to do anything." — Sarah K., Cafe owner`
   - `"Finally — a tool that asks for reviews without me having to remember." — Dan M., Electrician`
   - `"Our Google rating went from 4.1 to 4.7 in 6 weeks." — Anita R., Physio clinic`
   - (These are illustrative testimonials for launch — replace with real ones once you have customers)

3. **Competitor comparison table** — add after features grid. Show ReviewMate vs NiceJob vs Birdeye vs Podium on price, AI replies, and Google API integration.

4. **FAQ section** — add before footer:
   - "Does it work for SMS and email?" → Yes, both. SMS uses Twilio, works with Australian numbers.
   - "Do I need a Google Business Profile?" → Yes — you need a verified Google Business Profile to sync and reply to reviews. ReviewMate helps you connect it in the onboarding.
   - "Is there a free plan?" → Yes — 1 location, 50 customers, 10 requests/month.
   - "Can I cancel any time?" → Yes. No contracts, cancel from your billing settings.
   - "What businesses does it work for?" → Any local business with a Google Business Profile — tradies, cafes, salons, gyms, health clinics, retail, professional services.

5. **Remove waitlist form** — replace with a single "Start free trial" CTA button pointing to `/login`.

**Acceptance criteria:**
- `npm run types:check` passes
- All existing tests still pass
- Landing page loads and CTA links to `/login`

---

### Task 5: Post-Launch Features (Backlog)

Do not build these until ReviewMate has paying customers. Spec here for future agents.

#### 5a: Improved Review Reply Suggestions

**Current:** `ReviewReplyAgent` generates 3 options in a single prompt.

**Improvement:** Allow users to specify tone (friendly / professional / brief). Add a "regenerate" button that calls the endpoint again with a different seed. Show a character count (Google replies max 4096 chars). Cache suggestions per review for 24 hours so repeated clicks don't bill Anthropic.

**Files:** `app/Ai/Agents/ReviewReplyAgent.php`, `resources/js/pages/reviews/show.tsx`

---

#### 5b: Bulk CSV Import Improvements

**Current:** Accepts `name`, `email`, `phone` columns only.

**Improvement:** Add a column mapping step in the UI — user uploads CSV → sees a preview table → maps columns to fields via dropdowns → confirms import. Also support importing `notes` column.

**Files:** `app/Http/Controllers/CustomerController.php` (`importCsv`), `resources/js/pages/customers/index.tsx`

---

#### 5c: Analytics Dashboard Enhancements

**Current:** Dashboard shows average rating, total reviews, requests sent, conversion rate, monthly chart.

**Improvements:**
- Add rating distribution chart (bar chart: 1-star to 5-star counts)
- Add "review velocity" metric — reviews per week trend
- Add "response rate" metric — % of reviews with a reply
- Export analytics as CSV for a date range
- Multi-location comparison chart on the analytics page

**Files:** `app/Http/Controllers/AnalyticsController.php`, `resources/js/pages/analytics.tsx`

---

#### 5d: Multi-Location Support Improvements

**Current:** Users can have multiple businesses, switch via session. Pro plan ($99/mo) supports 5 locations.

**Improvements:**
- Add a location switcher dropdown in the navbar (currently switching requires navigating to a separate page)
- Add aggregate stats across all locations on the analytics page
- Add a "send to all locations" bulk send option

---

#### 5e: Webchat Widget

**Future product expansion (post $5k MRR).** Would significantly increase retention — businesses use it daily.

Spec: Embed a `<script>` snippet → shows a chat bubble on the business's website → messages come into a ReviewMate inbox tab → owner replies via ReviewMate app → creates daily engagement habit that makes churn much harder.

**Note:** This is a major feature. Do not start until reviewmate has 20+ paying customers.

---

## Key Files

| File | Purpose |
|------|---------|
| `/routes/web.php` | All application routes |
| `/app/Models/User.php` | User model — `isAdmin()`, `onFreePlan()`, `currentBusiness()`, Cashier `Billable` |
| `/app/Models/Business.php` | Business model — Google OAuth fields, `isGoogleConnected()`, `conversionRate()`, `averageRating()` |
| `/app/Models/Customer.php` | Customer — `unsubscribed_at`, `unsubscribe_token`, `initials()`, `requestStatus()` |
| `/app/Models/ReviewRequest.php` | Tracks each review request — status: `sent`, `opened`, `reviewed`. Has `hasRecentRequest()` static guard |
| `/app/Models/Review.php` | Google reviews — `google_review_name`, `google_reply`, `wasViaReviewMate()` |
| `/app/Http/Controllers/ReviewController.php` | Review inbox, AI reply suggestions, post reply to Google |
| `/app/Http/Controllers/CustomerController.php` | Customer CRUD, CSV import/export, bulk send, unsubscribe |
| `/app/Http/Controllers/BillingController.php` | Stripe Checkout, billing portal |
| `/app/Http/Controllers/GoogleBusinessController.php` | Google OAuth flow (connect, callback, disconnect) |
| `/app/Http/Controllers/OnboardingController.php` | 3-step onboarding wizard |
| `/app/Services/GoogleBusinessProfileService.php` | Google API calls — fetch reviews, post replies, refresh tokens |
| `/app/Services/TwilioSmsService.php` | SMS sending via Twilio |
| `/app/Services/DefaultTemplateService.php` | Generates default email templates on business creation |
| `/app/Ai/Agents/ReviewReplyAgent.php` | Claude AI reply suggestion agent |
| `/app/Jobs/SyncGoogleReviews.php` | Scheduled job — syncs reviews every 2 hours per connected business |
| `/app/Jobs/SendFollowUpRequests.php` | Scheduled job — daily 09:00, sends day-5 follow-ups |
| `/app/Jobs/SendWeeklyDigests.php` | Scheduled job — Mondays 08:00, sends weekly digest emails |
| `/app/Mail/ReviewRequestMail.php` | Email with tracking link and personalisation |
| `/app/Mail/WeeklyDigestMail.php` | Monday digest with stats |
| `/app/Mail/NewReviewAlertMail.php` | Alert when new Google review syncs |
| `/resources/js/pages/welcome.tsx` | Landing page (currently shows waitlist — needs updating post-launch) |
| `/resources/js/pages/dashboard.tsx` | Main dashboard with stats, chart, recent reviews |
| `/resources/js/pages/reviews/index.tsx` | Review inbox — needs reply / replied / all tabs |
| `/resources/js/pages/reviews/show.tsx` | Single review — AI reply suggestions, post reply |
| `/resources/js/pages/customers/index.tsx` | Customer list — bulk send, CSV import, add, edit, delete |
| `/resources/js/pages/settings/billing.tsx` | Billing plan selection, Stripe Checkout, portal |
| `/resources/js/pages/analytics.tsx` | Multi-location analytics |
| `/resources/js/pages/qr-code.tsx` | QR code page |
| `/resources/js/pages/quick-send.tsx` | One-off quick send |
| `DEPLOYMENT.md` | Full deployment guide with all env vars |
| `MARKET.md` | Competitor analysis and positioning |
| `CHORES.md` | Ongoing ops, automations, support templates |

---

## Tests

**Current count: 107 tests passing, 383 assertions.**

```bash
# Run all tests
./vendor/bin/pest --parallel

# Run specific test file
php artisan test --compact --filter=ReviewRequestTest

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/pest --coverage
```

**What is covered:**

| Test file | Coverage area |
|-----------|--------------|
| `ReviewRequestTest.php` | Sending requests (email + SMS), channel validation, free plan limits |
| `ReviewTrackingTest.php` | Token tracking link updates review request status |
| `ResendGuardTest.php` | 30-day guard prevents duplicate requests |
| `CustomerTest.php` | CRUD, free plan 50-customer limit, bulk send |
| `CsvImportTest.php` | CSV import — column mapping, duplicate handling, skipped rows |
| `CustomerExportTest.php` | CSV export format and content |
| `OnboardingTest.php` | 3-step wizard, onboarding completion, redirect logic |
| `BusinessSettingsTest.php` | Business settings update, Google Place ID |
| `MultiBusinessTest.php` | Creating businesses, switching between them |
| `DashboardTest.php` | Stats calculation, chart data, redirect if not onboarded |
| `TemplateTest.php` | Email template editor CRUD |
| `MailTest.php` | Mailable classes render with correct content |
| `SyncGoogleReviewsTest.php` | Google sync job, token refresh, review upsert |
| `SendFollowUpRequestsTest.php` | Follow-up automation — day 5 logic, already-reviewed guard |
| `QuickSendTest.php` | Quick send to ad-hoc customer |
| `WaitlistTest.php` | Waitlist signup form validation and storage |
| `LegalTest.php` | Terms and Privacy pages return 200 |
| `Settings/ProfileUpdateTest.php` | Profile update via WorkOS |

**What is NOT covered (known gaps):**
- AI reply suggestions endpoint (mocking Anthropic)
- Google OAuth callback flow (integration test)
- Stripe webhook handling (Cashier provides its own tests)
- Billing controller — subscribe and portal redirect
- Weekly digest email job
- New review alert email

These gaps are acceptable pre-launch. The core request/review flow is thoroughly covered.

---

## Git Rules

- Always commit and push when done with any task.
- Use atomic commits: one commit per logical change.
- Commit format: `type: short description` where type is `feat`, `fix`, `docs`, `chore`, `test`, `refactor`.
- Run tests before committing: `./vendor/bin/pest --parallel`
- Run PHP lint before committing: `./vendor/bin/pint --dirty`
- Run TypeScript check before committing: `npm run types:check`
- Never leave work uncommitted locally — always push before ending a session.
- If working on a feature, use a branch named `feat/description` and open a PR.

```bash
git add path/to/changed/files
git commit -m "feat: add onboarding email drip sequence"
git push
```

---

## Business Context

**Target market:** Australian local businesses — tradies (plumbers, electricians, builders), cafes and restaurants, allied health (physios, chiros, gyms), salons, retail.

**Primary acquisition channel:** Cold email via Outpost (tool at `/Users/joshsalway/development/Outpost`). Three campaigns ready above (Task 2). Run these immediately after deploy.

**Launch pricing strategy:** Offer first 5 customers $29/month (instead of $49) in exchange for a testimonial and feedback call.

**Revenue goal:** 10 paying customers = ~$490/month. Validates the product before scaling spend.

**Competitor positioning:** We are cheaper than NiceJob ($75/mo), far cheaper than Birdeye ($299/mo) and Podium ($399/mo). Our differentiator is Claude AI replies (better than basic competitors) and deep Google Business Profile API integration.

See `MARKET.md` for full competitor breakdown.
