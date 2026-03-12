import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';
import { loginAsE2eUser } from './helpers/auth';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

test.describe('CSV Import', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('customers page has an Import CSV button', async ({ page }) => {
    await page.goto('/customers');
    await expect(page.getByTestId('import-csv-button')).toBeVisible();
  });

  test('clicking Import CSV opens the import dialog', async ({ page }) => {
    await page.goto('/customers');
    await page.getByTestId('import-csv-button').click();
    // Dialog should appear with the title
    await expect(page.getByText('Import Customers from CSV')).toBeVisible();
  });

  test('import dialog has a file input that accepts CSV files', async ({ page }) => {
    await page.goto('/customers');
    await page.getByTestId('import-csv-button').click();
    const fileInput = page.getByTestId('csv-file-input');
    await expect(fileInput).toBeVisible();
    // Verify it accepts CSV files
    const acceptAttr = await fileInput.getAttribute('accept');
    expect(acceptAttr).toContain('.csv');
  });

  test('import dialog shows preview after uploading a CSV file', async ({ page }) => {
    await page.goto('/customers');
    await page.getByTestId('import-csv-button').click();

    const fixturePath = path.resolve(__dirname, 'fixtures/customers.csv');
    await page.getByTestId('csv-file-input').setInputFiles(fixturePath);

    // Preview should show the count and table
    await expect(page.locator('body')).toContainText('customer');
    await expect(page.locator('body')).toContainText('Test Import User');
  });

  test('import dialog cancel button closes the dialog', async ({ page }) => {
    await page.goto('/customers');
    await page.getByTestId('import-csv-button').click();
    await expect(page.getByText('Import Customers from CSV')).toBeVisible();
    await page.getByRole('button', { name: 'Cancel' }).click();
    await expect(page.getByText('Import Customers from CSV')).not.toBeVisible();
  });
});
