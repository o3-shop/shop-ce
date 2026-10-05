# CAPTCHA cookie-tool consent integration — design

**Date:** 2026-06-30
**Feature:** extends #213 (core CAPTCHA provider layer)
**Branch base:** `b-1.6` (includes #183 — fail-closed consent + consent-exempt providers)
**Status:** design approved; rebased onto #183

## Problem

The core CAPTCHA layer (`source/Internal/Domain/Captcha/`) gates the widget behind
a single shop-wide boolean, `blCaptchaRequireConsent`:

- `true` (default) → the widget **never** auto-loads; the visitor only sees a consent
  notice, and (since #183) server-side verification **fails closed** — the submission is
  blocked.
- `false` → the widget always loads and always verifies.

Neither state can read a **per-visitor** consent decision. A merchant running a cookie/
consent tool (Cookiebot, Usercentrics, Borlabs, Klaro, …) cannot make the CAPTCHA switch
on only for visitors who accepted "Google reCAPTCHA" in the banner. Today that requires a
custom module implementing `CaptchaConsentInterface` and rebinding it in DI.

This feature adds a **config-driven cookie consent mode** to core so the common case needs
no module, while keeping the `CaptchaConsentInterface` seam for the hard cases (base64/JSON
cookies, signed values, client-only consent tools).

## Relationship to #183 (already merged)

`#183` made two decisions this design builds on, not against:

1. **Fail closed.** When a (non-exempt) provider's consent is required but **not granted**,
   `CaptchaService::verifyForForm` returns `false` (blocks the submission) and logs it.
   Letting it through would let bots bypass the CAPTCHA entirely. **This design keeps that
   behavior unchanged** — `verifyForForm` is not modified.
2. **Consent-exempt providers.** `ConsentExemptCaptchaProviderInterface` is an opt-in marker
   for providers that make no third-party calls (e.g. a self-hosted proof-of-work CAPTCHA);
   `CaptchaService` loads/verifies them **without** the consent gate. This design preserves
   that bypass: the exempt check runs **before** any consent-mode logic, so cookie mode never
   applies to an exempt provider.

Consequence for this feature: in cookie mode a visitor who **declines** the reCAPTCHA cookie
sees the consent notice and **cannot submit** the protected form until they accept. That is
the intended, consistent fail-closed behavior (confirmed during brainstorming), not a
regression.

## Why not a single universal cookie reader

There is no standard cookie. Each tool writes a different cookie name and a different value
format (Cookiebot `CookieConsent` structured string; Usercentrics base64 JSON; Borlabs
`borlabs-cookie` JSON; custom banners — anything, sometimes localStorage only and never a
server-readable cookie). Core therefore supports the readable-substring case only and leaves
the rest to the interface seam.

## Decisions (locked during brainstorming)

1. **Match rule:** cookie name + marker **substring contains** (no regex, no JSON parsing).
2. **Admin control:** one shop-wide **3-way "Consent mode" dropdown** (not per-provider,
   not an implicit checkbox+fields combination).
3. **Render strategy for cookie mode:** **client-side gate** (script-blocking) — Google's
   script is injected only after the browser confirms the marker. Checked **once on page
   load**, no polling, no tool-specific events → fully tool-agnostic.
4. **Verify strategy:** unchanged from #183 — the server reads the consent state via
   `isConsentGranted()` and **fails closed** when it is not granted. Cookie mode only changes
   *what `isConsentGranted()` returns* (a cookie substring check); it does not touch
   `verifyForForm`.
5. **Structure:** config-driven single default implementation (approach A) — the
   `CaptchaConsentInterface` seam is unchanged; provider output is treated as opaque so the
   generic gate works for any provider; the consent-exempt bypass is preserved.

## The three consent modes (`sCaptchaConsentMode`)

| Mode | Render (non-exempt provider) | Verify (non-exempt provider) | When |
|---|---|---|---|
| `always` | script + widget load immediately | consent granted → token verified | consent handled elsewhere / out of GDPR scope. Equivalent to old `blCaptchaRequireConsent = false`. |
| `gate` *(default)* | widget never auto-loads; notice only | not granted → **blocked** (fail closed) | safe default; shop sends nothing to Google. Once a provider is enabled in this mode, protected forms are blocked until consent is wired — so a merchant moves to `cookie` (or `always`) to make forms usable. Equivalent to old `blCaptchaRequireConsent = true`. |
| `cookie` | widget emitted inert + JS gate; activates only if marker present in browser | server reads same cookie; marker present → verified, absent → **blocked** (fail closed) | the real GDPR case: per-visitor on/off driven by the cookie banner; declining blocks the form. |

`always`/`gate` are static shop-wide decisions; `cookie` is per-visitor and dynamic
(decided fresh in the browser → cache-safe, re-confirmed on the server at submit).
**Consent-exempt providers ignore the mode entirely** and always load/verify.

## Components

### Configuration

`CaptchaConfiguration` (`source/Internal/Domain/Captcha/Configuration/`) gains:

- constants: `MODE_KEY = 'sCaptchaConsentMode'`, `COOKIE_NAME_KEY = 'sCaptchaConsentCookieName'`,
  `COOKIE_MARKER_KEY = 'sCaptchaConsentCookieMarker'`.
- mode value constants on `CaptchaConfigurationInterface`: `MODE_ALWAYS = 'always'`,
  `MODE_GATE = 'gate'`, `MODE_COOKIE = 'cookie'`.
- `getConsentMode(): string` — reads `MODE_KEY`; if unset, derives from the legacy
  `CONSENT_KEY` bool (`true`→`gate`, `false`→`always`); default `gate`. Unknown values
  coerce to `gate`.
- `getConsentCookieName(): string`, `getConsentCookieMarker(): string`.

All keys persist under the `module:captcha` config section (consistent with the existing
keys). `isConsentRequired()` is retained for backward-compat reads but is no longer the
primary control; the legacy bool is read only as the fallback default described above.

### Server-side consent (`ConfigBasedCaptchaConsent::isConsentGranted`)

Becomes a switch on `getConsentMode()`:

- `always` → `true`
- `gate` → `false`
- `cookie` → read `Registry::getUtilsServer()->getOxCookie($name)`; return `true` only if
  `$name !== ''` **and** `$marker !== ''` **and** `str_contains($value, $marker)`. Blank
  name or marker → `false` (fail closed to "no consent", never over-grant).

The `CaptchaConsentInterface` signature is unchanged (`isConsentGranted(Request $request)`);
the cookie is read via `UtilsServer`, not `Request` (Request carries only GET/POST params).
This is the value `CaptchaService::verifyForForm` already consumes — the verify path is
**not modified**; only its truth source changes for cookie mode.

### Render (`CaptchaService::renderForForm`) — exempt first, then mode

Reworked on top of #183's current method (which already does the exempt check + notice):

1. `!isEnabledForForm` → `''`.
2. provider is `ConsentExemptCaptchaProviderInterface` → render widget + head-script directly
   (no gate; #183 behavior preserved).
3. mode `cookie` → **deferred gate** (below).
4. otherwise (`always`/`gate`) → if `!isConsentGranted(getRequest())` → notice; else widget +
   head-script. (Server-side decision, exactly as #183.)

**Deferred gate** (cookie mode, non-exempt), emitted per protected form:
  1. the consent notice div, visible by default;
  2. an inert `<template>` holding the provider's head-script **and** widget markup;
  3. a bootstrap `<script>` carrying cookie name + marker via `json_encode` (safe JS-string
     embedding).

  The bootstrap script and the existing `headScriptEmitted` de-dup flag remain once-per-request.

### JS gate mechanics

On `DOMContentLoaded` the bootstrap reads `document.cookie`, finds the named cookie, and
tests `value.indexOf(marker) !== -1`. If matched: hide the notice, then move the `<template>`
contents into the DOM, **re-creating each `<script>` as a fresh element** (copying
`src`/attributes/inline text) because template/innerHTML-inserted scripts do not execute.
This is the standard consent-tool "unblocking" technique and is provider-agnostic.

The gate is emitted inline by core → ships in **shop-ce only**, no theme-repo change.

**Assumption:** a provider's head script is a normal `<script src>` (true for reCAPTCHA
v2/v3). A provider emitting only inline script still works (inline text is copied), but a
provider with unusual ordering requirements is out of scope for the generic gate.

### Verify (`CaptchaService::verifyForForm`)

**Unchanged from #183.** Exempt provider or consent granted → `provider->verify()`;
otherwise fail closed (`return false`) + info log. Cookie mode reaches this with the cookie
check feeding `isConsentGranted`, so a declining visitor is blocked — consistent with every
other gated path.

### Admin (`CaptchaConfigController` + `captcha_config.tpl`)

- The single "require consent" checkbox is replaced by a **Consent mode `<select>`** with
  three options.
- Two text inputs (cookie name, marker) render hidden and are revealed only when `cookie`
  is selected, reusing the existing JS-toggle pattern already in the template for provider
  credential fields.
- `save()` persists the three new keys as `str` via
  `saveShopConfVar('str', …, 'module:captcha')`; the legacy bool save is dropped.
- New accessors for the template: `getConsentMode()`, `getConsentCookieName()`,
  `getConsentCookieMarker()`.
- New admin lang keys (de + en): mode label, the three option labels, cookie-name and
  marker labels, and a short help line stating that in cookie mode a visitor who has not
  accepted the cookie **cannot submit** the protected form (fail-closed).

## Data flow

```
Page render (cookie mode, non-exempt provider)
  ViewConfig::getCaptchaWidget(formId)
    → CaptchaService::renderForForm(formId)
      → not exempt, mode = cookie → notice (visible) + <template>{headScript+widget}</template> + bootstrap JS
  Browser DOMContentLoaded
    → bootstrap reads document.cookie[name], checks contains(marker)
      → present: hide notice, re-create scripts → Google loads, widget renders
      → absent:  notice stays, Google never contacted

Form submit (cookie mode, non-exempt provider)
  <Form>::send()/addme()/forgotPassword()/registerUser()
    → CaptchaService::verifyForForm(formId, request)   [#183, unchanged]
      → isConsentGranted(request): cookie mode reads UtilsServer cookie + marker
        → granted:     provider->verify(request, formId)
        → not granted: return false (fail closed) + info log
```

## Error handling / edge cases

- **Cookie mode, blank name or marker:** server returns not-granted; client bootstrap
  no-ops → notice stays; submission fails closed. Net behavior = `gate`. Safe.
- **Marker format (real-world):** consent tools store *category* booleans, not a literal
  `recaptcha` key. For Cookiebot the cookie `CookieConsent` decodes to
  `{…,statistics:true,marketing:true,…}` and reCAPTCHA is filed under a category — so the
  marker is the category boolean the merchant assigned, e.g. `marketing:true`. Substring-
  contains correctly separates `marketing:true` from `marketing:false`. The raw cookie is
  URL-encoded: PHP auto-decodes `$_COOKIE` (then `checkParamSpecialChars` escapes quotes /
  `<>&` but leaves `category:true` intact) and the JS bootstrap calls `decodeURIComponent`,
  so both sides match the same plain marker. **Caveat:** a marker containing quotes or
  `<>&` (e.g. `method:'explicit'`) is escaped server-side but not client-side → the two
  paths disagree. Admin guidance: use a plain `category:true` marker. This is documented in
  the admin hint, not enforced in code.
- **Consent-exempt provider in cookie mode:** the exempt check short-circuits before the
  mode branch → widget loads and verifies normally, no gate. (Covered by #183's
  `CaptchaServiceConsentExemptTest`, which must keep passing.)
- **Multiple protected forms on one page:** notice + template are per-form; bootstrap and
  head-script de-dup stay once-per-request.
- **Injection:** cookie name/marker are operator-set and emitted only into a `json_encode`'d
  JS string and admin inputs (escaped); provider widget markup is trusted core/module output;
  notice text stays translated + `htmlspecialchars`'d.
- **Unknown/legacy config:** unknown `sCaptchaConsentMode` coerces to `gate`; missing mode
  with legacy bool maps as described; missing both defaults to `gate`.

## Testing (PHPUnit, mirroring existing captcha tests)

- `CaptchaConfiguration`: mode getters, legacy bool→mode mapping, default, unknown-value
  coercion.
- `ConfigBasedCaptchaConsent`: all three modes; cookie mode with marker present / absent /
  blank-config (mock `UtilsServer`).
- `CaptchaService::renderForForm`: `always`→widget, `gate`→notice-only, `cookie`→notice +
  template + bootstrap, and assert **no live Google `<script>` outside the template** in
  cookie mode. Exempt provider in cookie mode still renders the widget directly (no gate).
- `CaptchaService::verifyForForm`: **not modified** — #183's tests (incl. fail-closed and
  `CaptchaServiceConsentExemptTest`) must keep passing.
- `CaptchaConfigController::save`: persists the three new keys.

## Out of scope

- Any change to `verifyForForm` / the fail-closed decision (owned by #183).
- Regex / JSON / base64 cookie parsing (use a module via `CaptchaConsentInterface`).
- Live re-check on consent change without reload (polling / tool-specific events).
- Per-provider consent configuration (consent is a shop-wide policy).
- Reverse-proxy/full-page-cache vary headers (cookie mode is cache-safe by construction;
  `always`/`gate` are static).

## Delivery

Single repo: **shop-ce** (PHP services, admin controller/template, admin lang keys, inline
JS gate). No theme-repo or storefront-lang changes — the existing `O3_CAPTCHA_CONSENT_NOTICE`
storefront key and `captcha_form` theme blocks are reused unchanged.
