import { test, expect } from '../../../fixtures';
import { AdminRevocationDetailPage } from '../../../pages/admin/revocation/DetailPage';
import { writeRevocationConfig } from '../../../helpers/config';

const SEED_NAME = 'Detail-View Tester';
const SEED_EMAIL = 'detail-tester@example.test';
const SEED_FREETEXT = 'Erste Zeile.\nZweite Zeile.';

test.describe('admin revocation detail (cl=revocation_main)', () => {
  // The resend test requires emails to actually go out; ensure operator
  // notification stays off so we only assert customer-side delivery.
  test.beforeEach(async ({ db }) => {
    await writeRevocationConfig(db, {
      blShowRevocationForm: true,
      blRevocationRequireLogin: false,
      blRevocationNotifyOperator: false,
      sRevocationOperatorEmail: '',
    });
  });

  test('P0 renders all six fields of a stored submission', async ({ adminPage, db }) => {
    const orderIdent = `ORD-DETAIL-${Date.now()}`;
    const oxid = await db.seedRevocation({
      name: SEED_NAME,
      orderIdent,
      email: SEED_EMAIL,
      freeText: SEED_FREETEXT,
    });

    try {
      const detail = new AdminRevocationDetailPage(adminPage);
      await detail.goto(oxid);
      await detail.expectDetailVisible();

      await expect(detail.oxidCell).toContainText(oxid);
      // Submitted: just confirm a non-empty value renders (formatting
      // is the |oxformdate filter — locale-dependent).
      await expect(detail.submittedCell).not.toBeEmpty();
      await expect(detail.nameCell).toContainText(SEED_NAME);
      await expect(detail.orderIdentCell).toContainText(orderIdent);
      await expect(detail.emailLink).toHaveAttribute('href', `mailto:${SEED_EMAIL}`);
      await expect(detail.freeTextCell).toContainText('Erste Zeile.');
      await expect(detail.freeTextCell).toContainText('Zweite Zeile.');
    } finally {
      await db.deleteRevocation(oxid);
    }
  });

  test('P0 resend button re-sends the customer confirmation email', async ({
    adminPage,
    db,
    mailpit,
  }) => {
    const orderIdent = `ORD-RESEND-${Date.now()}`;
    const oxid = await db.seedRevocation({
      name: SEED_NAME,
      orderIdent,
      email: SEED_EMAIL,
      sendFailed: true,
    });

    try {
      const detail = new AdminRevocationDetailPage(adminPage);
      await detail.goto(oxid);
      await detail.clickResend();

      // Mailpit gets a fresh customer-facing message.
      await mailpit.waitForMessage({ to: SEED_EMAIL, timeoutMs: 8000 });

      // OXSENDFAILED should be cleared after a successful resend.
      const flags = await db.readRevocationFlags(oxid);
      expect(flags?.sendFailed).toBe(0);
    } finally {
      await db.deleteRevocation(oxid);
    }
  });

  test('P1 OXSENDFAILED banner is visible when send failed flag is set', async ({
    adminPage,
    db,
  }) => {
    const orderIdent = `ORD-FAIL-${Date.now()}`;
    const oxid = await db.seedRevocation({
      name: SEED_NAME,
      orderIdent,
      email: SEED_EMAIL,
      sendFailed: true,
    });

    try {
      const detail = new AdminRevocationDetailPage(adminPage);
      await detail.goto(oxid);
      await expect(detail.sendFailedBadge).toBeVisible();
    } finally {
      await db.deleteRevocation(oxid);
    }
  });

  test('P1 customer email renders as a clickable mailto link', async ({ adminPage, db }) => {
    const orderIdent = `ORD-MAILTO-${Date.now()}`;
    const oxid = await db.seedRevocation({
      name: SEED_NAME,
      orderIdent,
      email: SEED_EMAIL,
    });

    try {
      const detail = new AdminRevocationDetailPage(adminPage);
      await detail.goto(oxid);
      await expect(detail.emailLink).toBeVisible();
      const href = await detail.emailLink.getAttribute('href');
      expect(href).toBe(`mailto:${SEED_EMAIL}`);
    } finally {
      await db.deleteRevocation(oxid);
    }
  });
});
