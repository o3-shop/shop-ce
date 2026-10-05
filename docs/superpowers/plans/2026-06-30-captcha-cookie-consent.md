# CAPTCHA Cookie-Tool Consent Integration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a merchant drive the core CAPTCHA widget on/off per visitor from their cookie-consent tool, configured entirely in admin — no module required for the common (readable-cookie) case.

**Architecture:** Replace the single `blCaptchaRequireConsent` boolean with a 3-way consent mode (`always` / `gate` / `cookie`). `gate`/`always` are static; `cookie` mode emits the widget inert behind a client-side script-blocking gate (Google's script loads only after the browser confirms a consent-cookie marker) and re-checks the same cookie server-side at submit. Builds on **#183** (already merged): the **fail-closed** `verifyForForm` and the `ConsentExemptCaptchaProviderInterface` bypass are preserved untouched — cookie mode only changes what `isConsentGranted()` returns, so a declining visitor is blocked (fail closed), consistent with #183. The `CaptchaConsentInterface` seam is unchanged, so non-substring cookie formats can still be handled by a module.

**Base:** `b-1.6` at or after #183 (`c4b107f8`). If `CaptchaService::verifyForForm` does **not** already contain the fail-closed `return false` + `ConsentExemptCaptchaProviderInterface` check, STOP — the base is wrong.

**Tech Stack:** PHP 7.4, OXID/O3 framework (`Registry`, `oxconfig`, `UtilsServer`), Symfony DI (YAML), Smarty 2.6 admin template, PHPUnit 9. All commands run inside Docker via `./docker.sh`.

**Spec:** `docs/superpowers/specs/2026-06-30-captcha-cookie-consent-design.md`

---

## Preconditions

- Docker env is up: `./docker.sh start`
- Single-file fast test runner: `./docker.sh test --fast <path>`
- Work on a feature branch off `b-1.6` (e.g. `213-captcha-cookie-consent`), not on `b-1.6` directly.

## File Structure

**Modify:**
- `source/Internal/Domain/Captcha/Configuration/CaptchaConfigurationInterface.php` — add mode constants + 3 getter signatures.
- `source/Internal/Domain/Captcha/Configuration/CaptchaConfiguration.php` — storage-key constants + getter implementations + legacy mapping.
- `source/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsent.php` — switch on mode; cookie read via `UtilsServer`.
- `source/Internal/Domain/Captcha/CaptchaService.php` — `renderForForm()` branches on mode; new private deferred-gate + bootstrap emitters.
- `source/Application/Controller/Admin/CaptchaConfigController.php` — `save()` persists the 3 new keys; new template accessors.
- `source/Application/views/admin/tpl/captcha_config.tpl` — mode dropdown + cookie fields + JS reveal.
- `source/Application/views/admin/en/lang.php` and `.../de/lang.php` — new admin lang keys.

**Modify (tests):**
- `tests/Unit/Internal/Domain/Captcha/Configuration/CaptchaConfigurationTest.php`
- `tests/Unit/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsentTest.php`
- `tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`
- `tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php`

No new files. No theme-repo or storefront-lang changes (existing `O3_CAPTCHA_CONSENT_NOTICE` + `captcha_form` blocks are reused).

---

## Task 1: Consent-mode configuration

**Files:**
- Modify: `source/Internal/Domain/Captcha/Configuration/CaptchaConfigurationInterface.php`
- Modify: `source/Internal/Domain/Captcha/Configuration/CaptchaConfiguration.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Configuration/CaptchaConfigurationTest.php`

- [ ] **Step 1: Write the failing tests**

Append these methods to `CaptchaConfigurationTest` (class already mocks `Config` via `$this->params`):

```php
    public function testConsentModeDefaultsToGateWhenNothingConfigured(): void
    {
        $this->assertSame(
            CaptchaConfiguration::MODE_GATE,
            (new CaptchaConfiguration())->getConsentMode()
        );
    }

    public function testConsentModeReadsExplicitValue(): void
    {
        $this->params['sCaptchaConsentMode'] = 'cookie';
        $this->assertSame(
            CaptchaConfiguration::MODE_COOKIE,
            (new CaptchaConfiguration())->getConsentMode()
        );
    }

    public function testUnknownConsentModeCoercesToGate(): void
    {
        $this->params['sCaptchaConsentMode'] = 'bogus';
        $this->assertSame(
            CaptchaConfiguration::MODE_GATE,
            (new CaptchaConfiguration())->getConsentMode()
        );
    }

    public function testConsentModeFallsBackToLegacyBoolWhenModeUnset(): void
    {
        // Legacy "require consent = false" → always-load.
        $this->params['blCaptchaRequireConsent'] = false;
        $this->assertSame(
            CaptchaConfiguration::MODE_ALWAYS,
            (new CaptchaConfiguration())->getConsentMode()
        );

        // Legacy "require consent = true" → gate (the safe default).
        $this->params['blCaptchaRequireConsent'] = true;
        $this->assertSame(
            CaptchaConfiguration::MODE_GATE,
            (new CaptchaConfiguration())->getConsentMode()
        );
    }

    public function testConsentCookieNameAndMarkerDefaultToEmpty(): void
    {
        $cfg = new CaptchaConfiguration();
        $this->assertSame('', $cfg->getConsentCookieName());
        $this->assertSame('', $cfg->getConsentCookieMarker());
    }

    public function testConsentCookieNameAndMarkerAreRead(): void
    {
        $this->params['sCaptchaConsentCookieName'] = 'CookieConsent';
        $this->params['sCaptchaConsentCookieMarker'] = 'recaptcha:true';
        $cfg = new CaptchaConfiguration();
        $this->assertSame('CookieConsent', $cfg->getConsentCookieName());
        $this->assertSame('recaptcha:true', $cfg->getConsentCookieMarker());
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Configuration/CaptchaConfigurationTest.php`
Expected: FAIL — `Error: Undefined constant ...::MODE_GATE` / `Call to undefined method ...getConsentMode()`.

- [ ] **Step 3: Add the contract to the interface**

In `CaptchaConfigurationInterface.php`, add the mode constants and method signatures inside the interface body:

```php
interface CaptchaConfigurationInterface
{
    public const MODE_ALWAYS = 'always';
    public const MODE_GATE   = 'gate';
    public const MODE_COOKIE = 'cookie';

    public function getActiveProviderId(): string;

    public function isFormEnabled(string $formId): bool;

    public function isConsentRequired(): bool;

    public function getConsentMode(): string;

    public function getConsentCookieName(): string;

    public function getConsentCookieMarker(): string;

    /** @return mixed */
    public function getProviderSetting(string $providerId, string $key, $default = null);
}
```

- [ ] **Step 4: Implement in the concrete class**

In `CaptchaConfiguration.php`, add the storage-key constants next to the existing ones:

```php
    public const PROVIDER_KEY = 'sCaptchaProvider';
    public const CONSENT_KEY = 'blCaptchaRequireConsent';
    public const FORM_PREFIX = 'blCaptchaForm_';
    public const PROVIDER_SETTING_PREFIX = 'sCaptcha_';
    public const MODE_KEY = 'sCaptchaConsentMode';
    public const COOKIE_NAME_KEY = 'sCaptchaConsentCookieName';
    public const COOKIE_MARKER_KEY = 'sCaptchaConsentCookieMarker';
```

Add the three methods (anywhere in the class body, e.g. after `isConsentRequired()`):

```php
    public function getConsentMode(): string
    {
        $mode = (string) Registry::getConfig()->getConfigParam(self::MODE_KEY, '');

        if ($mode === '') {
            // Backward-compat: derive from the legacy boolean so existing
            // installs never silently flip to an insecure (always-on) state.
            $legacy = Registry::getConfig()->getConfigParam(self::CONSENT_KEY, true);
            return $legacy ? self::MODE_GATE : self::MODE_ALWAYS;
        }

        if (!in_array($mode, [self::MODE_ALWAYS, self::MODE_GATE, self::MODE_COOKIE], true)) {
            return self::MODE_GATE;
        }

        return $mode;
    }

    public function getConsentCookieName(): string
    {
        return (string) Registry::getConfig()->getConfigParam(self::COOKIE_NAME_KEY, '');
    }

    public function getConsentCookieMarker(): string
    {
        return (string) Registry::getConfig()->getConfigParam(self::COOKIE_MARKER_KEY, '');
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Configuration/CaptchaConfigurationTest.php`
Expected: PASS (all tests, including the pre-existing ones).

- [ ] **Step 6: Commit**

```bash
git add source/Internal/Domain/Captcha/Configuration tests/Unit/Internal/Domain/Captcha/Configuration
git commit -m "feat(#213): add 3-way CAPTCHA consent mode config (always/gate/cookie) with legacy bool fallback"
```

---

## Task 2: Server-side consent decision

**Files:**
- Modify: `source/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsent.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsentTest.php`

- [ ] **Step 1: Write the failing tests**

Replace the body of `ConfigBasedCaptchaConsentTest` with the following (keeps the two legacy assertions re-expressed as modes, adds cookie coverage). Note the imports for `Config`, `Registry`, `UtilsServer`:

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Consent;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\ConfigBasedCaptchaConsent;
use PHPUnit\Framework\TestCase;

class ConfigBasedCaptchaConsentTest extends TestCase
{
    protected function tearDown(): void
    {
        Registry::set(UtilsServer::class, null);
    }

    private function config(string $mode, string $cookieName = '', string $marker = ''): CaptchaConfigurationInterface
    {
        $cfg = $this->createMock(CaptchaConfigurationInterface::class);
        $cfg->method('getConsentMode')->willReturn($mode);
        $cfg->method('getConsentCookieName')->willReturn($cookieName);
        $cfg->method('getConsentCookieMarker')->willReturn($marker);
        return $cfg;
    }

    private function withCookie(string $name, ?string $value): void
    {
        $utils = $this->createMock(UtilsServer::class);
        $utils->method('getOxCookie')->willReturnCallback(
            fn ($n = null) => $n === $name ? $value : null
        );
        Registry::set(UtilsServer::class, $utils);
    }

    public function testAlwaysModeGrantsConsent(): void
    {
        $consent = new ConfigBasedCaptchaConsent($this->config(CaptchaConfigurationInterface::MODE_ALWAYS));
        $this->assertTrue($consent->isConsentGranted($this->createMock(Request::class)));
    }

    public function testGateModeNeverGrantsConsent(): void
    {
        $consent = new ConfigBasedCaptchaConsent($this->config(CaptchaConfigurationInterface::MODE_GATE));
        $this->assertFalse($consent->isConsentGranted($this->createMock(Request::class)));
    }

    // Real Cookiebot `CookieConsent` value shape: PHP has already URL-decoded the
    // raw cookie ($_COOKIE) so commas are literal here. reCAPTCHA is filed under a
    // category, so the merchant's marker is the category boolean (e.g. 'marketing:true'),
    // NOT a literal 'recaptcha' key. Substring-contains distinguishes ':true' from ':false'.
    private const COOKIEBOT_ACCEPTED =
        "{stamp:'abc==',necessary:true,preferences:true,statistics:true,marketing:true,method:'explicit',ver:1,utc:1782201155329,region:'de'}";
    private const COOKIEBOT_DECLINED =
        "{stamp:'abc==',necessary:true,preferences:false,statistics:false,marketing:false,method:'explicit',ver:1,utc:1782201155329,region:'de'}";

    public function testCookieModeGrantsWhenMarkerPresentInCookieValue(): void
    {
        $this->withCookie('CookieConsent', self::COOKIEBOT_ACCEPTED);
        $consent = new ConfigBasedCaptchaConsent(
            $this->config(CaptchaConfigurationInterface::MODE_COOKIE, 'CookieConsent', 'marketing:true')
        );
        $this->assertTrue($consent->isConsentGranted($this->createMock(Request::class)));
    }

    public function testCookieModeDeniesWhenMarkerAbsent(): void
    {
        // Declined: the value has 'marketing:false', which does NOT contain 'marketing:true'.
        $this->withCookie('CookieConsent', self::COOKIEBOT_DECLINED);
        $consent = new ConfigBasedCaptchaConsent(
            $this->config(CaptchaConfigurationInterface::MODE_COOKIE, 'CookieConsent', 'marketing:true')
        );
        $this->assertFalse($consent->isConsentGranted($this->createMock(Request::class)));
    }

    public function testCookieModeDeniesWhenCookieMissing(): void
    {
        $this->withCookie('CookieConsent', null);
        $consent = new ConfigBasedCaptchaConsent(
            $this->config(CaptchaConfigurationInterface::MODE_COOKIE, 'CookieConsent', 'marketing:true')
        );
        $this->assertFalse($consent->isConsentGranted($this->createMock(Request::class)));
    }

    public function testCookieModeDeniesWhenConfigIncomplete(): void
    {
        // Blank marker must never over-grant, even if the cookie exists.
        $this->withCookie('CookieConsent', 'anything');
        $consent = new ConfigBasedCaptchaConsent(
            $this->config(CaptchaConfigurationInterface::MODE_COOKIE, 'CookieConsent', '')
        );
        $this->assertFalse($consent->isConsentGranted($this->createMock(Request::class)));
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsentTest.php`
Expected: FAIL — `isConsentGranted()` still keys off `isConsentRequired()`, so the new mode/cookie assertions fail.

- [ ] **Step 3: Rewrite `isConsentGranted` to switch on mode**

Replace the body of `ConfigBasedCaptchaConsent.php` (keep the file header/namespace) with:

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;

final class ConfigBasedCaptchaConsent implements CaptchaConsentInterface
{
    /** @var CaptchaConfigurationInterface */
    private $configuration;

    public function __construct(CaptchaConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isConsentGranted(Request $request): bool
    {
        switch ($this->configuration->getConsentMode()) {
            case CaptchaConfigurationInterface::MODE_ALWAYS:
                return true;
            case CaptchaConfigurationInterface::MODE_COOKIE:
                return $this->cookieMarkerPresent();
            case CaptchaConfigurationInterface::MODE_GATE:
            default:
                return false;
        }
    }

    private function cookieMarkerPresent(): bool
    {
        $name = $this->configuration->getConsentCookieName();
        $marker = $this->configuration->getConsentCookieMarker();

        if ($name === '' || $marker === '') {
            return false;
        }

        $value = (string) Registry::getUtilsServer()->getOxCookie($name);

        return $value !== '' && strpos($value, $marker) !== false;
    }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsentTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add source/Internal/Domain/Captcha/Consent tests/Unit/Internal/Domain/Captcha/Consent
git commit -m "feat(#213): drive server-side CAPTCHA consent from mode + cookie marker"
```

---

## Task 3: Render branching + client-side script-blocking gate

**Files:**
- Modify: `source/Internal/Domain/Captcha/CaptchaService.php`
- Test: `tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`

**Build on the CURRENT (#183) `CaptchaService`, do not revert it.** The merged code already: (a) checks `ConsentExemptCaptchaProviderInterface` and bypasses the gate for exempt providers, and (b) **fails closed** in `verifyForForm` (returns `false` + logs when consent is required but not granted).

This task changes **only `renderForForm`**, layering the cookie-mode deferred gate on top of #183's logic. The new order is: exempt provider → widget directly; else mode `cookie` → deferred gate; else (`always`/`gate`) → #183's server-side `isConsentGranted()` decision (notice vs widget). **`verifyForForm` is NOT modified** — cookie mode only changes what `isConsentGranted()` returns (Task 2), so a declining visitor is blocked (fail closed), consistent with #183.

The exempt check must stay **first** so `CaptchaServiceConsentExemptTest` keeps passing (its config mock does not stub `getConsentMode`).

- [ ] **Step 1: Update the test helper and migrate existing render tests**

In `CaptchaServiceTest.php`, replace the `service()` helper so the config mock also stubs the mode getters, then migrate the two render tests. New helper:

```php
    private function service(CaptchaProviderInterface $provider, array $opts): CaptchaService
    {
        $config = $this->createMock(CaptchaConfigurationInterface::class);
        $config->method('getActiveProviderId')->willReturn($opts['active'] ?? $provider->getId());
        $config->method('isFormEnabled')->willReturnCallback(fn ($f) => $opts['enabled'] ?? true);
        $config->method('getConsentMode')->willReturn($opts['mode'] ?? CaptchaConfigurationInterface::MODE_ALWAYS);
        $config->method('getConsentCookieName')->willReturn($opts['cookieName'] ?? 'CookieConsent');
        $config->method('getConsentCookieMarker')->willReturn($opts['marker'] ?? 'marketing:true');

        $consent = $this->createMock(CaptchaConsentInterface::class);
        $consent->method('isConsentGranted')->willReturn($opts['consent'] ?? true);

        $locator = new CaptchaProviderLocator([$provider], new NullCaptchaProvider());
        return new CaptchaService($locator, $config, $consent);
    }
```

Migrate the existing notice test — its current name is `testConsentRequiredButNotGrantedShowsNoticeAndBlocksVerification` and it asserts **fail closed** (`verifyForForm` returns `false`). Keep the fail-closed assertion (do NOT change it to fail-open) and make the mode explicit as `gate`:

```php
    public function testConsentRequiredButNotGrantedShowsNoticeAndBlocksVerification(): void
    {
        $svc = $this->service($this->provider('p'), [
            'mode' => CaptchaConfigurationInterface::MODE_GATE,
            'consent' => false,
        ]);
        $html = $svc->renderForForm('contact');
        $this->assertStringContainsString('o3-captcha-consent-notice', $html);
        $this->assertStringNotContainsString('id="widget"', $html);
        // Fail closed (#183): without consent the captcha cannot load, so submission is blocked.
        $this->assertFalse($svc->verifyForForm('contact', $this->createMock(Request::class)));
    }
```

The widget-renders test (`testEnabledAndConsentedRendersWidgetAndEnforces`) and `testHeadScriptEmittedOncePerRequest` keep working unchanged because the helper now defaults `mode` to `MODE_ALWAYS`.

- [ ] **Step 2: Add the new cookie-mode tests**

Append to `CaptchaServiceTest.php`:

```php
    public function testCookieModeEmitsDeferredGateNotLiveWidget(): void
    {
        $svc = $this->service($this->provider('p'), [
            'mode' => CaptchaConfigurationInterface::MODE_COOKIE,
        ]);
        $html = $svc->renderForForm('contact');

        // Visitor sees the notice and a deferred gate, not a live widget.
        $this->assertStringContainsString('o3-captcha-gate', $html);
        $this->assertStringContainsString('o3-captcha-consent-notice', $html);
        $this->assertStringContainsString('<template', $html);

        // The provider markup lives ONLY inside the <template> (inert until consent).
        $outside = $this->stripTemplates($html);
        $this->assertStringNotContainsString('id="widget"', $outside);
        $this->assertStringNotContainsString('id="head"', $outside);

        // ...but it IS present overall (inside the template).
        $this->assertStringContainsString('id="widget"', $html);
    }

    public function testCookieModeBootstrapCarriesCookieNameAndMarker(): void
    {
        $svc = $this->service($this->provider('p'), [
            'mode' => CaptchaConfigurationInterface::MODE_COOKIE,
            'cookieName' => 'CookieConsent',
            'marker' => 'marketing:true',
        ]);
        $html = $svc->renderForForm('contact');

        $this->assertStringContainsString('"CookieConsent"', $html);
        $this->assertStringContainsString('"marketing:true"', $html);
    }

    public function testCookieModeBootstrapEmittedOncePerRequest(): void
    {
        $svc = $this->service($this->provider('p'), [
            'mode' => CaptchaConfigurationInterface::MODE_COOKIE,
        ]);
        $first = $svc->renderForForm('contact');
        $second = $svc->renderForForm('newsletter');

        // Each form gets its own gate container...
        $this->assertStringContainsString('o3-captcha-gate', $second);
        // ...but the shared bootstrap function is emitted only once.
        $this->assertStringContainsString('o3CaptchaConsentGate', $first);
        $this->assertStringNotContainsString('o3CaptchaConsentGate', $second);
    }

    public function testExemptProviderInCookieModeRendersWidgetWithoutGate(): void
    {
        // A consent-exempt provider (#183) must bypass the cookie gate entirely.
        $exempt = new class () implements CaptchaProviderInterface, ConsentExemptCaptchaProviderInterface {
            public function getId(): string
            {
                return 'exempt';
            }
            public function getTitle(): string
            {
                return 'Exempt';
            }
            public function isConfigured(): bool
            {
                return true;
            }
            public function getConfigFields(): array
            {
                return [];
            }
            public function getHeadScript(): ?string
            {
                return '<script id="head"></script>';
            }
            public function renderWidget(string $formId): string
            {
                return '<div id="widget"></div>';
            }
            public function verify(Request $request, string $formId): bool
            {
                return true;
            }
        };

        $svc = $this->service($exempt, ['mode' => CaptchaConfigurationInterface::MODE_COOKIE]);
        $html = $svc->renderForForm('contact');

        // Widget renders live (no deferred gate, no template) despite cookie mode.
        $this->assertStringContainsString('id="widget"', $html);
        $this->assertStringNotContainsString('o3-captcha-gate', $html);
        $this->assertStringNotContainsString('<template', $html);
    }
```

This test needs two imports at the top of the test file (add if absent):

```php
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface;
```

Add this helper method to the test class (removes every `<template>...</template>` block so we can assert on the remainder):

```php
    private function stripTemplates(string $html): string
    {
        return (string) preg_replace('#<template.*?</template>#s', '', $html);
    }
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`
Expected: FAIL — `renderForForm` does not yet emit `o3-captcha-gate` / `<template>` / the bootstrap.

- [ ] **Step 4: Implement the mode branch + gate emitters**

In `CaptchaService.php`, add a `$bootstrapEmitted` flag next to `$headScriptEmitted`:

```php
    /** @var bool */
    private $headScriptEmitted = false;
    /** @var bool */
    private $bootstrapEmitted = false;
```

Replace **only** `renderForForm()` (leave `verifyForForm()` and `activeProvider()` exactly as #183 has them). The new body keeps the exempt-provider short-circuit first, adds the cookie branch, and falls back to #183's server-side consent decision:

```php
    public function renderForForm(string $formId): string
    {
        if (!$this->isEnabledForForm($formId)) {
            return '';
        }

        $provider = $this->activeProvider();

        // #183: consent-exempt providers make no third-party calls → never gated.
        if ($provider instanceof ConsentExemptCaptchaProviderInterface) {
            return $this->renderWidgetWithHeadScript($provider, $formId);
        }

        // Cookie mode defers the load/consent decision to the browser so the markup
        // is cache-safe and the provider's script is never emitted up-front.
        if ($this->configuration->getConsentMode() === CaptchaConfigurationInterface::MODE_COOKIE) {
            return $this->renderDeferredGate($provider, $formId);
        }

        // always → consent granted → widget; gate → not granted → notice (#183).
        if (!$this->consent->isConsentGranted(Registry::getRequest())) {
            return $this->renderNotice();
        }

        return $this->renderWidgetWithHeadScript($provider, $formId);
    }
```

Add the private helpers (the inlined #183 widget/notice markup is extracted into these so all three render paths share it):

```php
    private function renderWidgetWithHeadScript(CaptchaProviderInterface $provider, string $formId): string
    {
        $html = '';
        if (!$this->headScriptEmitted) {
            $script = $provider->getHeadScript();
            if ($script !== null) {
                $html .= $script;
                $this->headScriptEmitted = true;
            }
        }
        return $html . $provider->renderWidget($formId);
    }

    private function renderNotice(): string
    {
        return '<div class="o3-captcha-consent-notice">'
            . htmlspecialchars((string) Registry::getLang()->translateString('O3_CAPTCHA_CONSENT_NOTICE'), ENT_QUOTES)
            . '</div>';
    }

    private function renderDeferredGate(CaptchaProviderInterface $provider, string $formId): string
    {
        // The provider's head script is included in the FIRST gate's template
        // only; the bootstrap re-creates whatever <script> nodes it finds.
        $deferred = '';
        if (!$this->headScriptEmitted) {
            $script = $provider->getHeadScript();
            if ($script !== null) {
                $deferred .= $script;
                $this->headScriptEmitted = true;
            }
        }
        $deferred .= $provider->renderWidget($formId);

        $gate = '<div class="o3-captcha-gate">'
            . $this->renderNotice()
            . '<template class="o3-captcha-deferred">' . $deferred . '</template>'
            . '</div>';

        return $gate . $this->bootstrapScript();
    }

    private function bootstrapScript(): string
    {
        if ($this->bootstrapEmitted) {
            return '';
        }
        $this->bootstrapEmitted = true;

        $cookieName = json_encode($this->configuration->getConsentCookieName());
        $marker = json_encode($this->configuration->getConsentCookieMarker());

        return '<script>'
            . '(function(){'
            . 'var COOKIE=' . $cookieName . ',MARKER=' . $marker . ';'
            . 'function o3CaptchaConsentGate(){'
            . 'if(!COOKIE||!MARKER){return;}'
            . 'var parts=document.cookie?document.cookie.split(";"):[];var granted=false;'
            . 'for(var i=0;i<parts.length;i++){var c=parts[i].replace(/^\s+/,"");'
            . 'if(c.indexOf(COOKIE+"=")===0){'
            . 'if(decodeURIComponent(c.substring(COOKIE.length+1)).indexOf(MARKER)!==-1){granted=true;}break;}}'
            . 'if(!granted){return;}'
            . 'var gates=document.querySelectorAll(".o3-captcha-gate");'
            . 'for(var g=0;g<gates.length;g++){'
            . 'var tpl=gates[g].querySelector("template.o3-captcha-deferred");if(!tpl){continue;}'
            . 'var notice=gates[g].querySelector(".o3-captcha-consent-notice");if(notice){notice.style.display="none";}'
            . 'var frag=tpl.content.cloneNode(true);var old=frag.querySelectorAll("script");'
            . 'for(var s=0;s<old.length;s++){var n=document.createElement("script");'
            . 'for(var a=0;a<old[s].attributes.length;a++){n.setAttribute(old[s].attributes[a].name,old[s].attributes[a].value);}'
            . 'n.text=old[s].textContent;old[s].parentNode.replaceChild(n,old[s]);}'
            . 'gates[g].appendChild(frag);}'
            . '}'
            . 'if(document.readyState!=="loading"){o3CaptchaConsentGate();}'
            . 'else{document.addEventListener("DOMContentLoaded",o3CaptchaConsentGate);}'
            . '})();'
            . '</script>';
    }
```

All types used here (`CaptchaConfigurationInterface`, `CaptchaProviderInterface`, `ConsentExemptCaptchaProviderInterface`, `Registry`) are **already imported** in #183's `CaptchaService.php`. Verify the `use` block and do not duplicate.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`
Expected: PASS (migrated + new tests).

- [ ] **Step 6: Commit**

```bash
git add source/Internal/Domain/Captcha/CaptchaService.php tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php
git commit -m "feat(#213): client-side script-blocking gate for cookie consent mode"
```

---

## Task 4: Admin controller — persist the new keys + template accessors

**Files:**
- Modify: `source/Application/Controller/Admin/CaptchaConfigController.php`
- Test: `tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php`

- [ ] **Step 1: Write the failing test**

Append to `CaptchaConfigControllerTest`:

```php
    public function testSavePersistsConsentModeAndCookieFields(): void
    {
        $this->requestParams = [
            CaptchaConfiguration::PROVIDER_KEY => '',
            CaptchaConfiguration::MODE_KEY => 'cookie',
            CaptchaConfiguration::COOKIE_NAME_KEY => 'CookieConsent',
            CaptchaConfiguration::COOKIE_MARKER_KEY => 'recaptcha:true',
        ];
        $this->mockConfigAndRequest();

        $controller = $this->makeController('');
        $controller->save();

        $byName = $this->indexBy($this->savedConfVars, 1);

        $this->assertSame('cookie', $byName[CaptchaConfiguration::MODE_KEY][2]);
        $this->assertSame('str', $byName[CaptchaConfiguration::MODE_KEY][0]);
        $this->assertSame('CookieConsent', $byName[CaptchaConfiguration::COOKIE_NAME_KEY][2]);
        $this->assertSame('recaptcha:true', $byName[CaptchaConfiguration::COOKIE_MARKER_KEY][2]);

        // The legacy consent bool is no longer written.
        $this->assertArrayNotHasKey(CaptchaConfiguration::CONSENT_KEY, $byName);

        foreach ($this->savedConfVars as $call) {
            $this->assertSame('module:captcha', $call[4]);
        }
    }
```

Also update the now-stale `testSavePersistsProviderConsentAndPerFormFlags`: remove the `CONSENT_KEY => '1'` request param and the two assertions referencing `CaptchaConfiguration::CONSENT_KEY` (consent is no longer a saved bool). Replace its consent param with the mode param so the test stays representative:

```php
        $this->requestParams = [
            CaptchaConfiguration::PROVIDER_KEY => self::FAKE_PROVIDER_ID,
            CaptchaConfiguration::MODE_KEY => 'gate',
            CaptchaConfiguration::FORM_PREFIX . 'contact' => '1',
            'providerField_fake_scoreThreshold' => '0.7',
        ];
```

and replace the two consent-flag assertions with:

```php
        $this->assertSame('gate', $byName[CaptchaConfiguration::MODE_KEY][2]);
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php`
Expected: FAIL — `save()` still writes `CONSENT_KEY` and does not write `MODE_KEY`/cookie keys.

- [ ] **Step 3: Update `save()`**

In `CaptchaConfigController.php`, inside `save()`, replace the legacy consent `saveShopConfVar('bool', CONSENT_KEY, ...)` block with three string saves:

```php
        $config->saveShopConfVar(
            'str',
            CaptchaConfiguration::MODE_KEY,
            (string) $request->getRequestEscapedParameter(CaptchaConfiguration::MODE_KEY),
            $shopId,
            'module:captcha'
        );
        $config->saveShopConfVar(
            'str',
            CaptchaConfiguration::COOKIE_NAME_KEY,
            (string) $request->getRequestEscapedParameter(CaptchaConfiguration::COOKIE_NAME_KEY),
            $shopId,
            'module:captcha'
        );
        $config->saveShopConfVar(
            'str',
            CaptchaConfiguration::COOKIE_MARKER_KEY,
            (string) $request->getRequestEscapedParameter(CaptchaConfiguration::COOKIE_MARKER_KEY),
            $shopId,
            'module:captcha'
        );
```

- [ ] **Step 4: Replace the `isConsentRequired()` accessor with the new template accessors**

Replace the `isConsentRequired()` method with:

```php
    public function getConsentMode(): string
    {
        return $this->configuration()->getConsentMode();
    }

    public function getConsentCookieName(): string
    {
        return $this->configuration()->getConsentCookieName();
    }

    public function getConsentCookieMarker(): string
    {
        return $this->configuration()->getConsentCookieMarker();
    }
```

(Also update the class-level docblock bullet list: replace the `blCaptchaRequireConsent` line with the three new keys `sCaptchaConsentMode` / `sCaptchaConsentCookieName` / `sCaptchaConsentCookieMarker`.)

- [ ] **Step 5: Run the test to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add source/Application/Controller/Admin/CaptchaConfigController.php tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php
git commit -m "feat(#213): admin save persists consent mode + cookie name/marker"
```

---

## Task 5: Admin template — mode dropdown + revealable cookie fields

**Files:**
- Modify: `source/Application/views/admin/tpl/captcha_config.tpl`

No unit test (Smarty markup). Verified by the controller accessors (Task 4) and a manual admin check in Task 7.

- [ ] **Step 1: Replace the consent checkbox block**

In `captcha_config.tpl`, replace the existing consent `<p>...</p>` block:

```smarty
        <p>
            <label>
                <input type="checkbox" name="blCaptchaRequireConsent" value="1"
                       [{if $oView->isConsentRequired()}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_CAPTCHA_REQUIRE_CONSENT"}]
            </label>
        </p>
```

with a mode dropdown plus a revealable cookie-field group:

```smarty
        <p>
            <label for="sCaptchaConsentMode">
                [{oxmultilang ident="O3_CAPTCHA_CONSENT_MODE_LABEL"}]
            </label>
            <select id="sCaptchaConsentMode" name="sCaptchaConsentMode">
                [{assign var="consentMode" value=$oView->getConsentMode()}]
                <option value="always" [{if $consentMode == "always"}]selected="selected"[{/if}]>
                    [{oxmultilang ident="O3_CAPTCHA_CONSENT_MODE_ALWAYS"}]
                </option>
                <option value="gate" [{if $consentMode == "gate"}]selected="selected"[{/if}]>
                    [{oxmultilang ident="O3_CAPTCHA_CONSENT_MODE_GATE"}]
                </option>
                <option value="cookie" [{if $consentMode == "cookie"}]selected="selected"[{/if}]>
                    [{oxmultilang ident="O3_CAPTCHA_CONSENT_MODE_COOKIE"}]
                </option>
            </select>
        </p>

        <div class="o3-captcha-cookie-fields" [{if $consentMode != "cookie"}]style="display:none;"[{/if}]>
            <p class="o3-captcha-hint">[{oxmultilang ident="O3_CAPTCHA_CONSENT_COOKIE_HINT"}]</p>
            <p>
                <label for="sCaptchaConsentCookieName">
                    [{oxmultilang ident="O3_CAPTCHA_CONSENT_COOKIE_NAME"}]
                </label>
                <input type="text" id="sCaptchaConsentCookieName" name="sCaptchaConsentCookieName"
                       value="[{$oView->getConsentCookieName()|escape:'html'}]">
            </p>
            <p>
                <label for="sCaptchaConsentCookieMarker">
                    [{oxmultilang ident="O3_CAPTCHA_CONSENT_COOKIE_MARKER"}]
                </label>
                <input type="text" id="sCaptchaConsentCookieMarker" name="sCaptchaConsentCookieMarker"
                       value="[{$oView->getConsentCookieMarker()|escape:'html'}]">
            </p>
        </div>
```

- [ ] **Step 2: Extend the inline JS to toggle the cookie fields**

In the `<script>` block at the bottom of the template, inside the IIFE, add a consent-mode toggle alongside the existing provider-field sync. Insert before the closing `})();`:

```javascript
            var consentModeSelect = document.getElementById('sCaptchaConsentMode');
            if (consentModeSelect) {
                var cookieFields = document.querySelector('.o3-captcha-cookie-fields');
                function syncConsentFields() {
                    if (cookieFields) {
                        cookieFields.style.display = (consentModeSelect.value === 'cookie') ? '' : 'none';
                    }
                }
                consentModeSelect.addEventListener('change', syncConsentFields);
                syncConsentFields();
            }
```

- [ ] **Step 3: Commit**

```bash
git add source/Application/views/admin/tpl/captcha_config.tpl
git commit -m "feat(#213): admin template — consent mode dropdown + revealable cookie fields"
```

---

## Task 6: Admin language keys (en + de)

**Files:**
- Modify: `source/Application/views/admin/en/lang.php`
- Modify: `source/Application/views/admin/de/lang.php`

The legacy `O3_CAPTCHA_REQUIRE_CONSENT` key (en line ~1942, de line ~1943) is no longer used by the template; leave it in place (harmless) and add the new keys next to it.

- [ ] **Step 1: Add English keys**

In `source/Application/views/admin/en/lang.php`, add next to `O3_CAPTCHA_REQUIRE_CONSENT`:

```php
    'O3_CAPTCHA_CONSENT_MODE_LABEL'         => 'Consent mode',
    'O3_CAPTCHA_CONSENT_MODE_ALWAYS'        => 'Always load (no consent gate)',
    'O3_CAPTCHA_CONSENT_MODE_GATE'          => 'Require consent, never auto-load',
    'O3_CAPTCHA_CONSENT_MODE_COOKIE'        => 'Load when the consent cookie allows it',
    'O3_CAPTCHA_CONSENT_COOKIE_NAME'        => 'Consent cookie name',
    'O3_CAPTCHA_CONSENT_COOKIE_MARKER'      => 'Consent marker (substring)',
    'O3_CAPTCHA_CONSENT_COOKIE_HINT'        => 'The CAPTCHA loads only when this cookie contains the marker. Until the visitor accepts, the protected form cannot be submitted. Use the marker your consent tool writes for the category reCAPTCHA is in — e.g. Cookiebot: cookie "CookieConsent", marker "marketing:true". Use a plain category:true form; avoid quotes or < > &.',
```

- [ ] **Step 2: Add German keys**

In `source/Application/views/admin/de/lang.php`, add next to `O3_CAPTCHA_REQUIRE_CONSENT`:

```php
    'O3_CAPTCHA_CONSENT_MODE_LABEL'         => 'Einwilligungsmodus',
    'O3_CAPTCHA_CONSENT_MODE_ALWAYS'        => 'Immer laden (keine Einwilligung erforderlich)',
    'O3_CAPTCHA_CONSENT_MODE_GATE'          => 'Einwilligung erforderlich, kein automatisches Laden',
    'O3_CAPTCHA_CONSENT_MODE_COOKIE'        => 'Laden, wenn das Einwilligungs-Cookie es erlaubt',
    'O3_CAPTCHA_CONSENT_COOKIE_NAME'        => 'Name des Einwilligungs-Cookies',
    'O3_CAPTCHA_CONSENT_COOKIE_MARKER'      => 'Einwilligungs-Merkmal (Teilzeichenkette)',
    'O3_CAPTCHA_CONSENT_COOKIE_HINT'        => 'Das CAPTCHA wird nur geladen, wenn dieses Cookie das Merkmal enthält. Solange der Besucher nicht zustimmt, kann das geschützte Formular nicht abgeschickt werden. Verwenden Sie das Merkmal, das Ihr Consent-Tool für die Kategorie von reCAPTCHA setzt – z. B. Cookiebot: Cookie "CookieConsent", Merkmal "marketing:true". Nutzen Sie die Form kategorie:true ohne Anführungszeichen oder < > &.',
```

- [ ] **Step 3: Commit**

```bash
git add source/Application/views/admin/en/lang.php source/Application/views/admin/de/lang.php
git commit -m "feat(#213): admin lang keys for consent mode + cookie fields (en/de)"
```

---

## Task 7: Full verification

- [ ] **Step 1: cs-fixer**

Run: `./docker.sh cs-fixer`
Expected: no violations (or auto-fixed). Re-stage and amend the last commit if it reformats anything:
`git add -A && git commit --amend --no-edit`

- [ ] **Step 2: Run the full captcha unit set**

Run each, expect PASS:
```
./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Configuration/CaptchaConfigurationTest.php
./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsentTest.php
./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php
./docker.sh test --fast tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php
./docker.sh test --fast tests/Unit/Core/Captcha/ViewConfigCaptchaTest.php
```

- [ ] **Step 3: Manual admin smoke check**

`./docker.sh start`, open `http://localhost:8080/admin/` (admin@example.com / admin123), go to the CAPTCHA config page. Confirm: the Consent mode dropdown shows three options; selecting **Cookie-based** reveals the cookie name + marker fields and other modes hide them; Save persists the selection (reload shows it retained).

- [ ] **Step 4: Invoke the finish gate**

Run the `/finish` skill (`./docker.sh test-all-coverage` + memory update) per project protocol. Address any failure before claiming done.

- [ ] **Step 5: Update shared memory**

Append to `.claude/memory/captcha-provider-layer.md`: the consent layer is now a 3-way mode (`always`/`gate`/`cookie`); cookie mode is a client-side script-blocking gate emitted by `CaptchaService` (ships in shop-ce, no theme change); the `CaptchaConsentInterface` seam is still the override point for non-substring cookie formats.

---

## Self-Review

**Spec coverage:**
- 3 modes + config keys + legacy fallback → Task 1. ✔
- Server-side cookie read feeding `isConsentGranted` (mode switch) → Task 2. ✔
- Client-side script-blocking gate, once-on-load, `<script>` re-creation, per-form gate + once-per-request bootstrap, exempt-provider bypass preserved → Task 3. ✔
- `verifyForForm` fail-closed (#183) left untouched; cookie-decline = blocked → Task 3 preamble + migrated test. ✔
- Admin dropdown + revealable cookie fields + save → Tasks 4–5. ✔
- Lang keys incl. fail-closed warning → Task 6. ✔
- Testing matrix (config mapping, consent modes, render-per-mode, no-live-script-outside-template, exempt-in-cookie-mode, #183 fail-closed tests still pass, admin save) → Tasks 1–4. ✔
- Out-of-scope items (verifyForForm change, regex/JSON, live re-check, per-provider, cache-vary) → not implemented, by design. ✔

**Placeholder scan:** none — every code/step is concrete.

**Type consistency:** mode constants live on `CaptchaConfigurationInterface` (`MODE_ALWAYS/GATE/COOKIE`) and are referenced consistently in config impl, consent, service, and tests; storage-key constants (`MODE_KEY`, `COOKIE_NAME_KEY`, `COOKIE_MARKER_KEY`) live on the concrete `CaptchaConfiguration` and are referenced by the controller + controller test. JS bootstrap function name `o3CaptchaConsentGate` matches the once-per-request test assertion. Container CSS hooks `o3-captcha-gate` / `o3-captcha-deferred` / `o3-captcha-consent-notice` match between `CaptchaService` and the bootstrap.
