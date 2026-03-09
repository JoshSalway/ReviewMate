import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Template Editor', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsE2eUser(page);
    });

    test('templates page shows Request Email tab', async ({ page }) => {
        await page.goto('/templates');
        await expect(page.locator('body')).toContainText('Request Email');
    });

    test('templates page shows Follow-up Email tab', async ({ page }) => {
        await page.goto('/templates');
        await expect(page.locator('body')).toContainText('Follow-up Email');
    });

    test('templates page shows SMS tab', async ({ page }) => {
        await page.goto('/templates');
        await expect(page.locator('body')).toContainText('SMS');
    });

    test('request email tab has subject field', async ({ page }) => {
        await page.goto('/templates');
        // The Request Email tab is active by default — look in the active panel
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        const subjectInput = activePanel.locator('#subject');
        await expect(subjectInput).toBeVisible();
    });

    test('request email tab has body textarea', async ({ page }) => {
        await page.goto('/templates');
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        const bodyTextarea = activePanel.locator('#body');
        await expect(bodyTextarea).toBeVisible();
    });

    test('template editor shows variable insertion buttons', async ({ page }) => {
        await page.goto('/templates');
        // Variables are shown in the active panel
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        await expect(activePanel).toContainText('{customer_name}');
        await expect(activePanel).toContainText('{business_name}');
        await expect(activePanel).toContainText('{review_link}');
    });

    test('template editor has Save Template button', async ({ page }) => {
        await page.goto('/templates');
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        const saveButton = activePanel.getByRole('button', { name: /save template/i });
        await expect(saveButton).toBeVisible();
    });

    test('template editor shows live preview panel', async ({ page }) => {
        await page.goto('/templates');
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        await expect(activePanel).toContainText('Preview');
    });

    test('clicking follow-up tab switches to follow-up template', async ({ page }) => {
        await page.goto('/templates');

        // Click the Follow-up Email tab
        await page.getByRole('tab', { name: /follow-up email/i }).click();

        // After switching tabs, subject and body should become visible (active tab panel)
        // The active panel for follow_up contains the editor with #subject and #body
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        await expect(activePanel).toBeVisible();
        await expect(activePanel.locator('#subject')).toBeVisible();
    });

    test('clicking SMS tab shows SMS template without subject field', async ({ page }) => {
        await page.goto('/templates');

        // Click the SMS tab
        await page.getByRole('tab', { name: /sms/i }).click();

        // The active panel for SMS should be visible
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        await expect(activePanel).toBeVisible();

        // SMS tab should show SMS Message label, not Subject Line
        await expect(activePanel).toContainText('SMS Message');
        await expect(activePanel).not.toContainText('Subject Line');
    });

    test('inserting a variable appends it to the body on request tab', async ({ page }) => {
        await page.goto('/templates');

        // The request tab is active by default
        const activePanel = page.locator('[role="tabpanel"]:not([hidden])');
        const bodyTextarea = activePanel.locator('#body');

        // Set a known value in the body
        await bodyTextarea.fill('Hello ');

        // Click the {customer_name} variable button
        await page.getByRole('button', { name: '{customer_name}' }).click();

        // Body should now contain the variable
        await expect(bodyTextarea).toHaveValue(/Hello \{customer_name\}/);
    });
});
