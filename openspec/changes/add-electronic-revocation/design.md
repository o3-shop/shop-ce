## Context

Issue #99 mandates a § 356a BGB-compliant electronic revocation flow live by 2026-06-19 across all O3-Shop B2C installations. The proposal frames the *what* and *why*; this document settles the *how* — controller shape, state handling between the form and the confirmation step, DB schema, mail dispatch, multi-repo template work, fresh-vs-upgrade defaults, and the spam-protection extension point that #113 will plug into.

This is a cross-cutting change spanning shop-ce (controllers, models, migration, admin templates, lang keys) and the wave-theme repo (storefront templates + storefront lang keys), with an o3-Theme follow-up before 2026-05-01. It introduces a new public route, a new admin module, a new DB table, a new CMS snippet ident, and four new `oxconfig` rows. There is a hard prerequisite (#114, the entrypoint git-clone migration) and a parallel out-of-scope follow-up (#113, Altcha CAPTCHA).

Stakeholders: shop operators (legal compliance, low-friction admin), consumers (legally protected, accessible flow), translators (per-language strings via existing engine), reviewers across three repositories.

## Goals / Non-Goals

**Goals**
- Statutorily-correct flow: footer entry → form (3 mandatory + 1 optional field) → confirmation step → durable-medium receipt to consumer; submission persisted; admin can see and act on it.
- Theme-portable templates so the wave→o3-theme cutover is mechanical.
- Zero hardcoded user-facing strings; everything routes through the translation engine under the `O3_REVOCATION_*` prefix.
- Operator notice above the form via existing CMS plumbing (no bespoke admin UI).
- Fresh-install defaults that are legally safe; upgrade defaults that respect existing operator setups.
- An anti-spam extension point sized so #113 can drop Altcha in without restructuring this code.

**Non-Goals**
- Substantive legal evaluation of the revocation declaration (the law explicitly defines this as a manual operator step).
- Email-vs-order-email matching or any other gatekeeping that the law forbids.
- Automated retention / deletion of submissions (operator policy; out of scope).
- Spam protection itself (delivered by #113).
- A standalone admin "Revocations" module with its own DB-namespacing or DI service graph — this is small enough to live in the existing admin tree.
- Modifications to checkout / order / account flows.

## Decisions

### D1. Single O3 controller with multiple actions, not a Symfony route or a controller-per-step

Use a single classic O3 controller `OxidEsales\EshopCommunity\Application\Controller\RevocationController` (the namespace is `OxidEsales\EshopCommunity\` for upstream-fork reasons) exposed under `?cl=revocation`, with Smarty-rendered actions:

| Step | Action | URL | Token check |
|---|---|---|---|
| 1 | Footer link target → render form (embed `stoken` hidden field via `Session::hiddenSid()`) | `?cl=revocation` (default action) | none |
| 2 | Form submit (button labelled "Widerruf bestätigen" → `O3_REVOCATION_CONFIRM_BUTTON`) → `Session::checkSessionChallenge()` → validate → persist + send emails → 303 redirect | `?cl=revocation&fnc=submit` | `stoken` required |
| 3 | GET receipt page after redirect | `?cl=revocation&fnc=receipt` | none |

**Why no separate confirm step:** § 356a Abs. 3 requires that the form's action button be labelled unambiguously ("Widerruf bestätigen") and that clicking it makes the declaration legally effective. The unambiguous label *is* the legal safeguard against accidental revocations — a separate "preview-and-confirm" step would be UX overhead without legal necessity. The form's submit button carries the `O3_REVOCATION_CONFIRM_BUTTON` label directly.

Why this shape over alternatives:
- **Symfony controllers** would require new DI wiring and break the convention of every other public form in the shop (contact, newsletter). Not worth the cost for one form.
- **One controller per step** would scatter shared validation logic across files and need a coordinating session key anyway.
- **Single action with branching on `$_POST['step']`** would tangle three view templates into one method.
The four-action pattern keeps each concern in one method (~30–50 lines each), matches O3's existing public-form idioms, and gives reviewers a clear matchup to the four steps in § 356a.

### D2. Single POST: form submit IS the legally-effective declaration; no inter-step state needed

The form is processed in one POST. The submit button's label `O3_REVOCATION_CONFIRM_BUTTON` ("Widerruf bestätigen") is the unambiguous, legally-meaningful action label. On submit: validate → if valid, persist + send emails + 303 redirect to the receipt page. There is no separate confirm view, no second POST, and therefore no inter-request state to carry — no session stash, no hidden form re-post, no DB DRAFT row.

This collapses what an earlier draft of this design treated as a two-step "submit → preview → confirm" flow. § 356a Abs. 3 requires the action button to be unambiguously labelled (so the consumer cannot misunderstand what they're doing) and for the click to make the declaration legally effective. A separate preview-and-confirm step is UX overhead without legal necessity once the button label carries the legal meaning.

Risks eliminated by this decision:
- No "session expired between form and confirm" failure mode.
- No "user navigates away with a draft in session" cleanup question.
- No "script bypasses preview by POSTing the confirm action directly" surface (because there is no confirm action).

### D3. DB schema: new `o3revocation` table; O3 column conventions on the rest

Use the `o3` brand prefix for the table name itself to keep new O3-Shop tables visually distinct from the inherited upstream `ox*` tables — same rationale that drives the `o3_revocation_notice` CMS ident and the `O3_REVOCATION_*` lang keys. Column names inside the new table still use the `OX*` prefix because that prefix is the cross-table column convention (every OXID/O3 table column starts with `OX*`); we are following the column convention, not branding.

```sql
CREATE TABLE `o3revocation` (
  `OXID`         CHAR(32)     NOT NULL
                              COMMENT 'O3 convention: 32-char primary key, generated with UtilsObject::generateUID().',
  `OXSHOPID`     INT          NOT NULL DEFAULT 1
                              COMMENT 'Owning shop ID (multi-shop installations).',
  `OXLANG`       INT          NOT NULL
                              COMMENT 'Consumer language ID at submission time. Drives the language of the confirmation email.',
  `OXNAME`       VARCHAR(255) NOT NULL
                              COMMENT 'Consumer full name as typed in the form. Mandatory per § 356a Abs. 2 BGB.',
  `OXORDERIDENT` VARCHAR(255) NOT NULL
                              COMMENT 'Order/contract identifier as typed by the consumer. Mandatory per § 356a Abs. 2 BGB. NOT a foreign key to oxorder.OXORDERNR — the law forbids rejecting submissions that do not match shop records. Accept verbatim.',
  `OXEMAIL`      VARCHAR(255) NOT NULL
                              COMMENT 'Consumer electronic communication channel for the confirmation receipt. Mandatory per § 356a Abs. 2 BGB. Do NOT validate or match against oxorder.OXBILLEMAIL or oxuser.OXUSERNAME — the law forbids such gatekeeping.',
  `OXFREETEXT`   TEXT         NULL
                              COMMENT 'Optional free-text from the consumer (e.g. partial revocation scope). Not legally required; never made mandatory.',
  `OXSUBMITTED`  DATETIME     NOT NULL
                              COMMENT 'Legal time of receipt per § 356a Abs. 4 BGB. Written ONCE on persist; MUST NEVER be updated. Goes into the confirmation email.',
  `OXTIMESTAMP`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                              COMMENT 'Standard O3 housekeeping column (row last touched). Auto-maintained by the DB engine; do not write from application code.',
  PRIMARY KEY (`OXID`),
  KEY `IDX_O3REVOCATION_SHOPLANG` (`OXSHOPID`, `OXLANG`),
  KEY `IDX_O3REVOCATION_SUBMITTED` (`OXSUBMITTED`)
) ENGINE=InnoDB
  CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Electronic revocation declarations per § 356a BGB (effective 2026-06-19). One row per consumer submission. Insert-only in normal operation; updates are rare (e.g. flagging a confirmation-email resend).';
```

Notes:
- `OXORDERIDENT` is **not** a foreign key to `oxorder.OXORDERNR`. The consumer types whatever they have on their invoice; we accept it verbatim and surface it to the operator. The law forbids us from rejecting the form because it doesn't match.
- `OXEMAIL` is the consumer's contact channel from the form, **not** matched against the order's stored email.
- `OXSUBMITTED` and `OXTIMESTAMP` look redundant but are not. **`OXSUBMITTED`** is the legal "time of receipt" per § 356a Abs. 4: written **once** on persist (step 3 confirm), goes into the confirmation email body, must never change — any later row update (e.g. "operator marked handled", "confirmation email resent") must leave it alone. **`OXTIMESTAMP`** is the standard O3 housekeeping column ("row last touched"): auto-maintained by MySQL on every INSERT and UPDATE, expected by admin list/sync/replication tooling on every O3 table; we keep it for convention even though our table is largely insert-only. Application code MUST NOT touch `OXTIMESTAMP` (DB engine maintains it) and MUST NOT update `OXSUBMITTED` (one-shot write at create time).
- **No IP, no User-Agent column.** Persisting the request IP would require an Art. 6(1)(f) "legitimate interest" carve-out (with a balancing test against the consumer's privacy expectations), and the only operational use we have for IP is in-flight rate-limiting in the `NoopAntiSpamService` (D8) — which works against a short-lived counter store (cache/session), not the submission row. If forensic analysis ever turns out to be needed in production, add the column then with a documented purpose. Default position: collect only what the law requires.
- Storage of the remaining columns is GDPR-justified by Art. 6(1)(c) (legal obligation under § 356a Abs. 4): § 356a Abs. 2 lists name + order identifier + electronic communication channel as the consumer's mandatory inputs, and Abs. 4 mandates a durable-medium acknowledgement that includes the submission content and timestamp — every persisted column maps directly to one of those legal requirements. No legitimate-interest balancing is needed.

### D4. Migration: Doctrine version under `source/migration/data/`, idempotent — schema only, no defaults

One new `VersionYYYYMMDDhhmmss.php` migration that:
1. `CREATE TABLE IF NOT EXISTS o3revocation (...)`
2. `INSERT IGNORE INTO oxcontents` one row per shop with `OXLOADID='o3_revocation_notice'`, `OXSNIPPET=1`, `OXTYPE=0`, all per-language slots `OXACTIVE_*=0`, `OXTITLE_*=''`, `OXCONTENT_*=''`. The `oxcontents` schema uses suffixed columns for multi-language (one row per snippet, columns per language slot), so one row covers every active language. *Idempotent — `INSERT IGNORE` keyed on the `OXLOADID` UNIQUE index never overwrites operator-edited content.*
3. **Does NOT** seed `oxconfig` rows. Defaults are handled by D5 (code defaults for absent rows; `initial_data.sql` for fresh-install seed of `blShowRevocationForm`).

Doctrine migrations run on both fresh installs and upgrades, so any defaulting logic that branches on environment is fragile. We split fresh-install seeding into `source/Setup/Sql/initial_data.sql` (fresh-install only) and rely on code-side defaults for the absent-row case (= upgrade) — see D5. The migration here covers the schema parts that must exist on both paths.

### D5. Per-flag defaults: code-side fallback, not migration heuristic

The migration writes **no** `oxconfig` rows for any of the four feature config keys. Each flag's default is hardcoded in the application code that reads it, using `getConfigParam($key, $default)`. Heuristics like "row count of `oxorder` is zero" or "`sShopVersion` < release" to detect fresh-vs-upgrade are easy to get wrong (operators clone production data into staging, or upgrade through multiple releases at once), so we avoid them entirely.

| `oxconfig` key | Type | Default in code | Set by | Rationale |
|---|---|---|---|---|
| `blShowRevocationForm` | bool | **false** | Install wizard writes `1` on fresh install only; absent on upgrade. | Fresh install = on (legally safe). Upgrade = off (don't silently change behaviour for B2B-only shops or shops with external solutions). Operator flips it on after legal review. |
| `blRevocationRequireLogin` | bool | **false** | Admin form (operator opt-in). | Most shops have guest checkout; opt-in keeps the form reachable. |
| `blRevocationNotifyOperator` | bool | **true** | Admin form (operator can disable). | If off by default, operator never finds out about submissions. Legal/operational risk too high. |
| `sRevocationOperatorEmail` | string | **`""`** at runtime, falls back to `oxshops.oxorderemail` at send time. **Strictly mandatory and validated at admin save time** when `blRevocationNotifyOperator = 1` — the entire form save is rejected if the field is empty or fails `FILTER_VALIDATE_EMAIL`. | Admin form (conditionally mandatory — see asymmetry note below). | Every functioning shop already has `oxorderemail` set; out-of-the-box delivery for fresh installs and unconfigured upgrades. Once the operator opens the admin form, save-time validation forces a conscious choice instead of silently accepting the implicit fallback. |

Implementation pattern at **runtime** (every read site — lenient, falls back to make fresh installs work):
```php
$showForm = (bool) Registry::getConfig()->getConfigParam('blShowRevocationForm', false);
$reqLogin = (bool) Registry::getConfig()->getConfigParam('blRevocationRequireLogin', false);
$notify   = (bool) Registry::getConfig()->getConfigParam('blRevocationNotifyOperator', true);

$opEmail = trim((string) Registry::getConfig()->getConfigParam('sRevocationOperatorEmail', ''));
if ($opEmail === '') {
    $opEmail = trim((string) Registry::getConfig()->getActiveShop()->oxshops__oxorderemail->value);
    if ($opEmail !== '') {
        // Log NOTICE per the operator-notification-email requirement
    }
}
```

Implementation pattern at **admin save time** (cross-field validation in the config controller — strict, refuses the save):
```php
if ((bool) $submitted['blRevocationNotifyOperator'] === true) {
    $email = trim((string) $submitted['sRevocationOperatorEmail']);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Reject entire form save per the all-or-nothing rule (D11);
        // re-render with submitted values pre-filled per form-input-preservation rule.
    }
}
```

The asymmetry is intentional: runtime is lenient so fresh and unconfigured shops still get notifications somewhere sensible; admin-save is strict so once the operator interacts with the form they must consciously choose the recipient instead of silently relying on the implicit fallback.

**Fresh-install seeding** for the four feature rows happens in **`source/Setup/Sql/initial_data.sql`** (loaded by the install wizard at `source/Setup/Controller.php:639` on every fresh install, with or without optional demo data). The change is **four** extra `INSERT INTO oxconfig (...)` rows alongside the nine that already live there for the default baseline — one per feature key, with values matching the absent-row code defaults from the table above. Seeding is purely for self-documentation: a fresh-install database lets an operator inspect `SELECT * FROM oxconfig WHERE OXVARNAME LIKE '%Revocation%'` and see the canonical state explicitly, instead of inferring it from "the row is missing, therefore the default applies". No runtime behaviour difference from the upgrade case. Critically: `initial_data.sql` is **not** loaded on shop upgrades — Doctrine migrations under `source/migration/data/` are. So an upgrade reads the four config keys via the absent-row code defaults, and a fresh install reads the same values from explicit rows. Two distinct code paths converging on identical runtime behaviour, no fragile detection logic in either.

Effect: every flag has a sensible default whether the row exists or not. Fresh installs (any kind — with or without demo data) get the show-form flag on via `initial_data.sql`. Upgrades inherit the safe defaults from code. No fragile environment-detection heuristics, no new install-wizard hook.

### D6. Operator notice rendered through `{oxifcontent}`

Storefront template includes the notice with the O3-standard content tag:
```smarty
{oxifcontent ident="o3_revocation_notice" object="oCont"}
  <div class="o3-revocation-notice">{$oCont->oxcontents__oxcontent->getRawValue()}</div>
{/oxifcontent}
```
`{oxifcontent}` already short-circuits to render nothing if the snippet is missing, inactive, or empty per language. Operators activate and edit it via the existing admin CMS module — no new admin UI needed. Decision recorded in proposal; restating here for the implementer.

### D7. Mail dispatch via `Core\Email`, mirrors `sendOrderEmailToUser`/`sendOrderEmailToOwner`

Reference implementation: `OxidEsales\EshopCommunity\Core\Email::sendOrderEmailToUser()` (around `Email.php:589`) and `::sendOrderEmailToOwner()` (around `Email.php:640`). The pattern in O3-Shop today is:

- **Body**: pure Smarty templates per HTML/plain variant (`tpl/email/html/<name>.tpl`, `tpl/email/plain/<name>.tpl`). Static text uses `{oxmultilang ident="..."}` keys; dynamic data passes through `setViewData()`.
- **Subject**: an optional Smarty subject template `<name>_subj.tpl`. Order email falls back to the per-language admin-editable `oxshops.oxordersubject` column when the subject template is missing.
- **Language**: explicitly set on the Shop model via the protected `_getShop($langId)` helper, which reloads `oxshops` in the requested language. Order owner email uses `Language::getObjectTplLanguage()`; we use the language stored on the submission row.
- **No CMS snippet involvement** for email bodies — that's storefront-only.

Mirroring this here, add two methods to `Core\Email` — names follow the existing `sendOrderEmailToUser` / `sendOrderEmailToOwner` shape so they show up next to their kin in any "find the X email" search:

```php
public function sendRevocationEmailToCustomer(O3Revocation $submission): bool
public function sendRevocationEmailToOperator(O3Revocation $submission): bool
```

Templates (located in the **wave-theme repo** for storefront-bound templates — same place existing order emails live). Filenames are recipient-first, purpose-second so a directory listing is self-explanatory:

| Email | HTML body | Plain body | Subject |
|---|---|---|---|
| Customer confirmation (§ 356a Abs. 4 receipt) | `tpl/email/html/revocation_customer_confirmation.tpl` | `tpl/email/plain/revocation_customer_confirmation.tpl` | `tpl/email/html/revocation_customer_confirmation_subj.tpl` (`{oxmultilang ident="O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT"}` + `' (#'~submission_id~')'`) |
| Operator notification (heads-up) | `tpl/email/html/revocation_operator_notification.tpl` | `tpl/email/plain/revocation_operator_notification.tpl` | `tpl/email/html/revocation_operator_notification_subj.tpl` |

The slightly-longer-than-O3-default `_customer_confirmation` / `_operator_notification` suffixes (vs the legacy `_cust` / `_owner`) are deliberate: they answer *what kind of email is this?* at a glance, which matters because revocation has two distinct emails with very different legal weight (the customer one is the legally-required durable-medium acknowledgement; the operator one is operational only). Trading two extra words against an evening of grep-and-guess is the right call.

Templates pass `setViewData('submission', $submission)`; static labels via `{oxmultilang}`; the body should be prose translated as a whole through block-level keys rather than fragmented sentence-by-sentence keys, to keep the mail readable when a translator works on a single language. Suggested keys (each scoped by recipient so customer-facing wording can never accidentally leak into the operator email):
- Customer: `O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT`, `O3_REVOCATION_CUSTOMER_EMAIL_BODY_INTRO`, `O3_REVOCATION_CUSTOMER_EMAIL_BODY_RECEIPT_NOTE`, `O3_REVOCATION_CUSTOMER_EMAIL_BODY_FOOTER`
- Operator: `O3_REVOCATION_OPERATOR_EMAIL_SUBJECT`, `O3_REVOCATION_OPERATOR_EMAIL_BODY`

**Language selection**: the submission row carries `OXLANG` (set at submit time from the consumer's session language). Send-time, the email service calls `_getShop($submission->getLang())` so the shop name, tone, and `{oxmultilang}` lookups all resolve in the consumer's language. Missing-key fallback to the shop default is the translation engine's standard behaviour — no custom code needed (closes Open Question #2). The **operator** notification email uses the shop's default language (the operator chose their own admin language; we don't translate per-submission for them) and the recipient resolution from D5: `sRevocationOperatorEmail` if non-empty, else fall back to `oxshops.oxorderemail`. If both are empty (a misconfigured shop), log an `ERROR` and skip the operator notification — the consumer-side flow still completes.

The confirmation email is **legally required** (§ 356a Abs. 4 — durable medium acknowledgement). The synchronous send may fail (bad MX, full queue, mail-server unreachable, configuration error). Decision: **persist first, send second**, log the email error, surface a "send failed" flag in the admin detail view. The submission *is* the legal declaration; the receipt is acknowledgement of it. Operators can manually resend from the admin detail page (a "Resend confirmation" button).

Important framing for the indicator wording: we only know whether the local send call returned an error. We do **not** track downstream delivery — bounces, spam classification, and non-delivery reports are async signals our application doesn't subscribe to. The admin UI says "send failed" (or equivalent translated string), never "delivery failed", because the latter would overpromise what we can actually detect.

Missing-template handling is **not** in this method's scope — D11 (activation-time template-presence gate) ensures every required template is present before the feature can be turned on, so by the time `sendRevocationEmailToCustomer()` runs, the templates exist. The runtime safety net for the rare post-activation disappearance is documented in the risks section.

### D8. Anti-spam hook today, Altcha-shaped tomorrow — second layer behind the CSRF token

D8 sits **behind** D10 in the defensive stack. The CSRF/session-challenge token from D10 fires first and rejects any POST that did not originate from a real form-render flow on this session — that's our zero-cost first layer against direct/cross-origin abuse. D8 catches what gets past it: scripts that legitimately load the form, scrape the token, then bot-loop the submit. Phrased as a request lifecycle:

```
incoming POST
   ↓
[D10] Session::checkSessionChallenge()         ← rejects zero-effort attacks
   ↓ (passed)
[D8]  RevocationAntiSpamService::verify()      ← catches scaled bot loops
   ↓ (passed)
controller business logic
```

Add a `RevocationAntiSpamService` interface with one method `bool verify(\OxidEsales\Eshop\Core\Request $request)` and a hook `recordSuccess(\OxidEsales\Eshop\Core\Request $request): void` that the controller calls right after a successful persist. Default implementation `NoopAntiSpamService` enforces a **two-mode IP rate limit** against a transient cache counter store (the rate limit lives inside the anti-spam service precisely because the decision belongs there — not in the controller, not in D10):

| Counter | Limit | TTL | Purpose |
|---|---|---|---|
| Failed-submission | **3** per IP | **60 s** sliding window | Lets a fumbling legitimate user retry: typo → server-side validation error → fix → submit, easily 2 attempts in 20 s; a 3rd slot covers "fix, error again, fix, succeed". Bot loops hit the ceiling almost immediately. |
| Successful-submission | **1** per IP | **300 s** (5 min) lockout | After a successful persist, the same IP is blocked for 5 minutes regardless of failed-counter state. Rationale: under normal use a single human does not legitimately submit two revocations from the same IP inside 5 minutes; the second attempt is overwhelmingly abuse. The trade-off: a household with two orders to revoke from the same router has to wait 5 minutes between submissions — accepted as a rare-but-tolerable edge case in exchange for cutting off the obvious abuse path. |

Behaviour at `verify()`:
1. If the successful-submission counter for this IP is present → return `false` (5-min lockout).
2. Else if the failed-submission counter for this IP is `>= 3` → return `false`.
3. Else return `true`.

After a successful confirm → persist, the controller calls `recordSuccess()` which sets the successful-submission counter for the IP with a 300-second TTL. On rejection from any later step (validation, token mismatch — anything past `verify()` returning `true`), the controller calls `recordFailure()` (sister hook) which increments the failed-submission counter with a 60-second TTL.

Why two separate counters and not one combined "rejection" counter: a legitimate user can produce up to 3 failed attempts en route to a successful one — that path needs to count as "fumbling, not abuse". Once they succeed, they're done; further attempts are abusive. Combining the counters either punishes the fumble path (too tight) or fails to catch the after-success abuse (too loose). Two counters with different TTLs gives us the right shape.

Wire it via the DI container (`source/Internal/Framework/.../services.yaml`) so #113 can rebind to `AltchaAntiSpamService` without touching the controller. On `verify() == false`, the submit action renders the form again with a generic translated error (`O3_REVOCATION_VALIDATION_SPAM`) — no leaking which counter triggered the rejection.

This is the smallest hook that lets #113 land independently. The `Request` parameter is wide enough to read POST fields (Altcha) or headers (rate-limit) without further interface changes.

Why two distinct layers and not one combined check: the D10 `stoken` is *built into core* and applies to many controllers — pulling it inside `RevocationAntiSpamService` would either duplicate the logic or couple our service to OXID core internals. Keeping D10 (cheap, free, generic CSRF guard) separate from D8 (revocation-specific anti-abuse logic, bot-aware) keeps each layer single-purpose and makes it obvious to a reviewer what each one is for.

### D9. Multi-repo work split

| Repo | Files added/changed |
|---|---|
| `o3-shop/shop-ce` | `Application/Controller/RevocationController.php`, `Application/Controller/Admin/RevocationListController.php`, `Application/Controller/Admin/RevocationDetailController.php`, `Application/Model/O3Revocation.php`, `Core/Email.php` (extend), `migration/data/Version<ts>.php`, `Application/views/admin/<lang>/lang.php` (new keys), `Application/views/admin/tpl/revocation_*.tpl`, `Internal/.../services.yaml` (anti-spam binding) |
| `o3-shop/wave-theme` | **Page templates** — `tpl/page/revocation/revocation.tpl` (form, step 1) and `tpl/page/revocation/revocationreceipt.tpl` (receipt page, step 3 — there is no step-2 view, the form submit goes straight to persist+email+receipt-redirect per D2); **Footer extension** — `tpl/layout/footer.tpl`; **Storefront lang keys** — `de/lang.php` + `en/lang.php`; **Email templates** (mirror existing `order_cust`/`order_owner` placement) — `tpl/email/html/revocation_customer_confirmation.tpl`, `tpl/email/plain/revocation_customer_confirmation.tpl`, `tpl/email/html/revocation_customer_confirmation_subj.tpl`, `tpl/email/html/revocation_operator_notification.tpl`, `tpl/email/plain/revocation_operator_notification.tpl`, `tpl/email/html/revocation_operator_notification_subj.tpl` |
| `o3-shop/o3-Theme` | Same set as wave-theme, ported during the cutover |

Two coordinated PRs (shop-ce + wave-theme) for the immediate ship; o3-Theme follows with its own PR. Cross-link in all PR descriptions.

### D10. Session challenge token on every state-changing action ("must have visited the form first")

To prevent direct submissions to `?cl=revocation&fnc=submit` from clients that never rendered the form — i.e. scripts and cross-origin CSRF attempts — the single state-changing action requires the O3 session challenge token (`stoken`). The mechanism is built into core and already conventional across the codebase:

- **Form render** (step 1) embeds `<input type="hidden" name="stoken" value="...">` via `Session::hiddenSid()` (auto-included by the Smarty form helpers used everywhere else; explicit in our case for clarity).
- **Submit / confirm controllers** (steps 2 + 3) start with `if (!Registry::getSession()->checkSessionChallenge()) { redirectToForm(); return; }`. Reference: `Session::checkSessionChallenge()` at `source/Core/Session.php:319` and existing usages in `BasketController`, `OrderController`, `ContactController`, etc.
- The token is per-session and auto-regenerated when missing. A client without a session can never produce a valid token, so direct POSTs from outside a real form-render flow are rejected.

Why this is enough at this layer (not "everything"):
- It blocks the lowest-effort attack surface (direct/cross-origin POST without a form fetch) at zero implementation cost — it's already in core.
- It does **not** block determined abuse where a script loads the form, scrapes the token, then submits — that's exactly what #113 (Altcha) and the temporary IP rate-limit in D8 are for. Layered defence:
  1. **`stoken` (this decision)** — must have rendered the form on this session.
  2. **Anti-spam service `verify()` + IP rate-limit fallback (D8)** — must not be a bot loop.
  3. **Altcha proof-of-work (#113)** — must not be a scaled bot.

Behaviour on token mismatch: redirect to `?cl=revocation` with a translated info message (`O3_REVOCATION_VALIDATION_SESSION_EXPIRED` — same UX as the session-timeout case in D2 risk; user-perceptually they are the same problem). Logged at `WARNING` (the standard Monolog request-context attachment provides whatever signal is needed for incident response; we do not persist IP in the submission row — see D3).

### D11. Template-presence gate at admin activation time

Move the bulk of the missing-template defence out of the consumer-facing runtime and into the **admin save handler** that flips `blShowRevocationForm` from `0` to `1`. By the time a real revocation submission arrives, all required templates for all active shop languages are guaranteed present, so the runtime fall-back chain shrinks to a "should never happen" safety net.

**Where the gate fires** — any admin-initiated change that could leave the feature on while a required asset is missing:
1. Admin saves the shop configuration with `blShowRevocationForm = 1` (initial enable, or any later edit while the flag is on).
2. Admin **enables a new shop language** while `blShowRevocationForm = 1`.
3. Admin **switches the active storefront theme** while `blShowRevocationForm = 1` — the new theme may not carry the revocation templates yet (especially during the wave→o3-theme cutover before 2026-05-01). Same validator runs against the *new* active theme; if it fails, the theme switch is rejected with the same per-asset list, and the operator either installs the templates in the new theme first or deactivates the revocation feature before swapping.

**What the gate checks** — only the **currently-active** theme. The validator deliberately does not walk inactive themes: assets in an inactive theme cannot affect the consumer-facing path, and re-checking every installed theme on every save would be noisy and tempt operators to ignore the warnings. If a different theme later becomes active, trigger 3 above re-validates at that moment. For each currently-active shop language:

| Template type | Path pattern in the active theme | Lives in repo |
|---|---|---|
| Page (form) | `<active-theme>/tpl/page/revocation/revocation.tpl` | wave-theme today; o3-Theme post-cutover |
| Page (receipt) | `<active-theme>/tpl/page/revocation/revocationreceipt.tpl` | wave-theme today; o3-Theme post-cutover |
| Customer email body (HTML + plain) | `<active-theme>/<lang>/tpl/email/{html,plain}/revocation_customer_confirmation.tpl` | wave-theme today; o3-Theme post-cutover |
| Customer email subject | `<active-theme>/<lang>/tpl/email/html/revocation_customer_confirmation_subj.tpl` | wave-theme today; o3-Theme post-cutover |
| Operator email body (HTML + plain) | `<active-theme>/<lang>/tpl/email/{html,plain}/revocation_operator_notification.tpl` | wave-theme today; o3-Theme post-cutover |
| Operator email subject | `<active-theme>/<lang>/tpl/email/html/revocation_operator_notification_subj.tpl` | wave-theme today; o3-Theme post-cutover |
| Storefront lang keys | every `O3_REVOCATION_*` ident used by storefront templates resolves to a non-empty value in `<active-theme>/<lang>/lang.php` | wave-theme today; o3-Theme post-cutover |
| Admin/email lang keys | every `O3_REVOCATION_*` ident used by admin templates and email subject/body keys resolves to a non-empty value in `source/Application/translations/<lang>/lang.php` | shop-ce |

**Implementation:** a single class lives in shop-ce:

```php
RevocationTemplateValidator::validate(
    int $shopId,
    string $themeId,        // active theme to validate against (caller passes the *new* theme on theme-switch)
    array $activeLangIds    // int[] — caller passes the *new* set on language-activation
): array;                   // MissingAsset[]
```

The caller supplies the prospective state (new theme / new language set / current state on a plain save) explicitly, so the validator never has to second-guess what's about to be committed and stays trivially testable. It walks the table above using the existing renderer's `exists()` method and the translation engine's lookup. It is used by all three trigger sites (config save, language activation, theme switch) and by a CLI healthcheck:

```
bin/oe-console o3:check-templates
```

The command name is **deliberately feature-neutral** — it does not say "revocation" — so future features that have their own template-presence requirements can plug in without us having to rename a published CLI entry-point (a breaking change). Today the command's only check is the revocation template set: it instantiates `RevocationTemplateValidator` against the current active theme and languages and reports the same missing-asset list the admin save would. Later, when a second feature needs the same kind of validation, the command can dispatch to a registry of validators (one provider per feature). We do **not** build that registry now — YAGNI — but we secure the namespace today.

**Failure mode:** if `validate()` returns a non-empty list, the calling admin handler **rejects the entire save — all or nothing.** No partial activation, no split state where some submitted fields landed and others didn't. The operator sees a clean error, fixes the underlying problem, and re-submits the form. **Critically, the form re-renders with every value the operator just typed pre-filled — nothing they entered may disappear.** This is a usability invariant for every form in the system (admin and storefront), not specific to this feature: if a user submitted a value and the server rejected the submission for any reason, that value goes back into the form unchanged so the user only has to fix the offending part, never re-type the rest. See `feedback_form-input-preservation.md` in shared memory for the underlying principle. Per trigger:
- Trigger 1 (config save with `blShowRevocationForm = 1`): **the whole config-form save is rejected**. Nothing on this form is committed — neither the toggle nor any other field changed in the same submission. Avoids the confusing case where an operator changed five fields, the toggle was one, and ends up wondering which of the other four took effect.
- Trigger 2 (language activation): the entire language-activation operation is rejected; the language stays disabled until templates exist for it.
- Trigger 3 (theme switch): the entire theme-switch operation is rejected; the active theme stays as it was.

Each rejection surfaces the missing-asset list with one remediation hint per row ("install `<file>` under `source/Application/views/<active-theme>/<lang>/tpl/email/...`"). The operator either fixes the missing files (e.g. copies from the wave-theme / o3-Theme repo) and retries, or leaves the feature deactivated until they're ready.

**Why activation-time, not migration-time:** the template package may have been installed before the language is activated, after, or via a separate bootstrap (#114 path). Tying the check to migration would either fire too early (before the theme zip is unpacked) or too late (the migration already ran on a previous shop start). Activation is the natural moment because it's exactly when the templates are about to start being needed.

**Why it's safe to block here:** activation is an out-of-band admin action, no consumer is waiting, and the operator is the right person to act on the error. This is **not** the same as fail-fast on the consumer path — see `feedback_graceful-degradation.md` in shared memory and the (much-reduced) runtime fall-back below.

### D12. PHP version & strict typing for all new code

All new code in this change MUST run on **PHP 7.4 – 8.x** (matches `composer.json: "^7.4 || ^8.0"`) and MUST be strictly typed:

- **Every new file** opens with `declare(strict_types=1);` and uses parameter types, return types, and typed properties everywhere.
- **Allowed in 7.4** and used freely: scalar types, `?Type` nullable, typed properties, arrow functions, `??=`, spread in arrays.
- **Forbidden in new code (PHP-8-only)**: union types (`int|string`), `mixed`, constructor property promotion, named arguments, `match`, nullsafe `?->`, `readonly`, enums, first-class callable syntax, intersection types, standalone `true`/`false`/`null` types. When a 7.4-incompatible feature would be cleaner, document the incompatibility with a `@param`/`@return` docblock instead.
- **Methods added to existing untyped files** (notably `Core/Email.php`): add types to the new method's signature only. Do **NOT** insert `declare(strict_types=1)` at the top of a file that doesn't already have it — the directive is per-file and would change the semantics of every existing method in the file. Converting `Email.php` to strict types is out of scope for this change.
- **Overriding inherited core methods** (e.g. extending an admin controller from the inherited upstream `OxidEsales\…` namespace): the signature MUST match the parent. If the parent uses untyped parameters, the override must too. PHP's LSP check will reject mismatches. Do not try to add stricter types to an inherited signature.

The full rule, including the rationale and a comprehensive list of disallowed PHP-8 features, lives in `.claude/memory/project-conventions.md` ("PHP version and type safety"). That memory entry binds every change in the repo, not just this one — restating here so reviewers of #99 see the constraint in-context.

### D13. Logging per CLAUDE.md standards

Five log points, all using `__METHOD__ . ' - '` prefix and the structured `data` parameter for IDs:
- `INFO` on form render (one line, debug-grade).
- `INFO` on validation pass inside the submit handler (no submission `OXID` yet — the row hasn't been written; if the visitor is logged in, the user's `oxuser.OXID` MAY be included for correlation).
- `NOTICE` on persist (after the `o3revocation` row is written; includes the submission's `OXID` and, for a logged-in visitor, the user's `oxuser.OXID`).
- `ERROR` on email send failure (with the underlying error string and the submission's `OXID`; never the consumer's email address).
- `WARNING` on anti-spam reject or session-token mismatch (no IP in the message body — the standard Monolog request context covers it; we do not persist IP).

Opaque IDs (`o3revocation.OXID`, `oxuser.OXID`) are explicitly *encouraged* in the message body for correlation; personal data (name, email, free-text) is forbidden there. See the spec's "Logging at five defined points" requirement for the full personal-data rule and `feedback_graceful-degradation.md` in shared memory for the broader logging principle.

## Risks / Trade-offs

[**Confirmation email fails after persist**] → The legal declaration is recorded; the synchronous send call returned an error. *Mitigation:* admin "send failed" surface + manual resend button + email-failure ERROR log. The legal position remains "we received the declaration on date X" which the operator can prove from the table even without the email. We deliberately do not claim to detect downstream delivery — bounces and spam classification are async signals outside our scope.

[**Bot abuse before #113 ships**] → Public form with no CAPTCHA invites garbage submissions. *Mitigation (layered):* (1) the O3 session challenge token (D10) blocks zero-effort direct/cross-origin POSTs out of the box at no implementation cost; (2) the `NoopAntiSpamService` ships with a two-mode IP rate limit (D8): up to 3 failed attempts in a 60 s window for the legitimate fumble path, plus a 5-minute lockout after each successful persist that catches any after-success abuse. Bot loops hit one ceiling or the other almost immediately while real users with typos retain headroom; (3) #113 lands and replaces the noop with Altcha for scaled-bot resistance.

[**Migration attempts to seed `oxconfig` after fresh-install seeding**] → Inconsistent default if both code paths write. *Mitigation:* migration writes nothing; only the install wizard writes the default-on row. Code reads the absent-row case as off.

[**Theme cutover before May 1**] → Wave templates copied to o3-Theme that bake in wave class names cause double work. *Mitigation:* enforced via the design (no `getActiveTheme()` branching, no wave-only class in PHP); shared memory entry `architecture_theme-repos.md` makes this convention visible.

[**Translation key omission**] → Implementer under deadline pressure hardcodes a literal string. *Mitigation:* code review checklist, plus the `O3_REVOCATION_*` prefix lets a single grep audit completeness pre-merge: `grep -rIE "(>|\")[A-ZÄÖÜ][^<\"']{3,}(<|\")" source/Application/views/.../revocation*.tpl` should return zero hits not in `{oxmultilang}` form. Documented in tasks.md.

[**Smarty mail template drift**] → Adding new templates per language means missing one breaks that language path. *Mitigation (primary):* the activation-time gate from **D11** catches this in the common case — the operator cannot enable the feature with missing templates, and cannot enable a new shop language while the feature is on with missing templates for that language. By the time a consumer submits a revocation, all required templates for all active languages are guaranteed present. *Mitigation (runtime safety net):* if a template disappears post-activation (file deleted by hand, theme swap mid-life, package corruption), the email service falls back to the shop's default-language template; if that's also gone, the submission still persists, the admin entry is flagged "send failed", and one `ERROR` line is logged per fall-through naming the missing path and a remediation hint. The runtime path is intentionally short — the activation gate is the real defence; the runtime safety net just keeps the consumer-facing flow from breaking on the rare edge case. See `feedback_graceful-degradation.md` in shared memory for the underlying principle (fall back, log, don't block on user-facing paths).

[**`OXORDERIDENT` text typed by consumer is unbounded**] → Could be abused to inject data. *Mitigation:* VARCHAR(255) cap, HTML-escape on render, no SQL by hand (DBAL parameter binding). No HTML in confirmation email body — plain text or escaped HTML only.

## Migration Plan

1. Land #114 (entrypoint `git clone` bootstrap).
2. Open shop-ce PR with controller, model, migration, admin templates, anti-spam service interface and noop binding, lang keys.
3. Open wave-theme PR with form templates, footer extension, storefront lang keys, email templates.
4. Cross-link PRs; merge in order shop-ce first, then wave-theme (theme references shop-ce keys).
5. After merge, run `./docker.sh start` on a fresh state to confirm:
   - `o3revocation` table exists; `oxcontents` row present, inactive, empty.
   - Footer link visible without login.
   - Form renders, all strings translated, no console/network errors.
   - Submit → confirm → email arrives in Mailpit, contains submission id and timestamp, all language-correct.
   - Admin → Customer Info → Revocations shows the entry; detail view renders.
   - Operator activates the CMS snippet, fills content for `de`, refreshes form → notice appears above form.
6. Track o3-Theme port as a separate task before 2026-05-01.

**Rollback:** if a regression surfaces post-merge, the storefront feature can be disabled instantly by toggling `blShowRevocationForm` to off via admin (no code rollback needed). For DB-level rollback, the migration's down() drops `o3revocation` and the `oxcontents` rows with our ident; `oxconfig` rows we did not insert need no cleanup.

## Open Questions

1. ~~Install-wizard hook for `blShowRevocationForm = 1` seeding~~ — **resolved.** Add one `INSERT INTO oxconfig` row to `source/Setup/Sql/initial_data.sql`, alongside the nine config rows already seeded there. The file is loaded by `source/Setup/Controller.php:639` on every fresh install (with or without optional demo data), so the seed fires on all fresh installs and never on upgrades — exactly the semantics we want. No new extension point, no separate repo, no `shop-demodata-*` dependency for this. See D5.
2. ~~Fallback language for the confirmation email~~ — **resolved.** Mirror `sendOrderEmailToUser`/`sendOrderEmailToOwner`: pure Smarty templates with `{oxmultilang}` keys; language set explicitly via `_getShop($langId)` from the submission row's `OXLANG`; the translation engine handles missing-key fallback to shop default automatically. No CMS snippet for email body. See D7.
3. ~~CSV / PDF export from admin list~~ — **out of scope.** Operators dealing with legal evidence can use the standard admin list view; an export feature is a separate concern and will be considered as its own issue if and when an operator asks for it. Not deferred-but-implied; explicitly off the table for this change.
4. ~~Retention policy / automated deletion~~ — **resolved: no automatic deletion, ever.** Submissions remain in `o3revocation` until the operator manually deletes them (or runs their own external retention job against the table). Rationale: revocation declarations carry legal weight; an automated cron quietly destroying evidence rows would be the wrong default and could expose operators to claims they can't refute. Retention is the operator's responsibility under their own privacy notice and record-keeping policy, exactly as they handle order data today. Admin help text states this clearly; the codebase ships nothing scheduled, configurable, or otherwise that touches existing rows.
5. ~~IP storage opt-out~~ — **resolved**: we do not persist IP or User-Agent in the submission row at all (D3). In-flight rate-limit (D8) reads the request IP for the duration of the check only, against a transient cache counter — no DB column, no admin flag required.
6. ~~Rate-limit threshold for the temporary anti-spam fallback~~ — **resolved: two-mode IP rate limit.** Up to 3 failed attempts per IP within a 60-second sliding window (covers the fumble path) plus a 5-minute lockout per IP after each successful persist (cuts after-success abuse). Both thresholds are class constants in `NoopAntiSpamService`; not admin-configurable in this change. See D8.
