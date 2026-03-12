import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Settings', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('settings page loads without error', async ({ page }) => {
        await page.goto('/settings');
        // /settings redirects somewhere valid (profile or home) — it must not error
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('auto-reply settings page loads with heading', async ({ page }) => {
        await page.goto('/settings/auto-reply');
        await expect(page).toHaveURL(/settings\/auto-reply/);
        await expect(page.locator('h1')).toContainText('Auto-Reply to Reviews');
    });

    test('notification settings page loads with heading', async ({ page }) => {
        await page.goto('/settings/notifications');
        await expect(page).toHaveURL(/settings\/notifications/);
        await expect(page.locator('h1')).toContainText('Notification Settings');
    });

    test('business settings page loads with heading', async ({ page }) => {
        await page.goto('/settings/business');
        await expect(page).toHaveURL(/settings\/business/);
        await expect(page.locator('h1')).toContainText('Business Settings');
    });
});
