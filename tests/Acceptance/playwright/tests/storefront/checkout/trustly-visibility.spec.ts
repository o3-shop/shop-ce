import { test, expect } from '../../../fixtures';
import { CheckoutPaymentPage } from '../../../pages/storefront/checkout/PaymentPage';

const DEMO_PRODUCT_ID = '01c9499c21914ae7de1b2412ea4b019f'; // "Go Home Pinguin" (buyable in the O3 demo dataset)

const TRUSTLY_ID = 'oscpaypal_trustly';

const guestAddress = (suffix: string, countryId?: string) => ({
  email: `guest-${suffix}-${Date.now()}@example.test`,
  firstName: 'Erika',
  lastName: 'Mustermann',
  street: 'Musterstraße',
  streetNo: '1',
  zip: '12345',
  city: 'Musterstadt',
  countryId,
});

const GERMANY = 'a7c40f631fc920687.20179984'; // Trustly country
const SWITZERLAND = 'a7c40f6321c6f6109.43859248'; // not a Trustly country

/**
 * Trustly (oscpaypal_trustly) storefront visibility — design-doc
 * verification: sandbox mode, EUR, Trustly country shows the payment
 * method, a non-Trustly country hides it.
 */
test.describe('Trustly payment method visibility', () => {
  test('P0 shown for a Trustly country (DE) on the payment step', async ({
    storefrontPage,
  }) => {
    test.skip(process.env.PAYPAL_E2E !== '1', 'requires PAYPAL_E2E=1 with the PayPal module and sandbox');
    const checkout = new CheckoutPaymentPage(storefrontPage);
    await checkout.setupAndGoto(DEMO_PRODUCT_ID, guestAddress('trustly-de', GERMANY));

    const trustly = checkout.paymentRadio(TRUSTLY_ID);
    await expect(trustly, 'oscpaypal_trustly must be offered on the payment step').toHaveCount(1);
    await expect(trustly).toBeVisible();

    // The neighbouring PayPal APMs prove the module's payment list in
    // general rendered — so a missing Trustly would be module-specific.
    await expect(checkout.paymentRadio('oscpaypal')).toHaveCount(1);
  });

  test('P0 hidden for a non-Trustly country (CH)', async ({ storefrontPage, db }) => {
    test.skip(process.env.PAYPAL_E2E !== '1', 'requires PAYPAL_E2E=1 with the PayPal module and sandbox');
    await db.execute("UPDATE oxcountry SET OXACTIVE = 1 WHERE OXISOALPHA2 = 'CH'");
    try {
      const checkout = new CheckoutPaymentPage(storefrontPage);
      await checkout.setupAndGoto(DEMO_PRODUCT_ID, guestAddress('trustly-ch', SWITZERLAND));

      await expect(
        checkout.paymentRadio(TRUSTLY_ID),
        'oscpaypal_trustly must not be offered outside its country set',
      ).toHaveCount(0);

      await expect(checkout.paymentRadio('oscpaypal')).toHaveCount(1);
    } finally {
      await db.execute("UPDATE oxcountry SET OXACTIVE = 0 WHERE OXISOALPHA2 = 'CH'");
    }
  });
});
