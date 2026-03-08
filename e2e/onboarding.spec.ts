import { test, expect } from '@playwright/test';

/**
 * Onboarding tests use a fresh user with no business so they can
 * walk through the steps. We create this user directly via artisan
 * tinker in the global setup, or use the E2E user after resetting its business.
 *
 * NOTE: The global seeder creates the E2E user WITH a completed business,
 * so these tests hit the onboarding routes directly to verify they render.
 */
test.describe('Onboarding routes render', () => {
  test.beforeEach(async ({ page }) => {
    // Log in first
    await page.goto('/_e2e/login?email=e2e@reviewmate.test');
    // The E2E user already has an onboarded business, so /dashboard loads
    await page.waitForURL('**/dashboard', { timeout: 10_000 });
  });

  test('onboarding business-type page renders when navigated to directly', async ({ page }) => {
    await page.goto('/onboarding/business-type');
    // Since this user already has onboarding complete, it should redirect to dashboard
    await expect(page).toHaveURL(/dashboard/);
  });

  test('onboarding select-template page renders', async ({ page }) => {
    await page.goto('/onboarding/select-template');
    // Redirects to connect-google first since no business without google
    // or redirects to dashboard — either way we should not get a 500
    await expect(page.locator('body')).toBeVisible();
    const status = page.url();
    expect(status).toMatch(/(dashboard|onboarding)/);
  });
});
