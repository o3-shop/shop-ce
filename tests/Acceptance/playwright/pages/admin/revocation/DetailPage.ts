import { Page, Locator, expect } from '@playwright/test';
import { BaseAdminPage } from '../BaseAdminPage';

/**
 * POM for the admin revocation detail view (cl=revocation_main).
 *
 * Like ConfigPage, we navigate top-level — `revocation_main.tpl` renders
 * directly without the OXID frameset, so selectors are at page level.
 */
export class AdminRevocationDetailPage extends BaseAdminPage {
  constructor(page: Page) {
    super(page);
  }

  async goto(oxid: string): Promise<void> {
    const stoken = await this.extractStoken();
    await this.page.goto(
      `/admin/index.php?cl=revocation_main&oxid=${encodeURIComponent(oxid)}&stoken=${stoken}`,
      { waitUntil: 'domcontentloaded' },
    );
  }

  private async extractStoken(): Promise<string> {
    const navFrame = this.page
      .frameLocator('frame[name="navigation"]')
      .frameLocator('frame[name="adminnav"]');
    const link = await navFrame.locator('a[href*="stoken="]').first().getAttribute('href');
    const match = link?.match(/stoken=([A-Za-z0-9]+)/);
    if (!match) {
      throw new Error('AdminRevocationDetailPage: could not extract stoken from admin nav.');
    }
    return match[1] ?? '';
  }

  get editForm(): Locator {
    return this.page.locator('form#myedit');
  }

  get oxidCell(): Locator {
    // First <code> in the detail table holds the OXID.
    return this.page.locator('form#myedit code').first();
  }

  get submittedCell(): Locator {
    // Row 2 of the table — by position; relies on the canonical layout
    // documented in revocation_main.tpl. If the layout changes, this
    // selector needs updating in lockstep.
    return this.page.locator('form#myedit table tr').nth(1).locator('td.edittext').nth(1);
  }

  get nameCell(): Locator {
    return this.page.locator('form#myedit table tr').nth(2).locator('td.edittext').nth(1);
  }

  get orderIdentCell(): Locator {
    return this.page.locator('form#myedit table tr').nth(3).locator('td.edittext').nth(1);
  }

  get emailLink(): Locator {
    return this.page.locator('form#myedit a[href^="mailto:"]');
  }

  get freeTextCell(): Locator {
    return this.page.locator('form#myedit pre').first();
  }

  /** Either the "sent" status or the "errorbox" badge. */
  get statusCell(): Locator {
    return this.page
      .locator('form#myedit table tr')
      .last()
      .locator('td.edittext')
      .last();
  }

  get sendFailedBadge(): Locator {
    return this.statusCell.locator('span.errorbox');
  }

  get resendButton(): Locator {
    return this.page.locator('form#myedit input[type="submit"][onClick*="resend"]');
  }

  get deleteButton(): Locator {
    return this.page.locator('form#myedit input[type="button"][onClick*="deleteThis"]');
  }

  async clickResend(): Promise<void> {
    await Promise.all([
      this.page.waitForLoadState('domcontentloaded'),
      this.resendButton.click(),
    ]);
  }

  async expectDetailVisible(): Promise<void> {
    await expect(this.editForm).toHaveCount(1);
  }
}
