import { chromium, FullConfig } from '@playwright/test';

const ADMIN_USER = process.env.ADMIN_USER ?? 'admin@example.com';
const ADMIN_PASS = process.env.ADMIN_PASS ?? 'admin123';

/**
 * Pre-flight credential check. We do **not** persist storageState —
 * OXID's admin session-token model means storageState alone isn't enough
 * to skip the per-test login (see `fixtures/auth.ts` for rationale). This
 * setup just fails fast with a clear error if admin credentials are wrong
 * before any spec runs.
 */
export default async function globalSetup(config: FullConfig): Promise<void> {
  const baseURL = config.projects[0]?.use.baseURL;
  if (!baseURL) {
    throw new Error('globalSetup: baseURL is not configured.');
  }

  const browser = await chromium.launch();
  try {
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto(`${baseURL}/admin/`, { waitUntil: 'domcontentloaded' });
    await page.locator('#usr').fill(ADMIN_USER);
    await page.locator('#pwd').fill(ADMIN_PASS);
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      page.locator('form#login input[type="submit"]').click(),
    ]);

    const stillOnLogin = await page.locator('#usr').count();
    if (stillOnLogin > 0) {
      throw new Error(
        `globalSetup: admin login failed for '${ADMIN_USER}'. ` +
          `The login form is still visible after submit. ` +
          `Check ADMIN_USER / ADMIN_PASS env vars (or shop reachability at ${baseURL}).`,
      );
    }
    await page.waitForSelector('frame[name="basefrm"]', { timeout: 10_000 });
  } finally {
    await browser.close();
  }
}
