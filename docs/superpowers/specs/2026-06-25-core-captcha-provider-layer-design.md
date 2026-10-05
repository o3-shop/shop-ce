# Design: Pluggable CAPTCHA provider layer in core (Google reCAPTCHA v2 + v3)

- **Date:** 2026-06-25
- **Status:** Approved (design); spec revised for v2+v3 scope
- **Issue:** o3-shop/o3-shop#213
- **Related:** #113 (Altcha — becomes a provider under this layer), #191 (closed external captcha-module — superseded), #99/#144 (revocation anti-spam seam — stays separate for now)

## 1. Summary

Add a small, pluggable CAPTCHA layer to core. The layer ships present and active, but **no provider is enabled by default**. In the admin backend the merchant selects an active provider, enters its credentials, and toggles which forms it protects. Two built-in providers ship: **Google reCAPTCHA v2** ("I'm not a robot" checkbox) and **Google reCAPTCHA v3** (invisible, score-based). Only one provider is active at a time.

The provider seam is a DI-tagged interface, so **third-party developers can ship their own CAPTCHA as a module**: implement `CaptchaProviderInterface`, tag the service `oxid.captcha.provider` in the module's `services.yaml`, activate — and it appears in the admin provider list automatically with its own credential fields (declared via `getConfigFields()`). No core changes required.

## 2. Goals / Non-goals

**Goals**
- A core provider abstraction with interchangeable implementations (Google reCAPTCHA v2 + v3 now; Altcha #113 later).
- Protect the 7 public forms that already expose a `captcha_form` template block, with a **per-form on/off toggle** (the merchant chooses which pages each is active on).
- Support **reCAPTCHA v2 (checkbox)** and **v3 (invisible/score-based)**, admin-selectable, one active at a time, each with its own key pair; v3 adds a configurable score threshold and per-form action names.
- Server-side verification via the official **`google/recaptcha`** library, wrapped behind a `CaptchaVerifierInterface` for testability.
- Consent-gated by default (EU-safe), with a decoupled hook so a consent tool (e.g. usercentrics) can grant consent without core depending on it.
- Module-extensible: new providers appear in the admin dropdown via DI tagging, declaring their own config fields.
- Theme-agnostic widget markup working in both wave (Bootstrap 4) and o3-theme (Bootstrap 5).

**Non-goals (this iteration)**
- reCAPTCHA Enterprise.
- Unifying the revocation form's `RevocationAntiSpamServiceInterface` into this layer (keeps its own richer seam; may adopt a provider later).
- A new database table (configuration lives in `oxconfig`).
- Shipping a usercentrics consent binding (we ship only the hook).

## 3. Decisions (from brainstorming)

| # | Decision |
|---|---|
| D1 | Pluggable provider layer in core; Google reCAPTCHA is the first vendor; Altcha (#113) slots in later behind the same interface. |
| D2 | Cover all 7 forms with an existing `captcha_form` hook, each individually switchable in admin. Revocation form stays separate. |
| D3 | Ship **both** v2 (checkbox) and v3 (invisible/score) as **two providers**; one active at a time. |
| D4 | No provider on by default. Core layer present; admin selects provider + enters credentials to activate. |
| D5 | Consent-gated by default; decoupled `CaptchaConsentInterface` hook; core stays module-free. See consent policy in §5.3. |
| D6 | Third-party providers ship as modules via the `oxid.captcha.provider` DI tag; admin dropdown + credential fields built dynamically. |
| D7 | Server verification uses the official `google/recaptcha` Composer library, wrapped behind `CaptchaVerifierInterface`. |

## 4. Architecture

New domain: **`source/Internal/Domain/Captcha/`** (mirrors `Internal/Domain/Revocation`). DI-wired via `Captcha/services.yaml`.

```
Internal/Domain/Captcha/
  Provider/
    CaptchaProviderInterface.php
    GoogleReCaptchaV2Provider.php
    GoogleReCaptchaV3Provider.php
    NullCaptchaProvider.php
    CaptchaProviderLocator.php
  Verifier/
    CaptchaVerifierInterface.php
    VerificationResult.php              // value object: success(bool), score(?float), action(?string), errorCodes(string[])
    GoogleReCaptchaVerifier.php         // wraps google/recaptcha
  Configuration/
    CaptchaConfigurationInterface.php
    CaptchaConfiguration.php            // reads/writes oxconfig
  Consent/
    CaptchaConsentInterface.php
    ConfigBasedCaptchaConsent.php       // default; overridable by a module
  Field/
    CaptchaConfigField.php              // value object: key, label, type(text|password|checkbox|number), default
  Form/
    CaptchaFormRegistry.php             // canonical list of the 7 protectable form ids + their lang idents
  CaptchaServiceInterface.php
  CaptchaService.php                    // facade used by storefront + controllers
  services.yaml
```

### 4.1 Provider interface (the seam)

```php
interface CaptchaProviderInterface
{
    public function getId(): string;                          // 'google_recaptcha_v2'
    public function getTitle(): string;                       // dropdown label (translatable ident)
    public function isConfigured(): bool;                     // required credentials present?
    /** @return CaptchaConfigField[]  declarative admin fields */
    public function getConfigFields(): array;
    public function getHeadScript(): ?string;                 // <script> loader for the page (or null)
    public function renderWidget(string $formId): string;     // markup injected into the form block
    public function verify(Request $request, string $formId): bool;
}
```

`$formId` flows into `renderWidget()` and `verify()` so v3 can set a per-form **action name** equal to the form id.

- **`GoogleReCaptchaV2Provider`** (`google_recaptcha_v2`): `getConfigFields()` → `siteKey` (text), `secretKey` (password). `getHeadScript()` → `<script src="https://www.google.com/recaptcha/api.js" async defer>`. `renderWidget()` → `<div class="g-recaptcha" data-sitekey="…"></div>`. `verify()` → `CaptchaVerifierInterface::verify(secret, token, ip)` and returns `result->isSuccess()`. Token field: `g-recaptcha-response`.
- **`GoogleReCaptchaV3Provider`** (`google_recaptcha_v3`): `getConfigFields()` → `siteKey` (text), `secretKey` (password), `scoreThreshold` (number, default `0.5`). `getHeadScript()` → `<script src="https://www.google.com/recaptcha/api.js?render=SITEKEY">`. `renderWidget($formId)` → a hidden input `g-recaptcha-response-<formId>` + inline JS that, on the enclosing form's submit, calls `grecaptcha.execute(SITEKEY, {action: '<formId>'})`, writes the token into the hidden field, and submits. `verify($request, $formId)` → `CaptchaVerifierInterface::verify(secret, token, ip, expectedAction=$formId, scoreThreshold=$threshold)`; returns `isSuccess()` (the verifier applies action + threshold). Google requires the v3 badge or a text disclosure — the widget includes the standard disclosure text block.
- **`NullCaptchaProvider`**: the resolved provider when none is selected. `isConfigured()=false`, `getHeadScript()=null`, `renderWidget()=''`, **`verify()=true`** (forms are never blocked when CAPTCHA is off).
- **`CaptchaProviderLocator`**: built from `!tagged oxid.captcha.provider`; `getAll()` (dropdown), `getById(id)` (falls back to `NullCaptchaProvider`).

### 4.1a v3 client flow & prior art

The v3 client flow is adapted from a working reference module (`fensterhandel/fhrecaptcha`, v3/contact-only): on the enclosing form's `submit`, `preventDefault()` → `grecaptcha.ready()` → `grecaptcha.execute(siteKey, {action: '<formId>'})` → write the token into a hidden input → `form.submit()`. Refinements over the reference:
- **Robust form targeting:** the widget script targets its own enclosing `<form>` (the `captcha_form` block renders *inside* the form; resolve via the script's nearest ancestor form) rather than guessing with an `action*="cl=…"` selector.
- **Token field:** v3 uses a hidden input named `recaptcha_token`; v2 uses Google's auto-generated `g-recaptcha-response`. Each provider reads its own field in `verify()`.
- **Security fix:** the reference verifies only `success` + `score` and **never checks the returned `action`** — allowing token replay across actions. Our `GoogleReCaptchaVerifier` passes `setExpectedAction($formId)` so a token minted for another action fails. This is the main reason for D7 (official library).

### 4.2 Verifier (wraps the official library)

```php
interface CaptchaVerifierInterface
{
    public function verify(
        string $secret,
        string $token,
        ?string $remoteIp,
        ?string $expectedAction = null,  // v3
        ?float $scoreThreshold = null    // v3
    ): VerificationResult;
}
```
`GoogleReCaptchaVerifier` builds `new \ReCaptcha\ReCaptcha($secret)`, applies `setExpectedAction()` / `setScoreThreshold()` when provided, calls `verify($token, $remoteIp)`, and maps the response into `VerificationResult`. Unit tests mock `CaptchaVerifierInterface` — **no network**.

### 4.3 Service facade

```php
interface CaptchaServiceInterface
{
    public function isEnabledForForm(string $formId): bool;
    public function renderForForm(string $formId): string;   // head script + widget, consent-gated, or ''
    public function verifyForForm(string $formId, Request $request): bool;
}
```
`CaptchaService` resolves the active provider id from `CaptchaConfiguration`, asks the locator for it, applies the per-form toggle and consent policy (§5.3). It de-duplicates the head script per request (emitted once even if several protected forms render).

### 4.4 Configuration (`oxconfig`)

| Key | Meaning | Default |
|---|---|---|
| `sCaptchaProvider` | active provider id (`''` = none) | `''` |
| `blCaptchaRequireConsent` | gate script behind consent | `true` |
| `blCaptchaForm_contact` … `_invite` (7) | per-form toggles | `false` |
| `sCaptcha_google_recaptcha_v2_siteKey` / `_secretKey` | v2 key pair | — |
| `sCaptcha_google_recaptcha_v3_siteKey` / `_secretKey` | v3 key pair | — |
| `sCaptcha_google_recaptcha_v3_scoreThreshold` | v3 min score (0.0–1.0) | `0.5` |

Provider credentials are stored under keys namespaced by provider id (from `getConfigFields()`), so v2/v3/module providers never collide.

The 7 form ids: `contact`, `newsletter`, `suggest`, `forgotpwd`, `register`, `pricealarm`, `invite`.

## 5. Data flow

### 5.1 Render
Storefront form template reaches `[{block name="captcha_form"}]` → `[{$oViewConf->getCaptchaWidget('contact')}]` → `CaptchaService::renderForForm('contact')` → if active provider configured **and** form enabled **and** consent satisfied (§5.3) → head script (once) + `provider->renderWidget('contact')` → else `''` (or consent notice).

### 5.2 Verify
Controller calls `CaptchaService::verifyForForm('contact', $request)` before processing → active provider `verify($request,'contact')` (Google lib siteverify; v3 checks action+score) → on **false**, the controller sets a form error and re-renders **preserving submitted input** (never persists); on **true** (or form unprotected / consent-not-granted per §5.3), processing continues.

### 5.3 Consent policy
`CaptchaConsentInterface::isConsentGranted(Request): bool`. Default `ConfigBasedCaptchaConsent` returns `true` when `blCaptchaRequireConsent` is off; otherwise it returns whatever a consent integration provides (default: `false`).

- **Consent not required** (`blCaptchaRequireConsent` = false): widget + Google script always load; verify enforces. Hard protection; merchant owns the privacy disclosure.
- **Consent required and granted:** same as above (script loads, verify enforces).
- **Consent required and NOT granted:** **no Google script is loaded** and `renderForForm()` returns a short notice (no widget). For that submission `verifyForForm()` returns **true** (CAPTCHA inactive — we cannot run Google without consent, and we must not block a non-consenting user from using the form). This deliberately trades spam protection for non-consenting visitors in exchange for legal cleanliness; a merchant who wants unconditional protection turns `blCaptchaRequireConsent` off. This resolves the v3-invisible case: v3 simply does not engage until consent is granted.

A consent tool integrates by overriding the `CaptchaConsentInterface` DI binding in its own module — core never references usercentrics.

## 6. Storefront integration

- One new **`ViewConfig::getCaptchaWidget(string $formId): string`** delegating to `CaptchaService`.
- Fill the 7 empty blocks in **both themes** (o3-theme + wave) — 14 edits, each `[{block name="captcha_form"}][{$oViewConf->getCaptchaWidget('<formId>')}][{/block}]`:

| Form id | Template (relative to each theme `tpl/`) |
|---|---|
| `contact` | `form/contact.tpl` |
| `newsletter` | `form/newsletter.tpl` |
| `suggest` | `form/suggest.tpl` |
| `forgotpwd` | `form/forgotpwd_email.tpl` |
| `register` | `form/fieldset/user_billing.tpl` (covers account page **and** checkout user step) |
| `pricealarm` | `form/pricealarm.tpl` |
| `invite` | `form/privatesales/invite.tpl` |

Widget markup is theme-agnostic (no Bootstrap-specific classes) so it renders identically under wave (BS4) and o3-theme (BS5).

## 7. Server-side verification integration (cross-cutting)

A thin **`CaptchaVerificationHelper`** (resolves `CaptchaServiceInterface` from the container) is invoked at the start of each submit method; on failure it sets a form error and preserves input (never persists). Verified hook points:

| Form id | File | Method |
|---|---|---|
| `contact` | `ContactController` | `send()` |
| `suggest` | `SuggestController` | `send()` |
| `forgotpwd` | `ForgotPasswordController` | `forgotPassword()` |
| `newsletter` | `NewsletterController` | `addme()` |
| `pricealarm` | `PriceAlarmController` | `addme()` |
| `invite` | `InviteController` | `send()` |
| `register` | `UserComponent` | `registerUser()` (covers account + checkout user step) |

This is the only genuinely invasive change; everything else is additive, isolated files.

## 8. Admin screen

`Admin/CaptchaConfigController` + `captcha_config.tpl` + `menu.xml` entry + DE/EN lang keys, mirroring `RevocationConfig`:
- **Provider dropdown** built dynamically from `CaptchaProviderLocator::getAll()` (id → `getTitle()`), plus a "None" entry.
- **Provider credential fields** rendered generically from the selected provider's `getConfigFields()` (text/password/number) — so v2 shows its key pair, v3 shows its key pair + threshold, and a module provider shows whatever it declares.
- **7 per-form checkboxes**.
- **"Require consent before loading"** checkbox.

## 9. Module extension contract (deliverable: short doc)

A third-party provider is a module that (1) implements `CaptchaProviderInterface`, (2) registers the service tagged `oxid.captcha.provider` in its `services.yaml`, (3) implements the calls to its own Anbieter in `renderWidget()`/`getHeadScript()`/`verify()` and declares credentials via `getConfigFields()`. On activation it is collected by the locator and appears in the admin dropdown with its own fields. No core or admin-template changes. A `docs/` page documents this with a minimal example.

## 10. Dependencies

Add **`google/recaptcha`** (`^1.3`) to `composer.json` `require`. Dependency-free, BSD-3 (GPL-compatible). Tracked under the dependency/security posture (#164/#165).

## 11. Error handling

- siteverify network/HTTP error or malformed response → `VerificationResult::isSuccess()` false → `verify()` returns **false** (fail-closed for a protected, consented form) and logs at WARNING (`__METHOD__ . ' - '`, quoted values).
- Missing/empty token → `false` without an outbound call.
- Provider id in config not found in locator → `NullCaptchaProvider` (forms behave as unprotected; logged at NOTICE).
- Per the graceful-degradation rule, a misconfigured CAPTCHA must not 500 a public form; it falls back to unprotected + log.

## 12. Testing

**Unit (no network; `CaptchaVerifierInterface` mocked):**
- `GoogleReCaptchaV2Provider` — config fields, isConfigured, widget markup, head script, verify pass/fail, empty-token short-circuit.
- `GoogleReCaptchaV3Provider` — config fields incl. threshold default, widget hidden field + action name = formId, head script carries site key, verify passes formId as expectedAction + threshold, fail on low score.
- `GoogleReCaptchaVerifier` — maps library success/score/action/error-codes into `VerificationResult` (library's `RequestMethod` faked; no network).
- `NullCaptchaProvider` — renders nothing, verify true.
- `CaptchaService` — active-vs-null resolution, per-form toggle, consent policy (required+granted → enforce; required+not-granted → notice + verify true; not-required → enforce), head-script de-dup, unprotected form → verify true.
- `CaptchaConfiguration` — defaults and namespaced provider keys.
- `ConfigBasedCaptchaConsent` — granted only when consent not required (default integration false).
- `CaptchaProviderLocator` — tag collection + getById fallback.
- `CaptchaFormRegistry` — the 7 ids.

**Acceptance / manual (documented checklist, not automated here):**
- Widget visible on all enabled forms in both wave (BS4) and o3-theme (BS5); mobile (≤768px) + desktop (1920px); no JS console errors.
- v2: submitting without solving → server-side rejection with a visible error + preserved input.
- v3: submitting with score < threshold → server-side rejection with a visible error.
- Valid challenge → normal submit. Switching v2↔v3 in admin requires no code change.

cs-fixer clean; the ≥90% coverage gate must stay green.

## 13. Files (summary)

**New:** `Internal/Domain/Captcha/**` (interfaces, v2 + v3 + Null providers, locator, verifier + result, configuration, consent, service, field + form registry VOs, `services.yaml`); `Admin/CaptchaConfigController`; `captcha_config.tpl`; module-extension docs page.
**Edited (additive):** `composer.json` (+google/recaptcha); `ViewConfig` (+1 method); `menu.xml`; admin DE/EN `lang.php`.
**Edited (cross-cutting):** 6 controllers + `UserComponent`; 14 theme templates (2 themes × 7 forms).

## 14. Out of scope / follow-ups

- Altcha provider (delivered under #113 #207–#210, now as a provider in this layer).
- Revocation form adopting a captcha provider (extends #144).
- reCAPTCHA Enterprise.
- An actual usercentrics consent binding (separate module).
