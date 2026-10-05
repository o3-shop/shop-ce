import { Page } from '@playwright/test';
import { URLS } from '../../helpers/urls';

/**
 * Admin login. In normal test runs the admin is already authenticated via
 * `globalSetup.ts` + storageState — this Page Object is for tests that
 * explicitly cover the login flow itself (or that intentionally start
 * unauthenticated).
 */
export class AdminLoginPage {
  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto(URLS.adminLogin, { waitUntil: 'domcontentloaded' });
  }

  async login(user: string, password: string): Promise<void> {
    await this.page.locator('#usr').fill(user);
    await this.page.locator('#pwd').fill(password);
    await Promise.all([
      this.page.waitForLoadState('domcontentloaded'),
      this.page.locator('form#login input[type="submit"]').click(),
    ]);
  }
}
