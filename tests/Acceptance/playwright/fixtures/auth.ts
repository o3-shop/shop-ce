import { test as base, BrowserContext, Page } from '@playwright/test';

const ADMIN_USER = process.env.ADMIN_USER ?? 'admin@example.com';
const ADMIN_PASS = process.env.ADMIN_PASS ?? 'admin123';

export interface AuthFixtures {
  /** A page already authenticated as the shop admin. */
  adminPage: Page;
  /** An unauthenticated storefront page. */
  storefrontPage: Page;
  /** A long-lived admin browser context (rare — most tests want adminPage). */
  adminContext: BrowserContext;
}

/**
 * OXID admin sessions bind security tokens to a single browser session and
 * a sequence of redirects after `checklogin` (the cookie alone is not
 * sufficient — see the existing Codeception suite which logs in fresh per
 * test). For that reason this fixture performs a real login per test.
 *
 * Cost: ~1.5–2 s per admin test. Worth it for reliability; if the suite
 * grows past ~50 admin tests, revisit storageState + a session-anchoring
 * navigation.
 */
export const test = base.extend<AuthFixtures>({
  adminContext: async ({ browser }, use) => {
    const ctx = await browser.newContext();
    await use(ctx);
    await ctx.close();
  },

  adminPage: async ({ adminContext, baseURL }, use) => {
    if (!baseURL) {
      throw new Error('adminPage fixture: baseURL is not configured.');
    }

    const page = await adminContext.newPage();
    await page.goto(`${baseURL}/admin/`, { waitUntil: 'domcontentloaded' });
    await page.locator('#usr').fill(ADMIN_USER);
    await page.locator('#pwd').fill(ADMIN_PASS);
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      page.locator('form#login input[type="submit"]').click(),
    ]);
    await page.waitForSelector('frame[name="basefrm"]', { timeout: 10_000 });

    await use(page);
    await page.close();
  },

  storefrontPage: async ({ browser }, use) => {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await use(page);
    await ctx.close();
  },
});
