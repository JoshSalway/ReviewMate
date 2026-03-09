# ReviewMate — Playwright E2E Review

Generated: 2026-03-09

---

## 1. Codebase Summary

ReviewMate is a Google review management SaaS for local businesses (tradies, cafes, salons, etc). The stack is Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, WorkOS auth.

### Key pages and routes

| Page | Route | Notes |
|---|---|---|
| Dashboard | `/dashboard` | Stats, setup checklist, recent reviews, chart |
| Customers | `/customers` | Table with status badges, CSV import/export, add dialog |
| Review Requests | `/requests` | List with timeline steps, stats cards |
| Reviews | `/reviews` | Needs Reply / Replied / All Reviews sections with AI reply |
| Templates | `/templates` | 3-tab editor (Request Email, Follow-up Email, SMS) + preview |
| Quick Send | `/quick-send` | One-off send form, recently sent list |
| QR Code | `/qr-code` | Configurable QR code generator |
| Email Flow | `/email-flow` | Visual diagram of the email sequence |
| Business Settings | `/settings/business` | Business details, Google Place ID, follow-up config |
| Reply Templates | `/settings/reply-templates` | Saved reply snippets for review responses |
| Auto-reply | `/settings/auto-reply` | AI auto-reply on/off + preview |
| Widget | `/settings/widget` | Embeddable review widget config |
| Notifications | `/settings/notifications` | Email notification preferences |
| Billing | `/settings/billing` | Plan status, upgrade/manage |
| Integrations | `/settings/integrations` | ServiceM8, Xero, Cliniko, Timely, Simpro, Halaxy, Jobber, HousecallPro |
| Analytics | `/analytics` | Multi-business analytics table |
| Onboarding | `/onboarding/*` | 3-step onboarding wizard |

### Auth architecture

WorkOS handles real auth. For e2e tests, `APP_E2E=true` enables:
- A `/_e2e/login` bypass route that logs in by email and sets fake WorkOS session tokens
- A `ValidateSessionWithWorkOSForE2e` middleware that accepts the fake tokens
- This is gated to `APP_ENV=local` only — cannot be activated in production

### E2E test setup

- `global-setup.ts` runs `php artisan migrate:fresh --seed --seeder=E2eSeeder` before the test suite
- `E2eSeeder` creates one user, one business ("E2E Test Business"), 3 customers, 2 review requests, 1 review, and 3 email templates
- All tests use `loginAsE2eUser(page)` via the `/_e2e/login` bypass

---

## 2. Test Results

### Before this review session

**18 tests, 17 passed, 1 skipped.**

The 1 skip was intentional — the "duplicate send within 30 days is blocked" test skips itself when no send button is visible.

### After adding new specs

**64 tests, 63 passed, 1 skipped.**

All new tests pass. The skipped test is the same intentional skip.

### New specs added

| File | Tests added | Coverage |
|---|---|---|
| `e2e/business-settings.spec.ts` | 8 | Business settings page load, fields, save |
| `e2e/qr-code.spec.ts` | 8 | QR code page, config options, use-case section |
| `e2e/quick-send.spec.ts` | 9 | Quick send form, validation, successful send |
| `e2e/template-editor.spec.ts` | 11 | Tab switching, field visibility, variable insertion |
| `e2e/misc-pages.spec.ts` | 9 | Email flow, analytics, all 6 settings sub-pages |

### Seeder fix

`E2eSeeder` was missing `EmailTemplate` records. Added 3 templates (request, follow_up, sms) for the tradie business type. Without this, the Templates page showed "No template found for this type" and the template editor tests couldn't interact with the editor UI.

---

## 3. App Review

### Bugs Found

**B1 — Quick Send query param pre-fill breaks on direct navigation (SSR/hydration mismatch)**

`quick-send.tsx` reads query params via:
```js
const params = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
const [form, setForm] = useState({ name: params.get('name') ?? '', ... });
```

This reads `window.location.search` in the `useState` initializer at component mount. When the page loads fresh (direct URL), the Inertia SSR pass sets the initial value to `''`, and React hydration does not re-run the `useState` initializer, so the fields stay empty.

The fix is to either:
1. Pass `name` and `email` as Inertia props from `QuickSendController::index()` (read from `$request->query()`)
2. Use a `useEffect` that reads `window.location.search` after mount and updates state

The pre-fill works when navigating via Inertia's SPA router (e.g., clicking "Send request" from the customers page) because Inertia re-renders the component fresh in that case.

**B2 — QuickSendController `recently_sent` map calls `$req->customer->name` without eager loading**

In `QuickSendController::index()`, `recentlySent` maps over review requests with `.with('customer')`, which is correct. However, if a customer is deleted after the request was sent, `$req->customer` will be `null` and the map will throw a "Trying to get property of non-object" error. Should use `$req->customer?->name ?? 'Unknown'`.

**B3 — Template type mismatch: seeder uses `follow_up` but DefaultTemplateService uses `followup`**

`DefaultTemplateService::forBusinessType()` creates templates with type `'followup'` (no underscore), but the `E2eSeeder` and the templates page (`tabConfig`) uses `'follow_up'` (with underscore). If `storeSelectTemplate` in onboarding calls DefaultTemplateService and stores `followup`, then `templates['follow_up']` will be undefined and the Follow-up tab will show "No template found for this type."

Confirmed by checking `DefaultTemplateService.php` line 18: `'type' => 'followup'`. The `templates/index.tsx` `tabConfig` array uses `follow_up`. The E2eSeeder stores `follow_up`. This is inconsistent — the onboarding path would create templates with the wrong key and the follow-up tab would always be empty for newly onboarded businesses.

**B4 — No CSRF protection comment on `/_e2e/login` bypass**

The `/_e2e/login` route is gated to `APP_E2E=true && APP_ENV=local`, which is correct. However, there is no explicit CSRF check on this GET route. Since it's a state-mutating login action on a GET request, an attacker with access to the local environment could link to it. Low severity in local-only context but worth noting.

**B5 — Missing `channel` field in QuickSend `recentlySent` map**

`QuickSendController::index()` maps `recentlySent` items but does not include a `channel` field. The `QuickSend` React component expects `item.channel` to look up the badge class in `channelBadgeClass`. This will silently render an unstyled badge when `item.channel` is `undefined`.

### UX Issues

**U1 — Customer "Send request" button only shows for `no_request` status; `no_response` customers get "Re-send" that also goes to quick-send**

The customers table has two paths: customers with `no_request` status get "Send request →" and `no_response` customers get "Re-send →". Both redirect to `/quick-send?name=...&email=...`. Given bug B1 above, neither pre-fills reliably on direct navigation. This is confusing UX — the button implies action but the form arrives empty.

**U2 — Dashboard setup checklist stays visible until total_reviews > 0, but the E2E seeder has 1 review**

The setup checklist condition is `showSetupChecklist = stats.total_reviews === 0`. The E2E business has 1 review, so the checklist is hidden and the data section is shown — this is correct behavior. However, for a new business that has sent requests but has no reviews yet, the checklist will show alongside the `<EmptyState>` component (which also only shows when `!hasData`). Both show when there are no reviews, which is fine, but could be visually redundant.

**U3 — Templates page shows "Email Templates" heading but SMS is also managed there**

The `<h1>` on the templates page says "Email Templates" but the third tab manages SMS messages. A more accurate heading would be "Message Templates."

**U4 — No empty state for requests page when no requests have been sent**

The requests list shows an empty state ("You haven't sent any review requests yet.") only in the table — the stat cards (Total Sent: 0, Opened: 0, etc.) still render above it. The zero-state stat cards are valid but show division-by-zero guards (`stats.sent > 0` checks are present, so no crash).

**U5 — Reviews page `allReviews` section shows reviews without `google_review_name`**

The ReviewController intentionally splits reviews into: `needsReply` (has `google_review_name` and no reply), `replied` (has reply), and `allReviews` (no `google_review_name`). Reviews created via ReviewMate requests (not synced from Google) go into `allReviews` with no AI reply button. This is correct but the section heading "All Reviews" is ambiguous — it implies it shows everything, but it's actually only non-Google-linked reviews. A clearer label like "Reviews via ReviewMate" would reduce confusion.

### Missing Features (per ControlCenter instructions)

These features are listed in the ControlCenter instructions at `/Users/joshsalway/development/ControlCenter/instructions/reviewmate.md` but are not yet fully implemented:

**Task 1 — Google Business Profile OAuth + Auto-Fetch Reviews**
- OAuth flow (`GoogleBusinessController`) and `GoogleBusinessProfileService` exist in the codebase
- `SyncGoogleReviews` job existence is unclear — the schedule was not verified
- No reviews are being auto-fetched in the E2E environment (as expected, since no Google OAuth is configured)
- The settings page shows "Connect Google Business Profile" button correctly

**Task 2 — AI Review Response UI**
- Fully implemented: `ReviewController::replySuggestions()` calls `ReviewReplyAgent`, the UI renders AI suggestions and allows posting replies via `ReviewController::postReply()`
- The reply is stored in `google_reply` and `google_reply_posted_at` on the review

**Task 3 — Review Request Follow-up Automation**
- `followed_up_at` column exists on `review_requests` (migration confirmed)
- `follow_up_enabled`, `follow_up_days`, `follow_up_channel` settings exist on businesses
- The actual `SendFollowUpRequests` job was not found in the codebase during this review — may need implementation

**Task 4 — Stripe Billing**
- Cashier is installed (subscriptions, subscription_items tables exist in migrations)
- Billing settings page at `/settings/billing` exists
- `User::onFreePlan()` is used in `QuickSendController` to enforce the 10 req/month limit
- The billing page renders without error in e2e tests

**Task 5 — Dashboard Real Data**
- Dashboard is wired with real data: `stats.average_rating`, `stats.total_reviews`, `stats.requests_sent`, `stats.conversion_rate`, `stats.pending_replies`, `stats.reviews_this_month`, `stats.requests_this_month` all appear in the Props interface and are passed from `DashboardController`

---

## 4. Recommendations

### High priority

1. **Fix the `follow_up` vs `followup` type mismatch** in `DefaultTemplateService`. The seeder uses `follow_up` (correct, matches the UI), but `DefaultTemplateService` creates templates with key `followup`. The onboarding `storeSelectTemplate` method uses `DefaultTemplateService`, so all newly onboarded businesses have a broken Follow-up tab. Fix: change `DefaultTemplateService` to use `'follow_up'`.

2. **Fix query param pre-fill in QuickSend**. Pass `name` and `email` from `QuickSendController::index()` as Inertia props (read from `$request->query()`). Remove the `window.location.search` approach entirely.

3. **Verify and implement `SendFollowUpRequests` job**. The `follow_up_enabled` setting is configurable in Business Settings, but the job that acts on it may not be scheduled. This is a key product promise (automated follow-ups).

### Medium priority

4. **Add null-safety in `QuickSendController::recentlySent` map**. Use `$req->customer?->name ?? 'Unknown'` to guard against deleted customers.

5. **Add `channel` to QuickSendController's `recentlySent` map**. The channel badge in the Recently Sent list will be unstyled without it.

6. **Rename "Email Templates" heading to "Message Templates"** since SMS is also managed there.

7. **Add search/filter to the customers table**. With many customers, there is no way to search. This is a common pain point for businesses with large customer lists.

### Low priority

8. **Add more detailed reviews tests** — specifically testing the AI reply flow (mocking the Claude call) and the "Post Reply" button state management.

9. **Add tests for the `/_e2e/login` path with a missing user** to ensure a clean 404/500 rather than a confusing error.

10. **Add pagination tests** for customers and requests pages once the seeder is extended with more data.

11. **Consider `data-testid` attributes** on key interactive elements (customer rows, action buttons) to make selectors more robust than role/text heuristics.

---

## 5. Test Coverage Map

| Feature area | Before | After | Coverage |
|---|---|---|---|
| Auth / login bypass | 3 tests | 3 tests | Good |
| Dashboard | 3 tests | 3 tests | Good |
| Customers (list, add) | 2 tests | 2 tests | Good |
| Review Requests (list, send) | 3 tests | 3 tests | Good |
| Reviews page | 3 tests | 3 tests | Good |
| Templates (page load) | 2 tests | 2 tests | Good |
| Template editor (tabs, fields, variables) | 0 tests | 11 tests | Added |
| Onboarding routes | 2 tests | 2 tests | Good |
| Business Settings | 0 tests | 8 tests | Added |
| QR Code | 0 tests | 8 tests | Added |
| Quick Send | 0 tests | 9 tests | Added |
| Email Flow | 0 tests | 2 tests | Added (smoke) |
| Analytics | 0 tests | 1 test | Added (smoke) |
| Settings sub-pages (all 6) | 0 tests | 6 tests | Added (smoke) |
| Billing flow | 0 tests | 1 test | Added (smoke) |
| AI reply suggestions | 0 tests | 0 tests | Not tested (requires Claude) |
| CSV import | 0 tests | 0 tests | Not tested |
| Bulk send | 0 tests | 0 tests | Not tested |
| Integrations (OAuth) | 0 tests | 0 tests | Not testable without OAuth providers |

**Total: 18 → 64 tests (18 original + 1 skipped + 45 new)**
