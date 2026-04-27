## ADDED Requirements

### Requirement: Footer entry-link visibility matrix

The storefront SHALL render a "revocation entry" link in the footer of every page subject to a two-flag, login-state matrix:

| `blShowRevocationForm` | `blRevocationRequireLogin` | User state | Link rendered? |
|---|---|---|---|
| `0` | any | any | **No** — feature is off |
| `1` | `0` | anonymous | **Yes** |
| `1` | `0` | authenticated | **Yes** |
| `1` | `1` | anonymous | **No** — anonymous visitors can't reach the form anyway, so the link would mislead them |
| `1` | `1` | authenticated | **Yes** |

When the link is rendered, its text SHALL be resolved from `O3_REVOCATION_FOOTER_LINK` and its target SHALL be `?cl=revocation`. When the link is not rendered, no whitespace, container, or visual artifact MAY remain in its place.

#### Scenario: Feature off — link never shown
- **WHEN** any visitor loads any storefront page and `blShowRevocationForm` is unset or `0`
- **THEN** the footer contains no revocation link and no whitespace artifact remains

#### Scenario: Feature on, login not required, anonymous visitor — link visible
- **WHEN** an anonymous visitor loads any storefront page and `blShowRevocationForm = 1` and `blRevocationRequireLogin = 0`
- **THEN** the footer contains a link with text resolved from `O3_REVOCATION_FOOTER_LINK` pointing to `?cl=revocation`

#### Scenario: Feature on, login not required, authenticated visitor — link visible
- **WHEN** an authenticated visitor loads any storefront page and `blShowRevocationForm = 1` and `blRevocationRequireLogin = 0`
- **THEN** the footer contains the same revocation link in the same position

#### Scenario: Feature on, login required, anonymous visitor — link hidden
- **WHEN** an anonymous visitor loads any storefront page and `blShowRevocationForm = 1` and `blRevocationRequireLogin = 1`
- **THEN** the footer contains no revocation link and no whitespace artifact remains
- **AND** there is no visual hint that a revocation feature exists for this shop

#### Scenario: Feature on, login required, authenticated visitor — link visible
- **WHEN** an authenticated visitor loads any storefront page and `blShowRevocationForm = 1` and `blRevocationRequireLogin = 1`
- **THEN** the footer contains the revocation link

#### Scenario: Login state changes during the session
- **WHEN** a previously-anonymous visitor logs in on a shop with `blShowRevocationForm = 1` and `blRevocationRequireLogin = 1`
- **THEN** the next storefront page they load contains the footer link
- **AND** when they log out again, the next page loaded does not contain the link

### Requirement: Revocation form rendering (step 1)

When a visitor with access (per the footer-link visibility matrix) reaches the form, the system SHALL render it via controller `?cl=revocation` (default action) containing exactly the three statutory mandatory input fields, one optional free-text field, the operator notice block above the form, a session challenge token, and a submit button. Form rendering MUST be the same for anonymous and authenticated visitors that are allowed in — login state controls *access*, not form *content*.

#### Scenario: Form renders for any visitor with access
- **WHEN** a visitor allowed by the visibility matrix loads `?cl=revocation`
- **THEN** the form renders with fields *name*, *order identification*, *email address*, *optional free-text* and a submit button labelled from `O3_REVOCATION_SUBMIT_BUTTON`
- **AND** a hidden `stoken` input is present
- **AND** the operator notice from CMS snippet `o3_revocation_notice` is included above the form

### Requirement: Direct-URL access control

When a visitor navigates directly to `?cl=revocation` (rather than via the footer link), the access decision SHALL apply the same rules as the link-visibility matrix. Anonymous visitors hitting the URL while `blRevocationRequireLogin = 1` MUST be sent to the login form first; visitors hitting the URL while the feature is off MUST be sent to a generic "page not available" response, not to a revealing error.

#### Scenario: Anonymous direct navigation while login is required
- **WHEN** an anonymous visitor types `?cl=revocation` and `blShowRevocationForm = 1` and `blRevocationRequireLogin = 1`
- **THEN** the visitor is redirected to the login form
- **AND** after successful login they reach the revocation form (no second redirect needed)

#### Scenario: Direct navigation while feature is off
- **WHEN** any visitor navigates to `?cl=revocation` and `blShowRevocationForm` is unset or `0`
- **THEN** the visitor sees a generic "page not available" response (404 or equivalent)
- **AND** the response MUST NOT reveal that the revocation feature exists but is disabled (no message like "this feature has been turned off by the shop owner")

### Requirement: Mandatory and optional form fields

The form SHALL present exactly three mandatory fields — *full name*, *order/contract identification*, *electronic communication channel (email)* — and one optional *free-text* field. No additional field MAY be marked as required. The optional free-text field MUST NOT be enforced as mandatory under any configuration.

#### Scenario: Submit with all mandatory fields filled
- **WHEN** the visitor submits the form with all three mandatory fields populated and the free-text field empty
- **THEN** validation passes and the flow advances to the confirmation step

#### Scenario: Submit with one mandatory field empty
- **WHEN** the visitor submits the form with the *email address* field left blank
- **THEN** validation fails and the form re-renders with the previously-typed values for *name* and *order identification* preserved
- **AND** the *email address* field shows an error styled around it with message resolved from `O3_REVOCATION_VALIDATION_REQUIRED`

#### Scenario: Submit with free-text omitted
- **WHEN** the visitor submits the form with all mandatory fields filled and the free-text field empty
- **THEN** the system MUST NOT treat the empty free-text as a validation error

### Requirement: No matching of submitted email against order data

The system MUST NOT validate, match, or compare the submitted email address against `oxorder.OXBILLEMAIL`, `oxuser.OXUSERNAME`, or any other stored email field. The system MUST NOT validate or match the submitted order identifier against `oxorder.OXORDERNR` or any other stored order field. The submission MUST be accepted regardless of whether the typed values exist in the shop's records.

#### Scenario: Submitted email does not match any order email
- **WHEN** the visitor submits the form with an `OXEMAIL` value that has no matching row in `oxorder.OXBILLEMAIL`
- **THEN** the submission is accepted and the flow advances to the confirmation step

#### Scenario: Submitted order identifier does not match any order
- **WHEN** the visitor submits the form with an `OXORDERIDENT` value that has no matching row in `oxorder.OXORDERNR`
- **THEN** the submission is accepted and the flow advances to the confirmation step

### Requirement: Session challenge token required on state-changing actions

The submit action (`?cl=revocation&fnc=submit`) and the confirm action (`?cl=revocation&fnc=confirm`) MUST verify the session challenge token (`stoken`) before processing input. Requests with an absent or mismatched token MUST be rejected without persisting any data and without emitting any email.

#### Scenario: Submit without token
- **WHEN** a POST arrives at `?cl=revocation&fnc=submit` with no `stoken` parameter
- **THEN** the action redirects to `?cl=revocation` with a translated info message resolved from `O3_REVOCATION_VALIDATION_SESSION_EXPIRED`
- **AND** no row is written to `o3revocation`
- **AND** no email is sent
- **AND** one `WARNING` log line is emitted

#### Scenario: Submit with mismatched token
- **WHEN** a POST arrives at `?cl=revocation&fnc=submit` with a `stoken` value that does not match the session token
- **THEN** the same rejection behaviour applies as for the absent-token case

#### Scenario: Confirm without token
- **WHEN** a POST arrives at `?cl=revocation&fnc=confirm` with no valid `stoken`
- **THEN** no `o3revocation` row is written, no email is sent, and the visitor is redirected back to the form

### Requirement: Anti-spam verification with 3-per-minute IP rate-limit default

After the session challenge token check passes, the submit and confirm actions MUST call the configured `RevocationAntiSpamService::verify()` and reject the request if it returns `false`. The default service implementation `NoopAntiSpamService` SHALL enforce a 3-submissions-per-IP-per-minute limit. Rejection responses MUST display a generic translated error and MUST NOT reveal which signal triggered the rejection.

#### Scenario: First three submissions within a minute from the same IP succeed
- **WHEN** a single IP submits three valid forms within 60 seconds
- **THEN** all three are processed normally

#### Scenario: Fourth submission within a minute is rejected
- **WHEN** a single IP submits a fourth valid form within the same 60-second window
- **THEN** the form re-renders with the submitted values preserved and a generic error message resolved from `O3_REVOCATION_VALIDATION_SPAM`
- **AND** no row is written to `o3revocation`
- **AND** one `WARNING` log line is emitted

#### Scenario: Anti-spam service replacement
- **WHEN** the DI container rebinds `RevocationAntiSpamService` to a different implementation (e.g. `AltchaAntiSpamService`)
- **THEN** the controller code MUST NOT need to change

### Requirement: Confirmation step (step 2 view)

When validation in step 2 passes, the system SHALL stash the validated submission data in the user session under key `o3_revocation_pending` and render a confirmation page that displays the submitted values back to the visitor along with a "confirm" button (label resolved from `O3_REVOCATION_CONFIRM_BUTTON`) and a fresh session challenge token. The confirmation page MUST NOT persist anything to the database and MUST NOT emit any email.

#### Scenario: Validation passes — confirmation step renders
- **WHEN** the submit action processes a valid form
- **THEN** the validated values are written to the session key `o3_revocation_pending`
- **AND** the visitor sees the confirmation step with submitted values displayed read-only
- **AND** the visitor sees a confirm button and a fresh `stoken` hidden input
- **AND** no row exists in `o3revocation` yet
- **AND** no email has been sent

#### Scenario: Visitor refreshes the confirmation page
- **WHEN** the visitor refreshes the confirmation step page
- **THEN** the page re-renders with the same data from session and no row is created

### Requirement: Confirmation submission persists and triggers emails (step 3)

When the visitor submits the confirm action with a valid token, the system SHALL persist the submission to the `o3revocation` table first, then attempt to send the customer confirmation email and the operator notification email, then clear the session key, then issue a 303 redirect to the receipt page.

#### Scenario: Successful confirm — full flow
- **WHEN** the visitor submits `?cl=revocation&fnc=confirm` with a valid token and a populated session key
- **THEN** a new row is inserted into `o3revocation` with `OXSUBMITTED` set to the current timestamp
- **AND** `sendRevocationEmailToCustomer($submission)` is called
- **AND** `sendRevocationEmailToOperator($submission)` is called when `blRevocationNotifyOperator = 1`
- **AND** the session key `o3_revocation_pending` is cleared
- **AND** the response is HTTP 303 to `?cl=revocation&fnc=receipt`

#### Scenario: Confirm with empty session
- **WHEN** the visitor submits the confirm action but the session key `o3_revocation_pending` is missing or expired
- **THEN** the visitor is redirected back to `?cl=revocation` with a translated info message
- **AND** no row is written and no email is sent

### Requirement: Persist-first ordering for legal robustness

The submission row MUST be persisted before any email send is attempted. Failure of any email send MUST NOT cause the persisted row to be rolled back or deleted. The legally-meaningful "time of receipt" is the persistence timestamp, independent of email delivery success.

#### Scenario: Customer email fails after persist
- **WHEN** persistence succeeds but the customer email send returns failure
- **THEN** the `o3revocation` row remains in place
- **AND** the admin detail view displays a "delivery failed" flag for that row
- **AND** one `ERROR` log line names the submission ID and the underlying error
- **AND** the consumer-side flow still completes with a receipt page

#### Scenario: Operator email fails after persist
- **WHEN** persistence succeeds, the customer email succeeds, and the operator email send returns failure
- **THEN** the row remains in place, the customer receives their receipt, and one `ERROR` log line records the operator-email failure

### Requirement: Receipt page (step 4)

After a successful confirm, the system SHALL render a receipt page in response to the GET request at `?cl=revocation&fnc=receipt`. The page MUST acknowledge the submission and indicate that a confirmation email has been (or is being) sent. The page MUST NOT require the visitor to remain in any particular session state.

#### Scenario: GET receipt after confirm
- **WHEN** the visitor follows the 303 redirect from the confirm action
- **THEN** the receipt page renders with heading `O3_REVOCATION_CONFIRMATION_PAGE_HEADING`

#### Scenario: GET receipt without prior submission
- **WHEN** the visitor navigates directly to `?cl=revocation&fnc=receipt` with no recent submission
- **THEN** the page renders a generic acknowledgement (no PII) without exposing a previous submission's data

### Requirement: Customer confirmation email content

The customer confirmation email SHALL be sent in the consumer's submission language (`OXLANG`), via Smarty templates `tpl/email/{html,plain}/revocation_customer_confirmation.tpl` and subject template `tpl/email/html/revocation_customer_confirmation_subj.tpl`. The body MUST include the submitted *full name*, the submitted *order identifier*, the *time of receipt* (`OXSUBMITTED`), and the submission identifier. The email MUST NOT include the request IP, User-Agent, or any data not visible to the consumer in the form.

#### Scenario: Email rendered in submission language
- **WHEN** the consumer submitted with `OXLANG = 1` (English)
- **THEN** the email is rendered in English using the English lang file translations

#### Scenario: Email rendered in language with missing key falls back
- **WHEN** a language-specific lang file is missing one of the `O3_REVOCATION_CUSTOMER_EMAIL_*` keys
- **THEN** the translation engine falls back to the shop default language for that key only
- **AND** the email is still sent (no failure, no skipped send)

#### Scenario: Email contains submission timestamp
- **WHEN** a confirmation email is generated
- **THEN** the body contains the `OXSUBMITTED` timestamp formatted in the consumer's language locale

### Requirement: Operator notification email

When `blRevocationNotifyOperator = 1`, the system SHALL send an operator-facing email per submission via Smarty templates `tpl/email/{html,plain}/revocation_operator_notification.tpl`. The recipient address SHALL be `sRevocationOperatorEmail` when non-empty, otherwise `oxshops.oxorderemail`. If both are empty the operator email MUST be skipped (consumer flow unaffected) and one `ERROR` log line emitted. The email MUST be rendered in the shop's default language.

#### Scenario: Notification on, recipient configured
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = "ops@example.com"`
- **THEN** an operator email is sent to `ops@example.com`

#### Scenario: Notification on, recipient empty, fallback
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = ""` and `oxshops.oxorderemail = "shop@example.com"`
- **THEN** the operator email is sent to `shop@example.com`

#### Scenario: Notification on, both addresses empty
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = ""` and `oxshops.oxorderemail = ""`
- **THEN** the operator email is skipped, one `ERROR` log line names the misconfiguration, and the consumer receipt path completes normally

#### Scenario: Notification off
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 0`
- **THEN** no operator email is sent and no error is logged

### Requirement: Operator notice above the form via CMS snippet

The form page SHALL include the CMS snippet identified by `OXIDENT = 'o3_revocation_notice'` directly above the form, scoped to the consumer's current language. The snippet MUST render nothing visible (no whitespace artifact, no heading, no border) when it is missing, inactive, or empty.

#### Scenario: Snippet inactive
- **WHEN** the form page is rendered and the `o3_revocation_notice` snippet has `OXACTIVE = 0`
- **THEN** the page contains no notice block above the form

#### Scenario: Snippet active and populated
- **WHEN** the form page is rendered and the snippet has `OXACTIVE = 1` and `OXCONTENT` is non-empty for the current language
- **THEN** the snippet content is rendered above the form inside a `<div class="o3-revocation-notice">…</div>` wrapper

#### Scenario: Snippet absent for the current language
- **WHEN** the form page is rendered and no `oxcontents` row exists for the current language
- **THEN** no notice block is rendered and no error is shown to the consumer

### Requirement: Persistence schema and immutability

Each accepted submission SHALL be stored as a single row in the `o3revocation` table containing `OXID`, `OXSHOPID`, `OXLANG`, `OXNAME`, `OXORDERIDENT`, `OXEMAIL`, `OXFREETEXT` (nullable), `OXSUBMITTED`, and `OXTIMESTAMP`. The system MUST NOT persist the request IP or User-Agent. `OXSUBMITTED` MUST be written exactly once at insert and MUST NOT be updated by application code thereafter.

#### Scenario: Row written with all required columns
- **WHEN** a submission is confirmed
- **THEN** a row exists in `o3revocation` with all listed columns populated and `OXFREETEXT` either populated or `NULL`
- **AND** no `OXIP` or `OXUSERAGENT` column exists in the table schema

#### Scenario: OXSUBMITTED preserved across an update
- **WHEN** an admin action (e.g. "Resend confirmation") triggers an UPDATE on the submission row
- **THEN** the `OXSUBMITTED` value is unchanged after the update
- **AND** `OXTIMESTAMP` reflects the time of the update

### Requirement: Three admin configuration switches plus operator email

The admin shop configuration SHALL expose four `oxconfig` entries: `blShowRevocationForm` (bool), `blRevocationRequireLogin` (bool), `blRevocationNotifyOperator` (bool), and `sRevocationOperatorEmail` (string). Each switch label SHALL be resolved from a translation key under the `O3_REVOCATION_CONFIG_*` family.

#### Scenario: Admin form lists the four entries
- **WHEN** an admin opens the shop configuration page section that hosts the revocation feature
- **THEN** the page renders four form fields corresponding to the four config keys with labels translated for the admin's UI language

#### Scenario: Operator-email field accepts an empty value
- **WHEN** an admin saves the configuration with `sRevocationOperatorEmail = ""`
- **THEN** the save succeeds (the field is not mandatory)

### Requirement: Per-flag default behaviour for absent oxconfig rows

The application code SHALL treat absent `oxconfig` rows as: `blShowRevocationForm = false`, `blRevocationRequireLogin = false`, `blRevocationNotifyOperator = true`, `sRevocationOperatorEmail = ""`. Reading code MUST pass these defaults explicitly to `getConfigParam()` rather than relying on global system defaults.

#### Scenario: Upgrade with no rows seeded
- **WHEN** an upgraded shop has no `oxconfig` rows for any of the four feature keys
- **THEN** the storefront treats the form as off, login as not required, operator notification as on (default), and the operator-email recipient as empty (which makes notification fall back to `oxshops.oxorderemail`)

### Requirement: Fresh-install seeding of `blShowRevocationForm = 1`

A fresh shop install SHALL come up with `blShowRevocationForm = 1` already present in `oxconfig`, seeded by `source/Setup/Sql/initial_data.sql`. An upgrade MUST NOT seed this row through any path.

#### Scenario: Fresh install via the install wizard
- **WHEN** the install wizard runs `source/Setup/Sql/initial_data.sql`
- **THEN** `oxconfig` contains a row with `OXVARNAME = 'blShowRevocationForm'`, `OXVARTYPE = 'bool'`, `OXVARVALUE = '1'`

#### Scenario: Upgrade does not seed
- **WHEN** an existing shop runs only the Doctrine migrations (no install wizard)
- **THEN** no row is added to `oxconfig` for `blShowRevocationForm` by any code path of this change

### Requirement: Doctrine migration creates schema and seeds CMS snippet only

The Doctrine migration delivered by this change SHALL `CREATE TABLE IF NOT EXISTS o3revocation` and `INSERT IGNORE INTO oxcontents` one inactive empty row per active shop language with `OXIDENT = 'o3_revocation_notice'`. The migration MUST NOT touch `oxconfig`. Re-running the migration MUST be a no-op.

#### Scenario: Migration on a clean database
- **WHEN** the migration runs against a database that does not yet contain `o3revocation`
- **THEN** the table is created and one `oxcontents` row per active language is inserted

#### Scenario: Migration on a database that already ran it
- **WHEN** the migration runs against a database where the schema and the `oxcontents` rows already exist
- **THEN** neither the table nor the existing rows are modified

#### Scenario: Migration preserves operator-edited CMS snippet
- **WHEN** the operator has edited the `o3_revocation_notice` snippet content and the migration runs again
- **THEN** the operator's content is preserved unchanged

### Requirement: Translation engine routing for all user-facing strings

Every consumer-facing and admin-facing string introduced by this feature SHALL be resolved through the translation engine. No hardcoded German or English literal text MAY appear in templates, controllers, models, or mail templates. All translation keys for this feature SHALL share the prefix `O3_REVOCATION_`. Customer-facing email keys SHALL be scoped under `O3_REVOCATION_CUSTOMER_EMAIL_*` and operator email keys under `O3_REVOCATION_OPERATOR_EMAIL_*`.

#### Scenario: Storefront render with German active
- **WHEN** the form page renders with the visitor's language set to German
- **THEN** all visible text is sourced from the German `lang.php` and no untranslated literals appear in the HTML

#### Scenario: Storefront render with English active
- **WHEN** the form page renders with the visitor's language set to English
- **THEN** all visible text is sourced from the English `lang.php`

#### Scenario: Audit grep for hardcoded literals
- **WHEN** a reviewer greps the new templates and PHP files for non-translated user-facing prose
- **THEN** every match is wrapped in `{oxmultilang}` (templates) or `Registry::getLang()->translateString()` (PHP)

### Requirement: Template-presence gate at admin save time

When an admin action would result in the feature being on while a required template or translation is missing for any active shop language, the admin save SHALL be rejected in full (all-or-nothing). Three trigger sites apply: (a) saving shop configuration with `blShowRevocationForm = 1`; (b) activating a new shop language while `blShowRevocationForm = 1`; (c) switching the active storefront theme while `blShowRevocationForm = 1`. Each rejection MUST list every missing asset with a remediation hint and MUST re-render the form with all submitted values preserved.

#### Scenario: Activation save with all assets present
- **WHEN** an admin saves the shop configuration flipping `blShowRevocationForm` from `0` to `1` and `RevocationTemplateValidator` returns no missing assets
- **THEN** the save succeeds and the feature becomes active

#### Scenario: Activation save with one missing email template
- **WHEN** an admin saves the shop configuration flipping `blShowRevocationForm` from `0` to `1` and the active theme is missing `revocation_customer_confirmation.tpl` for one active language
- **THEN** the entire form save is rejected (no field on this form is committed)
- **AND** an admin-facing error message lists the missing template path with a remediation hint
- **AND** the form re-renders with every value the admin submitted pre-filled

#### Scenario: Language activation while feature is on with missing templates
- **WHEN** an admin activates a new shop language while `blShowRevocationForm = 1` and the active theme has no email templates for that language
- **THEN** the language activation is rejected and the language remains disabled
- **AND** other admin operations on the same form are unaffected

#### Scenario: Theme switch while feature is on with missing templates in the new theme
- **WHEN** an admin switches the active storefront theme while `blShowRevocationForm = 1` and the new theme is missing one of the required templates
- **THEN** the theme switch is rejected and the active theme remains as it was
- **AND** the admin sees a per-asset list with remediation hints

#### Scenario: Validator scope is the active theme only
- **WHEN** the validator runs against any of the three trigger sites
- **THEN** it inspects template paths only under the active theme directory and not under inactive themes

### Requirement: CLI healthcheck command

The system SHALL expose a feature-neutral CLI command `bin/oe-console o3:check-templates` that reports the same missing-asset list the admin save handler would produce, against the currently-active shop, theme, and active languages. The command SHALL exit with non-zero status when assets are missing and zero status when all required assets are present.

#### Scenario: Healthcheck on a fully-installed shop
- **WHEN** an operator runs `bin/oe-console o3:check-templates` and all required revocation assets are present
- **THEN** the command prints "OK" (or equivalent) and exits with status 0

#### Scenario: Healthcheck on a shop with missing templates
- **WHEN** an operator runs `bin/oe-console o3:check-templates` against a shop where the active theme is missing two language email templates
- **THEN** the command prints both missing paths with remediation hints and exits with non-zero status

### Requirement: Form input preservation on rejection

Any rejection path on any form introduced by this feature (admin or storefront) MUST re-render the same form template with every value the user submitted bound back into the form fields. The rejection handler MUST NOT issue an HTTP redirect that loses the POST data. Sensitive fields that should not round-trip MUST be explicitly cleared with an explanation.

#### Scenario: Storefront form rejection preserves typed values
- **WHEN** the storefront submit action rejects a submission for any reason (validation, anti-spam, token mismatch)
- **THEN** the form re-renders with all four field values typed by the consumer pre-filled

#### Scenario: Admin form rejection preserves typed values
- **WHEN** the admin save handler rejects the form due to the template-presence gate
- **THEN** the form re-renders with all submitted values pre-filled

### Requirement: Admin list view of submissions

The admin SHALL provide a list view of all `o3revocation` rows for the current shop, accessible under "Customer Info → Revocations" (or equivalent placement). The list SHALL show at minimum: submission ID, name, order identifier, email, submission timestamp, and a "delivery failed" indicator when the customer email send failed.

#### Scenario: List view renders with submissions
- **WHEN** an admin opens the revocations list with at least one row in `o3revocation`
- **THEN** the page renders one row per submission with the listed columns

#### Scenario: List view with no submissions
- **WHEN** an admin opens the revocations list and `o3revocation` is empty for the current shop
- **THEN** the page renders an empty-state translated message resolved from a key under `O3_REVOCATION_ADMIN_*`

### Requirement: Admin detail view with manual resend

The admin SHALL provide a detail view per submission containing the persisted values, the submission timestamp, and a "Resend confirmation" button. Clicking the button MUST re-attempt only the customer confirmation email and update `OXTIMESTAMP` (housekeeping) without altering `OXSUBMITTED`.

#### Scenario: Resend on a delivery-failed row
- **WHEN** an admin clicks "Resend confirmation" on a row flagged "delivery failed"
- **THEN** the customer email is re-attempted
- **AND** `OXSUBMITTED` is unchanged after the action
- **AND** `OXTIMESTAMP` is updated to the time of the resend

#### Scenario: Resend success clears the failure flag
- **WHEN** the resend succeeds on a previously-failed row
- **THEN** the "delivery failed" flag is cleared on subsequent renders of the detail view

### Requirement: No automatic deletion of submissions

The system MUST NOT include any scheduled job, cron task, time-based purge, or admin auto-delete configuration that removes existing rows from `o3revocation`. Deletion of submissions MUST be performed manually by the operator or by tooling external to this feature.

#### Scenario: No code path deletes existing submissions
- **WHEN** the codebase is searched for `DELETE FROM o3revocation` or equivalent
- **THEN** the only matches are: the migration's `down()` (rollback only), the admin manual-delete button (admin-initiated, single-row), and tests

#### Scenario: No scheduled job ships
- **WHEN** the codebase is searched for cron or scheduler registration touching `o3revocation`
- **THEN** no such registration exists

### Requirement: Logging at five defined points

The runtime SHALL emit log lines at five points using the project's logging conventions (`__METHOD__ . ' - '` prefix, full English sentence ending in `.`, ISO-8601 microsecond timestamp via Monolog):
- `INFO` on form render (debug-level acceptable)
- `INFO` on submit-step validation pass
- `NOTICE` on confirm-step persist (with submission ID)
- `ERROR` on email send failure (customer or operator) with the underlying error
- `WARNING` on anti-spam reject or session-token mismatch

No log line MUST embed personal data (name, email, free-text) in the message body; structured context goes through the data array parameter.

#### Scenario: Successful submit produces the expected log sequence
- **WHEN** a consumer completes a successful form → confirm flow
- **THEN** the log contains, in order, an `INFO` for form render, an `INFO` for validation pass, and a `NOTICE` for persist
- **AND** none of these lines includes the consumer's name, email, or free-text in the message body

#### Scenario: Email failure produces an ERROR log line
- **WHEN** the customer email send returns failure
- **THEN** one `ERROR` log line names the submission ID and the underlying error message

### Requirement: Storefront templates portable across themes

Storefront template files added by this feature SHALL be authored portably so that copying them from `wave-theme` to `o3-Theme` is mechanical. Template files MUST NOT bake in wave-specific CSS class names that are returned from PHP, and controllers MUST NOT branch on `Registry::getConfig()->getActiveTheme()` for revocation-specific behaviour.

#### Scenario: Audit for theme branching in controllers
- **WHEN** a reviewer greps the revocation controller(s) for `getActiveTheme()`
- **THEN** no match is found

#### Scenario: Template file paths mirror across themes
- **WHEN** the o3-Theme port is performed by copying template files from wave-theme
- **THEN** the destination paths and file names are identical to the source paths and file names

### Requirement: PHP version and strict typing for new code

Every new PHP file introduced by this feature SHALL declare `declare(strict_types=1);` and provide parameter, return, and property type declarations. The new code MUST run on PHP 7.4 through 8.x. PHP-8-only language features (union types, `mixed`, constructor property promotion, named arguments, `match`, nullsafe `?->`, `readonly`, enums, first-class callable syntax, intersection types, standalone `true`/`false`/`null` types) MUST NOT appear in new code. Methods overriding inherited methods MUST match the parent signature exactly even when the parent uses untyped parameters.

#### Scenario: New file declares strict types
- **WHEN** a new file is added under `source/Application/Controller/`, `source/Application/Model/`, `source/Internal/...`, or `source/migration/data/`
- **THEN** the file's first non-comment, non-namespace line is `declare(strict_types=1);`

#### Scenario: PHP 7.4 syntax only
- **WHEN** the new code is parsed by `php -l` on a PHP 7.4 binary
- **THEN** parsing succeeds with no syntax errors

#### Scenario: Inherited method override matches parent signature
- **WHEN** a new admin controller class extends an inherited core class and overrides a parent method that uses untyped parameters
- **THEN** the override declares the same untyped parameters (no stricter types added)
