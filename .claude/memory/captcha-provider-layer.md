---
name: Core CAPTCHA provider layer (#213)
description: Pluggable CAPTCHA layer in core — architecture, conventions learned, and env gotchas from building it
type: reference
---

Feature #213, branch `213-core-captcha-provider` off b-1.6. Pluggable CAPTCHA layer in `source/Internal/Domain/Captcha/`.

## What it is
- `CaptchaProviderInterface` (DI tag `oxid.captcha.provider`) → collected by `CaptchaProviderLocator`. Built-in providers: `GoogleReCaptchaV2Provider`, `GoogleReCaptchaV3Provider`, `NullCaptchaProvider` (inert; `verify()`=true). Verification wraps the official `google/recaptcha ^1.3` lib behind `CaptchaVerifierInterface` (v3 action+score checked via `setExpectedAction`/`setScoreThreshold` — closes the action-replay gap naive forks have).
- `CaptchaService` (facade): active provider from `CaptchaConfiguration` (oxconfig), per-form toggle, consent policy, head-script de-dup. Consent-gated by default via `CaptchaConsentInterface` (override the binding to integrate usercentrics). Storefront: `ViewConfig::getCaptchaWidget($formId)` rendered into the 7 `captcha_form` theme blocks. Server verify hooked into 7 submit methods (Contact/Suggest/Invite `send()`, ForgotPassword `forgotPassword()`, Newsletter/PriceAlarm `addme()`, UserComponent `registerUser()`). Admin: `CaptchaConfigController` + `captcha_config.tpl` + menu.xml `cl=captcha_config`.
- Consent policy (deliberate, security-scanner will flag as "fail-open"): when consent is REQUIRED but NOT granted, `verifyForForm` returns true (Google can't run without consent → don't block non-consenting users). Merchant sets "require consent"=off for unconditional/fail-closed protection. Documented in spec §5.3.

## Conventions learned (reusable for future Internal/Domain work)
- **Domain services.yaml are imported in `source/Internal/Domain/services.yaml`** (e.g. `- { resource: Captcha/services.yaml }`), NOT in `source/Internal/Container/services.yaml`.
- **Tagged-service collection uses `!tagged <name>`** in this codebase's Symfony DI — `!tagged_iterator` is NOT supported.
- **Reaching the container from a core class:** use `$this->getContainer()` (defined `protected` on `source/Core/Base.php` → `ContainerFactory::getInstance()->getContainer()`). It's mockable in unit tests (partial-mock `getContainer()`); the **compiled container REJECTS `$container->set()`** (`BadMethodCallException`), so the `ViewConfigRevocationTest`-style direct set() does not work — mock `getContainer()` instead. Controllers + components reach it via Base too.
- Services accessed via `container->get(...)` must be `public: true` (CaptchaService, CaptchaConfigurationInterface, CaptchaProviderLocator, CaptchaFormRegistry); autowired/tagged ones stay private.
- Admin config save: `Registry::getConfig()->saveShopConfVar($type,$name,$value,$shopId,'module:captcha')` — the `module:` section is loaded back by `getConfigParam` (LIKE 'module:%'), so the round-trip works.

## Cross-repo (see [[architecture_theme-repos]])
The 14 `captcha_form` template block edits are in the SEPARATE theme repos (o3-theme commit 4f3ee5b, wave 8df3b32), not shop-ce. Storefront `O3_*` lang keys live in shop-ce `source/Application/translations/{de,en}/lang.php` (themes only *use* them). Delivery = 3 coordinated pushes/releases: shop-ce + o3-theme + wave.

## Env gotchas hit during the build
- The `testing-library` satellite's working tree (tracked files) and its `vendor/` can get wiped mid-session → `--fast` tests fail ("Cannot open vendor/o3-shop/testing-library/bootstrap.php"). Fix: `git -C testing-library checkout -- .` then `composer install` inside `testing-library/`.
- BUT that `composer install` regenerates `testing-library/test_config.yml` with DB **placeholders** (`<dbHost>`/`<dbUser>`) → the FULL `test-all-coverage` shop-install can't connect ("Failed to install shop ... Could not connect to '<dbHost>'"). `--fast` unit tests still pass (they print that as a non-fatal warning). To run the full suite, the worktree test DB config must be restored. The PR CI pipeline runs full tests + coverage on a clean env regardless.

See [[issue-tracker-umbrella-repo]].
