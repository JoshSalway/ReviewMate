# ReviewMate — Next Steps & Known Gaps

## Production Readiness

- [ ] Stripe price IDs (`services.stripe.price_starter`, `services.stripe.price_pro`) must be set in production `.env` — billing page returns null prices if missing, and tests stub `config()` values
- [ ] WorkOS credentials must be configured before auth works — no graceful fallback
- [ ] ClickSend or Twilio API keys required for SMS to function — SMS silently swallowed via `rescue()` if unconfigured
- [ ] Google Business Profile OAuth client credentials required before Google connect works
- [ ] Mailgun credentials required — queued emails silently fail without them
- [ ] Stripe webhook secret must match Cashier config — webhook endpoint at `/stripe/webhook` is public with no token validation beyond Cashier's own signature check

## Bugs Discovered During Testing

### QuickSend: composite-key customer dedup is fragile

`QuickSendController::send()` uses `firstOrCreate(['email' => X, 'phone' => Y])` as the lookup. If the caller provides only email (and no phone), the lookup becomes `{email: X, phone: null}`. An existing customer with `email: X` and a real phone number will NOT be matched — a duplicate customer is silently created.

This means:
- The unsubscribed guard can be bypassed by a customer with an existing phone number
- The 30-day duplicate guard can be bypassed the same way
- Customers accumulate duplicates over time if quick-send is used repeatedly

**Fix needed:** Look up customer by email OR phone separately before creating.

### API `ReviewRequestController::store` misses plan limit enforcement

The web `ReviewRequestController::store` enforces the free plan 10/month cap. The API version (`Api\V1\ReviewRequestController::store`) does not. A free plan user with an API token can send unlimited review requests via the API.

### Business `googleReviewUrl()` returns `'#'` for disconnected businesses

`Business::googleReviewUrl()` returns `'#'` when no `google_place_id` is set. The track endpoint (`/r/{token}`) then redirects to `#` (a relative hash). In production this would redirect back to the same page, not to a Google review page. The fallback URL `https://search.google.com/local/writereview` in `ReviewRequestController::track()` is correct, but the business model method itself is misleading.

### `follow_up_channel` logic silently ignores unknown values

`SendFollowUpRequests` uses a `match` expression with `default` to handle an unknown `follow_up_channel`. If an unexpected value is stored in the database, the `default` branch fires the same-channel logic as `'same'`. No error is thrown.

## Missing Features

- [ ] **Email unsubscribe confirmation**: The unsubscribe page (`/unsubscribe/{token}`) renders the `unsubscribed` view but does not send a confirmation email to the customer
- [ ] **API rate limiting per user**: The API is rate limited at 60 req/min per IP but not per user/token. A single user with many tokens could bypass this
- [ ] **Stripe webhook handlers**: There are no custom webhook handlers (e.g. for subscription cancelled, payment failed) beyond what Cashier handles by default. No event listener for `invoice.payment_failed` to downgrade a user's plan
- [ ] **Review reply via API**: The API exposes reviews (read-only) but has no endpoint to post a Google reply. The web UI has this functionality
- [ ] **Customer update via API**: The API has no endpoint to update or delete customers
- [ ] **Referral reward email**: `IssueReferralReward` job is present but not tested for edge cases (already-rewarded referrals, expired referral tokens)
- [ ] **`ReplyTemplate` has no factory**: `ReplyTemplate::factory()` does not exist. Tests must use `ReplyTemplate::create()` directly. This was discovered when writing tests
- [ ] **`onFreePlan()` trial check**: The `trial_ends_at` field on users is checked in `onFreePlan()` but no code sets this field during the normal signup flow. It appears to be set only via `IssueReferralReward`. No tests cover this path

## Testing Gaps

After this session the following areas remain untested or lightly tested:

- `ReviewController::replySuggestions` — calls Claude AI agent; no test because it would require mocking a deeply nested service chain. Should be tested with a proper AI client mock
- `GoogleBusinessController` (disconnect flow) — the disconnect route is tested for redirect in onboarding tests but the OAuth state parameter validation is not exercised
- `ReferralController` — basic tracking tests exist but the full referral reward flow is not end-to-end tested
- `PublicController` (pricing, features, changelog, docs pages) — basic smoke tests only
- Stripe billing portal and checkout URL generation — tested to the extent of asserting a redirect is returned, but actual Stripe API calls are not mocked, so these tests rely on the user not being a Cashier customer
- `IssueReferralReward` job — no tests at all
- `RefreshGoogleStats` job — no tests at all
- `SyncGoogleReviews` with no Google integration — untested path when `$business->integration('google')` returns null

## Technical Debt

- `QuickSendController` and `ReviewRequestController` both contain nearly identical send logic (email + SMS dispatch + duplicate guard). This should be extracted to a shared service or action class
- `AnalyticsController` performs multiple `count()` queries inside a loop per business — N+1 style. Should be refactored to use subqueries or eager loading for scale
- `BusinessController::store` plan-limit logic uses nested `if` conditions that are hard to follow and test. The starter/pro distinction is tied to a specific Stripe price ID which must be configured; if unconfigured the check silently passes
- `SendFollowUpRequests` loads all eligible requests into memory with no chunking — could be slow at scale
- `hasRecentRequest` on `ReviewRequest` model uses 30-day window in the model but the controller error message says "30 days" while the original product spec mentioned 90-day dedup for incoming webhooks. The two windows are inconsistent
- No middleware or policy to protect against accessing resources of a deleted/suspended business
- No soft-deletes on `Business`, `Customer`, or `ReviewRequest` — data is permanently deleted

## Discovered During Testing

- The `isAdmin` check on `User` reads `is_admin` (boolean column) while `isSuperAdmin` reads `role === 'superadmin'`. These two concepts exist in parallel with overlapping usage — `is_admin` grants billing bypass and plan bypasses; `role=superadmin` gates the Filament admin panel. This is confusing and should be unified
- `Business::slug` is generated in an `after creating` hook but the `slug` column is not `unique` in a database-enforced way — only checked via PHP code. A race condition could create duplicate slugs under concurrent requests
- The `webhook_token` on businesses is 40 chars of random string — no HMAC signing. Any caller who discovers the URL can send arbitrary webhook payloads
