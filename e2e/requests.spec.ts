import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Requests', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('requests page loads for authenticated user', async ({ page }) => {
        await page.goto('/requests');
        await expect(page).toHaveURL(/requests/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('requests page shows Review Requests heading', async ({ page }) => {
        await page.goto('/requests');
        await expect(page.locator('h1')).toContainText('Review Requests');
    });

    test('requests page shows subtitle with tracking description', async ({ page }) => {
        await page.goto('/requests');
        await expect(page.locator('body')).toContainText('Track the status of your review requests');
    });

    test('requests page shows All Requests card', async ({ page }) => {
        await page.goto('/requests');
        await expect(page.locator('body')).toContainText('All Requests');
    });
});
