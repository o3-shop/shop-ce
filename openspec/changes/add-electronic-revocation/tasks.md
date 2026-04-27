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

- [ ] 7.1 Create or extend the appropriate admin shop-config controller to expose the four new `oxconfig` fields. Default rendering pulls values from `oxconfig` directly (already happens for existing fields).
- [ ] 7.2 Add the admin template fragment / form fields for `blShowRevocationForm`, `blRevocationRequireLogin`, `blRevocationNotifyOperator`, `sRevocationOperatorEmail`. Use translation keys `O3_REVOCATION_CONFIG_*_LABEL` for the labels.
- [ ] 7.3 Implement the cross-field save validation: if submitted `blRevocationNotifyOperator = 1`, the submitted `sRevocationOperatorEmail` must be non-empty AND pass `FILTER_VALIDATE_EMAIL`. On failure: reject the **entire** form save (all-or-nothing per D11), re-render the form with all submitted values pre-filled, show the error styled around the email field with `O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED` (empty) or `O3_REVOCATION_VALIDATION_EMAIL_FORMAT` (invalid).
- [ ] 7.4 Unit-test the admin save handler: valid notify-on + valid email → success; notify-on + empty email → reject with all values preserved; notify-on + invalid email → reject; notify-off + any email value → success.

## 8. Admin: template-presence gate at three trigger sites (shop-ce)

- [ ] 8.1 At trigger site (1) — admin shop-config save with `blShowRevocationForm = 1` — call `RevocationTemplateValidator::validate(currentShopId, currentThemeId, currentActiveLangIds)`. On non-empty result: reject the entire form save per the all-or-nothing rule, re-render with submitted values pre-filled, show the per-asset list with remediation hints.
- [ ] 8.2 At trigger site (2) — admin language activation while `blShowRevocationForm = 1` — extend the language admin controller to call the validator with the prospective language list. On failure: reject the language activation (language stays disabled), surface the per-asset list.
- [ ] 8.3 At trigger site (3) — admin theme switch while `blShowRevocationForm = 1` — extend the theme admin controller to call the validator with the prospective theme. On failure: reject the theme switch (active theme stays as it was), surface the per-asset list.
- [ ] 8.4 Integration-test each trigger site end-to-end: simulate a valid save → success; simulate a save that would leave the feature on with missing assets → entire save rejected, all submitted values pre-filled in the re-rendered form, missing-asset list shown.
- [ ] 8.5 Manual smoke test the activation gate: temporarily delete one customer-email template from the wave-theme working tree, save the admin config (or toggle the feature) — confirm the entire form save is rejected with a missing-asset list and the form values are preserved. Restore the template and retry — save succeeds.

## 9. Admin: list view, detail view, manual resend, manual delete (shop-ce)

- [ ] 9.1 Create `source/Application/Controller/Admin/RevocationListController.php` extending the appropriate admin list base class. Maps to `o3revocation`. Strict types.
- [ ] 9.2 Create the admin list template `source/Application/views/admin/tpl/revocation_list.tpl` showing submission ID, name, order identifier, email, submission timestamp, and a "send failed" indicator (translation key `O3_REVOCATION_ADMIN_FLAG_SEND_FAILED`). Empty-state message via `O3_REVOCATION_ADMIN_LIST_EMPTY`.
- [ ] 9.3 Create `source/Application/Controller/Admin/RevocationDetailController.php` for the per-row detail view.
- [ ] 9.4 Create the admin detail template `source/Application/views/admin/tpl/revocation_detail.tpl` showing all persisted fields and a "Resend confirmation" button.
- [ ] 9.5 Implement the "Resend confirmation" admin action: re-attempt only the customer email; update `OXTIMESTAMP` (DB engine); leave `OXSUBMITTED` untouched; clear the "send failed" flag if the resend succeeded.
- [ ] 9.6 Implement the manual-delete action with a confirmation prompt; emit one `NOTICE` log line naming the submission `OXID` and the admin user `OXID`.
- [ ] 9.7 Add the admin nav entry under "Customer Info → Revocations" — extend `menu.xml` (or whichever the admin nav config is in this codebase).
- [ ] 9.8 Verify the admin's "send failed" indicator wording does NOT claim "delivery failed" (review-time grep).
- [ ] 9.9 Unit-test resend action preserves `OXSUBMITTED` and updates `OXTIMESTAMP`; manual delete writes the audit log line.
- [ ] 9.10 Manual smoke test admin: open `http://localhost:8080/admin/`, navigate to "Customer Info → Revocations", confirm the test submission shows up in the list with the correct columns; open the detail view; click resend; verify Mailpit gets a fresh customer email and the "send failed" flag clears; manually delete one row via the admin button and verify it disappears with the audit `NOTICE` log line.

## 10. Translation keys (shop-ce — admin and email)

- [ ] 10.1 Add all `O3_REVOCATION_*` keys used by admin templates and email templates to `source/Application/translations/de/lang.php`. Required keys per the spec: `O3_REVOCATION_CONFIG_SHOW_LABEL`, `O3_REVOCATION_CONFIG_REQUIRELOGIN_LABEL`, `O3_REVOCATION_CONFIG_NOTIFY_LABEL`, `O3_REVOCATION_CONFIG_OPERATOR_EMAIL_LABEL`, `O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED`, `O3_REVOCATION_VALIDATION_EMAIL_FORMAT`, `O3_REVOCATION_ADMIN_NAV_LABEL`, `O3_REVOCATION_ADMIN_LIST_HEADING`, `O3_REVOCATION_ADMIN_LIST_EMPTY`, `O3_REVOCATION_ADMIN_FLAG_SEND_FAILED`, `O3_REVOCATION_ADMIN_RESEND_BUTTON`, `O3_REVOCATION_ADMIN_DELETE_BUTTON`, `O3_REVOCATION_ADMIN_DELETE_CONFIRM`. Plus the `_CUSTOMER_EMAIL_*` and `_OPERATOR_EMAIL_*` body/subject keys.
- [ ] 10.2 Mirror every key from 10.1 to `source/Application/translations/en/lang.php` with English translations.
- [ ] 10.3 Add the `oxcontents` snippet description text — the title used for the seeded `oxcontents` rows. One key per language.
- [ ] 10.4 Audit grep: `grep -rE "O3_REVOCATION_" source/Application/translations/{de,en}/lang.php` lists every key used in the codebase. Manually cross-check against the shop-ce templates and PHP files: every key referenced is present in both `de` and `en`.

## 11. Storefront templates (wave-theme repo)

- [ ] 11.1 In `source/Application/views/wave/` (the wave-theme working tree), checked out on branch `99-add-electronic-revocation-function`, create `tpl/page/revocation/revocation.tpl` — the form. Markup MUST conform to the spec's "Form markup contract" requirement: single `<form method="post" action="?cl=revocation&fnc=submit">`; name + order ident as `<input type="text" required>`; email as `<input type="email" required>`; free-text as `<textarea>`; every input has `<label for>`; mandatory fields carry `aria-required="true"` and a visible required marker; validation errors associated via `aria-describedby`; single `<button type="submit">` labelled from `O3_REVOCATION_CONFIRM_BUTTON`.
- [ ] 11.2 Above the form, include the operator notice via `{oxifcontent ident="o3_revocation_notice" object="oCont"}<div class="o3-revocation-notice">{$oCont->oxcontents__oxcontent->getRawValue()}</div>{/oxifcontent}`.
- [ ] 11.3 Embed the stoken hidden input via the standard `Session::hiddenSid()` Smarty helper.
- [ ] 11.4 On the form re-render path (rejection), pre-fill every field with the submitted value passed from the controller. Errors are rendered next to their fields with the matching `id` referenced by `aria-describedby`.
- [ ] 11.5 Create `tpl/page/revocation/revocationreceipt.tpl` — the receipt page. Renders `O3_REVOCATION_CONFIRMATION_PAGE_HEADING`, a generic acknowledgement, and a brief note that a confirmation email is being sent. No PII, no submission data exposed (the page is GET-able by anyone).
- [ ] 11.6 Extend `tpl/layout/footer.tpl` to render the revocation entry link. Visibility logic per the matrix: feature on AND (login not required OR user authenticated). The Smarty condition reads booleans the controller layer feeds in (e.g. `$oViewConf->getRevocationLinkVisible()`); the template MUST NOT directly read shop config — that's controller-layer data.
- [ ] 11.7 Add storefront translation keys to `de/lang.php` and `en/lang.php`: `O3_REVOCATION_FOOTER_LINK`, `O3_REVOCATION_FORM_HEADING`, `O3_REVOCATION_FIELD_NAME_LABEL`, `O3_REVOCATION_FIELD_ORDERNUMBER_LABEL`, `O3_REVOCATION_FIELD_EMAIL_LABEL`, `O3_REVOCATION_FIELD_FREETEXT_LABEL`, `O3_REVOCATION_CONFIRM_BUTTON`, `O3_REVOCATION_CONFIRMATION_PAGE_HEADING`, `O3_REVOCATION_VALIDATION_REQUIRED`, `O3_REVOCATION_VALIDATION_EMAIL_FORMAT`, `O3_REVOCATION_VALIDATION_SESSION_EXPIRED`, `O3_REVOCATION_VALIDATION_SPAM`.
- [ ] 11.8 Add the customer-email keys to the language files: `O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT`, `..._BODY_INTRO`, `..._BODY_RECEIPT_NOTE`, `..._BODY_FOOTER`. Also operator-email keys: `O3_REVOCATION_OPERATOR_EMAIL_SUBJECT`, `..._BODY`.
- [ ] 11.9 Create the email body templates in `tpl/email/`: `html/revocation_customer_confirmation.tpl`, `plain/revocation_customer_confirmation.tpl` (mirror `order_cust.tpl` placement and structure). Every label `{oxmultilang}`-translated. Body includes submission name, order identifier, submission timestamp, and submission ID.
- [ ] 11.10 Create the customer-email subject template: `html/revocation_customer_confirmation_subj.tpl` rendering `{oxmultilang ident="O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT"}` followed by the submission ID parenthetical (mirroring how `order_cust_subj.tpl` appends `(#…)`).
- [ ] 11.11 Create the operator-email body templates: `html/revocation_operator_notification.tpl`, `plain/revocation_operator_notification.tpl`. Includes name, order ident, email, free-text excerpt (operator may legitimately read PII — they're handling the case), submission timestamp, submission ID, link to admin detail view.
- [ ] 11.12 Create the operator-email subject template: `html/revocation_operator_notification_subj.tpl`.
- [ ] 11.13 Audit grep on the wave-theme branch: `grep -rIE "(>|\")[A-ZÄÖÜ][^<\"']{3,}(<|\")" tpl/page/revocation tpl/email/*revocation*` — every match should be inside a `{oxmultilang}` or `{oxcontent}` tag, not a hardcoded string.

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
