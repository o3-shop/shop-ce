import { test, expect } from '../../../fixtures';
import { CheckoutPaymentPage } from '../../../pages/storefront/checkout/PaymentPage';

const DEMO_PRODUCT_ID = '01c9499c21914ae7de1b2412ea4b019f';
const GERMANY = 'a7c40f631fc920687.20179984';

/**
 * Regression for the failed-Trustly-payment retry bug: after PayPal's
 * "Test Failed Payment" APM simulation the customer lands on the payment
 * step with payerror=2. Retrying must start a new shop/PayPal order and
 * return to PayPal — not reuse the abandoned order and show `thankyou`.
 */
test.describe('Trustly failed payment retry', () => {
  test.setTimeout(90_000);

  test('retry starts a new order instead of showing the thank-you page', async ({
    storefrontPage,
  }) => {
    test.skip(process.env.PAYPAL_E2E !== '1', 'requires PAYPAL_E2E=1 with the PayPal module and sandbox');
    const page = storefrontPage;
    const checkout = new CheckoutPaymentPage(page);

    await checkout.setupAndGoto(DEMO_PRODUCT_ID, {
      email: `e2e-trustly-fail-${Date.now()}@example.test`,
      firstName: 'Erika',
      lastName: 'Mustermann',
      street: 'Musterstraße',
      streetNo: '1',
      zip: '12345',
      city: 'Musterstadt',
      countryId: GERMANY,
    });

    await checkout.selectPayment('oscpaypal_trustly');
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      page.locator('button[name="userform"]').click(),
    ]);
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      page.locator('.order__navi button[type=submit]').click(),
    ]);
    await page.waitForURL(/sandbox\.paypal\.com/, { timeout: 20_000 });

    await expect(page.locator('#Failed')).toHaveCount(1);
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      page.locator('#Failed').click(),
    ]);
    await page.waitForURL(/[?&]cl=payment/, { timeout: 30_000 });
    expect(page.url()).toContain('payerror=2');

    // Retry: payment step -> order step -> submit.
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      page.locator('button[name="userform"]').click(),
    ]);
    await page.waitForURL(/[?&]cl=order/, { timeout: 20_000 });
    const submit = page.locator('.order__navi button[type=submit]');
    await expect(submit).toHaveCount(1);
    await Promise.all([
      page.waitForLoadState('domcontentloaded'),
      submit.click(),
    ]);

    await page.waitForURL(/sandbox\.paypal\.com/, { timeout: 25_000 });
    expect(page.url()).not.toContain('cl=thankyou');
  });
});
