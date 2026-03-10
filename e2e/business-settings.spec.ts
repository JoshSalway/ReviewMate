import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Business Settings', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('business settings page loads', async ({ page }) => {
        await page.goto('/settings/business');
        await expect(page).toHaveURL(/settings\/business/);
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('business settings page shows heading', async ({ page }) => {
        await page.goto('/settings/business');
        await expect(page.locator('h1')).toContainText('Business Settings');
    });

    test('business settings shows business name field pre-filled', async ({ page }) => {
        await page.goto('/settings/business');
        // The E2E seeder creates "E2E Test Business"
        const nameInput = page.locator('#business-name');
        await expect(nameInput).toBeVisible();
        await expect(nameInput).toHaveValue('E2E Test Business');
    });

    test('business settings shows owner name field', async ({ page }) => {
        await page.goto('/settings/business');
        const ownerInput = page.locator('#owner-name');
        await expect(ownerInput).toBeVisible();
        // E2E seeder sets owner_name = "E2E Owner"
        await expect(ownerInput).toHaveValue('E2E Owner');
    });

    test('business settings shows Google Business Profile section', async ({ page }) => {
        await page.goto('/settings/business');
        await expect(page.locator('body')).toContainText('Google Business Profile');
    });

    test('business settings shows Google Place ID field', async ({ page }) => {
        await page.goto('/settings/business');
        const placeIdInput = page.locator('#place-id');
        await expect(placeIdInput).toBeVisible();
    });

    test('can update business name and save', async ({ page }) => {
        await page.goto('/settings/business');

        const nameInput = page.locator('#business-name');
        await nameInput.fill('Updated Business Name');

        const saveButton = page.getByRole('button', { name: /save changes/i });
        await saveButton.click();

        // Should show success indicator
        await expect(page.locator('body')).toContainText(/saved|changes saved/i);

        // Restore original name
        await nameInput.fill('E2E Test Business');
        await saveButton.click();
    });

    test('business settings has follow-up reminder section', async ({ page }) => {
        await page.goto('/settings/business');
        await expect(page.locator('body')).toContainText('Follow-up Reminder');
    });
});
