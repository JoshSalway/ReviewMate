import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Pricing page (public)', () => {
    test('pricing page is publicly accessible without auth', async ({ page }) => {
        await page.goto('/pricing');
        await expect(page).toHaveURL(/pricing/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('pricing page shows pricing heading', async ({ page }) => {
        await page.goto('/pricing');
        await expect(page.locator('h1')).toContainText('Simple, honest pricing');
    });

    test('pricing page shows plan names', async ({ page }) => {
        await page.goto('/pricing');
        // Plans: Free, Starter, Pro
        await expect(page.locator('body')).toContainText('Free');
        await expect(page.locator('body')).toContainText('Starter');
        await expect(page.locator('body')).toContainText('Pro');
    });
});

test.describe('Billing settings (authenticated)', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('billing settings page loads', async ({ page }) => {
        await page.goto('/settings/billing');
        await expect(page).toHaveURL(/settings\/billing/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('billing settings page shows Billing heading', async ({ page }) => {
        await page.goto('/settings/billing');
        await expect(page.locator('h1')).toContainText('Billing');
    });

    test('billing settings page shows current plan', async ({ page }) => {
        await page.goto('/settings/billing');
        // The E2E user is on free plan — page should show plan information
        await expect(page.locator('body')).toContainText(/plan|free|starter|pro/i);
    });
});

test.describe('Billing redirect (unauthenticated)', () => {
    test('billing settings redirects to login when unauthenticated', async ({ page }) => {
        await page.goto('/settings/billing');
        // Should redirect away from billing — either to login or WorkOS
        await expect(page).not.toHaveURL(/settings\/billing/);
    });
});
