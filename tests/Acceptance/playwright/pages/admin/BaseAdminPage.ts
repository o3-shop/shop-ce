import { Page, Locator } from '@playwright/test';

/**
 * Base for every admin Page Object. The OXID admin uses a top frameset:
 *
 *   - frame `navigation` → child frame `adminnav` → menu DOM
 *   - frame `basefrm`    → content DOM (forms, lists, detail views)
 *
 * Concrete page objects should reach into the right frame via the helpers
 * here rather than dealing with frame-locator plumbing inline.
 */
export abstract class BaseAdminPage {
  constructor(protected readonly page: Page) {}

  /** Locator scoped to the admin content frame (`basefrm`). */
  protected get content(): Locator {
    return this.page.frameLocator('frame[name="basefrm"]').locator('body');
  }

  /** Locator scoped to the admin menu frame (`navigation > adminnav`). */
  protected get menu(): Locator {
    return this.page
      .frameLocator('frame[name="navigation"]')
      .frameLocator('frame[name="adminnav"]')
      .locator('body');
  }

  /** Wait for the admin frameset to settle after navigation. */
  async waitForReady(): Promise<void> {
    await this.page.waitForLoadState('domcontentloaded');
    await this.page.waitForSelector('frame[name="basefrm"]');
  }
}
