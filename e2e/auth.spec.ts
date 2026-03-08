import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Auth', () => {
  test('visiting / shows the landing page (not a redirect to WorkOS)', async ({ page }) => {
    await page.goto('/');
    // The home page is the waitlist/landing page — it should NOT require auth
    await expect(page).not.toHaveURL(/login/);
    // Basic sanity: page loads without error
    await expect(page.locator('body')).toBeVisible();
  });

  test('visiting /dashboard without auth redirects to login', async ({ page }) => {
    await page.goto('/dashboard');
    // Should redirect somewhere that is not /dashboard
    await expect(page).not.toHaveURL(/dashboard/);
  });

  test('test login helper authenticates and lands on dashboard', async ({ page }) => {
    await loginAsE2eUser(page);
    await expect(page).toHaveURL(/dashboard/);
    // Dashboard should render the business name
    await expect(page.locator('body')).toContainText('E2E Test Business');
  });
});
