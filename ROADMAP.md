# ReviewMate Roadmap

Features planned to close the gap on NiceJob and expand the product. Ordered by impact vs effort.

---

## Already Built (do not rebuild)

- AI review reply suggestions (3 options via Claude Haiku, `ReviewReplyAgent`)
- Follow-up sequence (3-day SMS/email reminder, configurable per business)
- Review widget (embeddable `<script>` tag, public API, all plans)
- Referral program (customer + business referral, 1-month free reward)
- Self-confirm "I already reviewed" button in emails
- Two-tier review verification (self_confirmed → verified via Google API, or unverified_claim)
- Unsubscribe page review CTA
- Unverified claim dashboard banner

---

## Tier 1 — High Impact, Buildable Soon

### 1. Stories — Auto Social Media Posts from Reviews

**Why:** NiceJob's single biggest differentiator. Tradies love seeing their reviews turned into branded posts automatically. It's marketing they don't have to think about.

**How it works:**
1. A new review comes in (via `SyncGoogleReviews` or self-confirm)
2. ReviewMate generates a branded image using the review text + business logo/colours
3. Business gets an email: "Your new review from Sarah is ready to share" + the image attached
4. Optional: auto-post to their connected Facebook Page or Google Business Profile
5. Manual mode first (email them the image) → auto-post as v2

**Implementation options:**
- **Bannerbear API** (`bannerbear.com`) — templated image generation, ~$49/mo. Easiest.
- **Imgix + HTML canvas** — generate a Blade template → headless browser screenshot (Browsershot). Free but complex.
- **Cloudinary** — image transformation API, has text overlay. Middle ground.

**Template design:** Business name + logo, star rating, reviewer first name, truncated review quote, business type colour scheme. One template per business type (tradie, cafe, salon, etc.).

**Files to create:**
- `app/Services/StoryGeneratorService.php` — calls Bannerbear or Cloudinary
- `app/Jobs/GenerateReviewStory.php` — dispatched after review verified
- `app/Mail/ReviewStoryMail.php` — sends image to business owner
- `resources/js/pages/settings/stories.tsx` — connect Facebook, choose template style
- Migration: add `stories_enabled`, `story_template`, `facebook_page_id`, `facebook_page_token` to businesses

**Plan gating:** Pro plan only.

**Estimated effort:** 3-5 days (with Bannerbear API). 1-2 weeks (DIY image generation).

---

### 2. PWA Push Notifications (Mobile App feel, no App Store required)

**Why:** Tradies are on their phones all day. A push notification the moment a review comes in — "⭐⭐⭐⭐⭐ Sarah just reviewed Dave's Plumbing!" — is high-value and NiceJob does it via a native app. A PWA gets 80% of the way there for 10% of the effort.

**How it works:**
- ReviewMate is already a web app served over HTTPS — PWA-ready
- Add a `manifest.json` and service worker
- Use the **Web Push API** (Laravel + `web-push-php` package) for server-side push
- Business owner visits the site on their phone, gets prompted to "Add to Home Screen"
- On first load: "Allow notifications?" — they tap yes
- Store their push subscription in the database
- When a review comes in → dispatch a push notification

**Notification triggers:**
- New review received (any rating)
- New reply to a review
- Follow-up sequence sent (optional — probably too noisy)
- Weekly summary (Saturday morning — "You got 3 reviews this week!")

**Implementation:**
- `composer require minishlink/web-push`
- `app/Services/PushNotificationService.php`
- `app/Models/PushSubscription.php` + migration (`endpoint`, `public_key`, `auth_token`, `user_id`)
- `POST /push/subscribe` — stores subscription from browser
- `app/Jobs/SendPushNotification.php`
- `public/sw.js` — service worker (handles push events)
- `public/manifest.json`
- Frontend: subscription prompt on dashboard after login

**Estimated effort:** 3-4 days.

---

### 3. Multi-Platform Review Requests (Tripadvisor, Yelp, Houzz)

**Why:** Some businesses care about Tripadvisor (cafes, tourism) or Houzz (builders, interior designers) as much as Google. NiceJob lets you direct customers to multiple platforms.

**How it works:**
- During onboarding or settings, business adds their Tripadvisor/Yelp/Houzz profile URL
- Review request SMS/email includes multiple options:
  > "You can leave a review on [Google] or [Tripadvisor] — whichever you prefer."
- Track which platform they clicked (separate tracking tokens per platform)

**Implementation:**
- Migration: add `tripadvisor_url`, `yelp_url`, `houzz_url` to businesses
- Update `ReviewRequestMail` + SMS templates to show multiple CTAs when URLs are present
- Update `TrackingController` to handle platform-specific click tracking
- Settings UI: "Your review platforms" section

**Estimated effort:** 1-2 days.

---

### 4. Team Members / Staff Attribution

**Why:** Larger businesses (plumbing companies with 5 vans, salon with 8 stylists) want to know which staff member drove a review. "Sarah's customers leave 4.9 stars on average" is motivating for staff and useful for the owner.

**How it works:**
- Business owner adds staff members (name, email, phone)
- When sending a review request, they optionally tag which staff member did the job
- Dashboard shows per-staff review stats
- Optional: staff member gets a notification when their customer reviews

**Implementation:**
- `staff_members` table: `id`, `business_id`, `name`, `email`, `phone`, `avatar`
- `review_requests.staff_member_id` FK
- `StaffMemberController` (CRUD)
- `QuickSendController` — add optional staff_member_id to send form
- Dashboard stats breakdown by staff member
- Integration hooks: ServiceM8 passes job assignee → map to staff member

**Estimated effort:** 3-4 days.

---

## Tier 2 — Valuable, Build After First Revenue

### 5. AI Review Response — Tone Customisation

**Currently:** `ReviewReplyAgent` generates 3 generic options.

**Enhancement:** Let business owner set their preferred tone in settings:
- Professional and formal
- Warm and friendly (default)
- Casual and relaxed
- Short and punchy

Pass tone preference into the agent instructions. Small change, big perceived value.

**Estimated effort:** 2-3 hours.

---

### 6. Negative Review Triage

**Why:** A 1-star review needs a different response than a 5-star. Currently the reply flow is the same regardless.

**How it works:**
- Reviews with rating <= 2 trigger an immediate email to the business owner: "You got a 1-star review — act fast"
- Different AI reply suggestions for negative reviews (empathetic, de-escalating tone)
- Optional: private response link — "Would you like to contact this customer directly to resolve the issue?" → one-click to send them a personal email/SMS
- Dashboard: "Negative review alert" banner

**Implementation:**
- `SyncGoogleReviews` — check rating, dispatch `NegativeReviewAlert` notification
- `app/Notifications/NegativeReviewReceived.php`
- Update `ReviewReplyAgent` to detect low-rating context and adjust tone
- New settings toggle: "Alert me immediately for reviews under 3 stars"

**Estimated effort:** 1-2 days.

---

### 7. Automated Google Business Profile Replies

**Why:** Most business owners never reply to reviews. NiceJob has AI auto-reply. ReviewMate could auto-post a reply using the existing `GoogleBusinessProfileService::postReply()` — it's already built.

**How it works:**
- Optional setting: "Auto-reply to 5-star reviews" (safest to start — don't auto-reply to negatives)
- When a 5-star review comes in, `ReviewReplyAgent` generates a reply → auto-posted to Google
- Business gets an email: "We auto-replied to Sarah's review — here's what we said. [Edit reply]"
- They can override within 24 hours

**Implementation:**
- Settings toggle: `auto_reply_enabled` (boolean, default false), `auto_reply_min_rating` (default 5)
- `SyncGoogleReviews` — after creating Review, check auto_reply_enabled → dispatch `AutoReplyToReview` job
- `app/Jobs/AutoReplyToReview.php` — generates reply via agent, posts via `GoogleBusinessProfileService`
- `app/Mail/AutoReplyPostedMail.php` — notifies business owner

**Estimated effort:** 1 day (all the pieces exist).

---

### 8. Bulk Import from CSV / Past Customer Outreach

**Why:** A plumber signing up today has 3 years of past customers in their phone contacts or an Excel spreadsheet. They want to send review requests to all of them on day one.

**Currently:** CSV import exists in `LeadController` (Outpost) but not ReviewMate.

**How it works:**
- Upload a CSV: Name, Email, Phone, Date of Service (optional)
- ReviewMate imports and sends review requests in batches (50/day to avoid spam flags)
- Free plan: 50 total. Pro plan: unlimited.

**Implementation:**
- `POST /customers/import-csv` → `CustomerController@importCsv`
- Queue: `ProcessCsvImport` job (batched, rate-limited)
- UI: drag-and-drop upload on Customers page, column mapping step, preview before sending

**Estimated effort:** 2-3 days.

---

### 9. White-Label / Agency Tier

**Why:** Marketing agencies managing reviews for 20 local business clients would pay $299+/month for a white-label ReviewMate under their own brand.

**How it works:**
- Agency signs up, sets their brand (logo, colours, domain)
- They manage multiple client businesses from one dashboard
- Clients see the agency's brand, not ReviewMate
- Agency billing: flat monthly fee per seat

**Implementation:**
- `agencies` table: `id`, `name`, `logo`, `primary_colour`, `domain`, `user_id`
- `businesses.agency_id` FK
- Subdomain routing: `agency-name.reviewmate.app` or custom domain
- Agency dashboard: list of client businesses, aggregate stats
- New pricing tier: Agency $149/mo (up to 10 businesses), $299/mo (unlimited)

**Estimated effort:** 1-2 weeks.

---

## Tier 3 — Explore After Product-Market Fit

### 10. Native Mobile App (iOS + Android)

NiceJob has a native app. A PWA (Tier 1, item 2) gets most of the way there. Build a native app only if customers specifically ask for it after launch.

**Stack if built:** React Native + Expo (reuses existing React components). Estimated 4-6 weeks.

### 11. Facebook Auto-Post Integration

Facebook Graph API for auto-posting Stories (branded review images) directly to the business's Facebook Page. Requires Facebook App review approval (2-4 weeks). Build after Stories feature is proven.

### 12. QR Code Printed Materials Generator

Business can design and download a printable card/flyer/sticker with their QR code and a "Leave us a review" message. PDF generation via `dompdf` or `browsershot`. Estimated 1-2 days.

### 13. Review Gating Alternative (Feedback First)

Ask customers for a private rating (1-5) before directing them to Google. If 4-5 stars → send to Google review. If 1-3 stars → collect private feedback only, don't send to Google.

**Note:** Google's terms technically discourage this but NiceJob does it. Tread carefully — implement as "collect feedback" not "filter reviews".

---

## Competitive Gap Summary

| Feature | NiceJob | ReviewMate | Priority |
|---------|---------|------------|----------|
| AI review responses | Yes | Yes (built) | Done |
| Follow-up sequences | Yes | Yes (built) | Done |
| Review widget | Yes | Yes (built) | Done |
| Referral program | Yes | Yes (built) | Done |
| Self-confirm button | No | Yes (built) | Done |
| Stories / social posts | Yes | Planned (Tier 1) | High |
| Mobile push notifications | App | PWA planned (Tier 1) | High |
| Multi-platform requests | Yes | Planned (Tier 1) | Medium |
| Staff attribution | Yes | Planned (Tier 1) | Medium |
| AI tone customisation | No | Planned (Tier 2) | Low |
| Negative review triage | Yes | Planned (Tier 2) | Medium |
| Auto Google replies | Yes | Planned (Tier 2) | Medium |
| CSV bulk import | Yes | Planned (Tier 2) | Medium |
| White-label / agency | Yes | Planned (Tier 3) | Low |
| Native mobile app | Yes | Tier 3 | Low |
| Price (AUD) | ~$115/mo | $49-99/mo | Won |
| AU integrations (ServiceM8 etc.) | Partial | Full | Won |
