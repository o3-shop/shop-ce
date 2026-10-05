import { test, expect } from '../../../fixtures';
import { CheckoutPaymentPage } from '../../../pages/storefront/checkout/PaymentPage';

const DEMO_PRODUCT_ID = '01c9499c21914ae7de1b2412ea4b019f'; // "Go Home Pinguin"

const guestAddress = (suffix: string) => ({
  email: `guest-${suffix}-${Date.now()}@example.test`,
  firstName: 'Erika',
  lastName: 'Mustermann',
  street: 'Musterstraße',
  streetNo: '1',
  zip: '12345',
  city: 'Musterstadt',
});

test.describe('issue #116 — payment-method radio toggles oxiddebitnote bank fields', () => {
  test('P0 bank-detail fields are disabled when a different payment method is selected', async ({
    storefrontPage,
  }) => {
    const checkout = new CheckoutPaymentPage(storefrontPage);
    await checkout.setupAndGoto(DEMO_PRODUCT_ID, guestAddress('disabled'));

    // Pick something other than debitnote. Invoice is always available
    // in the o3-shop demo data.
    await checkout.selectPayment('oxidinvoice');
    await checkout.expectDebitnoteBankFieldsDisabled();
  });

  test('P0 bank-detail fields are enabled when oxiddebitnote is selected', async ({
    storefrontPage,
  }) => {
    const checkout = new CheckoutPaymentPage(storefrontPage);
    await checkout.setupAndGoto(DEMO_PRODUCT_ID, guestAddress('enabled'));

    await checkout.selectPayment('oxiddebitnote');
    await checkout.expectDebitnoteBankFieldsEnabled();
  });

  test('P0 toggling away from oxiddebitnote re-disables the bank fields', async ({
    storefrontPage,
  }) => {
    const checkout = new CheckoutPaymentPage(storefrontPage);
    await checkout.setupAndGoto(DEMO_PRODUCT_ID, guestAddress('toggle'));

    await checkout.selectPayment('oxiddebitnote');
    await checkout.expectDebitnoteBankFieldsEnabled();

    await checkout.selectPayment('oxidinvoice');
    await checkout.expectDebitnoteBankFieldsDisabled();
  });
});
