# Design: Altcha CAPTCHA provider — addon module + core consent-exempt marker

- **Date:** 2026-06-26
- **Issue:** o3-shop/o3-shop#113 (sub-issues #207–#210)
- **Builds on:** the pluggable core CAPTCHA layer (o3-shop/shop-ce#180) and the reference `recaptcha-module`
- **Status:** Approved (design); pending spec review

## 1. Summary

Deliver Altcha — an open-source, self-hosted, proof-of-work, GDPR-clean CAPTCHA — as a **provider module** plugging into the core CAPTCHA layer, plus one small **non-breaking core addition** so a privacy-clean provider can load without the consent gate.

#113 predates the core layer and assumed building the integration point, per-form toggles, admin enable/disable, and form wiring from scratch. The core layer (#180) now provides all of that, so this work is far smaller than the issue envisioned: a single `AltchaCaptchaProvider` (+ its HMAC secret, bundled JS, lang) and a marker interface in core.

## 2. Decisions (from brainstorming)

| # | Decision |
|---|---|
| D1 | **Embedded challenge.** The provider generates a fresh HMAC-signed challenge server-side in `renderWidget()` and embeds it in `<altcha-widget>`. No challenge endpoint/route; replay mitigated by a short challenge expiry. |
| D2 | **Consent-exempt via a core marker interface.** Altcha makes no third-party calls / no tracking, so it must load without the consent gate. Add `ConsentExemptCaptchaProviderInterface` to core; `CaptchaService` skips consent for providers implementing it. Non-breaking — existing providers stay consent-gated. |
| D3 | **Module is PHP 8.0+**, depends on `altcha-org/altcha` (altcha-lib-php). Lives in its own repo `o3-shop/altcha-module` (like `recaptcha-module`). |
| D4 | **HMAC secret auto-generated per shop**, stored in `oxconfig` (`sCaptcha_altcha_hmacSecret`), never sent to the frontend; rotatable by clearing the value. Zero operator key setup. |
| D5 | **Self-hosted JS** — the Altcha web component is bundled in the module's `out/` and served from the shop; no CDN. |
| D6 | **Complexity** is a single number config field (the PoW `maxnumber`, default 100000); low/med/high documented as suggested values. (A `select` field-type in core is a possible future enhancement, deliberately out of scope.) |

## 3. Core change (shop-ce, on the #180 branch)

- New marker interface `source/Internal/Domain/Captcha/Provider/ConsentExemptCaptchaProviderInterface` (empty marker; extends nothing).
- `CaptchaService`: in `renderForForm()`/`verifyForForm()`, when the active provider `instanceof ConsentExemptCaptchaProviderInterface`, treat consent as satisfied (skip the gate). All other behavior unchanged.
- Unit test: a consent-exempt stub provider renders/verifies even when consent is required-and-not-granted; a normal provider still gates.
- Doc: note the marker in `docs/captcha-provider-modules.md`.

## 4. Module: `o3-shop/altcha-module`

```
composer.json   (php>=8.0, altcha-org/altcha, type oxideshop-module, psr-4 O3Shop\Altcha\)
metadata.php    (id 'o3altcha')
services.yaml   (AltchaCaptchaProvider tagged oxid.captcha.provider)
src/
  Provider/AltchaCaptchaProvider.php
  Secret/HmacSecretStore.php          (get-or-generate per-shop secret)
  Challenge/AltchaChallengeFactory.php (wraps altcha-lib challenge creation)
out/altcha.min.js                      (bundled web component, self-hosted)
Application/views/admin/{en,de}/module_options.php   (provider title + complexity label)
Application/translations/{en,de}/...   (storefront widget strings: loading/verifying/verified/error)
tests/...                              (provider + secret + challenge unit tests)
README.md
```

### `AltchaCaptchaProvider implements CaptchaProviderInterface, ConsentExemptCaptchaProviderInterface`
- `getId()` → `'altcha'`; `getTitle()` → `'O3_ALTCHA_PROVIDER_TITLE'`.
- `getConfigFields()` → `[ new CaptchaConfigField('complexity', 'O3_ALTCHA_COMPLEXITY', TYPE_NUMBER, '100000') ]`.
- `isConfigured()` → `true` (the secret auto-generates; no keys needed).
- `getHeadScript()` → a `<script type="module">` (or `defer`) loading the bundled web component from the module asset URL (resolved via the module out path); emitted once per request by the core service.
- `renderWidget(string $formId)` → build a signed challenge with `AltchaChallengeFactory` (HMAC secret from `HmacSecretStore`, `maxnumber` = complexity, `expires` = now + short TTL), then output `<altcha-widget>` carrying the embedded challenge JSON + a localized `strings` attribute. The submitted solution posts as the `altcha` field.
- `verify(Request $request, string $formId)` → read the `altcha` payload; `Altcha\Altcha::verifySolution($payload, $secret, checkExpires: true)`; return the boolean. Empty/missing payload → `false` without throwing.

### `HmacSecretStore`
- `getSecret(): string` — reads `sCaptcha_altcha_hmacSecret` via `CaptchaConfigurationInterface`/`Registry::getConfig()`; if empty, generates `bin2hex(random_bytes(32))`, persists it (`saveShopConfVar`, `module:captcha` section), returns it. Never rendered client-side.

### Localization
- Admin idents (`O3_ALTCHA_PROVIDER_TITLE`, `O3_ALTCHA_COMPLEXITY`) in the module's `module_options.php` (de+en).
- Storefront widget strings (de+en) injected into the widget's `strings` attribute via the shop language engine.

## 5. Data flow

**Render:** core `captcha_form` block → `ViewConfig::getCaptchaWidget` → `CaptchaService::renderForForm($formId)` → Altcha is consent-exempt (skip gate) → emit bundled JS once → `AltchaCaptchaProvider::renderWidget` → signed challenge embedded in `<altcha-widget>`.
**Submit:** controller hook → `CaptchaService::verifyForForm($formId,$request)` → `AltchaCaptchaProvider::verify` → altcha-lib verifies solution + HMAC + expiry against the per-shop secret → bool. On failure the controller re-renders with the `O3_CAPTCHA_FAILED` error (core behavior).

**No external network calls** at any point (bundled JS, server-side challenge + verify) — satisfies #113's outbound-blocked acceptance criterion.

## 6. Error handling
- Missing/empty `altcha` payload → `verify()` returns `false` (no exception).
- Malformed payload / lib throws → caught, `verify()` returns `false`, logged WARNING (`__METHOD__ . ' - '`).
- Missing/unwritable secret store → log + treat as not-configured (graceful; never 500 a public form), consistent with the core graceful-degradation rule.

## 7. Testing
- **Module unit tests:** `verify()` accepts a correctly-solved payload and rejects a tampered/expired/empty one (fixed test secret, lib-generated payload); `getConfigFields()` exposes `complexity` with default; `renderWidget()` embeds a challenge + the form is the `altcha` field; `HmacSecretStore` generates-once and is stable.
- **Core unit test:** `CaptchaService` skips the consent gate for a `ConsentExemptCaptchaProviderInterface` provider and still gates a normal one.
- cs-fixer clean; CI green; module tests run via the same bootstrap approach as `recaptcha-module`.

## 8. Out of scope
- Challenge endpoint + per-IP throttling (D1 chose embedded challenges).
- A `select` field-type in core (D6 uses a number field).
- Secret-rotation admin UI (rotate = clear the stored value; auto-regenerates).
- Packagist publishing / version tags (release-time).
