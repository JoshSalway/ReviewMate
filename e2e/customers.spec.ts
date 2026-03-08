import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Customers', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('customer list page loads', async ({ page }) => {
    await page.goto('/customers');
    await expect(page).toHaveURL(/customers/);
    await expect(page.locator('body')).toContainText('Alice Smith');
    await expect(page.locator('body')).toContainText('Bob Jones');
    await expect(page.locator('body')).toContainText('Carol White');
  });

  test('can add a new customer and see them in the list', async ({ page }) => {
    await page.goto('/customers');

    // Look for an "Add Customer" button or similar trigger
    const addButton = page.getByRole('button', { name: /add customer/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    // Fill the form
    await page.getByLabel(/name/i).fill('Dave New Customer');
    await page.getByLabel(/email/i).fill('dave@example.test');

    // Submit
    const saveButton = page.getByRole('button', { name: /save|add|create/i }).last();
    await saveButton.click();

    // After saving, the new customer should appear
    await expect(page.locator('body')).toContainText('Dave New Customer');
  });
});
