import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Email Flow Page', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('email flow page loads', async ({ page }) => {
        await page.goto('/email-flow');
        await expect(page).toHaveURL(/email-flow/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('email flow page shows email flow content', async ({ page }) => {
        await page.goto('/email-flow');
        await expect(page.locator('body')).toBeVisible();
        // The page renders a visual email flow diagram
        await expect(page.locator('body')).toContainText(/email|review|request/i);
    });
});

test.describe('Analytics Page', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('analytics page loads', async ({ page }) => {
        await page.goto('/analytics');
        await expect(page).toHaveURL(/analytics/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });
});

test.describe('Settings pages', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('notification settings page loads', async ({ page }) => {
        await page.goto('/settings/notifications');
        await expect(page).toHaveURL(/settings\/notifications/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('reply templates settings page loads', async ({ page }) => {
        await page.goto('/settings/reply-templates');
        await expect(page).toHaveURL(/settings\/reply-templates/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('auto-reply settings page loads', async ({ page }) => {
        await page.goto('/settings/auto-reply');
        await expect(page).toHaveURL(/settings\/auto-reply/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('billing settings page loads', async ({ page }) => {
        await page.goto('/settings/billing');
        await expect(page).toHaveURL(/settings\/billing/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('widget settings page loads', async ({ page }) => {
        await page.goto('/settings/widget');
        await expect(page).toHaveURL(/settings\/widget/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('integrations settings page loads', async ({ page }) => {
        await page.goto('/settings/integrations');
        await expect(page).toHaveURL(/settings\/integrations/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });
});
