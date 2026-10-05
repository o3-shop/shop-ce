# electronic-revocation Specification

## Purpose
TBD - created by syncing change add-electronic-revocation. Update Purpose after archive.

## Requirements

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

**On the initial GET, every input field MUST render empty** — regardless of login state. The system MUST NOT pre-fill *name* from `oxuser__oxfname`/`oxlname`, MUST NOT pre-fill *email* from `oxuser__oxusername`, and MUST NOT pre-fill *order identification* from any order the logged-in user has placed. Reasons: (a) § 356a Abs. 2 frames the mandatory inputs as values the consumer provides on the form — pre-filling implicitly transfers data from the user record into the submission, blurring what was actually typed; (b) the logged-in user may be revoking on behalf of someone else (household member, etc.) and a different name/email may be intended; (c) blank-by-default is the simpler, more predictable rule. The only case in which the form re-renders with previously-typed values is on rejection — covered by the form-input-preservation requirement.

#### Scenario: Initial GET — anonymous visitor, all fields empty
- **WHEN** an anonymous visitor with access loads `?cl=revocation` for the first time
- **THEN** the form renders with fields *name*, *order identification*, *email address*, *optional free-text* and a submit button labelled from `O3_REVOCATION_CONFIRM_BUTTON` (default German label *"Widerruf bestätigen"* — the click on this button is the legally-effective declaration; there is no separate confirmation step)
- **AND** every input field is rendered with an empty value
- **AND** a hidden `stoken` input is present
- **AND** the operator notice from CMS snippet `o3_revocation_notice` is included above the form

#### Scenario: Initial GET — authenticated visitor, fields still empty (no pre-fill from profile)
- **WHEN** an authenticated visitor loads `?cl=revocation` for the first time
- **THEN** the form renders identically to the anonymous case
- **AND** the *name* field is empty even though the user's `oxuser__oxfname`/`oxlname` is known
- **AND** the *email address* field is empty even though the user's `oxuser__oxusername` is known
- **AND** the *order identification* field is empty even though the user has placed orders
- **AND** the rendered HTML must not contain the user's profile data anywhere in the form region

#### Scenario: Re-load after rejection — values preserved
- **WHEN** a previous submission was rejected (validation, anti-spam, token mismatch, template gate) and the form re-renders
- **THEN** every field the user typed into is pre-filled with the value they submitted, per the form-input-preservation requirement

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

The form SHALL present exactly three mandatory fields — *full name*, *order/contract identification*, *electronic communication channel (email)* — and one optional *free-text* field. No additional field MAY be marked as required. The optional free-text field MUST NOT be enforced as mandatory under any configuration. Server-side validation SHALL enforce: each mandatory field is non-empty after trimming whitespace; the *email address* field additionally is syntactically a valid email (validated via `filter_var($value, FILTER_VALIDATE_EMAIL)`). The system MUST NOT validate the email against any other data source (no MX-record check, no comparison against existing customer or order emails).

#### Scenario: Submit with all mandatory fields filled
- **WHEN** the visitor submits the form with all three mandatory fields populated and the free-text field empty
- **THEN** validation passes and the flow advances to the confirmation step

#### Scenario: Submit with one mandatory field empty
- **WHEN** the visitor submits the form with the *email address* field left blank (or whitespace-only)
- **THEN** validation fails and the form re-renders with the previously-typed values for *name* and *order identification* preserved
- **AND** the *email address* field shows an error styled around it with message resolved from `O3_REVOCATION_VALIDATION_REQUIRED`

#### Scenario: Submit with syntactically invalid email
- **WHEN** the visitor submits the form with the *email address* field set to a value that fails `FILTER_VALIDATE_EMAIL` (e.g. `"foo"`, `"foo@"`, `"@bar.com"`, `"foo@@bar.com"`)
- **THEN** validation fails and the form re-renders with the previously-typed values for *name*, *order identification*, *email address*, and *free-text* all preserved (so the user only fixes the email)
- **AND** the *email address* field shows a format error styled around it with message resolved from `O3_REVOCATION_VALIDATION_EMAIL_FORMAT`
- **AND** the field MUST NOT silently transform the value (no auto-lowercase, no trimming-then-saving) before showing it back to the user — what they typed is what they see, plus the error

#### Scenario: Submit with free-text omitted
- **WHEN** the visitor submits the form with all mandatory fields filled and the free-text field empty
- **THEN** the system MUST NOT treat the empty free-text as a validation error

### Requirement: Form markup contract

The rendered form HTML SHALL conform to the following markup contract. The contract specifies *behavioural* HTML — input types, accessibility hooks, structural elements — that affects how mobile keyboards, password managers, screen readers, and HTML5 client-side validation behave. It deliberately does **not** specify pixel-level visual design (column layout, colours, spacing, button shape, typography); those remain the theme's responsibility.

- **Form element:** the four input fields and the submit button SHALL be wrapped in a single `<form method="post" action="…?cl=revocation&fnc=submit">` element. There MUST NOT be multiple `<form>` elements, nested forms, or AJAX-only submission paths that bypass the standard POST.
- **Name field:** `<input type="text" required name="…">` with a unique `id`. Server still validates non-empty after trim — the `required` attribute is an HTML5 client-side hint only.
- **Order identification field:** same as Name — `<input type="text" required name="…">`.
- **Email field:** `<input type="email" required name="…">`. The `type="email"` is a hint that triggers email-style on-screen keyboards on mobile and HTML5-level format validation in the browser. The server still independently runs `FILTER_VALIDATE_EMAIL`; the client hint is convenience, not security.
- **Free-text field:** `<textarea name="…"></textarea>` — multi-line, NOT marked `required`, NOT decorated with a required marker. Empty submission is valid for this field.
- **Label binding:** every input and the textarea SHALL have a `<label for="…">` element whose `for` attribute matches the input's `id`. The label MUST contain the human-readable field name resolved from the relevant `O3_REVOCATION_FIELD_*_LABEL` translation key, not a separate non-`<label>` text node. Implicit-association (`<label><input></label>`) is acceptable as an alternative to explicit `for`.
- **Required-field signalling:** the three mandatory fields SHALL carry both a *visual* required marker (theme's choice — typical conventions: a trailing red asterisk, "(required)" text, or an outline style) AND `aria-required="true"` on the input element itself. Screen readers and visual users alike must know which fields are mandatory.
- **Error association:** when a server-side validation error is being shown for a field, the field's input element SHALL have `aria-describedby="<error-element-id>"` pointing at the DOM element that contains the error message. Screen-reader users hear the error when the field receives focus.
- **Submit button:** exactly **one** `<button type="submit">` (or `<input type="submit">`) inside the form, labelled from `O3_REVOCATION_CONFIRM_BUTTON` ("Widerruf bestätigen"). There MUST NOT be a "preview" button, a "save draft" button, or any other secondary action button on the form.

#### Scenario: Single form element with POST action
- **WHEN** the form page is rendered
- **THEN** the page contains exactly one `<form>` whose `method` attribute is (case-insensitively) `post` and whose `action` attribute resolves to `?cl=revocation&fnc=submit`

#### Scenario: Field types match the contract
- **WHEN** the form page is rendered
- **THEN** the *name* and *order identification* inputs carry `type="text" required`
- **AND** the *email* input carries `type="email" required`
- **AND** the *free-text* field is a `<textarea>` element with no `required` attribute

#### Scenario: Every input has an associated label
- **WHEN** the form page is rendered
- **THEN** for every `<input>` and `<textarea>` representing a form field, either an explicit `<label for="…">` whose `for` matches the input's `id` exists, or the input is wrapped in an enclosing `<label>` element

#### Scenario: Required fields signal both visually and to assistive tech
- **WHEN** the form page is rendered
- **THEN** the three mandatory inputs each carry `aria-required="true"`
- **AND** each mandatory field is visually marked as required (theme-specific — verified by inspecting the rendered DOM around each mandatory field for the theme's required-marker convention)
- **AND** the *free-text* field carries neither `aria-required` nor a visual required marker

#### Scenario: Validation error is associated to its field
- **WHEN** the server rejects a submission for a field-level reason (e.g. invalid email format) and the form re-renders
- **THEN** the offending input element carries `aria-describedby="<id>"` whose value matches the `id` of the element rendering the error message text

#### Scenario: Exactly one submit button
- **WHEN** the form page is rendered
- **THEN** the form contains exactly one `<button type="submit">` (or `<input type="submit">`) and no other action button

### Requirement: No matching of submitted email against order data

The system MUST NOT validate, match, or compare the submitted email address against `oxorder.OXBILLEMAIL`, `oxuser.OXUSERNAME`, or any other stored email field. The system MUST NOT validate or match the submitted order identifier against `oxorder.OXORDERNR` or any other stored order field. The submission MUST be accepted regardless of whether the typed values exist in the shop's records.

#### Scenario: Submitted email does not match any order email
- **WHEN** the visitor submits the form with an `OXEMAIL` value that has no matching row in `oxorder.OXBILLEMAIL`
- **THEN** the submission is accepted and the flow advances to the confirmation step

#### Scenario: Submitted order identifier does not match any order
- **WHEN** the visitor submits the form with an `OXORDERIDENT` value that has no matching row in `oxorder.OXORDERNR`
- **THEN** the submission is accepted and the flow advances to the confirmation step

### Requirement: Session challenge token required on the submit action

The submit action (`?cl=revocation&fnc=submit`) MUST verify the session challenge token (`stoken`) before processing input. Requests with an absent or mismatched token MUST be rejected without persisting any data and without emitting any email.

#### Scenario: Submit without token
- **WHEN** a POST arrives at `?cl=revocation&fnc=submit` with no `stoken` parameter
- **THEN** the action redirects to `?cl=revocation` with a translated info message resolved from `O3_REVOCATION_VALIDATION_SESSION_EXPIRED`
- **AND** no row is written to `o3revocation`
- **AND** no email is sent
- **AND** one `WARNING` log line is emitted

#### Scenario: Submit with mismatched token
- **WHEN** a POST arrives at `?cl=revocation&fnc=submit` with a `stoken` value that does not match the session token
- **THEN** the same rejection behaviour applies as for the absent-token case

### Requirement: Anti-spam verification with two-mode IP rate-limit default

After the session challenge token check passes, the submit and confirm actions MUST call the configured `RevocationAntiSpamService::verify()` and reject the request if it returns `false`. The default service implementation `NoopAntiSpamService` SHALL enforce two independent IP-based counters in a transient cache store:
- **Failed-submission counter** — at most 3 failed attempts per IP within a 60-second sliding window. "Failed" means the request reached the controller and was rejected at any step after `verify()` (validation error, token mismatch, etc.). Each rejection increments the counter via `recordFailure()`.
- **Successful-submission lockout** — when a submission is successfully persisted, the IP is locked out for 300 seconds (5 minutes). The controller MUST call `recordSuccess()` immediately after a successful persist; this sets a counter that causes any subsequent `verify()` from the same IP to return `false` for the next 300 seconds, regardless of the failed counter.

Rejection responses MUST display a generic translated error resolved from `O3_REVOCATION_VALIDATION_SPAM` and MUST NOT reveal which counter triggered the rejection.

#### Scenario: Three failed attempts within 60 s — all reach the controller
- **WHEN** a single IP submits three forms within 60 seconds and each one is rejected by validation (e.g. typo in the email field, fixed and re-typoed)
- **THEN** all three submissions reach the controller and produce validation errors normally (the rate limit does not kick in within the 3-attempt window)
- **AND** the user can see and fix each validation error normally per the form-input-preservation rule

#### Scenario: Fourth failed attempt within 60 s is rate-limited
- **WHEN** the same IP submits a fourth form within the same 60-second window after three rejections
- **THEN** `verify()` returns `false` and the form re-renders with the submitted values preserved
- **AND** the displayed error is the generic `O3_REVOCATION_VALIDATION_SPAM` message
- **AND** no row is written to `o3revocation`
- **AND** one `WARNING` log line is emitted

#### Scenario: Successful submission triggers 5-minute lockout
- **WHEN** an IP successfully completes the form → confirm flow and a row is persisted to `o3revocation`
- **THEN** `recordSuccess()` is called and the IP is locked out
- **AND** any subsequent submit from that IP within the next 300 seconds is rejected with the generic `O3_REVOCATION_VALIDATION_SPAM` message regardless of how many failed attempts the IP had used

#### Scenario: Lockout expires after 5 minutes
- **WHEN** an IP that was locked out by a successful submission attempts to submit again more than 300 seconds later
- **THEN** `verify()` allows the attempt and the flow proceeds normally

#### Scenario: Failed counter does not block legitimate retry path
- **WHEN** an IP submits the form, hits a server-side validation error, fixes the field, and resubmits successfully — all within 30 seconds
- **THEN** the first submission is rejected by validation but `verify()` allowed it through
- **AND** the second submission succeeds and triggers the 5-minute lockout via `recordSuccess()`

#### Scenario: Anti-spam service replacement
- **WHEN** the DI container rebinds `RevocationAntiSpamService` to a different implementation (e.g. `AltchaAntiSpamService`)
- **THEN** the controller code MUST NOT need to change
- **AND** the new implementation receives the same `verify()` / `recordSuccess()` / `recordFailure()` contract

### Requirement: Form submit persists and triggers emails (step 2)

The form's submit button is labelled from `O3_REVOCATION_CONFIRM_BUTTON` ("Widerruf bestätigen") — clicking it IS the legally-effective declaration per § 356a Abs. 3. There is no separate "preview-then-confirm" view. When the visitor submits the form with a valid session challenge token and the data passes validation and the anti-spam check, the system SHALL persist the submission to the `o3revocation` table first, then attempt to send the customer confirmation email and the operator notification email, then issue a 303 redirect to the receipt page. The system MUST NOT carry inter-request state between any two steps of this flow (no session stash, no DB DRAFT row, no hidden re-post page).

#### Scenario: Successful submit — full flow in one click
- **WHEN** the visitor submits `?cl=revocation&fnc=submit` with a valid `stoken`, valid field values, and the anti-spam check passes
- **THEN** a new row is inserted into `o3revocation` with `OXSUBMITTED` set to the current timestamp
- **AND** `sendRevocationEmailToCustomer($submission)` is called
- **AND** `sendRevocationEmailToOperator($submission)` is called when `blRevocationNotifyOperator = 1`
- **AND** `recordSuccess()` is called on the anti-spam service to start the 5-minute lockout
- **AND** the response is HTTP 303 to `?cl=revocation&fnc=receipt`

#### Scenario: No inter-step state to expire
- **WHEN** the architecture is reviewed for "session timed out between form and confirm" failure modes
- **THEN** none exists — the form-to-persist transition is a single POST with no intermediate state to expire

### Requirement: Persist-first ordering for legal robustness

The submission row MUST be persisted before any email send is attempted. Failure of any email send MUST NOT cause the persisted row to be rolled back or deleted. The legally-meaningful "time of receipt" is the persistence timestamp, independent of email delivery success.

#### Scenario: Customer email fails after persist
- **WHEN** persistence succeeds but the customer email send returns failure
- **THEN** the `o3revocation` row remains in place
- **AND** the admin detail view displays a "send failed" flag for that row (we can only detect that the synchronous send call returned an error — we cannot detect downstream delivery failure such as bounces, spam classification, or non-delivery-report timeouts)
- **AND** one `ERROR` log line names the submission ID and the underlying error
- **AND** the consumer-side flow still completes with a receipt page

#### Scenario: Operator email fails after persist
- **WHEN** persistence succeeds, the customer email succeeds, and the operator email send returns failure
- **THEN** the row remains in place, the customer receives their receipt, and one `ERROR` log line records the operator-email failure

### Requirement: Receipt page (step 3)

After a successful submit, the system SHALL render a receipt page in response to the GET request at `?cl=revocation&fnc=receipt`. The page MUST acknowledge the submission and indicate that a confirmation email has been (or is being) sent. The page MUST NOT require the visitor to remain in any particular session state.

#### Scenario: GET receipt after submit
- **WHEN** the visitor follows the 303 redirect from the submit action
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

When `blRevocationNotifyOperator = 1`, the system SHALL send an operator-facing email per submission via Smarty templates `tpl/email/{html,plain}/revocation_operator_notification.tpl`. The email MUST be rendered in the shop's default language.

The runtime recipient resolution SHALL follow this two-step fallback chain:
1. If `sRevocationOperatorEmail` is non-empty and syntactically valid, send to that address.
2. Else if `oxshops.oxorderemail` is non-empty, send to that address as a fallback (for fresh shops or upgraded shops where the operator has not yet opened the revocation config form). Emit one `NOTICE` log line per send so the operator can spot the implicit fallback in logs and configure their own address.
3. Else skip the operator email entirely, emit one `ERROR` log line, and let the consumer-side flow complete normally.

The runtime fallback in step 2 is **asymmetric** with the admin-save validation: read-time is lenient (fresh installs work out of the box), save-time is strict (once the operator opens the form, they must consciously specify the address — see the cross-field validation in the configuration-switches requirement). This gives operators a working default without ever silently accepting an incomplete configuration once they've shown intent by editing.

#### Scenario: Notification on, dedicated recipient configured
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = "ops@example.com"`
- **THEN** the operator email is sent to `ops@example.com` and no fallback log line is emitted

#### Scenario: Notification on, recipient empty, fallback to order email
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = ""` and `oxshops.oxorderemail = "shop@example.com"`
- **THEN** the operator email is sent to `shop@example.com`
- **AND** one `NOTICE` log line names the implicit fallback with a hint to configure `sRevocationOperatorEmail` for an explicit recipient
- **AND** the consumer-side flow completes normally

#### Scenario: Notification on, both addresses empty
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = ""` and `oxshops.oxorderemail = ""`
- **THEN** the operator email is skipped
- **AND** one `ERROR` log line names the misconfiguration with a remediation hint ("set `sRevocationOperatorEmail` in admin or disable `blRevocationNotifyOperator`")
- **AND** the consumer-side flow completes normally

#### Scenario: Notification off
- **WHEN** a submission is persisted with `blRevocationNotifyOperator = 0`
- **THEN** no operator email is sent and no log line is emitted

### Requirement: Operator notice above the form via CMS snippet

The form page SHALL include the CMS snippet identified by `OXLOADID = 'o3_revocation_notice'` directly above the form, scoped to the consumer's current language. The snippet MUST render nothing visible (no whitespace artifact, no heading, no border) when it is missing, inactive, or empty for the current language.

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

`sRevocationOperatorEmail` is **conditionally mandatory at admin save time**: when `blRevocationNotifyOperator = 1`, the field MUST be non-empty AND syntactically valid (`filter_var(..., FILTER_VALIDATE_EMAIL)`). When `blRevocationNotifyOperator = 0`, the field is optional and ignored. Cross-field validation runs on every admin save of this form: if both conditions ("notify on" and "valid email") are not met simultaneously, the entire save is rejected per the all-or-nothing rule (D11).

This save-time strictness is **asymmetric** with the runtime behaviour: at runtime the operator-notification path falls back to `oxshops.oxorderemail` when `sRevocationOperatorEmail` is empty (see the operator-notification-email requirement). Why the asymmetry: a fresh-install or upgraded shop where the operator has not yet opened the revocation config form should still receive notifications somewhere sensible (the order email is always set on a functioning shop). But once the operator opens the form and clicks save, the implicit fallback should not silently "fix" an incomplete configuration — the operator is given the opportunity to consciously confirm the recipient.

#### Scenario: Admin form lists the four entries
- **WHEN** an admin opens the shop configuration page section that hosts the revocation feature
- **THEN** the page renders four form fields corresponding to the four config keys with labels translated for the admin's UI language

#### Scenario: Save rejected — notify on, email empty
- **WHEN** an admin saves the configuration with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = ""`
- **THEN** the entire form save is rejected (no field on this form is committed)
- **AND** the form re-renders with every submitted value pre-filled (per the form-input-preservation rule)
- **AND** an error styled around the email field shows a translated message resolved from `O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED`

#### Scenario: Save rejected — notify on, email syntactically invalid
- **WHEN** an admin saves the configuration with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = "ops"` (or any value that fails `FILTER_VALIDATE_EMAIL`)
- **THEN** the entire form save is rejected
- **AND** an error styled around the email field shows a translated message resolved from `O3_REVOCATION_VALIDATION_EMAIL_FORMAT`

#### Scenario: Save accepted — notify on, valid email
- **WHEN** an admin saves the configuration with `blRevocationNotifyOperator = 1` and `sRevocationOperatorEmail = "ops@example.com"`
- **THEN** the save succeeds

#### Scenario: Save accepted — notify off, email field ignored
- **WHEN** an admin saves the configuration with `blRevocationNotifyOperator = 0` and `sRevocationOperatorEmail = ""` (or any value, valid or not)
- **THEN** the save succeeds and the email value is stored verbatim (so re-enabling notify later doesn't lose what was typed)

### Requirement: Per-flag default behaviour for absent oxconfig rows

The application code SHALL treat absent `oxconfig` rows as: `blShowRevocationForm = false`, `blRevocationRequireLogin = false`, `blRevocationNotifyOperator = true`, `sRevocationOperatorEmail = ""`. Reading code MUST pass these defaults explicitly to `getConfigParam()` rather than relying on global system defaults.

The `blRevocationNotifyOperator = true` default is functionally dormant while the form is off (no submissions can happen, so no email is ever sent). It exists to make the first-activation path safe: when the operator later flips `blShowRevocationForm` from `0` to `1`, the save-time cross-field validation sees `notify=on` + `email=""` and rejects the save until the operator provides a valid email — guiding them into the fully-configured end-state. If the default were `false` instead, the activation would silently succeed without notifications and submissions could pile up unseen.

#### Scenario: Upgrade with no rows seeded — feature inert
- **WHEN** an upgraded shop has no `oxconfig` rows for any of the four feature keys
- **THEN** the storefront treats the form as off (no footer link, no `?cl=revocation` page, no submissions accepted)
- **AND** the values of `blRevocationNotifyOperator` and `sRevocationOperatorEmail` have no observable effect because no submission can be created in this state

#### Scenario: Operator opens admin and activates the form (first activation path)
- **WHEN** the operator on a previously-unseeded shop opens the admin config form (which renders `blRevocationNotifyOperator` as checked from its `true` default and `sRevocationOperatorEmail` as empty), ticks `blShowRevocationForm`, leaves `blRevocationNotifyOperator` checked, and saves with the email field still empty
- **THEN** the save is rejected by the cross-field validation
- **AND** the form re-renders with all submitted values pre-filled and an error styled around the email field
- **AND** the operator types a valid email and saves, the save succeeds, and the feature goes live with notifications on

### Requirement: Fresh-install seeding of all four feature config rows

A fresh shop install SHALL come up with all four feature `oxconfig` rows already present, seeded by `source/Setup/Sql/initial_data.sql`. The seeded values MUST match the canonical absent-row defaults from the per-flag-defaults requirement, so seeding is purely for explicitness in the database (state is self-documenting via `SELECT * FROM oxconfig WHERE OXVARNAME LIKE '%Revocation%'`) without changing any runtime behaviour. An upgrade MUST NOT seed any of these rows through any path; upgrades rely on the absent-row code defaults.

| Row | `OXVARTYPE` | `OXVARVALUE` |
|---|---|---|
| `blShowRevocationForm` | `bool` | `1` |
| `blRevocationRequireLogin` | `bool` | `0` |
| `blRevocationNotifyOperator` | `bool` | `1` |
| `sRevocationOperatorEmail` | `str` | `""` (empty string — runtime falls back to `oxshops.oxorderemail`) |

#### Scenario: Fresh install via the install wizard
- **WHEN** the install wizard runs `source/Setup/Sql/initial_data.sql`
- **THEN** `oxconfig` contains four rows for the feature with `OXVARNAME` and values matching the table above

#### Scenario: Upgrade does not seed
- **WHEN** an existing shop runs only the Doctrine migrations (no install wizard)
- **THEN** no row is added to `oxconfig` for any of the four feature keys by any code path of this change

#### Scenario: Seeded values match the absent-row code defaults
- **WHEN** the application reads any of the four config keys via `getConfigParam($key, $default)` on a fresh install
- **THEN** the value returned is identical to the value that would be returned on an upgrade where no row exists (because the seeded value matches the code default verbatim)

### Requirement: Doctrine migration creates schema and seeds CMS snippet only

The Doctrine migration delivered by this change SHALL `CREATE TABLE IF NOT EXISTS o3revocation` and `INSERT IGNORE INTO oxcontents` one row per shop with `OXLOADID = 'o3_revocation_notice'`, `OXSNIPPET = 1`, `OXTYPE = 0`, and every per-language slot inactive and empty (`OXACTIVE_* = 0`, `OXTITLE_* = ''`, `OXCONTENT_* = ''`). The migration MUST NOT touch `oxconfig`. Re-running the migration MUST be a no-op.

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

The admin SHALL provide a list view of all `o3revocation` rows for the current shop, accessible under "Customer Info → Revocations" (or equivalent placement). The list SHALL show at minimum: submission ID, name, order identifier, email, submission timestamp, and a **"send failed"** indicator when the synchronous customer email send returned an error. The indicator MUST NOT claim "delivery failed" — we cannot detect downstream delivery state (bounces, spam folder, non-delivery-report timeouts); we only know whether the local send call succeeded.

#### Scenario: List view renders with submissions
- **WHEN** an admin opens the revocations list with at least one row in `o3revocation`
- **THEN** the page renders one row per submission with the listed columns

#### Scenario: List view with no submissions
- **WHEN** an admin opens the revocations list and `o3revocation` is empty for the current shop
- **THEN** the page renders an empty-state translated message resolved from a key under `O3_REVOCATION_ADMIN_*`

### Requirement: Admin detail view with manual resend

The admin SHALL provide a detail view per submission containing the persisted values, the submission timestamp, and a "Resend confirmation" button. Clicking the button MUST re-attempt only the customer confirmation email and update `OXTIMESTAMP` (housekeeping) without altering `OXSUBMITTED`.

#### Scenario: Resend on a send-failed row
- **WHEN** an admin clicks "Resend confirmation" on a row flagged "send failed"
- **THEN** the customer email is re-attempted
- **AND** `OXSUBMITTED` is unchanged after the action
- **AND** `OXTIMESTAMP` is updated to the time of the resend

#### Scenario: Resend success clears the failure flag
- **WHEN** the resend succeeds on a previously-failed row (i.e. the synchronous send call returned without error)
- **THEN** the "send failed" flag is cleared on subsequent renders of the detail view
- **AND** the admin sees no claim about delivery — only that the most recent send attempt succeeded synchronously

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
- `INFO` on validation pass inside the submit handler
- `NOTICE` on persist (after the `o3revocation` row is written; includes the submission's `OXID`)
- `ERROR` on email send failure (customer or operator); includes the submission's `OXID` and the underlying error
- `WARNING` on anti-spam reject or session-token mismatch

**Personal-data rule:**
- The message body MUST NOT embed personal data: no consumer name, no email address, no free-text content, no other field a person would recognise as "their data".
- The message body MAY (and is encouraged to) embed **opaque identifiers** for log correlation: the submission's `OXID` (`o3revocation.OXID`), and where a logged-in user is involved, the user's `OXID` (`oxuser.OXID`). These are synthetic identifiers that don't reveal anything *about* the person to someone reading the log; they exist precisely to let an operator find "the row this incident is about" via `SELECT * FROM o3revocation WHERE OXID = '...'`.
- Structured context (HTTP request headers, response codes, internal state machine values, exception details) goes through the data-array parameter, not the message body — same convention used elsewhere in the codebase.

#### Scenario: Successful submit produces the expected log sequence
- **WHEN** a consumer completes a successful form → submit flow
- **THEN** the log contains, in order, an `INFO` for form render, an `INFO` for validation pass, and a `NOTICE` for persist
- **AND** the `NOTICE` line names the submission's `OXID`
- **AND** none of these lines includes the consumer's name, email, or free-text in the message body

#### Scenario: Email failure produces an ERROR log line
- **WHEN** the customer email send returns failure
- **THEN** one `ERROR` log line names the submission's `OXID` and the underlying error message
- **AND** the message body MUST NOT include the consumer's email address (the `OXID` is the correlation handle to find the row in the DB if needed)

#### Scenario: Logged-in submitter — user OXID also logged
- **WHEN** an authenticated visitor completes a successful submit
- **THEN** the `NOTICE` persist log line names the submission's `OXID` AND the visitor's `oxuser.OXID`
- **AND** still no personal data appears in the message body

### Requirement: Storefront templates portable across themes

Storefront template files added by this feature SHALL be authored portably so that copying them from `wave-theme` to `o3-Theme` is mechanical. Template files MUST NOT bake in wave-specific CSS class names that are returned from PHP, and **controllers MUST NOT branch on `Registry::getConfig()->getActiveTheme()` for revocation-specific behaviour**.

Why controllers must stay theme-agnostic — five compounding reasons, all of which apply:

1. **Separation of concerns.** Controllers carry business logic and HTTP-handling responsibility; themes carry visual presentation. A `getActiveTheme()` check inside a controller mixes the two layers and makes a presentation concern leak into business code.
2. **The wave → o3-theme cutover is imminent** (planned before 2026-05-01, see shared memory `project_o3-theme-migration.md`). If controllers branch on theme, porting the feature to `o3-theme` becomes a *cross-repo* change (shop-ce + o3-Theme) instead of a single theme-repo PR. The cutover is supposed to be a mechanical theme port; controller edits during a theme cutover are exactly the friction we're trying to avoid.
3. **Future themes scale unmaintainably.** Every additional theme that ships would need its own branch in the controller (`if ($theme == 'wave') ... elseif ($theme == 'o3-theme') ... elseif ($theme == 'partner-x') ...`), producing a switch statement that grows linearly with the theme catalogue. Themes are supposed to be drop-in replacements that don't require core changes.
4. **Hidden coupling.** A reviewer reading `RevocationController.php` does not expect business logic to vary by theme. A `getActiveTheme()` check is a non-obvious surprise that bites at debug time ("works on wave, broken on o3-theme — but I only changed the theme!"). Keeping controllers theme-blind makes the controller's behaviour visible from the controller alone.
5. **Testability.** Theme-branching controllers force every test to set up theme detection or mock it, multiplying setup permutations (`testSubmit_OnWave`, `testSubmit_OnO3Theme`, …). Theme-agnostic controllers test once and the result holds across themes.

What goes where instead: anything that needs to look different across themes belongs in the **templates** themselves (the theme repo has full control over its own Smarty files); anything that needs to vary based on the active *shop* (currency, language, configuration) belongs in the **controller** as before but driven by the shop config, not the theme.

#### Scenario: Audit for theme branching in controllers
- **WHEN** a reviewer greps `source/Application/Controller/Revocation*.php` and `source/Application/Controller/Admin/Revocation*.php` for `getActiveTheme()`
- **THEN** zero matches are found
- **AND** if any future PR introduces a `getActiveTheme()` call into a revocation controller, the review explicitly asks the author to move the variation into the templates instead

#### Scenario: Template file paths mirror across themes
- **WHEN** the o3-Theme port is performed by copying template files from wave-theme
- **THEN** the destination paths and file names are identical to the source paths and file names
- **AND** no controller code in shop-ce needs to be modified as part of the port

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
