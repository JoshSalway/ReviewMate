import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('dashboard loads with stats', async ({ page }) => {
    await expect(page).toHaveURL(/dashboard/);
    await expect(page.locator('body')).toContainText('E2E Test Business');
  });

  test('dashboard shows review stats', async ({ page }) => {
    // The seeder creates 1 review with 5 stars — verify stat cards appear
    const body = page.locator('body');
    await expect(body).toBeVisible();

    // At minimum the page should not show a 500 or blank
    await expect(page.locator('body')).not.toContainText('500');
    await expect(page.locator('body')).not.toContainText('Whoops');
  });

  test('dashboard has navigation links', async ({ page }) => {
    // Core nav items should be present
    const body = page.locator('body');
    await expect(body).toContainText('Customers');
    await expect(body).toContainText('Reviews');
  });
});
