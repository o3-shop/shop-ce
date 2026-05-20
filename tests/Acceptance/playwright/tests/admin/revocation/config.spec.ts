import { test, expect } from '../../../fixtures';
import { AdminRevocationConfigPage } from '../../../pages/admin/revocation/ConfigPage';
import { writeRevocationConfig, readConfigVar } from '../../../helpers/config';

const KNOWN_OPERATOR_EMAIL = 'operator-baseline@example.test';

test.describe('admin revocation config (cl=revocation_config)', () => {
  test('P0 loads current oxconfig values into the form on first render', async ({
    adminPage,
    db,
  }) => {
    await writeRevocationConfig(db, {
      blShowRevocationForm: true,
      blRevocationRequireLogin: true,
      blRevocationNotifyOperator: true,
      sRevocationOperatorEmail: KNOWN_OPERATOR_EMAIL,
    });

    const config = new AdminRevocationConfigPage(adminPage);
    await config.goto();
    await config.expectFormVisible();

    await expect(config.showRevocationFormCheckbox).toBeChecked();
    await expect(config.requireLoginCheckbox).toBeChecked();
    await expect(config.notifyOperatorCheckbox).toBeChecked();
    await expect(config.operatorEmailInput).toHaveValue(KNOWN_OPERATOR_EMAIL);
  });

  test('P0 saves all four fields atomically when valid', async ({ adminPage, db }) => {
    // Start from a known empty-ish state so the save is observable.
    await writeRevocationConfig(db, {
      blShowRevocationForm: false,
      blRevocationRequireLogin: false,
      blRevocationNotifyOperator: false,
      sRevocationOperatorEmail: '',
    });

    const config = new AdminRevocationConfigPage(adminPage);
    await config.goto();
    await config.setCheckbox(config.showRevocationFormCheckbox, true);
    await config.setCheckbox(config.requireLoginCheckbox, true);
    await config.setCheckbox(config.notifyOperatorCheckbox, true);
    await config.operatorEmailInput.fill('atomic-save@example.test');
    await config.submit();

    expect(await readConfigVar(db, 'blShowRevocationForm')).toBe('1');
    expect(await readConfigVar(db, 'blRevocationRequireLogin')).toBe('1');
    expect(await readConfigVar(db, 'blRevocationNotifyOperator')).toBe('1');
    expect(await readConfigVar(db, 'sRevocationOperatorEmail')).toBe('atomic-save@example.test');
  });

  test('P0 cross-field rule: rejects save when notify-operator=1 and operator email is empty', async ({
    adminPage,
    db,
  }) => {
    // Snapshot baseline: known values that should NOT change after the
    // rejected save (all-or-nothing invariant).
    await writeRevocationConfig(db, {
      blShowRevocationForm: false,
      blRevocationRequireLogin: false,
      blRevocationNotifyOperator: false,
      sRevocationOperatorEmail: KNOWN_OPERATOR_EMAIL,
    });

    const config = new AdminRevocationConfigPage(adminPage);
    await config.goto();
    await config.setCheckbox(config.notifyOperatorCheckbox, true);
    await config.operatorEmailInput.fill('');
    await config.submit();

    // Form re-renders with the cross-field error visible.
    await expect(config.operatorEmailError).toBeVisible();

    // All-or-nothing: NONE of the four oxconfig values changed.
    expect(await readConfigVar(db, 'blShowRevocationForm')).not.toBe('1');
    expect(await readConfigVar(db, 'blRevocationNotifyOperator')).not.toBe('1');
    expect(await readConfigVar(db, 'sRevocationOperatorEmail')).toBe(KNOWN_OPERATOR_EMAIL);
  });

  test('P0 cross-field rule: rejects save when operator email is malformed', async ({
    adminPage,
    db,
  }) => {
    await writeRevocationConfig(db, {
      blShowRevocationForm: false,
      blRevocationNotifyOperator: false,
      sRevocationOperatorEmail: KNOWN_OPERATOR_EMAIL,
    });

    const config = new AdminRevocationConfigPage(adminPage);
    await config.goto();
    await config.setCheckbox(config.notifyOperatorCheckbox, true);
    await config.operatorEmailInput.fill('not-an-email');
    await config.submit();

    await expect(config.operatorEmailError).toBeVisible();
    expect(await readConfigVar(db, 'sRevocationOperatorEmail')).toBe(KNOWN_OPERATOR_EMAIL);
    expect(await readConfigVar(db, 'blRevocationNotifyOperator')).not.toBe('1');
  });

  test('P0 preserves submitted values on rejection (form-input-preservation)', async ({
    adminPage,
    db,
  }) => {
    await writeRevocationConfig(db, {
      blShowRevocationForm: false,
      blRevocationRequireLogin: false,
      blRevocationNotifyOperator: false,
      sRevocationOperatorEmail: KNOWN_OPERATOR_EMAIL,
    });

    const config = new AdminRevocationConfigPage(adminPage);
    await config.goto();
    // Submit a state that triggers cross-field rejection. The user's
    // submitted toggles + email string must be what we see on re-render
    // (not the values still in oxconfig).
    await config.setCheckbox(config.showRevocationFormCheckbox, true);
    await config.setCheckbox(config.notifyOperatorCheckbox, true);
    await config.operatorEmailInput.fill('typo@@example.test');
    await config.submit();

    await expect(config.operatorEmailError).toBeVisible();
    await expect(config.showRevocationFormCheckbox).toBeChecked(); // user toggled this on
    await expect(config.notifyOperatorCheckbox).toBeChecked(); // user toggled this on
    await expect(config.operatorEmailInput).toHaveValue('typo@@example.test');
  });
});
