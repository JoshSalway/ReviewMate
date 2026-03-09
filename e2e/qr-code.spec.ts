import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('QR Code', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('qr code page loads', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page).toHaveURL(/qr-code/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('qr code page shows heading', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page.locator('h1')).toContainText('QR Code');
    });

    test('qr code page shows customise section', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page.locator('body')).toContainText('Customise');
    });

    test('qr code page shows place id prompt when no google place id set', async ({ page }) => {
        await page.goto('/qr-code');
        // E2E business has no google_place_id set — should show prompt
        await expect(page.locator('body')).toContainText(/Place ID|Business Settings|place id/i);
    });

    test('qr code page shows where to use section', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page.locator('body')).toContainText('Where to use your QR code');
    });

    test('qr code page shows use case items', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page.locator('body')).toContainText('Receipts');
        await expect(page.locator('body')).toContainText('Business cards');
    });

    test('qr code page has size selector', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page.locator('body')).toContainText('Size');
    });

    test('qr code page has style selector', async ({ page }) => {
        await page.goto('/qr-code');
        await expect(page.locator('body')).toContainText('Style');
    });
});
