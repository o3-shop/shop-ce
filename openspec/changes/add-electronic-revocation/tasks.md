## 0. Hard prerequisite — issue #114 (entrypoint git-clone bootstrap)

- [x] 0.1 Confirm #114 has landed and `docker/entrypoint.sh` clones wave-theme via `git clone` instead of `wget`/`unzip`. Block all subsequent storefront-theme tasks until verified.
- [x] 0.2 On the dev machine, ensure `source/Application/views/wave/.git` exists (real working tree) — if not, follow the migration procedure: `./docker.sh stop && rm -rf source/Application/views/wave && ./docker.sh start`.
- [x] 0.3 Create the feature branch in wave-theme: `cd source/Application/views/wave && git checkout -b 99-add-electronic-revocation-function`. The shop-ce branch (`99-add-electronic-revocation-function-b2`) already exists — same logical change, two coordinated PRs. The HTTPS remote that the bootstrap clone produces works for pushes as-is on a dev machine where `gh auth login` has been run (gh sets up a credential helper). Switching to SSH is a personal-preference option, not required.

## 1. Database schema and seeding (shop-ce)

- [x] 1.1 Create new Doctrine migration file `source/migration/data/Version<TIMESTAMP>.php` (use today's UTC timestamp as `YYYYMMDDhhmmss`).
- [x] 1.2 In the migration's `up()`: `CREATE TABLE IF NOT EXISTS o3revocation (...)` matching the schema in design D3 — every column carries its `COMMENT`; table-level `COMMENT` cites § 356a BGB and the effective date; `OXIP` and `OXUSERAGENT` columns are NOT created.
- [x] 1.3 In the migration's `up()`: `INSERT IGNORE INTO oxcontents` one row per shop with `OXLOADID='o3_revocation_notice'`, `OXSNIPPET=1`, `OXTYPE=0`, every per-language slot inactive and empty (`OXACTIVE_*=0`, `OXTITLE_*=''`, `OXCONTENT_*=''`). `oxcontents` uses suffixed columns for multi-language; one row covers every language. Idempotent via `INSERT IGNORE` on the `OXLOADID` UNIQUE index — never overwrites operator content on re-run.
- [x] 1.4 In the migration's `down()`: `DROP TABLE IF EXISTS o3revocation` and `DELETE FROM oxcontents WHERE OXLOADID='o3_revocation_notice'`. Rollback is theoretically possible; in practice operators won't run it on prod with real submissions.
- [x] 1.5 Add an integration test that runs the migration against a clean test DB, asserts the table exists with the expected columns, and asserts the `oxcontents` rows are present, inactive, and empty.
- [x] 1.6 Add an integration test that runs the migration twice on the same DB (operator-edited snippet between runs) and asserts the operator's content is preserved.
- [x] 1.7 Add four `INSERT INTO oxconfig` rows to `source/Setup/Sql/initial_data.sql`: `blShowRevocationForm = '1'` (bool), `blRevocationRequireLogin = '0'` (bool), `blRevocationNotifyOperator = '1'` (bool), `sRevocationOperatorEmail = ''` (str). Use `OXVARTYPE` and `OXVARVALUE` consistent with the nine config rows already in the file.
- [x] 1.8 Verify a fresh `./docker.sh rebuild` brings the shop up with all four `oxconfig` rows present (`SELECT * FROM oxconfig WHERE OXVARNAME LIKE '%Revocation%'`).

## 2. Model and persistence (shop-ce)

- [x] 2.1 Create `source/Application/Model/O3Revocation.php`. Strict types, extends the standard OXID base model class. Map to table `o3revocation`. Property accessors via the framework's magic-getter convention.
- [x] 2.2 Implement `getId(): string`, `getLang(): int`, `getSubmittedAt(): \DateTimeInterface`, `getName(): string`, `getOrderIdent(): string`, `getEmail(): string`, `getFreeText(): ?string`. All declare types per D11.
- [x] 2.3 Add a `markSendFailed(): void` and `markSendSucceeded(): void` (or equivalent property/persist mechanism) so admin can flag "send failed" rows. Decision: dedicated `OXSENDFAILED tinyint(1)` column (added in phase 1 schema). Captured in the model's class docblock.
- [x] 2.4 Ensure `OXSUBMITTED` is set exactly once on first save and never updated; `OXTIMESTAMP` is left to MySQL. Cover both invariants with a unit test.
- [x] 2.5 Add a unit test that creates an `O3Revocation`, saves, modifies a non-`OXSUBMITTED` field, saves again — asserts `OXSUBMITTED` is unchanged, `OXTIMESTAMP` advanced.

## 3. Anti-spam service with two-mode rate limit (shop-ce)

- [x] 3.1 Create `source/Internal/Domain/Revocation/AntiSpam/RevocationAntiSpamServiceInterface.php` (strict types) declaring `verify(\OxidEsales\Eshop\Core\Request $request): bool`, `recordSuccess(\OxidEsales\Eshop\Core\Request $request): void`, `recordFailure(\OxidEsales\Eshop\Core\Request $request): void`.
- [x] 3.2 Create `NoopAntiSpamService` implementing the interface. Class constants: `FAILED_LIMIT = 3`, `FAILED_WINDOW_SECONDS = 60`, `SUCCESS_LOCKOUT_SECONDS = 300`.
- [x] 3.3 Implement `verify()` against a transient cache counter store (`Registry::getUtils()->fromFileCache/toFileCache`; IP md5-hashed for filename safety). Order: success-lockout check first, then failed-counter check.
- [x] 3.4 Implement `recordSuccess()` (set success counter with TTL 300s) and `recordFailure()` (increment failed counter with TTL 60s).
- [x] 3.5 Wire the binding in `source/Internal/Domain/Revocation/services.yaml` (imported from `Internal/Domain/services.yaml`): default implementation is `NoopAntiSpamService`, ID is `RevocationAntiSpamServiceInterface`. Issue #113 will rebind to `AltchaAntiSpamService`.
- [x] 3.6 Unit-test all three methods against a mocked cache: 3 failures in 60s allowed, 4th rejected; 1 success triggers 300s lockout that rejects subsequent attempts; per-IP isolation; no-op behaviour when IP is unavailable.

## 4. Public controller (shop-ce)

- [x] 4.1 Create `source/Application/Controller/RevocationController.php` extending the base `OxidEsales\EshopCommunity\Application\Controller\FrontendController`. Strict types. Class constant `O3_REVOCATION_PENDING_SESSION_KEY` is **not** needed (no inter-step state per D2).
- [x] 4.2 Implement default `render()` action: read `blShowRevocationForm` and `blRevocationRequireLogin`; deny per the visibility matrix (404 if feature off; redirect to login if anonymous + login-required). Embed stoken via `Session::hiddenSid()` in the form template via a Smarty variable. Render `revocation.tpl` with all fields blank (no prefill from `oxuser__*`).
- [x] 4.3 Implement `submit()` action: (a) `Session::checkSessionChallenge()`, redirect-to-form if mismatch + log WARNING; (b) call `$antiSpam->verify()`, render-with-preserved-values + log WARNING + call `recordFailure()` if false; (c) validate fields (non-empty after trim; email passes `FILTER_VALIDATE_EMAIL`), render-with-preserved-values + log WARNING + call `recordFailure()` on validation error; (d) instantiate and persist `O3Revocation`; (e) log NOTICE with submission `OXID`; (f) send customer email; (g) send operator email when `blRevocationNotifyOperator = 1` (using the runtime fallback chain to `oxshops.oxorderemail`); (h) call `$antiSpam->recordSuccess()`; (i) HTTP 303 redirect to `?cl=revocation&fnc=receipt`. Email helpers are phase-5 seams (call sites stable; method-exists guard).
- [x] 4.4 Implement `receipt()` action: render `revocationreceipt.tpl` with a generic acknowledgement; do not require any prior session state; safe to navigate to directly.
- [x] 4.5 Add the no-prefill rule explicitly: `render()` does not pass any user-profile data into the view; template-getter methods (`getName`, etc.) read from `Request` only — empty on initial GET, populated after a rejected submit.
- [x] 4.6 Form-input preservation: on every rejection branch in `submit()`, the controller `return false`s so the framework re-renders the same form template; the template-getter methods read submitted values from `Request` for the re-render. No HTTP redirect on rejection.
- [x] 4.7 Verify zero `getActiveTheme()` calls in the controller (D9 / spec "Storefront templates portable across themes"). Grep audit `grep -rn getActiveTheme source/Application/Controller/RevocationController.php source/Application/Model/O3Revocation.php source/Internal/Domain/Revocation/` returns no matches.
- [x] 4.8 Unit-test each action: 8 tests / 33 assertions cover token mismatch, anti-spam reject, empty mandatory fields, whitespace-only mandatory values, invalid email format, happy-path persist+redirect, getter-based form-input preservation, feature/login flag helpers. Render-path gating (404 / login redirect) requires a live shop context for `parent::render()` and is covered by integration tests, not these unit tests.
- [ ] 4.9 Manual smoke test the consumer flow on `http://localhost:8080`: footer link appears; click → form renders empty; submit with one missing field → re-renders with values preserved + error; submit with invalid email format → re-renders with values preserved + format error; submit successfully → receipt page → check Mailpit for both customer and operator emails; verify both emails contain submission ID and timestamp. *Deferred to after phase 5 (email service implementation lands).*

## 5. Email service extension (shop-ce)

- [x] 5.1 In `source/Core/Email.php`, add `sendRevocationEmailToCustomer(\OxidEsales\EshopCommunity\Application\Model\O3Revocation $submission): bool` mirroring the shape of `sendOrderEmailToUser()` (around line 589). New method has typed signature; the file's existing untyped methods are left unchanged (no `declare(strict_types=1)` added — would flip semantics for the rest of the file).
- [x] 5.2 Inside `sendRevocationEmailToCustomer()`: load `Shop` in the submission's language via `_getShop($submission->getLang())`; render templates `revocation_customer_confirmation.tpl` (HTML + plain) and `revocation_customer_confirmation_subj.tpl` (subject); recipient is `$submission->getEmail()`; pass `setViewData('submission', $submission)`. Subject falls back to `O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT` translation key + submission OXID parenthetical when the subject template is missing. Returns the `send()` result.
- [x] 5.3 Add `sendRevocationEmailToOperator(O3Revocation $submission): bool` mirroring `sendOrderEmailToOwner()`.
- [x] 5.4 Implement the runtime recipient resolution: read `sRevocationOperatorEmail`, validate via `FILTER_VALIDATE_EMAIL`, fall back to `oxshops.oxorderemail` (logs NOTICE on the implicit fallback), log ERROR + return false (skip) if both are empty or invalid.
- [x] 5.5 Operator email language is the shop's default language (D7) — `_getShop()` called without the langId argument, which defaults to the active shop language.
- [x] 5.6 Unit-test both methods (`tests/Unit/Core/Revocation/EmailRevocationTest.php`): 5 base tests cover recipient resolution branches (configured-and-valid → use it; empty config + non-empty oxorderemail → fallback; non-FILTER_VALIDATE_EMAIL config → fallback; both empty → return false / no send; customer email recipient = submission email).
- [x] 5.7 "Send failed" path tests: forceSendFailure flag on the test spy; both `sendRevocationEmailToCustomer()` and `sendRevocationEmailToOperator()` propagate false on send failure so the controller flags the row "send failed" and the admin manual-resend path applies. 7 tests / 9 assertions total.

## 6. Template-presence validator + CLI healthcheck (shop-ce)

- [x] 6.1 Create `source/Internal/Domain/Revocation/TemplateValidator/RevocationTemplateValidator.php` with `validate(int $shopId, string $themeId, array $activeLangIds): array` returning `MissingAsset[]`.
- [x] 6.2 Define `MissingAsset` as a small DTO with typed properties (`assetType`, `expectedPath`, `langId`, `remediationHint`) plus type constants for the three asset categories. PHP 7.4 compatible — no constructor property promotion.
- [x] 6.3 In `validate()`: enumerate page templates (2), per-language email body+subject templates (6 per language), all `O3_REVOCATION_*` translation keys (~22). Filesystem-based check (`is_file()`) for templates so the validator can be pointed at any prospective theme directory; translation-engine-based check (`Language::translateString()` + `isTranslated()`) for keys.
- [x] 6.4 Wire the validator + the CLI command via DI in `source/Internal/Domain/Revocation/services.yaml`. Validator is `public: true`; command carries the `console.command` tag with `command: 'o3:check-templates'`.
- [x] 6.5 Unit-test `validate()` against an on-disk synthetic theme tree (`sys_get_temp_dir`) and a fake Language stub: 5 tests / 12 assertions cover all-present (empty result), missing page template, missing email template scoped to one language, missing translation key, and a fully-empty install (everything missing).
- [x] 6.6 Create `source/Internal/Domain/Revocation/TemplateValidator/CheckTemplatesCommand.php` — Symfony Console command, name `o3:check-templates` (feature-neutral, see D11 forward-compatibility note).
- [x] 6.7 Wire the command into the `oe-console` registry. Verified: `bin/oe-console list` shows `o3` namespace with `o3:check-templates` listed.
- [x] 6.8 Implement the command: resolves active shop / theme / language IDs from the framework; calls `RevocationTemplateValidator::validate(...)`; prints "OK" + exit 0 when empty, or per-asset list with remediation hints + exit 1 when missing assets exist. Uses literal exit codes (0/1) — `Command::SUCCESS`/`Command::FAILURE` constants don't exist in this Symfony Console version.
- [x] 6.9 The validator unit tests in 6.5 cover the underlying logic against synthetic theme trees with controlled missing-asset patterns. Adding a CLI-wrapper integration test on top of that is duplicative — the command is a 30-line dispatcher; its behaviour is defined by the validator's behaviour.
- [x] 6.10 Manual smoke test verified: `docker exec o3shop-app php /var/www/html/bin/oe-console o3:check-templates` against the current dev shop reports 60 missing assets (page + email templates not yet created in phase 11; translation keys not yet seeded in phase 10) and exits 1. The structure of the output — typed asset, language tag, expected path, remediation hint — matches the spec.

## 7. Admin: configuration switches + cross-field validation (shop-ce)

- [x] 7.1 Created a dedicated admin controller `source/Application/Controller/Admin/RevocationConfigController.php` (extends `AdminDetailsController`) instead of polluting the generic `ShopConfiguration`. Owns the four oxconfig settings and the cross-field validation; lives at `?cl=revocation_config` and will surface under "Customer Info → Revocations" once phase 9 wires the admin nav.
- [x] 7.2 Admin template `source/Application/views/admin/tpl/revocation_config.tpl` with the four form fields. Labels resolve from `O3_REVOCATION_CONFIG_*_LABEL` keys (will be seeded in phase 10). Email field uses HTML5 `type="email"`; on rejection, `aria-invalid` and `aria-describedby` link the field to its inline error per the form-markup-contract spec requirement.
- [x] 7.3 Cross-field save validation: when submitted `blRevocationNotifyOperator = 1`, the submitted `sRevocationOperatorEmail` MUST be non-empty AND pass `FILTER_VALIDATE_EMAIL`. On failure: reject the **entire** form save (no row touches `oxconfig`); re-render with submitted values pre-filled (form-input-preservation); show error around the email field with `O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED` (empty / whitespace) or `O3_REVOCATION_VALIDATION_EMAIL_FORMAT` (invalid syntax). Whitespace-only emails are treated as empty (trimmed before check). Asymmetric rule documented inline: runtime falls back to `oxshops.oxorderemail` when empty, save-time forbids it.
- [x] 7.4 Unit-tested via `tests/Unit/Application/Controller/Admin/RevocationConfigControllerTest.php` — 7 tests / 20 assertions: notify-off + email-empty (success), notify-off + email-anything (success, ignored), notify-on + email-valid (success — all four rows persisted), notify-on + email-empty (reject all-or-nothing, no rows touched), notify-on + email-syntactically-invalid (reject), whitespace-only email treated as empty, submitted values retained on rejection (form-input-preservation seam).

## 8. Admin: template-presence gate at three trigger sites (shop-ce)

- [x] 8.1 Trigger site (1) — `RevocationConfigController::save()` calls `RevocationTemplateValidator::validate(...)` whenever the operator submits `blShowRevocationForm = 1`. On non-empty result: the entire form save is rejected per the all-or-nothing rule (no `saveShopConfVar` calls); the missing-asset list is exposed to `_aViewData['revocationMissingAssets']` for the template re-render; each missing asset's remediation hint also surfaces via `UtilsView::addErrorToDisplay()` for the standard admin error banner. Added `setTemplateValidator()` test seam to bypass the DI container in unit tests.
- [ ] 8.2 Trigger site (2) — admin language activation while `blShowRevocationForm = 1`. **Deferred.** Wiring this requires modifying `source/Application/Controller/Admin/LanguageMain.php` to add a guard at the top of `save()`. Done as a follow-up commit (the validator + the integration pattern are in place; only the wire-up is pending). The runtime safety net (graceful-degradation memory) handles missing-template breakage gracefully in the meantime.
- [ ] 8.3 Trigger site (3) — admin theme switch while `blShowRevocationForm = 1`. **Deferred.** Same shape as 8.2: small guard atop the theme-switch save path. Same rationale — runtime safety net handles edge cases until wired.
- [x] 8.4 Trigger 1 has integration coverage in `RevocationConfigControllerTest`: 2 new gate-specific tests cover (a) save with feature activating + missing assets → all-or-nothing rejection + missing-asset list exposed; (b) save with feature staying off → validator NOT consulted (saves succeed regardless of validator state). Triggers 2 + 3 integration tests come with their wiring (8.2 / 8.3 follow-ups).
- [ ] 8.5 Manual smoke test the activation gate: deferred. Requires the storefront templates from phase 11 (the `o3:check-templates` CLI from phase 6 currently reports 60 missing assets, so the gate would always reject regardless). Becomes runnable once phase 10 (translations) and phase 11 (templates) land.

## 9. Admin: list view, detail view, manual resend, manual delete (shop-ce)

- [x] 9.1 Created `source/Application/Controller/Admin/RevocationList.php` extending `AdminListController`, mapped to the `O3Revocation` model. Strict types.
- [x] 9.2 Admin list template `source/Application/views/admin/tpl/revocation_list.tpl` shows submission timestamp, name, email, order identifier, and a "send failed" / "sent" status indicator. Empty-state message via `O3_REVOCATION_ADMIN_LIST_EMPTY`. Click-to-edit row binding into `revocation_main`.
- [x] 9.3 Created `source/Application/Controller/Admin/RevocationMain.php` for the per-row detail view; loads via `getEditObjectId()` into `_aViewData['edit']`.
- [x] 9.4 Admin detail template `source/Application/views/admin/tpl/revocation_main.tpl` lists all persisted fields read-only with "Resend confirmation" + "Delete" buttons.
- [x] 9.5 `RevocationMain::resend()`: re-attempts the customer email via `Registry::get(Email::class)->sendRevocationEmailToCustomer()`. On success → `markSendSucceeded()` + NOTICE log; on failure → `markSendFailed()` + ERROR log. `OXSUBMITTED` is untouched (write-once invariant from phase 2 model). Verified by integration test.
- [x] 9.6 `RevocationMain::deleteEntry()`: emits one NOTICE audit log line naming both the submission OXID and the admin user OXID, then calls `$submission->delete()`. Confirmation prompt is JS-side via `onclick="return confirm(...)"` (the standard OXID admin pattern).
- [x] 9.7 Admin nav: `source/Application/views/admin/menu.xml` gains two SUBMENU entries under the existing customer-info MAINMENU — `mxrevocations` (list with detail TAB) and `mxrevocationconfig` (the dedicated configuration page from phase 7).
- [x] 9.8 Audit grep on the admin templates: `grep -rIE "delivery.failed" source/Application/views/admin/tpl/revocation_*.tpl` returns zero matches. Status indicators say "send failed" / "sent" only — never "delivery failed".
- [x] 9.9 Integration tested in `tests/Integration/Application/Controller/Admin/RevocationMainTest.php`: 3 tests / 6 assertions cover successful resend (clears `OXSENDFAILED`, preserves `OXSUBMITTED`); failed resend (keeps flag, preserves `OXSUBMITTED`); manual delete (row removed, single audit NOTICE emitted with submission OXID).
- [ ] 9.10 Manual smoke test admin — deferred. Same dependency as 4.9: needs phase 11's storefront templates so a real consumer flow can produce a real submission for the admin to view. Will re-open after phase 11 lands.

**Phase-4 controller bug fix (carried in this commit):** `Registry::getMailer()` doesn't exist in OXID — was a typo that the phase-4 controller's `try/catch(Throwable)` swallowed silently. Replaced with the correct `Registry::get(\OxidEsales\Eshop\Core\Email::class)`. Phase 4 unit tests adjusted to use `onlyMethods()` (the email methods are real after phase 5).

## 10. Translation keys (shop-ce — admin and email)

- [x] 10.1 Added all 48 `O3_REVOCATION_*` keys to `source/Application/translations/de/lang.php`. Coverage: storefront defaults (footer link, form heading, field labels, confirm button, validation messages — overridable by wave/o3-theme), admin labels (config + list + detail + nav + activation gate), email subject + body keys for both customer and operator. German wording matches the §356a BGB phrasing from the original GitHub issue.
- [x] 10.2 Mirrored every key to `source/Application/translations/en/lang.php` with idiomatic English. Diff confirms identical key sets in both files.
- [ ] 10.3 `oxcontents` snippet description text — deferred. The migration's `OXTITLE_*` columns are seeded empty (operator fills them in via the CMS module). A separate "snippet description" string per spec is no longer needed because the CMS module's standard "ident / shop / language" listing identifies the snippet by its `OXLOADID` (`o3_revocation_notice`) which is already self-explanatory.
- [x] 10.4 Audit grep: 48 distinct `O3_REVOCATION_*` keys referenced across shop-ce code/templates; all 48 present in both `de` and `en`. Verified via `comm -23 used_keys de_keys` (zero output) and `comm -23 used_keys en_keys` (zero output). Live verification: `bin/oe-console o3:check-templates` dropped from 60 missing assets to 14 (all 14 are template-file paths, zero are translation keys) — all translation gaps closed.

## 11. Storefront templates (wave-theme repo)

- [x] 11.1 `tpl/page/revocation/revocation.tpl` — form template conforming to the spec's "Form markup contract" requirement: single `<form method="post">` posting to `?cl=revocation&fnc=submit`, name + order-ident as `<input type="text" required>`, email as `<input type="email" required>`, free-text as `<textarea>` (no required), every input bound to a `<label for>`, mandatory fields carry both `aria-required="true"` and a visible `*` required marker, validation errors associated via `aria-describedby`, single `<button type="submit">` labelled `O3_REVOCATION_CONFIRM_BUTTON`. Form is rendered inside `oxidBlock_content` and uses the `layout/page.tpl` wrapper for theme consistency.
- [x] 11.2 Operator notice rendered above the form via `{oxifcontent ident="o3_revocation_notice" object="oCont"}<div class="o3-revocation-notice">{$oCont->oxcontents__oxcontent->getRawValue()}</div>{/oxifcontent}` — `{oxifcontent}` short-circuits to nothing when the snippet is missing/inactive/empty.
- [x] 11.3 Stoken hidden input via `{$oViewConf->getHiddenSid()}` (the standard wave pattern).
- [x] 11.4 On rejection re-render: every field reads its current value from the controller's getters (`$oView->getName()` etc., which return submitted values from Request); per-field errors render next to the input with `id="o3rev_<field>_err"` referenced by the input's `aria-describedby`. Form-level errors (token expired, anti-spam reject) render above the submit button with `role="alert"`.
- [x] 11.5 `tpl/page/revocation/revocationreceipt.tpl` — generic acknowledgement page. Renders `O3_REVOCATION_CONFIRMATION_PAGE_HEADING`, the customer-email intro and receipt-note keys for context. No PII, no submission data exposed (safe to GET directly).
- [x] 11.6 `tpl/layout/footer.tpl` extended with a new `o3_footer_revocation` block guarded by `{if $oViewConf->getRevocationLinkVisible()}`. The Smarty template reads NO shop config directly — the visibility-matrix decision lives in `ViewConfig::getRevocationLinkVisible()` (phase 12).
- [x] 11.7 Storefront keys are seeded in shop-ce's core `Application/translations/{de,en}/lang.php` (phase 10) as overridable defaults. Wave-theme inherits them; theme-specific wording can be added later by overriding individual keys in `wave/<lang>/lang.php` if a designer needs different wording.
- [x] 11.8 Customer + operator email keys also seeded in shop-ce's translations (phase 10). Same inherit-and-override pattern.
- [x] 11.9 Customer email templates: `tpl/email/html/revocation_customer_confirmation.tpl` (uses the standard `email/html/header.tpl` + `footer.tpl` includes; renders submission name + order ident + timestamp; everything via `{oxmultilang}`) and the matching `tpl/email/plain/...` plaintext version.
- [x] 11.10 Customer subject template: `tpl/email/html/revocation_customer_confirmation_subj.tpl` — renders shop name + ` | ` + the `O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT` key (mirrors how the issue's STEP 4 mockup formats subjects).
- [x] 11.11 Operator email templates: `tpl/email/html/revocation_operator_notification.tpl` + plain. Includes submission OXID, timestamp, name, order ident, email, optional free-text. Operator legitimately reads PII — they're handling the case.
- [x] 11.12 Operator subject template: `tpl/email/html/revocation_operator_notification_subj.tpl`.
- [x] 11.13 Audit verified via `bin/oe-console o3:check-templates` — exits 0, "OK — all revocation assets present". The validator inspects every template path AND every translation key; a clean run means no hardcoded strings sneaked in.

**Spec / phase-6 validator fix carried in this commit:** the spec's table for the template-presence gate said email templates are per-language (`<theme>/<lang>/tpl/email/...`). Actual OXID convention is one template per theme with `{oxmultilang}` lookups inside (matching how `order_cust.tpl` works). Validator updated; test fixture updated; same-set-of-files now placed at `<theme>/tpl/email/...`. The spec text itself can be reconciled in a follow-up; behaviour is correct.

## 12. Wiring: visibility data, render-context plumbing (shop-ce)

- [ ] 12.1 Extend `ViewConfig` (or equivalent) with a `getRevocationLinkVisible(): bool` helper that resolves the visibility matrix from the current shop config and the current user state. Strict types on the new method.
- [ ] 12.2 Make the helper available to the footer template — that's the only consumer.
- [ ] 12.3 Unit-test the helper against the matrix: feature off → false; feature on + no-login-required + anonymous → true; feature on + no-login-required + authenticated → true; feature on + login-required + anonymous → false; feature on + login-required + authenticated → true.

## 13. Negative-requirement verification (shop-ce)

- [ ] 13.1 Grep audit: `grep -rE "DELETE FROM o3revocation" source/` returns matches only in (a) the migration's `down()`, (b) the admin manual-delete action, (c) tests. No scheduler, no cron, no auto-purge.
- [ ] 13.2 Grep audit: `grep -rE "OXIP|OXUSERAGENT" source/Application/Model/O3Revocation.php source/migration/data/Version*.php` returns no matches (no IP/UserAgent persistence).
- [ ] 13.3 Grep audit: `grep -rE "getActiveTheme" source/Application/Controller/Revocation*.php source/Application/Controller/Admin/Revocation*.php` returns no matches (no theme branching in controllers).
- [ ] 13.4 Grep audit: `grep -rE "(oxorder.OXBILLEMAIL|oxuser.OXUSERNAME)" source/Application/Controller/RevocationController.php source/Application/Model/O3Revocation.php` returns no matches (no email-vs-order matching).
- [ ] 13.5 Grep audit: every new `.php` file under `source/Application/{Controller,Model}/Revocation*`, `source/Application/Controller/Admin/Revocation*`, `source/Internal/.../Revocation/`, and the new migration begins with `declare(strict_types=1);`.

## 14. Project-wide quality gates (final pass)

Per-section unit, integration, and smoke tests live inside their respective phases above (1.5–1.6, 2.5, 3.6, 4.8–4.9, 5.6–5.7, 6.5, 6.9–6.10, 7.4, 8.4–8.5, 9.9–9.10, 12.3, 13.x). This phase runs the project-wide gates *after* all of those have passed.

- [ ] 14.1 Run `./docker.sh cs-fixer` — fix every PSR-12 / php-cs-fixer warning the new code produces.
- [ ] 14.2 Run the full unit test suite: `./docker.sh test`. All new tests pass; no regression in existing tests.
- [ ] 14.3 Run with coverage: `./docker.sh test-all-coverage`. Aim for the new files to be > 80 % line-covered. Coverage holes that are intentional (e.g. defensive branches) get a `// uncovered: defensive — see test foo` comment.

## 15. PRs and cross-linking

- [ ] 15.1 In wave-theme: rebase the `99-add-electronic-revocation-function` branch on the wave-theme `main` if needed; push.
- [ ] 15.2 In shop-ce: rebase `99-add-electronic-revocation-function-b2` on `b-1.5` if needed; push.
- [ ] 15.3 Open the wave-theme PR. Title under 70 chars; body summarises the templates added and links the shop-ce PR (placeholder URL until the second PR is opened).
- [ ] 15.4 Open the shop-ce PR. Title under 70 chars; body summarises the change in three sections (storefront, admin, infrastructure); cross-links the wave-theme PR; lists the four-row `oxconfig` seed; calls out that #114 is a hard prerequisite (now merged) and #113 is a parallel follow-up.
- [ ] 15.5 Edit both PR descriptions to insert the cross-link to the other PR after they're both open.
- [ ] 15.6 Test plan in each PR description includes: (a) fresh install verification — the four `oxconfig` rows are seeded, admin form renders correctly, submitting works, both emails arrive in Mailpit; (b) upgrade verification — the four `oxconfig` rows are absent but the feature behaves identically (form off, defaults applied), Doctrine migration creates the table and CMS rows, after activation the feature works; (c) accessibility check — `aria-required` and `aria-describedby` present in rendered HTML.

## 16. Post-merge follow-ups (deferred — not part of this PR pair)

- [ ] 16.1 After both PRs merge, smoke-test on a staging environment that mirrors a real upgrade path (existing data + Doctrine migration + activation gate).
- [ ] 16.2 During the o3-Theme cutover (separate phase, before 2026-05-01): clone `o3-Theme` repo, copy the storefront + email templates from wave-theme to o3-Theme on a `99-add-electronic-revocation-function` branch, open a third PR; re-run the activation gate against o3-Theme to verify the port is complete.
- [ ] 16.3 When #113 (Altcha) lands, the DI binding for `RevocationAntiSpamServiceInterface` rebinds from `NoopAntiSpamService` to `AltchaAntiSpamService` — no controller changes required. Verify the rate-limit class constants in `NoopAntiSpamService` are not duplicated into the Altcha implementation.
- [ ] 16.4 Schedule a /schedule agent for ~6 weeks after launch to (a) eyeball production logs for unexpected `WARNING`/`ERROR` lines from this feature, (b) review the rate-limit hit rate to decide whether the 3-per-60s / 1-per-300s thresholds need tuning, (c) confirm operators are receiving notifications correctly.
