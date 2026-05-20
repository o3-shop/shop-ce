import { test, expect } from '../../../fixtures';
import { StorefrontRevocationFormPage } from '../../../pages/storefront/revocation/FormPage';
import { writeRevocationConfig, clearRevocationAntiSpamCache } from '../../../helpers/config';
import { URLS } from '../../../helpers/urls';

const VALID_NAME = 'Erika Mustermann';
const VALID_ORDER = 'ORD-2026-04321';
const CUSTOMER_EMAIL = 'erika.mustermann@example.test';
const OPERATOR_EMAIL = 'revocation-ops@example.test';

test.describe('storefront revocation form (cl=revocation)', () => {
  test.beforeEach(async ({ db }) => {
    // Baseline: feature on, anonymous access, no operator notification.
    // Individual tests override what they need.
    await writeRevocationConfig(db, {
      blShowRevocationForm: true,
      blRevocationRequireLogin: false,
      blRevocationNotifyOperator: false,
      sRevocationOperatorEmail: '',
    });
    // Reset the anti-spam IP rate limit so successive tests don't trip
    // the 5-minute post-success lockout in NoopAntiSpamService.
    await clearRevocationAntiSpamCache();
  });

  test('P0 submits successfully and shows the receipt page', async ({ storefrontPage, db }) => {
    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();
    await form.fillAndSubmit({
      name: VALID_NAME,
      orderIdent: VALID_ORDER,
      email: CUSTOMER_EMAIL,
      freeText: 'Ich widerrufe meinen Vertrag.',
    });

    await form.expectOnReceiptPage();

    const oxid = await db.findRevocationByOrderIdent(VALID_ORDER);
    expect(oxid).not.toBeNull();
    await db.deleteRevocation(oxid!);
  });

  test('P0 returns 404 when the feature is disabled', async ({ storefrontPage, db }) => {
    await writeRevocationConfig(db, { blShowRevocationForm: false });

    const response = await storefrontPage.goto(URLS.storefrontRevocation);
    // OXID's handlePageNotFoundError emits a 404 page; either the HTTP
    // status is 404, or the response body is the OXID 404 template.
    // Accept both — different stack configurations route differently.
    const status = response?.status() ?? 0;
    if (status === 404) {
      expect(status).toBe(404);
    } else {
      const title = await storefrontPage.title();
      expect(title.toLowerCase()).toMatch(/not found|fehler|404/);
    }

    const form = new StorefrontRevocationFormPage(storefrontPage);
    await expect(form.form).toHaveCount(0);
  });

  test('P0 redirects anonymous users to login when blRevocationRequireLogin=1', async ({
    storefrontPage,
    db,
  }) => {
    await writeRevocationConfig(db, { blRevocationRequireLogin: true });

    await storefrontPage.goto(URLS.storefrontRevocation, { waitUntil: 'domcontentloaded' });

    // Controller redirects to ?cl=account&sourcecl=revocation for anonymous
    // visitors. Final URL must reflect that.
    expect(storefrontPage.url()).toMatch(/cl=account/);
    expect(storefrontPage.url()).toMatch(/sourcecl=revocation/);
  });

  test('P0 rejects empty required fields and preserves entered values', async ({
    storefrontPage,
  }) => {
    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();
    // Leave name empty; fill the other two with values that should be
    // preserved across the rejection.
    await form.fillAndSubmit({
      name: '',
      orderIdent: VALID_ORDER,
      email: CUSTOMER_EMAIL,
      freeText: 'Optional note.',
    });

    await form.expectStillOnFormPage();
    await expect(form.fieldError('name')).toBeVisible();

    // form-input-preservation: the values the user typed must still be
    // there, except the empty one.
    await expect(form.orderIdentInput).toHaveValue(VALID_ORDER);
    await expect(form.emailInput).toHaveValue(CUSTOMER_EMAIL);
    await expect(form.freeTextInput).toHaveValue('Optional note.');
  });

  test('P0 rejects invalid email format and preserves inputs', async ({ storefrontPage }) => {
    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();
    await form.fillAndSubmit({
      name: VALID_NAME,
      orderIdent: VALID_ORDER,
      email: 'not-an-email',
    });

    await form.expectStillOnFormPage();
    await expect(form.fieldError('email')).toBeVisible();

    await expect(form.nameInput).toHaveValue(VALID_NAME);
    await expect(form.orderIdentInput).toHaveValue(VALID_ORDER);
    await expect(form.emailInput).toHaveValue('not-an-email');
  });

  test('P1 dispatches confirmation email to the customer on success', async ({
    storefrontPage,
    db,
    mailpit,
  }) => {
    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();

    const orderIdent = `${VALID_ORDER}-EMAIL-${Date.now()}`;
    await form.fillAndSubmit({
      name: VALID_NAME,
      orderIdent,
      email: CUSTOMER_EMAIL,
    });
    await form.expectOnReceiptPage();

    const message = await mailpit.waitForMessage({ to: CUSTOMER_EMAIL, timeoutMs: 8000 });
    expect(message.To.some((t) => t.Address.toLowerCase() === CUSTOMER_EMAIL)).toBe(true);

    const oxid = await db.findRevocationByOrderIdent(orderIdent);
    if (oxid) await db.deleteRevocation(oxid);
  });

  test('P1 dispatches operator notification when blRevocationNotifyOperator=1', async ({
    storefrontPage,
    db,
    mailpit,
  }) => {
    await writeRevocationConfig(db, {
      blRevocationNotifyOperator: true,
      sRevocationOperatorEmail: OPERATOR_EMAIL,
    });

    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();
    const orderIdent = `${VALID_ORDER}-OP-${Date.now()}`;
    await form.fillAndSubmit({
      name: VALID_NAME,
      orderIdent,
      email: CUSTOMER_EMAIL,
    });
    await form.expectOnReceiptPage();

    // Both customer + operator emails should arrive.
    await mailpit.waitForMessage({ to: CUSTOMER_EMAIL, timeoutMs: 8000 });
    await mailpit.waitForMessage({ to: OPERATOR_EMAIL, timeoutMs: 8000 });

    const oxid = await db.findRevocationByOrderIdent(orderIdent);
    if (oxid) await db.deleteRevocation(oxid);
  });

  test('P1 accepts long UTF-8 + emoji in the free-text field', async ({ storefrontPage, db }) => {
    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();
    const orderIdent = `${VALID_ORDER}-UTF8-${Date.now()}`;
    const longText = ('Käufer-Notiz ❤️ → ' + 'A'.repeat(2000) + ' 🎉').slice(0, 2500);
    await form.fillAndSubmit({
      name: 'Müller',
      orderIdent,
      email: CUSTOMER_EMAIL,
      freeText: longText,
    });
    await form.expectOnReceiptPage();

    const oxid = await db.findRevocationByOrderIdent(orderIdent);
    expect(oxid).not.toBeNull();
    await db.deleteRevocation(oxid!);
  });

  test('P2 back-button after success does not resubmit the form', async ({
    storefrontPage,
    db,
  }) => {
    const form = new StorefrontRevocationFormPage(storefrontPage);
    await form.goto();
    const orderIdent = `${VALID_ORDER}-BACK-${Date.now()}`;
    await form.fillAndSubmit({
      name: VALID_NAME,
      orderIdent,
      email: CUSTOMER_EMAIL,
    });
    await form.expectOnReceiptPage();

    // The 303 from submit() means a back-navigation goes to the FORM
    // (GET), not the POST. Confirm only one row exists for this orderIdent.
    await storefrontPage.goBack();
    await storefrontPage.waitForLoadState('domcontentloaded');

    const rows = await db.query<{ c: number }>(
      `SELECT COUNT(*) AS c FROM o3revocation WHERE OXORDERIDENT = ?`,
      [orderIdent],
    );
    expect(rows[0]?.c).toBe(1);

    const oxid = await db.findRevocationByOrderIdent(orderIdent);
    if (oxid) await db.deleteRevocation(oxid);
  });
});
