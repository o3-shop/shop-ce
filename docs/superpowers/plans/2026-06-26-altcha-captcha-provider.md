# Altcha CAPTCHA Provider — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver Altcha (self-hosted, GDPR-clean, proof-of-work CAPTCHA) as a provider module plugging into the core CAPTCHA layer, plus a small non-breaking core marker so a privacy-clean provider loads without the consent gate.

**Architecture:** One core addition (a `ConsentExemptCaptchaProviderInterface` marker + a `CaptchaService` instanceof check, on the #180 branch). One new standalone module `o3-shop/altcha-module` (PHP 8.0+, depends on `altcha-org/altcha:^1.0`) with a single `AltchaCaptchaProvider` that generates an embedded HMAC-signed challenge, verifies the solution server-side, auto-generates a per-shop HMAC secret, and ships a bundled self-hosted widget. The core layer already provides toggles/admin/form-wiring.

**Tech Stack:** PHP 8.0+, `altcha-org/altcha` v1.3.3, Symfony DI (`oxid.captcha.provider` tag), Smarty admin template (reused from core), PHPUnit 9. Spec: `docs/superpowers/specs/2026-06-26-altcha-captcha-provider-design.md`.

---

## Conventions

- **STANDARD HEADER**: prepend the GPL-3 header (copy verbatim from any `source/Internal/Domain/Captcha/*.php`) + `declare(strict_types=1);` to every new `.php` file.
- Commit trailer on every commit:
  ```
  Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_018aRjjPnf9xxCc4xqRgsSvY
  ```
- Worktree: `/Users/nick/o3/shop-ce/.claude/worktrees/213-core-captcha-provider`. Container: `o3shop-213-core-captcha-provider-shop-1`.
- Core tests: `./docker.sh test --fast tests/Unit/...`. Module tests: run via the module's `phpunit.xml` + `tests/bootstrap.php` that requires the shop's `/var/www/html/vendor/autoload.php` and registers the module PSR-4 (mirror `packages/o3-recaptcha/tests/bootstrap.php`).

---

# Phase A — Core: consent-exempt marker (shop-ce, on the `213-core-captcha-provider` branch)

## Task 1: `ConsentExemptCaptchaProviderInterface` + CaptchaService skip

**Files:**
- Create: `source/Internal/Domain/Captcha/Provider/ConsentExemptCaptchaProviderInterface.php`
- Modify: `source/Internal/Domain/Captcha/CaptchaService.php`
- Test: `tests/Unit/Internal/Domain/Captcha/CaptchaServiceConsentExemptTest.php`
- Doc: `docs/captcha-provider-modules.md` (add a short "Consent-exempt providers" note)

- [ ] **Step 1: Create the marker interface**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

/**
 * Opt-in marker: a provider that makes no third-party calls and sets no
 * tracking (e.g. a self-hosted proof-of-work CAPTCHA) implements this so the
 * CaptchaService loads/verifies it WITHOUT the consent gate. Providers that do
 * not implement it stay consent-gated.
 */
interface ConsentExemptCaptchaProviderInterface
{
}
```

- [ ] **Step 2: Write the failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaService;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\CaptchaConsentInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider;
use PHPUnit\Framework\TestCase;

class CaptchaServiceConsentExemptTest extends TestCase
{
    private function exemptProvider(): CaptchaProviderInterface
    {
        return new class () implements CaptchaProviderInterface, ConsentExemptCaptchaProviderInterface {
            public function getId(): string { return 'exempt'; }
            public function getTitle(): string { return 'Exempt'; }
            public function isConfigured(): bool { return true; }
            public function getConfigFields(): array { return []; }
            public function getHeadScript(): ?string { return '<script id="head"></script>'; }
            public function renderWidget(string $formId): string { return '<div id="widget"></div>'; }
            public function verify(Request $request, string $formId): bool { return true; }
        };
    }

    private function service(CaptchaProviderInterface $provider): CaptchaService
    {
        $config = $this->createMock(CaptchaConfigurationInterface::class);
        $config->method('getActiveProviderId')->willReturn($provider->getId());
        $config->method('isFormEnabled')->willReturn(true);
        $consent = $this->createMock(CaptchaConsentInterface::class);
        $consent->method('isConsentGranted')->willReturn(false); // consent NOT granted
        $locator = new CaptchaProviderLocator([$provider], new NullCaptchaProvider());
        return new CaptchaService($locator, $config, $consent);
    }

    public function testExemptProviderRendersAndVerifiesWithoutConsent(): void
    {
        $svc = $this->service($this->exemptProvider());
        $html = $svc->renderForForm('contact');
        $this->assertStringContainsString('id="widget"', $html, 'Exempt provider renders its widget even without consent.');
        $this->assertTrue($svc->verifyForForm('contact', $this->createMock(Request::class)));
    }
}
```

Run: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceConsentExemptTest.php` → FAIL (exempt provider currently hits the consent notice; widget absent).

- [ ] **Step 3: Update `CaptchaService`** — resolve the active provider first, then skip consent when it's exempt. Replace the bodies of `renderForForm()` and `verifyForForm()` with:

```php
    public function renderForForm(string $formId): string
    {
        if (!$this->isEnabledForForm($formId)) {
            return '';
        }
        $provider = $this->activeProvider();
        if (!($provider instanceof ConsentExemptCaptchaProviderInterface)
            && !$this->consent->isConsentGranted(Registry::getRequest())) {
            return '<div class="o3-captcha-consent-notice">'
                . htmlspecialchars((string) Registry::getLang()->translateString('O3_CAPTCHA_CONSENT_NOTICE'), ENT_QUOTES)
                . '</div>';
        }

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

    public function verifyForForm(string $formId, Request $request): bool
    {
        if (!$this->isEnabledForForm($formId)) {
            return true;
        }
        $provider = $this->activeProvider();
        if (!($provider instanceof ConsentExemptCaptchaProviderInterface)
            && !$this->consent->isConsentGranted($request)) {
            return true;
        }
        return $provider->verify($request, $formId);
    }
```

Add the import at the top: `use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface;`

- [ ] **Step 4: Run the new test + the existing `CaptchaServiceTest`** — both green:
  `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceConsentExemptTest.php`
  `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`

- [ ] **Step 5: Doc note** — in `docs/captcha-provider-modules.md`, add a short subsection under Consent: "A provider that makes no third-party calls (e.g. a self-hosted PoW CAPTCHA like Altcha) may implement `ConsentExemptCaptchaProviderInterface`; the core then loads and verifies it without the consent gate."

- [ ] **Step 6: Commit** — `git add source/Internal/Domain/Captcha tests/Unit/Internal/Domain/Captcha docs/captcha-provider-modules.md && git commit -m "feat(#113): add ConsentExemptCaptchaProviderInterface; CaptchaService skips consent for exempt providers" -m "<trailer>"`

---

# Phase B — Altcha module (`o3-shop/altcha-module`)

Build the module at **`packages/o3-altcha/`** in the worktree (mirrors how `packages/o3-recaptcha` was set up). It is a separate package: `git init` it, and it's installed into the shop via a local Composer path repository (Phase C). It is NOT committed to shop-ce. The `packages/` dir is already kept out of shop-ce status via `.git/info/exclude` (verify; if the worktree's `.git` is a file, skip the exclude and just don't `git add` it).

## Task 2: Module scaffold + altcha dependency

**Files (all under `packages/o3-altcha/`):** `composer.json`, `metadata.php`, `services.yaml`, `phpunit.xml`, `tests/bootstrap.php`, `.gitignore`.

- [ ] **Step 1: `composer.json`**

```json
{
    "name": "o3-shop/altcha-module",
    "description": "Altcha (self-hosted, GDPR-clean, proof-of-work) CAPTCHA provider for the O3-Shop core CAPTCHA layer",
    "type": "oxideshop-module",
    "license": "GPL-3.0-only",
    "require": {
        "php": ">=8.0",
        "altcha-org/altcha": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9"
    },
    "autoload": { "psr-4": { "O3Shop\\Altcha\\": "src/" } },
    "autoload-dev": { "psr-4": { "O3Shop\\Altcha\\Tests\\": "tests/" } },
    "extra": { "oxideshop": { "target-directory": "o3-shop/altcha" } }
}
```

- [ ] **Step 2: `metadata.php`** (metadata version 2.1; module id `o3altcha`; no `extend`/`blocks` — the provider registers via `services.yaml` and the core renders it):

```php
<?php
// (GPL header above)
$sMetadataVersion = '2.1';
$aModule = [
    'id'          => 'o3altcha',
    'title'       => 'O3-Shop Altcha CAPTCHA',
    'description' => 'Self-hosted, GDPR-clean, proof-of-work CAPTCHA provider (Altcha) for the core CAPTCHA layer.',
    'version'     => '1.0.0',
    'author'      => 'O3-Shop',
    'url'         => 'https://www.o3-shop.com',
];
```

- [ ] **Step 3: `services.yaml`** (module ROOT — the only path module activation loads):

```yaml
services:
    _defaults:
        autowire: true
        public: false

    O3Shop\Altcha\Secret\HmacSecretStore: ~

    O3Shop\Altcha\Provider\AltchaCaptchaProvider:
        tags: ['oxid.captcha.provider']
```
(autowire injects the core `CaptchaConfigurationInterface` + the module's `HmacSecretStore`.)

- [ ] **Step 4: `phpunit.xml` + `tests/bootstrap.php`** — copy `packages/o3-recaptcha/phpunit.xml` and `tests/bootstrap.php`, adjusting the PSR-4 prefix to `O3Shop\Altcha\` → `src/` and the test prefix to `O3Shop\Altcha\Tests\` → `tests/`. `.gitignore`: `/vendor/` and `/.phpunit.result.cache`.

- [ ] **Step 5: install the altcha dependency into the SHOP vendor** (so the module's classes + altcha-lib autoload during tests and at runtime). This is done via the path-repo require in Phase C, but to develop/test now, add altcha to the shop vendor:
  `docker exec o3shop-213-core-captcha-provider-shop-1 sh -lc 'cd /var/www/html && COMPOSER_ROOT_VERSION=dev-b-1.6 composer require altcha-org/altcha:^1.0 --no-interaction --no-scripts 2>&1 | tail -5'`
  Confirm `\AltchaOrg\Altcha\Altcha` autoloads. (Note: this also lands altcha in the shop's composer.json — that's expected; the module declares it too. It's a local dev install, uncommitted to shop-ce.)

- [ ] **Step 6: `git init` the module + initial commit**
  `cd packages/o3-altcha && git init -q && git add -A && git commit -m "feat: scaffold o3-shop/altcha-module" -m "<trailer>"`

## Task 3: `HmacSecretStore`

**Files:** `packages/o3-altcha/src/Secret/HmacSecretStore.php`, `packages/o3-altcha/tests/Secret/HmacSecretStoreTest.php`

- [ ] **Step 1: Failing test** (mock `Config` via Registry)

```php
namespace O3Shop\Altcha\Tests\Secret;

use O3Shop\Altcha\Secret\HmacSecretStore;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use PHPUnit\Framework\TestCase;

class HmacSecretStoreTest extends TestCase
{
    private array $params = [];

    protected function setUp(): void
    {
        $this->params = [];
        $config = $this->createMock(Config::class);
        $config->method('getShopId')->willReturn(1);
        $config->method('getConfigParam')->willReturnCallback(fn ($n, $d = false) => $this->params[$n] ?? $d);
        $config->method('saveShopConfVar')->willReturnCallback(
            function ($t, $n, $v) { $this->params[$n] = $v; }
        );
        Registry::set(Config::class, $config);
    }

    protected function tearDown(): void
    {
        Registry::set(Config::class, null);
    }

    public function testGeneratesOnceThenIsStable(): void
    {
        $store = new HmacSecretStore();
        $first = $store->getSecret();
        $this->assertNotSame('', $first);
        $this->assertSame(64, strlen($first), 'bin2hex(32 bytes) = 64 hex chars.');
        $this->assertSame($first, $store->getSecret(), 'Secret persists and is reused, not regenerated.');
    }
}
```
Run → FAIL.

- [ ] **Step 2: Implement** `src/Secret/HmacSecretStore.php`

```php
namespace O3Shop\Altcha\Secret;

use OxidEsales\Eshop\Core\Registry;

final class HmacSecretStore
{
    private const KEY = 'sCaptcha_altcha_hmacSecret';

    /**
     * Returns the per-shop Altcha HMAC secret, generating + persisting one on
     * first use. Never exposed to the frontend. Rotate by clearing the value.
     */
    public function getSecret(): string
    {
        $config = Registry::getConfig();
        $secret = (string) $config->getConfigParam(self::KEY);
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
            $config->saveShopConfVar('str', self::KEY, $secret, $config->getShopId(), 'module:captcha');
        }
        return $secret;
    }
}
```
Run → PASS. Commit (`feat: add Altcha per-shop HMAC secret store`).

## Task 4: `AltchaCaptchaProvider`

**Files:** `packages/o3-altcha/src/Provider/AltchaCaptchaProvider.php`, `packages/o3-altcha/tests/Provider/AltchaCaptchaProviderTest.php`

- [ ] **Step 1: Failing test** — exercises the real altcha-lib end to end (create → solve → verify), config fields, empty-payload short-circuit, and the consent-exempt marker.

```php
namespace O3Shop\Altcha\Tests\Provider;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\SolveChallengeOptions; // confirm exact name against installed lib (see note)
use O3Shop\Altcha\Provider\AltchaCaptchaProvider;
use O3Shop\Altcha\Secret\HmacSecretStore;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface;
use PHPUnit\Framework\TestCase;

class AltchaCaptchaProviderTest extends TestCase
{
    private const SECRET = 'test-secret-key-0123456789abcdef';

    private function provider(int $complexity = 1000): AltchaCaptchaProvider
    {
        $config = $this->createMock(CaptchaConfigurationInterface::class);
        $config->method('getProviderSetting')->willReturnCallback(
            fn ($p, $k, $d = null) => $k === 'complexity' ? (string) $complexity : $d
        );
        $secretStore = $this->createMock(HmacSecretStore::class);
        $secretStore->method('getSecret')->willReturn(self::SECRET);
        return new AltchaCaptchaProvider($config, $secretStore);
    }

    public function testIsConsentExemptAndAlwaysConfigured(): void
    {
        $p = $this->provider();
        $this->assertInstanceOf(ConsentExemptCaptchaProviderInterface::class, $p);
        $this->assertTrue($p->isConfigured());
        $this->assertSame('altcha', $p->getId());
    }

    public function testConfigFieldIsComplexity(): void
    {
        $keys = array_map(fn ($f) => $f->getKey(), $this->provider()->getConfigFields());
        $this->assertSame(['complexity'], $keys);
    }

    public function testWidgetEmbedsAChallenge(): void
    {
        $html = $this->provider()->renderWidget('contact');
        $this->assertStringContainsString('<altcha-widget', $html);
        $this->assertStringContainsString('challengejson', $html);
    }

    public function testVerifyShortCircuitsOnEmptyPayload(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRequestEscapedParameter')->willReturn('');
        $this->assertFalse($this->provider()->verify($request, 'contact'));
    }

    public function testVerifyAcceptsACorrectlySolvedPayload(): void
    {
        // Build a real, solved payload with the same secret, using the lib.
        $altcha = new Altcha(self::SECRET);
        $challenge = $altcha->createChallenge(new ChallengeOptions(maxNumber: 1000));
        // Solve it (small maxNumber → fast). Confirm the solve API against the
        // installed lib; if solveChallenge differs, brute-force the number 0..1000
        // and assemble the payload manually (see note).
        $solution = $altcha->solveChallenge(new SolveChallengeOptions($challenge));
        $payload = base64_encode(json_encode([
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'number'    => $solution->number,
            'salt'      => $challenge->salt,
            'signature' => $challenge->signature,
        ], JSON_THROW_ON_ERROR));

        $request = $this->createMock(Request::class);
        $request->method('getRequestEscapedParameter')->willReturn($payload);

        $this->assertTrue($this->provider()->verify($request, 'contact'));
    }
}
```

> **Lib-API note (verify against installed v1.3.3 before finalizing the test):** confirm the exact solve API and payload field names. `Altcha::verifySolution(string|array $data, bool $checkExpires = true): bool` is confirmed. `createChallenge(new ChallengeOptions(maxNumber:, expires:))` returns a `Challenge` with public readonly `algorithm/challenge/maxNumber/salt/signature`. If `solveChallenge`/`SolveChallengeOptions` names differ in v1.3.3, solve by brute force: iterate `$n = 0..maxNumber`, compute `hash($algo, $salt.$n)`, find the one equal to `$challenge->challenge`, then use `$n` as `number`. Keep `maxNumber` small (e.g. 1000) so the test is fast.

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Implement** `src/Provider/AltchaCaptchaProvider.php`

```php
namespace O3Shop\Altcha\Provider;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use O3Shop\Altcha\Secret\HmacSecretStore;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface;

final class AltchaCaptchaProvider implements CaptchaProviderInterface, ConsentExemptCaptchaProviderInterface
{
    public const ID = 'altcha';
    private const MODULE_ID = 'o3altcha';
    private const PAYLOAD_FIELD = 'altcha';
    private const DEFAULT_COMPLEXITY = '100000';
    private const CHALLENGE_TTL_SECONDS = 300;

    /** @var CaptchaConfigurationInterface */
    private $configuration;
    /** @var HmacSecretStore */
    private $secretStore;

    public function __construct(CaptchaConfigurationInterface $configuration, HmacSecretStore $secretStore)
    {
        $this->configuration = $configuration;
        $this->secretStore = $secretStore;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getTitle(): string
    {
        return 'O3_ALTCHA_PROVIDER_TITLE';
    }

    public function isConfigured(): bool
    {
        return true; // self-hosted; the HMAC secret auto-generates — no operator keys.
    }

    public function getConfigFields(): array
    {
        return [
            new CaptchaConfigField('complexity', 'O3_ALTCHA_COMPLEXITY', CaptchaConfigField::TYPE_NUMBER, self::DEFAULT_COMPLEXITY),
        ];
    }

    public function getHeadScript(): ?string
    {
        $url = (string) Registry::getConfig()->getModuleUrl(self::MODULE_ID, 'out/altcha.min.js');
        if ($url === '') {
            return null;
        }
        return '<script async defer type="module" src="' . htmlspecialchars($url, ENT_QUOTES) . '"></script>';
    }

    public function renderWidget(string $formId): string
    {
        $altcha = new Altcha($this->secretStore->getSecret());
        $challenge = $altcha->createChallenge(new ChallengeOptions(
            maxNumber: $this->complexity(),
            expires: (new \DateTimeImmutable())->modify('+' . self::CHALLENGE_TTL_SECONDS . ' seconds'),
        ));

        $challengeJson = json_encode([
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'maxnumber' => $challenge->maxNumber,
            'salt'      => $challenge->salt,
            'signature' => $challenge->signature,
        ], JSON_THROW_ON_ERROR);

        $widget = '<altcha-widget challengejson="' . htmlspecialchars($challengeJson, ENT_QUOTES) . '"';
        $strings = $this->localizedStringsJson();
        if ($strings !== '') {
            $widget .= ' strings="' . htmlspecialchars($strings, ENT_QUOTES) . '"';
        }
        return $widget . '></altcha-widget>';
    }

    public function verify(Request $request, string $formId): bool
    {
        $payload = trim((string) $request->getRequestEscapedParameter(self::PAYLOAD_FIELD));
        if ($payload === '') {
            return false;
        }
        try {
            return (new Altcha($this->secretStore->getSecret()))->verifySolution($payload, true);
        } catch (\Throwable $e) {
            Registry::getLogger()->warning(__METHOD__ . " - Altcha solution verification failed: '" . $e->getMessage() . "'.");
            return false;
        }
    }

    private function complexity(): int
    {
        $value = (int) $this->configuration->getProviderSetting(self::ID, 'complexity', self::DEFAULT_COMPLEXITY);
        return $value > 0 ? $value : (int) self::DEFAULT_COMPLEXITY;
    }

    private function localizedStringsJson(): string
    {
        $lang = Registry::getLang();
        $strings = [
            'label'     => (string) $lang->translateString('O3_ALTCHA_STR_LABEL'),
            'verifying' => (string) $lang->translateString('O3_ALTCHA_STR_VERIFYING'),
            'verified'  => (string) $lang->translateString('O3_ALTCHA_STR_VERIFIED'),
            'expired'   => (string) $lang->translateString('O3_ALTCHA_STR_EXPIRED'),
            'error'     => (string) $lang->translateString('O3_ALTCHA_STR_ERROR'),
        ];
        // Drop any idents that didn't resolve (translateString returns the ident on miss).
        $strings = array_filter($strings, static fn ($v, $k) => $v !== '' && strpos($v, 'O3_ALTCHA_STR_') !== 0, ARRAY_FILTER_USE_BOTH);
        return $strings === [] ? '' : (string) json_encode($strings, JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 4: Run the provider test → PASS.** Adjust the test's solve step per the lib-API note if needed (do not weaken assertions). Commit (`feat: add AltchaCaptchaProvider (embedded challenge + server-side verify)`).

## Task 5: Bundle the self-hosted widget JS + lang

**Files:** `packages/o3-altcha/out/altcha.min.js`, `Application/views/admin/{en,de}/module_options.php`, storefront lang `Application/translations/{en,de}/lang.php`.

- [ ] **Step 1: Vendor the Altcha web component JS (self-hosted, no CDN).** Fetch the pinned UMD/ESM build of the `altcha` web component and save it to `out/altcha.min.js`. Source it from the npm package `altcha` (the released `dist/altcha.min.js`) at a version compatible with the v1 challenge format — e.g. download from `https://registry.npmjs.org/altcha/-/altcha-<ver>.tgz`, extract `dist/altcha.min.js`. Verify it's a single self-contained file (no further network fetches). Record the version in `README.md`.

- [ ] **Step 2: Admin lang** `Application/views/admin/en/module_options.php` (+ `de/`):
```php
<?php
$sLangName = 'English';
$aLang = [
    'charset' => 'UTF-8',
    'SHOP_MODULE_GROUP_o3altcha' => 'Altcha CAPTCHA',
    'O3_ALTCHA_PROVIDER_TITLE'   => 'Altcha (self-hosted, privacy-friendly)',
    'O3_ALTCHA_COMPLEXITY'       => 'PoW complexity (max number; higher = harder; ~50000 low / 100000 medium / 500000 high)',
];
```
(German file: `Altcha (selbst-gehostet, datenschutzfreundlich)`, `PoW-Komplexität (max. Zahl; höher = schwerer; ~50000 niedrig / 100000 mittel / 500000 hoch)`.)

- [ ] **Step 3: Storefront widget strings** in the module's storefront translations (de + en) for `O3_ALTCHA_STR_LABEL` ("I'm not a robot"), `_VERIFYING` ("Verifying…"), `_VERIFIED` ("Verified"), `_EXPIRED` ("Expired, please retry"), `_ERROR` ("Verification failed, please retry"). German equivalents. (Place where module storefront lang is loaded; mirror how a module ships storefront translations — confirm the load path, fall back to shop-level `source/Application/translations` if the module path isn't picked up.)

- [ ] **Step 4:** `php -l` all new PHP files. Commit (`feat: bundle self-hosted Altcha widget JS + admin/storefront language`).

## Task 6: README

- [ ] Write `packages/o3-altcha/README.md`: what it is (self-hosted, GDPR-clean, PoW), PHP 8.0+, install (`composer require o3-shop/altcha-module`) + activate, then select "Altcha" in Admin → CAPTCHA; note it's consent-exempt (loads without a consent prompt), the bundled JS version, and that the per-shop HMAC secret auto-generates (rotate by clearing `sCaptcha_altcha_hmacSecret`). Commit.

---

# Phase C — Install on :9630 + end-to-end verification

## Task 7: Path-repo install + activate + verify

- [ ] **Step 1:** add a Composer path repo + require (mirror the recaptcha install):
  `docker exec o3shop-213-core-captcha-provider-shop-1 sh -lc 'cd /var/www/html && COMPOSER_ROOT_VERSION=dev-b-1.6 composer config repositories.o3altcha path packages/o3-altcha && COMPOSER_ROOT_VERSION=dev-b-1.6 composer require o3-shop/altcha-module:@dev --no-interaction 2>&1 | tail -15'`
- [ ] **Step 2:** `oe:module:install-configuration source/modules/o3-shop/altcha` then `oe:module:activate o3altcha`, then `oe:cache:clear`.
- [ ] **Step 3: Verify the provider is collected + works:**
```
docker exec o3shop-213-core-captcha-provider-shop-1 php -r '
require "/var/www/html/source/bootstrap.php";
$c = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->getContainer();
$loc = $c->get(\OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator::class);
echo "providers: ".implode(",", array_keys($loc->getAll()))."\n"; // expect altcha (+ any others active)
$p = $loc->getById("altcha");
echo "consent-exempt: ".($p instanceof \OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface ? "yes":"no")."\n";
echo "widget: ".(strpos($p->renderWidget("contact"), "<altcha-widget") !== false ? "renders":"MISSING")."\n";
'
```
- [ ] **Step 4: Manual acceptance:** Admin → CAPTCHA → select **Altcha** (no keys needed) → save; enable the contact form. Load `/Kontakt` (no consent prompt — exempt), confirm the `<altcha-widget>` renders + solves; submit the contact form and confirm it passes when solved and is rejected when the `altcha` field is stripped. No outbound network requests (check the network tab — only the self-hosted `out/altcha.min.js`).
- [ ] **Step 5:** run the full module test suite (`phpunit -c packages/o3-altcha/phpunit.xml`) + the core suite for the captcha dir; cs-fixer clean.

---

## Self-review (completed by plan author)

- **Spec coverage:** consent marker (Task 1 ✓ — spec §3/D2), module scaffold + altcha dep (Task 2 ✓ — D3), HMAC secret auto-gen (Task 3 ✓ — D4), provider w/ embedded challenge + verify + complexity + consent-exempt (Task 4 ✓ — D1/D6/D2), self-hosted JS + lang (Task 5 ✓ — D5 + #113 localization), README (Task 6 ✓ — #113 docs criterion), install + no-network acceptance (Task 7 ✓ — #113 outbound-blocked criterion).
- **Type/name consistency:** `ConsentExemptCaptchaProviderInterface`, `AltchaCaptchaProvider` (id `altcha`), `HmacSecretStore::getSecret()`, config key `sCaptcha_altcha_hmacSecret`, payload field `altcha`, module id `o3altcha`, complexity field key `complexity` — consistent across tasks. Altcha API (`new Altcha($hmacKey)`, `createChallenge(ChallengeOptions)`, `verifySolution(string|array,bool)`) matches the verified v1.3.3 source.
- **Flagged verify-points (not placeholders):** the lib's solve API name in the Task-4 test (with a brute-force fallback spelled out), the module storefront-lang load path (Task 5 Step 3, with a shop-level fallback), and the bundled JS version (Task 5 Step 1). Each says exactly what to check + the fallback.
