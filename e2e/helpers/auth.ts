import { Page } from '@playwright/test';

const E2E_EMAIL = 'e2e@reviewmate.test';

/**
 * Log in via the /_e2e/login bypass route (APP_E2E=true required).
 * After calling this the page will be on /dashboard.
 */
export async function loginAsE2eUser(page: Page, email = E2E_EMAIL): Promise<void> {
  await page.goto(`/_e2e/login?email=${encodeURIComponent(email)}`);
  await page.waitForURL('**/dashboard', { timeout: 10_000 });
}
