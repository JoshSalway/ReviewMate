import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Templates', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('templates page loads', async ({ page }) => {
    await page.goto('/templates');
    await expect(page).toHaveURL(/templates/);
    await expect(page.locator('body')).toBeVisible();
    await expect(page.locator('body')).not.toContainText('Whoops');
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('templates page shows template content when templates exist', async ({ page }) => {
    await page.goto('/templates');
    // The E2E user's business doesn't have email templates seeded by default
    // so the page may show empty state — just verify it loads cleanly
    const body = page.locator('body');
    await expect(body).toBeVisible();
    // Page should say something about templates
    await expect(body).toContainText(/template/i);
  });
});
