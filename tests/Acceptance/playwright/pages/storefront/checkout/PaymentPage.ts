import { Page, Locator, expect } from '@playwright/test';
import { BaseStorefrontPage } from '../BaseStorefrontPage';

export interface GuestCheckoutAddress {
  email: string;
  firstName: string;
  lastName: string;
  street: string;
  streetNo: string;
  zip: string;
  city: string;
  /** OXID country ID. Default: Germany (`a7c40f631fc920687.20179984`). */
  countryId?: string;
}

const DEFAULT_COUNTRY_ID = 'a7c40f631fc920687.20179984'; // Germany

/**
 * POM for the storefront checkout payment page (cl=payment), with helpers
 * to drive the checkout flow up to that step from a fresh session.
 *
 * Used by the issue-#116 regression test: bank-detail fields under the
 * `oxiddebitnote` (Lastschrift) payment method must be disabled whenever
 * a different payment method is selected, so unrelated submissions are
 * not blocked by HTML5 `required` validation on hidden fields. The
 * `payment-toggle.js` widget in o3-theme implements that behaviour.
 */
export class CheckoutPaymentPage extends BaseStorefrontPage {
  constructor(page: Page) {
    super(page);
  }

  /**
   * Drive the checkout flow as a guest from a fresh session through to
   * the payment step. Adds `productId × qty`, completes the user step
   * with the supplied billing address, and lands on `cl=payment`.
   */
  async setupAndGoto(productId: string, address: GuestCheckoutAddress): Promise<void> {
    // The /tobasket POST also doubles as our session warmup — see
    // BaseStorefrontPage.warmupSession() rationale.
    await this.page.request.post(
      `/index.php?cl=start&fnc=tobasket&aid=${productId}&am=1`,
    );
    await this.page.goto('/index.php?cl=user', { waitUntil: 'domcontentloaded' });

    // Click "Without registration" / guest checkout. In o3-theme the
    // option semantics are inverted from standard OXID: `option=3` is
    // "Open Account" (full registration, password required) and
    // `option=1` is the no-password guest flow that creates a passwordless
    // account record. We want the latter.
    await this.page
      .locator('form input[name="option"][value="1"]')
      .first()
      .evaluate((input) => (input as HTMLInputElement).form?.submit());
    await this.page.waitForLoadState('domcontentloaded');

    // Fill the minimal billing address. No password fields render on the
    // option=1 path — this is the guest checkout.
    await this.page.locator('input[name="lgn_usr"]').first().fill(address.email);
    await this.page.locator('input[name="invadr[oxuser__oxfname]"]').fill(address.firstName);
    await this.page.locator('input[name="invadr[oxuser__oxlname]"]').fill(address.lastName);
    await this.page.locator('input[name="invadr[oxuser__oxstreet]"]').fill(address.street);
    await this.page.locator('input[name="invadr[oxuser__oxstreetnr]"]').fill(address.streetNo);
    await this.page.locator('input[name="invadr[oxuser__oxzip]"]').fill(address.zip);
    await this.page.locator('input[name="invadr[oxuser__oxcity]"]').fill(address.city);
    await this.page
      .locator('select[name="invadr[oxuser__oxcountryid]"]')
      .selectOption(address.countryId ?? DEFAULT_COUNTRY_ID);

    // Submit the address form to advance to the payment step. Bypass
    // browser-side validation for the same reason as the revocation
    // FormPage — we want to exercise server flow, not block on a
    // browser tooltip when something goes wrong.
    await this.page
      .locator('form')
      .filter({ has: this.page.locator('input[name="invadr[oxuser__oxfname]"]') })
      .first()
      .evaluate((f) => (f as HTMLFormElement).submit());
    await this.page.waitForLoadState('networkidle', { timeout: 15_000 });

    // After the address form submits, we may need to navigate explicitly
    // to the payment step. Some flows land on a confirmation/order step
    // instead — go to the payment URL deterministically.
    if (!/cl=payment|\/zahlart\b/.test(this.page.url())) {
      await this.page.goto('/index.php?cl=payment', { waitUntil: 'domcontentloaded' });
    }
    if (!/cl=payment|\/zahlart\b/.test(this.page.url())) {
      const title = await this.page.title().catch(() => '<unavailable>');
      const errors = await this.page
        .locator('.alert-danger')
        .allTextContents()
        .catch(() => []);
      throw new Error(
        `setupAndGoto: expected to land on payment step, got url=${this.page.url()} title=${title} errors=${JSON.stringify(errors)}`,
      );
    }
  }

  // ── locators ────────────────────────────────────────────────────────

  paymentRadio(paymentId: string): Locator {
    return this.page.locator(`input[type="radio"][name="paymentid"][value="${paymentId}"]`);
  }

  /** All inputs/selects/textareas inside the debitnote .form-check label. */
  get debitnoteFields(): Locator {
    return this.page.locator(
      '.form-check:has(input[type="radio"][value="oxiddebitnote"]) .form-check-label input, ' +
        '.form-check:has(input[type="radio"][value="oxiddebitnote"]) .form-check-label select, ' +
        '.form-check:has(input[type="radio"][value="oxiddebitnote"]) .form-check-label textarea',
    );
  }

  /** Specifically the four bank-detail inputs (excludes the radio itself). */
  get debitnoteBankInputs(): Locator {
    return this.page.locator(
      '.form-check:has(input[type="radio"][value="oxiddebitnote"]) .form-check-label input[name^="dynvalue"]',
    );
  }

  async selectPayment(paymentId: string): Promise<void> {
    await this.paymentRadio(paymentId).check();
  }

  async expectDebitnoteBankFieldsDisabled(): Promise<void> {
    const count = await this.debitnoteBankInputs.count();
    expect(count, 'expected oxiddebitnote bank inputs to exist').toBeGreaterThan(0);
    for (let i = 0; i < count; i++) {
      await expect(this.debitnoteBankInputs.nth(i)).toBeDisabled();
    }
  }

  async expectDebitnoteBankFieldsEnabled(): Promise<void> {
    const count = await this.debitnoteBankInputs.count();
    expect(count, 'expected oxiddebitnote bank inputs to exist').toBeGreaterThan(0);
    for (let i = 0; i < count; i++) {
      await expect(this.debitnoteBankInputs.nth(i)).toBeEnabled();
    }
  }
}
