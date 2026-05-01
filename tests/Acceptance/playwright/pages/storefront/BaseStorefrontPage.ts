import { Page } from '@playwright/test';

/**
 * Base for every storefront Page Object. The storefront has no frames, so
 * helpers are simpler than `BaseAdminPage`.
 */
export abstract class BaseStorefrontPage {
  constructor(protected readonly page: Page) {}

  async waitForReady(): Promise<void> {
    await this.page.waitForLoadState('domcontentloaded');
  }

  /**
   * Force OXID to create a session **before** the form GET that the test
   * needs. OXID's storefront sessions are lazy — pure GETs don't start
   * one, so `getHiddenSid()` renders no `stoken` input. Submitting such
   * a form trips `checkSessionChallenge()`, which the controller surfaces
   * as `O3_REVOCATION_VALIDATION_SESSION_EXPIRED`.
   *
   * A POST to any write action triggers session start. We navigate the
   * page itself (rather than `page.request.post`) so the resulting
   * cookies attach to the page's storage state cleanly — `request.post`
   * sometimes leaves the page context one transition behind.
   * `aid=__warmup__` never resolves, so the basket stays empty.
   */
  protected async warmupSession(): Promise<void> {
    await this.page.request.post('/index.php?cl=start&fnc=tobasket&aid=__warmup__');
  }
}
