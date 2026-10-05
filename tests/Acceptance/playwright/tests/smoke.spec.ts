import { test, expect } from '../fixtures';
import { URLS } from '../helpers/urls';

/**
 * Pipeline-validation tests: prove the whole stack runs end-to-end before
 * any feature specs are written.
 *
 * - storefront responds with HTTP 200 and serves an OXID page
 * - the adminPage fixture is authenticated (storageState from globalSetup)
 * - the Mailpit and DB fixtures are reachable
 *
 * If any of these fail, every other spec will fail downstream — fix here
 * first.
 */

test.describe('smoke', () => {
  test('storefront homepage loads', async ({ storefrontPage }) => {
    const response = await storefrontPage.goto(URLS.storefrontHome);
    expect(response?.ok()).toBe(true);
    await expect(storefrontPage).toHaveTitle(/.+/);
  });

  test('adminPage fixture lands on the framed admin', async ({ adminPage }) => {
    // adminPage logs in fresh per-test (see fixtures/auth.ts) and is
    // pre-positioned on the framed admin home. The fixture itself waits
    // for `basefrm`, so reaching this test body already proves auth.
    await expect(adminPage.locator('frame[name="basefrm"]')).toHaveCount(1);
    await expect(adminPage.locator('#usr')).toHaveCount(0);
  });

  test('Mailpit REST API is reachable and inbox starts empty', async ({ mailpit }) => {
    const messages = await mailpit.listMessages();
    expect(Array.isArray(messages)).toBe(true);
    expect(messages.length).toBe(0);
  });

  test('MySQL is reachable and oxconfig table exists', async ({ db }) => {
    const rows = await db.query<{ c: number }>(`SELECT COUNT(*) AS c FROM oxconfig`);
    expect(rows[0]?.c).toBeGreaterThan(0);
  });
});
