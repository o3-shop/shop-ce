import { test, expect } from '../../../fixtures';
import { CheckoutPaymentPage } from '../../../pages/storefront/checkout/PaymentPage';

const DEMO_PRODUCT_ID = '01c9499c21914ae7de1b2412ea4b019f';
const GERMANY = 'a7c40f631fc920687.20179984';

/**
 * PayPal's "Test Failed Payment" APM simulation leaves the Trustly capture
 * PENDING when the customer returns to the shop (a PAYMENT.CAPTURE.DENIED
 * arrives later). A PENDING capture is not a failed payment: the shop must not
 * show payerror=2 and invite a retry, because that would create a second order
 * while the first capture can still complete. It must show the pending /
 * thank-you state and let the webhook settle the order.
 */
test.describe('Trustly failed payment is treated as pending', () => {
  test.setTimeout(90_000);

  test('shows the pending state instead of starting a second order', async ({
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

    // The capture is PENDING at this point; the shop must keep the session
    // order and show the pending/thank-you state instead of payment?payerror=2.
    await page.waitForURL(/[?&]cl=thankyou/, { timeout: 30_000 });
    expect(page.url()).not.toContain('sandbox.paypal.com');
    expect(page.url()).not.toContain('payerror=2');
  });
});
