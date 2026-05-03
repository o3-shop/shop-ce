import { Page, Locator, expect } from '@playwright/test';
import { BaseStorefrontPage } from '../BaseStorefrontPage';
import { URLS } from '../../../helpers/urls';

export interface RevocationFormInput {
  name?: string;
  orderIdent?: string;
  email?: string;
  freeText?: string;
}

/**
 * Page Object for the customer-facing §356a revocation form (cl=revocation).
 *
 * Selectors are anchored to the IDs declared in
 * `source/Application/views/wave/tpl/page/revocation/revocation.tpl`.
 */
export class StorefrontRevocationFormPage extends BaseStorefrontPage {
  constructor(page: Page) {
    super(page);
  }

  async goto(): Promise<void> {
    await this.warmupSession();
    await this.page.goto(URLS.storefrontRevocation, { waitUntil: 'domcontentloaded' });
  }

  get form(): Locator {
    return this.page.locator('#o3-revocation-form');
  }

  get nameInput(): Locator {
    return this.page.locator('#o3rev_name');
  }

  get orderIdentInput(): Locator {
    return this.page.locator('#o3rev_orderident');
  }

  get emailInput(): Locator {
    return this.page.locator('#o3rev_email');
  }

  get freeTextInput(): Locator {
    return this.page.locator('#o3rev_freetext');
  }

  get submitButton(): Locator {
    return this.page.locator('button.o3-revocation-submit');
  }

  /** Per-field error message bound via aria-describedby. */
  fieldError(field: 'name' | 'orderident' | 'email'): Locator {
    return this.page.locator(`#o3rev_${field}_err`);
  }

  /** Form-level alert (CSRF / spam rejection). */
  get formError(): Locator {
    return this.page.locator('form#o3-revocation-form ~ * .alert-danger[role="alert"]')
      .or(this.page.locator('form#o3-revocation-form .alert-danger[role="alert"]'));
  }

  async fill(input: RevocationFormInput): Promise<void> {
    if (input.name !== undefined) await this.nameInput.fill(input.name);
    if (input.orderIdent !== undefined) await this.orderIdentInput.fill(input.orderIdent);
    if (input.email !== undefined) await this.emailInput.fill(input.email);
    if (input.freeText !== undefined) await this.freeTextInput.fill(input.freeText);
  }

  async submit(): Promise<void> {
    // Bypass jqBootstrapValidation. The form template wires
    // jqBootstrapValidation onto every input; clicking the submit
    // button would let it preventDefault for required-but-empty fields,
    // hiding the server-side validation paths from us. We test SERVER-SIDE
    // validation here — call the native HTMLFormElement.submit() directly.
    await this.form.evaluate((f) => (f as HTMLFormElement).submit());
    await this.page.waitForLoadState('networkidle', { timeout: 15_000 });
  }

  async fillAndSubmit(input: RevocationFormInput): Promise<void> {
    await this.fill(input);
    await this.submit();
  }

  async expectOnReceiptPage(): Promise<void> {
    await expect(this.page).toHaveURL(/fnc=receipt/);
  }

  async expectStillOnFormPage(): Promise<void> {
    await expect(this.form).toHaveCount(1);
  }
}
