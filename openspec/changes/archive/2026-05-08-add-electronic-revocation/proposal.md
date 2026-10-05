## Why

§ 356a BGB (BGBl. 2026 I Nr. 28, transposing EU Directive 2023/2673) requires every B2C online shop in Germany to provide an electronic revocation function ("Widerrufsbutton") starting **19 June 2026**. Without it, contracts concluded via the shop are exposed to indefinite revocation rights and the operator is in breach of consumer law. O3-Shop currently offers no such function, so every B2C shop on the platform will be non-compliant on the effective date unless this change ships.

## What Changes

- Add a new **electronic revocation** capability to the storefront covering the four-step flow defined in § 356a BGB:
  1. Permanent revocation-entry link in the footer of every storefront page, subject to the visibility matrix below (default German label *"Vertrag widerrufen"* shipped via the translation system):
     - When the feature is on and `blRevocationRequireLogin = 0` (guest access allowed): link visible to anonymous and authenticated visitors alike — meets § 356a "easily accessible" for shops with guest checkout.
     - When the feature is on and `blRevocationRequireLogin = 1` (login mandatory): link visible only to logged-in users; hidden from anonymous visitors, since they could not have placed an order in the first place and therefore have no contract to revoke. Showing the link to them would advertise an entry-point they cannot use.
     - When the feature is off: link never rendered, regardless of login state.
  2. Revocation form with the three statutory mandatory fields (name, contract/order identification, electronic communication channel) plus an optional free-text field for partial revocation. **Above the form**, render an operator-editable notice area (see "Operator notice above form" below) so shop owners can communicate scope-limiting or shop-specific information ("Revocation only for products X and Y", processing-time info, contact details for unusual cases, etc.).
  3. The form's action button itself carries the legally-meaningful label *"Widerruf bestätigen"* (default German, shipped via the translation system) — clicking it IS the legally-effective declaration per § 356a Abs. 3. There is **no separate "preview and confirm" view** between the form and the receipt: the unambiguous button label is the legal safeguard against accidental revocations; an extra preview step would be UX overhead without legal necessity.
  4. Immediate confirmation receipt to the consumer on a durable medium (email), including the declaration content and timestamp of receipt
- Persist every submission in a new database table and surface it in the **admin backend** (list + detail view).
- Add an optional, configurable **operator email notification** on each new submission.
- **Operator notice above form**: introduce a per-language, operator-editable notice that renders directly above the revocation form. Implemented as a new **CMS content snippet** (`oxcontents` row) with a stable ident `o3_revocation_notice`, edited via the existing admin CMS module (Customer Info → CMS Pages / Content Snippets), supporting the standard rich-text features (paragraphs, bold/italic, links). Storefront template includes it with `{oxcontent ident="o3_revocation_notice"}` and renders nothing if the snippet is empty, inactive, or unset. Why a CMS snippet, not a translation key + checkbox: the content is per-language *editorial* copy the operator authors themselves (potentially multi-paragraph, with formatting, scope-limiting language, links to T&Cs etc.) — that is exactly what the CMS module is built for, and it already provides per-language storage, the WYSIWYG editor, the activate/deactivate flag, and a familiar admin location. Migration seeds an empty, **inactive** snippet for each active shop language so operators see it in the CMS list immediately, with a placeholder description like "Notice rendered above the revocation form on the storefront". Operators activate the snippet and fill in content per language when they are ready.
- Add three shop-level configuration switches in admin:
  - "Show revocation function" — lets operators hide the form entirely if not legally required.
    - **Fresh installations**: default **on** (legally safe out of the box for the typical B2C shop).
    - **Existing shop upgrades**: default **off** so the migration does not silently change behaviour for shops that may already meet § 356a through other means or that are pure-B2B. Operators flip it on after reviewing their legal situation.
    - The migration MUST distinguish "fresh install" from "upgrade in place" (e.g. by checking whether the `oxconfig` row already exists / by detecting prior schema versions) and seed the value accordingly. Document the upgrade behaviour prominently in the release notes and in admin so operators are not caught out.
  - "Require login to access form" (default: off) — operators without a guest checkout may opt in
  - "Notify operator by email" (default: **on** for both fresh installs and upgrades) with recipient address. **Why on by default:** if it were off, the operator could go indefinitely without learning that revocations are arriving — submissions would just pile up in the admin module. That risks missed legal deadlines and unhappy consumers. The recipient address (`sRevocationOperatorEmail`) follows an **asymmetric rule**: at runtime it is *optional* — if empty, the email falls back to the shop's existing order-confirmation address (`oxshops.oxorderemail`) so fresh installs and unconfigured upgrades still receive notifications somewhere sensible; at admin save time it becomes *strictly mandatory* — once the operator opens the form, the save is rejected if notify is on and the email is empty or syntactically invalid. The asymmetry gives operators a working default without ever silently accepting an incomplete configuration once they've shown intent by editing. The boolean+string two-row pattern matches O3 convention so operators can mute notifications temporarily without losing their configured recipient address.
- Route **every** user-facing string through the existing translation engine — button labels, form labels, validation messages, page headings, footer link text, admin labels, email subject and email body. No hardcoded German (or English) literals anywhere in templates, controllers, or mail templates. Ship `de` and `en` out of the box; any German wording quoted from the BGB or this proposal is just the default `de` lang-file value.
- **Translation key prefix**: every translation constant introduced by this feature MUST share the prefix **`O3_REVOCATION_`** (uppercase, underscore-separated). Examples: `O3_REVOCATION_FOOTER_LINK`, `O3_REVOCATION_FORM_HEADING`, `O3_REVOCATION_FIELD_NAME_LABEL`, `O3_REVOCATION_FIELD_ORDERNUMBER_LABEL`, `O3_REVOCATION_FIELD_EMAIL_LABEL`, `O3_REVOCATION_FIELD_FREETEXT_LABEL`, `O3_REVOCATION_CONFIRM_BUTTON` (the form's only action button — labelled "Widerruf bestätigen"), `O3_REVOCATION_CONFIRMATION_PAGE_HEADING`, `O3_REVOCATION_VALIDATION_REQUIRED`, `O3_REVOCATION_VALIDATION_EMAIL_FORMAT`, `O3_REVOCATION_VALIDATION_SESSION_EXPIRED`, `O3_REVOCATION_ADMIN_NAV_LABEL`, `O3_REVOCATION_ADMIN_LIST_HEADING`, `O3_REVOCATION_CONFIG_SHOW_LABEL`, `O3_REVOCATION_CONFIG_REQUIRELOGIN_LABEL`, `O3_REVOCATION_CONFIG_NOTIFY_LABEL`. Email keys are scoped by recipient (so customer-facing wording can never leak into the operator email): `O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT`, `O3_REVOCATION_CUSTOMER_EMAIL_BODY_INTRO`, `O3_REVOCATION_CUSTOMER_EMAIL_BODY_RECEIPT_NOTE`, `O3_REVOCATION_CUSTOMER_EMAIL_BODY_FOOTER`; `O3_REVOCATION_OPERATOR_EMAIL_SUBJECT`, `O3_REVOCATION_OPERATOR_EMAIL_BODY`. The prefix scopes the keys to this feature, prevents collisions with the `DD_*` (wave-theme) and generic prefixes already in `lang.php`, and lets a single grep find every revocation-related string across the codebase. Note: the editorial *operator notice above the form* is **not** a translation key — it is a CMS content snippet (`oxcontents`, ident `o3_revocation_notice`) authored per-language by the operator. See the "Operator notice above form" bullet for the rationale.
- Explicitly **forbid** any validation that matches the submitted email against the order's stored email — the law does not permit such gatekeeping.
- Ship the footer button and form for **all active shop themes**. Today the only storefront theme is `wave`, so all template work in this change targets `wave` first. A separate, time-boxed migration to a new `o3-theme` is planned for **before 2026-05-01** (within the current week from the 2026-04-26 baseline), at which point the same templates and translation keys must be carried over to `o3-theme`. Templates and Smarty includes added here MUST be authored to make that transfer mechanical (no `wave`-specific class names baked into IDs/keys, no theme-dependent control-flow in controllers). The admin theme is unaffected on the storefront side but gains the new admin views.
- **Cross-repo work — themes live outside shop-ce**: both themes are separate GitHub repositories — `wave` is at https://github.com/o3-shop/wave-theme and `o3-theme` is at https://github.com/o3-shop/o3-Theme. In shop-ce, `source/Application/views/wave/` and `source/Application/views/o3-theme/` are **gitignored** (`.gitignore` lines 52–53). Theme template work for this change therefore lands in those upstream repos, **not** in shop-ce. Three coordinated PRs ship this feature: shop-ce (controllers, models, migrations, admin templates, admin lang keys), wave-theme (storefront templates + storefront `de`/`en` keys), and after the cutover o3-Theme (same templates).
- **Hard prerequisite — issue #114** (`dev: replace wave-theme zip bootstrap with git clone in docker/entrypoint.sh`) MUST land first. That issue switches `docker/entrypoint.sh` from a detached `wget`+`unzip` snapshot to a `git clone` directly into `source/Application/views/wave/`, so the directory becomes a real wave-theme working tree from day one. Without #114, theme work for this feature has no clean version-controlled path and devs would have to symlink or forward-port manually. **No template work for this change starts before #114 is merged and validated.**

No breaking changes for existing modules or themes — the footer addition is additive and the database table is new.

## Capabilities

### New Capabilities

- `electronic-revocation`: The full § 356a BGB compliance flow. Covers the footer entry point, the public revocation form, the confirmation step, the consumer confirmation email, persistence of submissions, the admin list/detail views, the operator notification email, the operator notice CMS snippet rendered above the form, and the three configuration switches and language-dependency requirements. (Spam protection / Altcha is intentionally out of scope here and tracked under issue #113.)

### Modified Capabilities

None. No existing OpenSpec capability specs exist in `openspec/specs/`, and the change is purely additive against the current codebase (footer template extension, new controller, new admin module, new DB table, new mail template).

## Impact

**Code (`source/`)**
- `Application/Controller/` — new public controller for the revocation form (display + submit + confirmation)
- `Application/Controller/Admin/` — new admin controller(s) for the submission list and detail view
- `Application/Model/` — new model for the revocation submission entity
- `Application/views/wave/` *(upstream repo: https://github.com/o3-shop/wave-theme — gitignored locally)* — footer template extension, new form templates, new confirmation page template. **Commits land in wave-theme, not in shop-ce.** All wave templates added here must be transferable to `o3-theme` (planned migration before 2026-05-01) — keep them theme-agnostic in structure (translation keys not hardcoded strings, no wave-only CSS class names hardcoded into PHP/controller responses, no controller branches on `oxConfig::getActiveTheme()`).
- `Application/views/o3-theme/` *(upstream repo: https://github.com/o3-shop/o3-Theme — gitignored locally)* — once the `o3-theme` migration phase begins (this week, before 2026-05-01), the same templates ship here as well via a PR against the o3-Theme repo. Listed in the impact for visibility; the actual files land as part of the theme-migration phase, not this change's primary commit.
- `Application/views/admin/` *(part of shop-ce)* — new admin list + detail templates, plus new fields in shop configuration. Commits land in shop-ce.
- `docker/entrypoint.sh` — **NOT modified by this change.** The bootstrap conversion to `git clone` is owned by **issue #114** and ships in a separate, prerequisite PR (see "Hard prerequisite" above and the Repository topology section).
- `Core/Email.php` (or equivalent mail service) — new `sendRevocationConfirmation()` and `sendRevocationOperatorNotification()` methods + Smarty mail templates under `Application/views/{admin,wave}/email/`
- `migration/` — new Doctrine migration creating the `o3revocation` table and seeding one empty + inactive `oxcontents` row per shop for the operator notice snippet (`OXLOADID='o3_revocation_notice'`; all per-language slots `OXACTIVE_*=0` and `OXTITLE_*`/`OXCONTENT_*` empty — `oxcontents` uses suffixed columns for multi-language, so one row per snippet covers every language). Idempotent — never overwrites existing operator content. The migration does **not** seed `oxconfig` rows (see "Setup" below).
- `Setup/Sql/initial_data.sql` *(loaded only on fresh install via `source/Setup/Controller.php:639`)* — add four `INSERT INTO oxconfig` rows for the feature (`blShowRevocationForm = 1`, `blRevocationRequireLogin = 0`, `blRevocationNotifyOperator = 1`, `sRevocationOperatorEmail = ""`), alongside the nine config rows already seeded there. Seeded values match the absent-row code defaults from design D5, so seeding is purely for self-documentation in the database — runtime behaviour is identical to the upgrade case. Fresh installs come up with the legally-safe defaults; upgrades inherit the same defaults from code without seeding. No `shop-demodata-*` repo touched.
- `translations/` (lang files) — new keys for button labels, form labels, validation messages, email subject and body in `de` and `en`

**Dependencies**
- No new runtime dependencies. Spam protection (Altcha) is intentionally **out of scope** here and is tracked under **issue #113** so it can land independently for the revocation form and any other public forms in the shop.
- No new framework or PHP version requirements.

**APIs / Routes**
- New public routes: `?cl=revocation` (form, GET), `?cl=revocation&fnc=submit` (single POST that persists + sends emails), `?cl=revocation&fnc=receipt` (receipt page after the 303 redirect)
- New admin routes under the existing admin navigation tree (e.g. "Customer info" → "Revocations")

**Database**
- New table for revocation submissions (id, name, order identifier, contact channel, optional free-text, submitted_at, language, shop_id). Explicitly **no** IP or User-Agent column — § 356a does not require it, GDPR's data-minimisation principle disfavours it, and our in-flight rate-limit (design D8) reads the request IP for the duration of the check only, against a transient cache counter.
- New `oxconfig` entries: `blShowRevocationForm`, `blRevocationRequireLogin`, `blRevocationNotifyOperator`, `sRevocationOperatorEmail`
- New `oxcontents` row: one per shop, `OXLOADID='o3_revocation_notice'`, all per-language slots inactive and empty (`OXACTIVE_*=0`, `OXTITLE_*=''`, `OXCONTENT_*=''`). `oxcontents` uses suffixed columns for multi-language, so one row covers every language. Operator-editable thereafter via the standard CMS module — the migration must NOT overwrite existing content if the row already exists (idempotent seed via `INSERT IGNORE`, keyed on the `OXLOADID` UNIQUE index).

**Operational / legal**
- Hard deadline: **19 June 2026**. Ships in milestone `v1.6.0`.
- Effort flagged "large", priority "high" on issue #99.
- Requires DPIA review only if operator email notification is enabled with personal data — submission data is already personal data the consumer is voluntarily providing for legal purposes, so storage itself is GDPR-justified by legal obligation.

**Repository topology and dev procedure**

This change spans up to three GitHub repositories. Reviewers and implementers should treat them as one logical change with three coordinated PRs:

| Repo | Role | What lands here | Branch name |
|---|---|---|---|
| `o3-shop/shop-ce` | Application core (this repo) | Controllers, models, DB migration, admin templates, admin lang keys, the `O3_REVOCATION_*` keys for admin/email | `99-add-electronic-revocation-function-b2` |
| `o3-shop/wave-theme` | Storefront theme (current) | Footer link, public form templates, confirmation page template, storefront `O3_REVOCATION_*` keys in wave's `de`/`en` lang files | `99-add-electronic-revocation-function` |
| `o3-shop/o3-Theme` | Storefront theme (incoming, before 2026-05-01) | Same storefront templates and lang keys ported from wave-theme | `99-add-electronic-revocation-function` (during cutover phase) |

**Local dev procedure** for the storefront theme parts (assumes #114 is merged):

1. On a fresh machine, `./docker.sh start` clones wave-theme directly into `source/Application/views/wave/`. The directory IS the wave-theme working tree (delivered by #114).
2. On an existing machine where the directory currently holds the legacy detached zip snapshot: stop docker, `rm -rf source/Application/views/wave/`, restart docker. The #114 bootstrap clones in the working tree.
3. Inside `source/Application/views/wave/`, switch the remote to SSH if you intend to push: `git remote set-url origin git@github.com:o3-shop/wave-theme.git`. Create the feature branch `99-add-electronic-revocation-function`. Edits are committed to wave-theme directly; saves are visible to the running shop instantly.
4. Run `./docker.sh test-all-coverage` from shop-ce as usual.
5. Open the wave-theme PR independently of the shop-ce PR, but cross-link them in both descriptions.
6. For o3-Theme during the migration phase: same shape — the #114 entrypoint already supports an idempotent clone for o3-Theme; activate it once the theme becomes the default.

This change does **not** modify `docker/entrypoint.sh`; that work belongs to #114.

**Out of scope**
- **Altcha / CAPTCHA spam protection** — tracked separately as **issue #113** and will be integrated into the revocation form (and other public forms) once that change ships. The revocation form will be designed so a CAPTCHA hook can be added later without restructuring the controller or template.
- **CSV / PDF export of revocations from the admin list** — operators can read submissions in the standard admin list view; a structured-export feature is a separate concern, will be considered as its own issue if asked. Explicitly off the table for this change, not deferred-but-implied.
- **Automated deletion / retention policy** — no scheduled cron, no time-based purge, no admin "auto-delete after N months" setting. Revocation declarations carry legal weight; an automated cron quietly destroying evidence rows is the wrong default and could expose operators to claims they can't refute. Operators delete manually (or run their own retention job against `o3revocation`) under their own privacy notice and record-keeping policy, exactly as they handle order data today. Admin help text states this clearly. Off the table now and as a follow-up.
- No automated processing of the revocation declaration (the law explicitly says the receipt is only an acknowledgement; substantive review happens manually by the operator).
- No matching/validation of the submitted email against the order — explicitly forbidden by the issue.
- No changes to the existing checkout, order, or account modules.
