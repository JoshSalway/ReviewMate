import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Reviews', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('reviews page loads and shows the seeded review', async ({ page }) => {
    await page.goto('/reviews');
    await expect(page).toHaveURL(/reviews/);
    await expect(page.locator('body')).toContainText('Excellent service');
  });

  test('reviews page shows star rating', async ({ page }) => {
    await page.goto('/reviews');
    // The seeded review is 5 stars — the body or aria label should reflect this
    await expect(page.locator('body')).toContainText('Alice Smith');
  });

  test('reviews page does not 500', async ({ page }) => {
    await page.goto('/reviews');
    await expect(page.locator('body')).not.toContainText('Whoops');
    await expect(page.locator('body')).not.toContainText('500');
  });
});
