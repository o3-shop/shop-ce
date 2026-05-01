import { Page, Locator, expect } from '@playwright/test';
import { BaseAdminPage } from '../BaseAdminPage';

/**
 * POM for the admin "Revocations → Settings" form (cl=revocation_config).
 *
 * Navigating the top-level page to the controller URL renders the form
 * markup directly (the OXID frameset only wraps the admin "home" view).
 * Selectors are top-level — no frame plumbing.
 */
export class AdminRevocationConfigPage extends BaseAdminPage {
  constructor(page: Page) {
    super(page);
  }

  async goto(): Promise<void> {
    const stoken = await this.extractStoken();
    await this.page.goto(
      `/admin/index.php?cl=revocation_config&stoken=${stoken}`,
      { waitUntil: 'domcontentloaded' },
    );
  }

  /**
   * Read the stoken from the admin nav frame. The adminPage fixture
   * leaves the page on the framed admin home, where every nav link
   * carries a `stoken=` query param. We need it for any subsequent
   * top-level admin navigation in the same session.
   */
  private async extractStoken(): Promise<string> {
    const navFrame = this.page
      .frameLocator('frame[name="navigation"]')
      .frameLocator('frame[name="adminnav"]');
    const link = await navFrame.locator('a[href*="stoken="]').first().getAttribute('href');
    const match = link?.match(/stoken=([A-Za-z0-9]+)/);
    if (!match) {
      throw new Error('AdminRevocationConfigPage: could not extract stoken from admin nav.');
    }
    return match[1] ?? '';
  }

  get form(): Locator {
    return this.page.locator('#myedit');
  }

  get showRevocationFormCheckbox(): Locator {
    return this.page.locator('input[name="blShowRevocationForm"]');
  }

  get requireLoginCheckbox(): Locator {
    return this.page.locator('input[name="blRevocationRequireLogin"]');
  }

  get notifyOperatorCheckbox(): Locator {
    return this.page.locator('input[name="blRevocationNotifyOperator"]');
  }

  get operatorEmailInput(): Locator {
    return this.page.locator('#sRevocationOperatorEmail');
  }

  get operatorEmailError(): Locator {
    return this.page.locator('#o3rev_operator_email_err');
  }

  get missingAssetsBlock(): Locator {
    return this.page.locator('.errorbox[role="alert"]');
  }

  get saveButton(): Locator {
    return this.page.locator('form#myedit input[type="submit"]');
  }

  async setCheckbox(locator: Locator, checked: boolean): Promise<void> {
    const isChecked = await locator.isChecked();
    if (isChecked !== checked) {
      await locator.click();
    }
  }

  async submit(): Promise<void> {
    // Bypass browser-side HTML5 validation. The operator email input is
    // `type="email"` with no `novalidate` on the form, so a malformed
    // value would trigger a browser tooltip and block submission —
    // hiding the SERVER-SIDE cross-field rule we're trying to exercise.
    // Calling the native HTMLFormElement.submit() skips constraint
    // validation and event handlers entirely.
    await this.form.evaluate((f) => (f as HTMLFormElement).submit());
    await this.page.waitForLoadState('networkidle', { timeout: 15_000 });
  }

  async expectFormVisible(): Promise<void> {
    await expect(this.form).toHaveCount(1);
  }
}
