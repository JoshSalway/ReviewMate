# ReviewMate — Operations & Chores

> This file documents ongoing admin tasks, what already runs automatically,
> and copy-paste agent prompts to automate the manual work.

---

## What Runs Automatically (No Action Needed)

| Task | How | Frequency |
|------|-----|-----------|
| Review request emails | Queue worker → email job | On trigger |
| Review request SMS | Queue worker → Twilio job | On trigger |
| Review status tracking | Webhook / polling | On review received |
| Reminder follow-ups | Scheduler → reminder jobs | Per campaign config |
| Stripe billing events | StripeWebhookController | On payment/subscription |

---

## What You Do Manually

| Task | Frequency | Notes |
|------|-----------|-------|
| Customer onboarding | Per new customer | Help them get their Google review link + import customer list |
| SMS delivery failures | Occasional | Twilio dashboard → investigate invalid numbers |
| Support emails | Per customer issue | Reply within 24h |
| Failed payment follow-up | Monthly | Stripe dashboard → Cashier handles retry logic |

---

## Automations Already Built

- ✅ Review request sending (email + SMS)
- ✅ Stripe subscription management
- ✅ Open/click tracking on review request emails

---

## Automations to Build

### Priority 1 — Onboarding Email Sequence
**Welcome new customers automatically. Reduces support load.**

```
You are working on ReviewMate — a Google review request SaaS for local businesses.
Stack: Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth, Resend.

Read AGENTS.md and INSTRUCTIONS.md before writing code.

Your task: send an automated onboarding email sequence to new customers.

1. When a new user registers, dispatch SendOnboardingEmail job with a 0-minute delay
2. Onboarding email 1 (immediate): "Welcome to ReviewMate — here's how to get your first review"
   - Step 1: Find your Google Business review link (link to guide)
   - Step 2: Import your first customer
   - Step 3: Send your first review request
3. Onboarding email 2 (day 3, if no review request sent yet): "Quick tip — most businesses get their first review within 48 hours"
4. Use Resend (MAIL_MAILER=resend), Blade email templates
5. Add unsubscribe link to each email

After all work:
- git add -A && git commit -m "feat: automated onboarding email sequence"
- git push
```

---

## Customer Support Template Responses

**"How do I get my Google review link?"**
> Go to Google Maps → search your business → click "Get more reviews" → copy the link.
> Paste it into ReviewMate under Settings → Review Link.

**"My SMS isn't sending"**
> Check the phone number format — it must include the country code (e.g. +61 for Australia).
> Go to Customers → find the customer → check their number.

**"A customer says they didn't get the email"**
> Check their email address for typos. Also ask them to check their spam folder.
> ReviewMate emails come from noreply@reviewmate.app.

---

## Monthly Checklist (10 mins)

- [ ] Check SMS delivery rate in Twilio dashboard (> 95% = healthy)
- [ ] Review any support emails
- [ ] Check Stripe for failed payments
- [ ] Review open rate on review request emails (> 40% = healthy for local biz)
