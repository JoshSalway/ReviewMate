# ReviewMate — Product Requirements Document

**Version:** 1.0
**Status:** Launched
**Owner:** Josh Salway
**Last updated:** 2026-03-05

---

## 1. Overview

ReviewMate is a Google review management SaaS for Australian local businesses. It automates the process of asking customers for reviews, following up, syncing Google reviews, and replying with AI — giving small businesses the same review velocity as large chains at a fraction of the cost.

**Target market:** Local businesses in Australia — tradies (plumbers, electricians, builders), cafes, salons, gyms, allied health (physios, chiropractors, GPs).

**Primary differentiator:** Claude AI-powered reply suggestions + deep Google Business Profile API integration, at 5-10x the price of Birdeye and Podium.

---

## 2. Pricing

| Plan | Price | Limits |
|------|-------|--------|
| Free | $0/month | 1 location, 50 customers, 10 requests/month |
| Starter | $49/month AUD | 1 location, unlimited |
| Pro | $99/month AUD | 5 locations, unlimited |

---

## 3. Core User Stories

### Review requests
- As a business owner, I want to send review request emails/SMS to customers after a job so they can leave a Google review
- As a business owner, I want ReviewMate to follow up automatically if a customer does not respond within 5 days
- As a business owner, I do not want the same customer to receive duplicate requests within 30 days

### Review management
- As a business owner, I want to see all my Google reviews in one inbox
- As a business owner, I want AI-suggested replies so I can respond to reviews quickly and professionally
- As a business owner, I want to post replies to Google directly from ReviewMate

### Integrations
- As a tradie using ServiceM8, I want review requests sent automatically when a job is completed
- As a business using Xero, I want review requests sent when an invoice is paid
- As a physio using Cliniko, I want review requests sent after an appointment is completed

### Customer management
- As a business owner, I want to import my customer list from a CSV
- As a business owner, I want customers to be able to unsubscribe from review requests
- As a business owner, I want to send a quick one-off review request without saving a customer record

### Analytics
- As a business owner, I want to see my average rating, total reviews, and conversion rate
- As a multi-location business, I want to compare performance across all my locations

---

## 4. Feature Map

### Core (built)
- WorkOS authentication (passwordless, SSO)
- Multi-business support
- Customer CRUD + CSV import/export
- Email + SMS review requests (ClickSend default, Twilio fallback)
- Automated day-5 follow-ups
- 30-day duplicate request guard
- Review request tracking link (marks request as opened/reviewed)
- Google Business Profile OAuth + auto-sync every 2 hours
- AI reply suggestions (Claude Haiku via Anthropic)
- Post replies to Google via API
- Saved reply templates
- Email template editor
- QR code page
- Quick Send (one-off, no customer record needed)
- Multi-location analytics
- Weekly digest email (Mondays 08:00)
- New review alert email
- Onboarding wizard (3-step)
- Stripe billing (Cashier) + plan enforcement
- Stripe webhooks
- Terms of Service + Privacy Policy

### Integrations (built)
- ServiceM8 (tradie job management — OAuth + webhook)
- Xero (invoice paid — OAuth + webhook)
- Cliniko (allied health — API key + polling)
- Timely (salons — OAuth + webhook)
- Simpro (large tradie businesses — OAuth + webhook)
- Halaxy (GPs, allied health — API key + polling)
- Generic incoming webhook (Zapier, Make, Fergus)
- Facebook Reviews link

### API (built)
- REST API at /api/v1 (Sanctum auth)
- Endpoints: businesses, customers, review-requests, reviews, stats
- MCP server (TypeScript) for AI assistant integration
- OpenAPI spec at docs/openapi.yaml
- Swagger UI at /api/docs

### Admin (built)
- /admin with superadmin role guard
- Role column on users (user/admin/superadmin)

### Public pages (built)
- / — Landing page
- /pricing — Plan comparison
- /features — Feature details
- /changelog — Release notes
- /docs — Customer documentation

---

## 5. Non-Goals (v1)

- Mobile app
- Chat widget / webchat
- White-labelling
- Agency reseller features
- Reputation monitoring (Yelp, TripAdvisor, etc.)

---

## 6. Success Metrics

| Metric | Target |
|--------|--------|
| First paying customer | Within 2 weeks of launch |
| 10 paying customers | Within 60 days |
| Review requests sent | > 100 in first month |
| Conversion rate | > 20% (request → review) |
| Monthly churn | < 5% |

---

## 7. Constraints

- Must be deployable on Laravel Cloud (Growth plan)
- Must work with Australian phone numbers (ClickSend AU carrier)
- Must be CAN-SPAM compliant (unsubscribe on every email)
- Must handle Stripe webhook signature verification
- No AWS/cloud costs beyond Laravel Cloud (keep ops simple)
