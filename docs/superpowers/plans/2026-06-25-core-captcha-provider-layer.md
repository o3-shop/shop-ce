# Core CAPTCHA Provider Layer — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a pluggable CAPTCHA layer to O3-Shop core with two built-in Google reCAPTCHA providers (v2 checkbox + v3 score), admin-selectable, consent-gated, protecting 7 public forms, and extensible by third-party modules via a DI tag.

**Architecture:** New `source/Internal/Domain/Captcha/` domain. A DI-tagged `CaptchaProviderInterface` (collected by `CaptchaProviderLocator`); a `CaptchaService` facade resolves the active provider from `oxconfig`, applies per-form toggles + consent policy, renders into the existing `captcha_form` template blocks (via a new `ViewConfig` method), and verifies submissions inside 7 controllers. Verification uses the official `google/recaptcha` library behind `CaptchaVerifierInterface`.

**Tech Stack:** PHP 7.4+/8.x, Symfony DI (Internal container), Smarty 2.6 templates, PHPUnit 9, `google/recaptcha ^1.3`. Spec: `docs/superpowers/specs/2026-06-25-core-captcha-provider-layer-design.md`.

---

## Conventions for every PHP file

**STANDARD HEADER** — prepend to every new `.php` file (source and test), then `declare(strict_types=1);`:

```php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);
```

- Source namespace root: `OxidEsales\EshopCommunity\Internal\Domain\Captcha\` → `source/Internal/Domain/Captcha/`.
- Test namespace root: `OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\` → `tests/Unit/Internal/Domain/Captcha/`.
- Run a single test file fast: `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/<File>.php`.
- Logging per CLAUDE.md: `Registry::getLogger()->warning(__METHOD__ . " - …'$var'.")`.
- Commit message trailer (every commit):
  ```
  Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_018aRjjPnf9xxCc4xqRgsSvY
  ```

The 7 form ids (used everywhere): `contact`, `newsletter`, `suggest`, `forgotpwd`, `register`, `pricealarm`, `invite`.

---

# Phase 1 — Core provider layer (foundation, fully unit-tested)

## Task 1: `CaptchaConfigField` value object

**Files:**
- Create: `source/Internal/Domain/Captcha/Field/CaptchaConfigField.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Field/CaptchaConfigFieldTest.php`

- [ ] **Step 1: Write the failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Field;

use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use PHPUnit\Framework\TestCase;

class CaptchaConfigFieldTest extends TestCase
{
    public function testExposesItsProperties(): void
    {
        $field = new CaptchaConfigField('siteKey', 'CAPTCHA_SITE_KEY', CaptchaConfigField::TYPE_TEXT, 'x');

        $this->assertSame('siteKey', $field->getKey());
        $this->assertSame('CAPTCHA_SITE_KEY', $field->getLabelIdent());
        $this->assertSame('text', $field->getType());
        $this->assertSame('x', $field->getDefault());
    }

    public function testDefaultsToEmptyString(): void
    {
        $field = new CaptchaConfigField('k', 'L', CaptchaConfigField::TYPE_PASSWORD);
        $this->assertSame('', $field->getDefault());
    }
}
```

- [ ] **Step 2: Run it, expect failure** — `./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/Field/CaptchaConfigFieldTest.php` → FAIL (class not found).

- [ ] **Step 3: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field;

final class CaptchaConfigField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CHECKBOX = 'checkbox';

    /** @var string */
    private $key;
    /** @var string */
    private $labelIdent;
    /** @var string */
    private $type;
    /** @var string */
    private $default;

    public function __construct(string $key, string $labelIdent, string $type, string $default = '')
    {
        $this->key = $key;
        $this->labelIdent = $labelIdent;
        $this->type = $type;
        $this->default = $default;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabelIdent(): string
    {
        return $this->labelIdent;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefault(): string
    {
        return $this->default;
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git add source/Internal/Domain/Captcha/Field tests/Unit/Internal/Domain/Captcha/Field && git commit -m "feat(#213): add CaptchaConfigField value object" -m "<trailer>"`

## Task 2: `VerificationResult` value object

**Files:**
- Create: `source/Internal/Domain/Captcha/Verifier/VerificationResult.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Verifier/VerificationResultTest.php`

- [ ] **Step 1: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Verifier;

use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\VerificationResult;
use PHPUnit\Framework\TestCase;

class VerificationResultTest extends TestCase
{
    public function testSuccessResultCarriesScoreAndAction(): void
    {
        $r = new VerificationResult(true, 0.9, 'contact', []);
        $this->assertTrue($r->isSuccess());
        $this->assertSame(0.9, $r->getScore());
        $this->assertSame('contact', $r->getAction());
        $this->assertSame([], $r->getErrorCodes());
    }

    public function testFailureResultDefaults(): void
    {
        $r = new VerificationResult(false, null, null, ['timeout-or-duplicate']);
        $this->assertFalse($r->isSuccess());
        $this->assertNull($r->getScore());
        $this->assertNull($r->getAction());
        $this->assertSame(['timeout-or-duplicate'], $r->getErrorCodes());
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier;

final class VerificationResult
{
    /** @var bool */
    private $success;
    /** @var float|null */
    private $score;
    /** @var string|null */
    private $action;
    /** @var string[] */
    private $errorCodes;

    /** @param string[] $errorCodes */
    public function __construct(bool $success, ?float $score = null, ?string $action = null, array $errorCodes = [])
    {
        $this->success = $success;
        $this->score = $score;
        $this->action = $action;
        $this->errorCodes = $errorCodes;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    /** @return string[] */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git add … && git commit -m "feat(#213): add VerificationResult value object" -m "<trailer>"`

## Task 3: `CaptchaProviderInterface`

**Files:**
- Create: `source/Internal/Domain/Captcha/Provider/CaptchaProviderInterface.php`

No standalone test (interface). It is exercised by Task 4 onward.

- [ ] **Step 1: Implement the interface**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;

interface CaptchaProviderInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function isConfigured(): bool;

    /** @return CaptchaConfigField[] */
    public function getConfigFields(): array;

    public function getHeadScript(): ?string;

    public function renderWidget(string $formId): string;

    public function verify(Request $request, string $formId): bool;
}
```

- [ ] **Step 2: Commit** — `git add … && git commit -m "feat(#213): add CaptchaProviderInterface" -m "<trailer>"`

## Task 4: `NullCaptchaProvider`

**Files:**
- Create: `source/Internal/Domain/Captcha/Provider/NullCaptchaProvider.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Provider/NullCaptchaProviderTest.php`

- [ ] **Step 1: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider;
use PHPUnit\Framework\TestCase;

class NullCaptchaProviderTest extends TestCase
{
    public function testIsInertAndAlwaysVerifies(): void
    {
        $p = new NullCaptchaProvider();

        $this->assertSame('', $p->getId());
        $this->assertFalse($p->isConfigured());
        $this->assertSame([], $p->getConfigFields());
        $this->assertNull($p->getHeadScript());
        $this->assertSame('', $p->renderWidget('contact'));
        $this->assertTrue($p->verify($this->createMock(Request::class), 'contact'));
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;

final class NullCaptchaProvider implements CaptchaProviderInterface
{
    public function getId(): string
    {
        return '';
    }

    public function getTitle(): string
    {
        return 'O3_CAPTCHA_PROVIDER_NONE';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function getHeadScript(): ?string
    {
        return null;
    }

    public function renderWidget(string $formId): string
    {
        return '';
    }

    public function verify(Request $request, string $formId): bool
    {
        return true;
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 5: `CaptchaVerifierInterface` + `GoogleReCaptchaVerifier` (+ composer dep)

**Files:**
- Modify: `composer.json` (add `"google/recaptcha": "^1.3"` to `require`)
- Create: `source/Internal/Domain/Captcha/Verifier/CaptchaVerifierInterface.php`
- Create: `source/Internal/Domain/Captcha/Verifier/GoogleReCaptchaVerifier.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Verifier/GoogleReCaptchaVerifierTest.php`

- [ ] **Step 1: Add the dependency**

In `composer.json`, under `"require"`, add `"google/recaptcha": "^1.3"`. Then in the worktree container run:
`docker exec o3shop-213-core-captcha-provider-1 composer require google/recaptcha:^1.3 --no-interaction` (or `composer update google/recaptcha` if already added by hand).
Expected: `vendor/google/recaptcha` present; `\ReCaptcha\ReCaptcha` autoloadable.

- [ ] **Step 2: Define the interface**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier;

interface CaptchaVerifierInterface
{
    public function verify(
        string $secret,
        string $token,
        ?string $remoteIp,
        ?string $expectedAction = null,
        ?float $scoreThreshold = null
    ): VerificationResult;
}
```

- [ ] **Step 3: Failing test (library transport faked — no network)**

`\ReCaptcha\ReCaptcha` accepts a `\ReCaptcha\RequestMethod` as its 2nd constructor arg; we inject a fake that returns canned JSON. `GoogleReCaptchaVerifier` must allow injecting a `RequestMethod` for testing.

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Verifier;

use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\GoogleReCaptchaVerifier;
use PHPUnit\Framework\TestCase;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

class GoogleReCaptchaVerifierTest extends TestCase
{
    private function verifierReturning(string $json): GoogleReCaptchaVerifier
    {
        $transport = new class ($json) implements RequestMethod {
            private $json;
            public function __construct(string $json)
            {
                $this->json = $json;
            }
            public function submit(RequestParameters $params): string
            {
                return $this->json;
            }
        };

        return new GoogleReCaptchaVerifier($transport);
    }

    public function testV2SuccessMapsToSuccessResult(): void
    {
        $verifier = $this->verifierReturning('{"success":true}');
        $result = $verifier->verify('secret', 'token', '203.0.113.1');
        $this->assertTrue($result->isSuccess());
    }

    public function testV3SuccessWithSufficientScoreAndMatchingAction(): void
    {
        $verifier = $this->verifierReturning('{"success":true,"score":0.9,"action":"contact"}');
        $result = $verifier->verify('secret', 'token', null, 'contact', 0.5);
        $this->assertTrue($result->isSuccess());
        $this->assertSame(0.9, $result->getScore());
    }

    public function testV3FailsWhenScoreBelowThreshold(): void
    {
        $verifier = $this->verifierReturning('{"success":true,"score":0.1,"action":"contact"}');
        $result = $verifier->verify('secret', 'token', null, 'contact', 0.5);
        $this->assertFalse($result->isSuccess());
    }

    public function testV3FailsWhenActionMismatch(): void
    {
        $verifier = $this->verifierReturning('{"success":true,"score":0.9,"action":"newsletter"}');
        $result = $verifier->verify('secret', 'token', null, 'contact', 0.5);
        $this->assertFalse($result->isSuccess());
    }

    public function testTransportFailureMapsToFailure(): void
    {
        $verifier = $this->verifierReturning('{"success":false,"error-codes":["timeout-or-duplicate"]}');
        $result = $verifier->verify('secret', 'token', null);
        $this->assertFalse($result->isSuccess());
        $this->assertContains('timeout-or-duplicate', $result->getErrorCodes());
    }
}
```

- [ ] **Step 4: Run, expect FAIL.**

- [ ] **Step 5: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;

final class GoogleReCaptchaVerifier implements CaptchaVerifierInterface
{
    /** @var RequestMethod|null */
    private $requestMethod;

    public function __construct(?RequestMethod $requestMethod = null)
    {
        $this->requestMethod = $requestMethod;
    }

    public function verify(
        string $secret,
        string $token,
        ?string $remoteIp,
        ?string $expectedAction = null,
        ?float $scoreThreshold = null
    ): VerificationResult {
        $recaptcha = new ReCaptcha($secret, $this->requestMethod);

        if ($expectedAction !== null) {
            $recaptcha->setExpectedAction($expectedAction);
        }
        if ($scoreThreshold !== null) {
            $recaptcha->setScoreThreshold($scoreThreshold);
        }

        $response = $recaptcha->verify($token, $remoteIp);

        return new VerificationResult(
            $response->isSuccess(),
            $response->getScore() !== null ? (float) $response->getScore() : null,
            $response->getAction() !== null ? (string) $response->getAction() : null,
            $response->getErrorCodes()
        );
    }
}
```

- [ ] **Step 6: Run, expect PASS.**
- [ ] **Step 7: Commit** — include `composer.json` and `composer.lock` if generated.

## Task 6: `GoogleReCaptchaV2Provider`

**Files:**
- Create: `source/Internal/Domain/Captcha/Provider/GoogleReCaptchaV2Provider.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Provider/GoogleReCaptchaV2ProviderTest.php`

The provider depends on `CaptchaConfigurationInterface` (Task 9) to read keys. To keep Tasks ordered, define a minimal local stub interface usage now and rely on Task 9's interface — implement Task 9 BEFORE Task 6 if executing strictly in order. **Execution note:** do Task 9 before Tasks 6–7. (Listed here for grouping by concept.)

- [ ] **Step 1: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\GoogleReCaptchaV2Provider;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\CaptchaVerifierInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\VerificationResult;
use PHPUnit\Framework\TestCase;

class GoogleReCaptchaV2ProviderTest extends TestCase
{
    private function config(array $values): CaptchaConfigurationInterface
    {
        $config = $this->createMock(CaptchaConfigurationInterface::class);
        $config->method('getProviderSetting')->willReturnCallback(
            fn (string $providerId, string $key, $default = null) => $values[$key] ?? $default
        );
        return $config;
    }

    public function testIdAndConfigFields(): void
    {
        $provider = new GoogleReCaptchaV2Provider($this->config([]), $this->createMock(CaptchaVerifierInterface::class));
        $this->assertSame('google_recaptcha_v2', $provider->getId());
        $keys = array_map(fn ($f) => $f->getKey(), $provider->getConfigFields());
        $this->assertSame(['siteKey', 'secretKey'], $keys);
    }

    public function testIsConfiguredRequiresBothKeys(): void
    {
        $this->assertFalse((new GoogleReCaptchaV2Provider($this->config(['siteKey' => 'a']), $this->createMock(CaptchaVerifierInterface::class)))->isConfigured());
        $this->assertTrue((new GoogleReCaptchaV2Provider($this->config(['siteKey' => 'a', 'secretKey' => 'b']), $this->createMock(CaptchaVerifierInterface::class)))->isConfigured());
    }

    public function testRenderWidgetContainsSiteKey(): void
    {
        $provider = new GoogleReCaptchaV2Provider($this->config(['siteKey' => 'SITE123']), $this->createMock(CaptchaVerifierInterface::class));
        $this->assertStringContainsString('g-recaptcha', $provider->renderWidget('contact'));
        $this->assertStringContainsString('SITE123', $provider->renderWidget('contact'));
    }

    public function testVerifyDelegatesToVerifierWithToken(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRequestEscapedParameter')->with('g-recaptcha-response')->willReturn('tok');

        $verifier = $this->createMock(CaptchaVerifierInterface::class);
        $verifier->expects($this->once())->method('verify')
            ->with('SECRET', 'tok', $this->anything(), null, null)
            ->willReturn(new VerificationResult(true));

        $provider = new GoogleReCaptchaV2Provider($this->config(['secretKey' => 'SECRET']), $verifier);
        $this->assertTrue($provider->verify($request, 'contact'));
    }

    public function testVerifyShortCircuitsOnEmptyToken(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRequestEscapedParameter')->willReturn('');
        $verifier = $this->createMock(CaptchaVerifierInterface::class);
        $verifier->expects($this->never())->method('verify');

        $provider = new GoogleReCaptchaV2Provider($this->config(['secretKey' => 'SECRET']), $verifier);
        $this->assertFalse($provider->verify($request, 'contact'));
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\CaptchaVerifierInterface;

final class GoogleReCaptchaV2Provider implements CaptchaProviderInterface
{
    public const ID = 'google_recaptcha_v2';

    /** @var CaptchaConfigurationInterface */
    private $configuration;
    /** @var CaptchaVerifierInterface */
    private $verifier;

    public function __construct(CaptchaConfigurationInterface $configuration, CaptchaVerifierInterface $verifier)
    {
        $this->configuration = $configuration;
        $this->verifier = $verifier;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getTitle(): string
    {
        return 'O3_CAPTCHA_PROVIDER_GOOGLE_V2';
    }

    public function getConfigFields(): array
    {
        return [
            new CaptchaConfigField('siteKey', 'O3_CAPTCHA_SITE_KEY', CaptchaConfigField::TYPE_TEXT),
            new CaptchaConfigField('secretKey', 'O3_CAPTCHA_SECRET_KEY', CaptchaConfigField::TYPE_PASSWORD),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function getHeadScript(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    public function renderWidget(string $formId): string
    {
        if (!$this->isConfigured()) {
            return '';
        }
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($this->siteKey(), ENT_QUOTES) . '"></div>';
    }

    public function verify(Request $request, string $formId): bool
    {
        $token = trim((string) $request->getRequestEscapedParameter('g-recaptcha-response'));
        if ($token === '') {
            return false;
        }
        $result = $this->verifier->verify($this->secretKey(), $token, $this->remoteIp(), null, null);
        return $result->isSuccess();
    }

    private function siteKey(): string
    {
        return (string) $this->configuration->getProviderSetting(self::ID, 'siteKey', '');
    }

    private function secretKey(): string
    {
        return (string) $this->configuration->getProviderSetting(self::ID, 'secretKey', '');
    }

    private function remoteIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 7: `GoogleReCaptchaV3Provider`

**Files:**
- Create: `source/Internal/Domain/Captcha/Provider/GoogleReCaptchaV3Provider.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Provider/GoogleReCaptchaV3ProviderTest.php`

- [ ] **Step 1: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\GoogleReCaptchaV3Provider;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\CaptchaVerifierInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\VerificationResult;
use PHPUnit\Framework\TestCase;

class GoogleReCaptchaV3ProviderTest extends TestCase
{
    private function config(array $values): CaptchaConfigurationInterface
    {
        $config = $this->createMock(CaptchaConfigurationInterface::class);
        $config->method('getProviderSetting')->willReturnCallback(
            fn (string $providerId, string $key, $default = null) => $values[$key] ?? $default
        );
        return $config;
    }

    public function testConfigFieldsIncludeThresholdWithDefault(): void
    {
        $provider = new GoogleReCaptchaV3Provider($this->config([]), $this->createMock(CaptchaVerifierInterface::class));
        $fields = [];
        foreach ($provider->getConfigFields() as $f) {
            $fields[$f->getKey()] = $f;
        }
        $this->assertArrayHasKey('scoreThreshold', $fields);
        $this->assertSame('0.5', $fields['scoreThreshold']->getDefault());
    }

    public function testWidgetCarriesPerFormActionAndHiddenField(): void
    {
        $provider = new GoogleReCaptchaV3Provider($this->config(['siteKey' => 'SITE']), $this->createMock(CaptchaVerifierInterface::class));
        $html = $provider->renderWidget('newsletter');
        $this->assertStringContainsString("{action: 'newsletter'}", $html);
        $this->assertStringContainsString('recaptcha_token', $html);
        $this->assertStringContainsString('SITE', $html);
    }

    public function testHeadScriptCarriesRenderSiteKey(): void
    {
        $provider = new GoogleReCaptchaV3Provider($this->config(['siteKey' => 'SITE']), $this->createMock(CaptchaVerifierInterface::class));
        $this->assertStringContainsString('render=SITE', (string) $provider->getHeadScript());
    }

    public function testVerifyPassesActionAndThreshold(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRequestEscapedParameter')->with('recaptcha_token')->willReturn('tok');

        $verifier = $this->createMock(CaptchaVerifierInterface::class);
        $verifier->expects($this->once())->method('verify')
            ->with('SECRET', 'tok', $this->anything(), 'contact', 0.5)
            ->willReturn(new VerificationResult(true, 0.9, 'contact'));

        $provider = new GoogleReCaptchaV3Provider($this->config(['secretKey' => 'SECRET', 'scoreThreshold' => '0.5']), $verifier);
        $this->assertTrue($provider->verify($request, 'contact'));
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\CaptchaVerifierInterface;

final class GoogleReCaptchaV3Provider implements CaptchaProviderInterface
{
    public const ID = 'google_recaptcha_v3';
    private const TOKEN_FIELD = 'recaptcha_token';
    private const DEFAULT_THRESHOLD = '0.5';

    /** @var CaptchaConfigurationInterface */
    private $configuration;
    /** @var CaptchaVerifierInterface */
    private $verifier;

    public function __construct(CaptchaConfigurationInterface $configuration, CaptchaVerifierInterface $verifier)
    {
        $this->configuration = $configuration;
        $this->verifier = $verifier;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getTitle(): string
    {
        return 'O3_CAPTCHA_PROVIDER_GOOGLE_V3';
    }

    public function getConfigFields(): array
    {
        return [
            new CaptchaConfigField('siteKey', 'O3_CAPTCHA_SITE_KEY', CaptchaConfigField::TYPE_TEXT),
            new CaptchaConfigField('secretKey', 'O3_CAPTCHA_SECRET_KEY', CaptchaConfigField::TYPE_PASSWORD),
            new CaptchaConfigField('scoreThreshold', 'O3_CAPTCHA_SCORE_THRESHOLD', CaptchaConfigField::TYPE_NUMBER, self::DEFAULT_THRESHOLD),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function getHeadScript(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $site = rawurlencode($this->siteKey());
        return '<script src="https://www.google.com/recaptcha/api.js?render=' . $site . '"></script>';
    }

    public function renderWidget(string $formId): string
    {
        if (!$this->isConfigured()) {
            return '';
        }
        $site = htmlspecialchars($this->siteKey(), ENT_QUOTES);
        $action = preg_replace('/[^a-zA-Z0-9_]/', '', $formId);
        $field = self::TOKEN_FIELD;

        // The block renders inside the form; target the nearest enclosing form via the script's own node.
        return <<<HTML
<input type="hidden" name="{$field}" value="">
<script>
(function () {
    var s = document.currentScript;
    var form = s ? s.closest('form') : null;
    if (!form) { return; }
    form.addEventListener('submit', function (e) {
        if (form.dataset.o3CaptchaDone === '1') { return; }
        e.preventDefault();
        grecaptcha.ready(function () {
            grecaptcha.execute('{$site}', {action: '{$action}'}).then(function (token) {
                var input = form.querySelector('input[name="{$field}"]');
                if (input) { input.value = token; }
                form.dataset.o3CaptchaDone = '1';
                form.submit();
            });
        });
    });
})();
</script>
HTML;
    }

    public function verify(Request $request, string $formId): bool
    {
        $token = trim((string) $request->getRequestEscapedParameter(self::TOKEN_FIELD));
        if ($token === '') {
            return false;
        }
        $action = preg_replace('/[^a-zA-Z0-9_]/', '', $formId);
        $result = $this->verifier->verify($this->secretKey(), $token, $this->remoteIp(), $action, $this->threshold());
        return $result->isSuccess();
    }

    private function siteKey(): string
    {
        return (string) $this->configuration->getProviderSetting(self::ID, 'siteKey', '');
    }

    private function secretKey(): string
    {
        return (string) $this->configuration->getProviderSetting(self::ID, 'secretKey', '');
    }

    private function threshold(): float
    {
        return (float) $this->configuration->getProviderSetting(self::ID, 'scoreThreshold', self::DEFAULT_THRESHOLD);
    }

    private function remoteIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 8: `CaptchaProviderLocator`

**Files:**
- Create: `source/Internal/Domain/Captcha/Provider/CaptchaProviderLocator.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Provider/CaptchaProviderLocatorTest.php`

- [ ] **Step 1: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Provider;

use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use PHPUnit\Framework\TestCase;

class CaptchaProviderLocatorTest extends TestCase
{
    private function provider(string $id): CaptchaProviderInterface
    {
        $p = $this->createMock(CaptchaProviderInterface::class);
        $p->method('getId')->willReturn($id);
        return $p;
    }

    public function testGetByIdReturnsMatchingProvider(): void
    {
        $v2 = $this->provider('google_recaptcha_v2');
        $locator = new CaptchaProviderLocator([$v2], new NullCaptchaProvider());
        $this->assertSame($v2, $locator->getById('google_recaptcha_v2'));
    }

    public function testGetByIdFallsBackToNull(): void
    {
        $null = new NullCaptchaProvider();
        $locator = new CaptchaProviderLocator([$this->provider('x')], $null);
        $this->assertSame($null, $locator->getById('does-not-exist'));
    }

    public function testGetAllIsKeyedById(): void
    {
        $locator = new CaptchaProviderLocator([$this->provider('a'), $this->provider('b')], new NullCaptchaProvider());
        $this->assertSame(['a', 'b'], array_keys($locator->getAll()));
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** (accepts an iterable of tagged providers + the null fallback)

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

final class CaptchaProviderLocator
{
    /** @var array<string, CaptchaProviderInterface> */
    private $providers = [];
    /** @var NullCaptchaProvider */
    private $nullProvider;

    /** @param iterable<CaptchaProviderInterface> $providers */
    public function __construct(iterable $providers, NullCaptchaProvider $nullProvider)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getId()] = $provider;
        }
        $this->nullProvider = $nullProvider;
    }

    /** @return array<string, CaptchaProviderInterface> */
    public function getAll(): array
    {
        return $this->providers;
    }

    public function getById(string $id): CaptchaProviderInterface
    {
        return $this->providers[$id] ?? $this->nullProvider;
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 9: `CaptchaConfigurationInterface` + `CaptchaConfiguration`

> **Execute this before Tasks 6–7** (they depend on the interface).

**Files:**
- Create: `source/Internal/Domain/Captcha/Configuration/CaptchaConfigurationInterface.php`
- Create: `source/Internal/Domain/Captcha/Configuration/CaptchaConfiguration.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Configuration/CaptchaConfigurationTest.php`

- [ ] **Step 1: Define interface**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration;

interface CaptchaConfigurationInterface
{
    public function getActiveProviderId(): string;

    public function isFormEnabled(string $formId): bool;

    public function isConsentRequired(): bool;

    /** @return mixed */
    public function getProviderSetting(string $providerId, string $key, $default = null);
}
```

- [ ] **Step 2: Failing test** (mock `Config` via Registry)

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Configuration;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfiguration;
use PHPUnit\Framework\TestCase;

class CaptchaConfigurationTest extends TestCase
{
    private array $params = [];

    protected function setUp(): void
    {
        $this->params = [];
        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn (string $name, $default = false) => $this->params[$name] ?? $default
        );
        Registry::set(Config::class, $config);
    }

    protected function tearDown(): void
    {
        Registry::set(Config::class, null);
    }

    public function testActiveProviderDefaultsToEmpty(): void
    {
        $this->assertSame('', (new CaptchaConfiguration())->getActiveProviderId());
    }

    public function testFormEnabledReadsPerFormFlag(): void
    {
        $this->params['blCaptchaForm_contact'] = true;
        $cfg = new CaptchaConfiguration();
        $this->assertTrue($cfg->isFormEnabled('contact'));
        $this->assertFalse($cfg->isFormEnabled('newsletter'));
    }

    public function testConsentRequiredDefaultsTrue(): void
    {
        $this->assertTrue((new CaptchaConfiguration())->isConsentRequired());
        $this->params['blCaptchaRequireConsent'] = false;
        $this->assertFalse((new CaptchaConfiguration())->isConsentRequired());
    }

    public function testProviderSettingNamespacesByProviderId(): void
    {
        $this->params['sCaptcha_google_recaptcha_v2_siteKey'] = 'ABC';
        $cfg = new CaptchaConfiguration();
        $this->assertSame('ABC', $cfg->getProviderSetting('google_recaptcha_v2', 'siteKey', ''));
        $this->assertSame('def', $cfg->getProviderSetting('google_recaptcha_v2', 'secretKey', 'def'));
    }
}
```

- [ ] **Step 3: Run, expect FAIL.**
- [ ] **Step 4: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration;

use OxidEsales\Eshop\Core\Registry;

final class CaptchaConfiguration implements CaptchaConfigurationInterface
{
    public const PROVIDER_KEY = 'sCaptchaProvider';
    public const CONSENT_KEY = 'blCaptchaRequireConsent';
    public const FORM_PREFIX = 'blCaptchaForm_';
    public const PROVIDER_SETTING_PREFIX = 'sCaptcha_';

    public function getActiveProviderId(): string
    {
        return (string) Registry::getConfig()->getConfigParam(self::PROVIDER_KEY, '');
    }

    public function isFormEnabled(string $formId): bool
    {
        return (bool) Registry::getConfig()->getConfigParam(self::FORM_PREFIX . $formId, false);
    }

    public function isConsentRequired(): bool
    {
        // Default true (consent-gated) when the param has never been saved.
        $value = Registry::getConfig()->getConfigParam(self::CONSENT_KEY, true);
        return (bool) $value;
    }

    public function getProviderSetting(string $providerId, string $key, $default = null)
    {
        return Registry::getConfig()->getConfigParam(self::PROVIDER_SETTING_PREFIX . $providerId . '_' . $key, $default);
    }
}
```

- [ ] **Step 5: Run, expect PASS.**
- [ ] **Step 6: Commit.**

## Task 10: `CaptchaConsentInterface` + `ConfigBasedCaptchaConsent`

**Files:**
- Create: `source/Internal/Domain/Captcha/Consent/CaptchaConsentInterface.php`
- Create: `source/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsent.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Consent/ConfigBasedCaptchaConsentTest.php`

- [ ] **Step 1: Define interface**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent;

use OxidEsales\Eshop\Core\Request;

interface CaptchaConsentInterface
{
    public function isConsentGranted(Request $request): bool;
}
```

- [ ] **Step 2: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Consent;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\ConfigBasedCaptchaConsent;
use PHPUnit\Framework\TestCase;

class ConfigBasedCaptchaConsentTest extends TestCase
{
    public function testGrantedWhenConsentNotRequired(): void
    {
        $cfg = $this->createMock(CaptchaConfigurationInterface::class);
        $cfg->method('isConsentRequired')->willReturn(false);
        $this->assertTrue((new ConfigBasedCaptchaConsent($cfg))->isConsentGranted($this->createMock(Request::class)));
    }

    public function testNotGrantedByDefaultWhenConsentRequired(): void
    {
        $cfg = $this->createMock(CaptchaConfigurationInterface::class);
        $cfg->method('isConsentRequired')->willReturn(true);
        $this->assertFalse((new ConfigBasedCaptchaConsent($cfg))->isConsentGranted($this->createMock(Request::class)));
    }
}
```

- [ ] **Step 3: Run, expect FAIL.**
- [ ] **Step 4: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent;

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
        // When consent is not required, it is implicitly granted.
        // When required, the default integration grants nothing; a consent
        // tool (e.g. usercentrics) overrides this binding to return its own signal.
        return !$this->configuration->isConsentRequired();
    }
}
```

- [ ] **Step 5: Run, expect PASS.**
- [ ] **Step 6: Commit.**

## Task 11: `CaptchaFormRegistry`

**Files:**
- Create: `source/Internal/Domain/Captcha/Form/CaptchaFormRegistry.php`
- Test: `tests/Unit/Internal/Domain/Captcha/Form/CaptchaFormRegistryTest.php`

- [ ] **Step 1: Failing test**

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Form;

use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Form\CaptchaFormRegistry;
use PHPUnit\Framework\TestCase;

class CaptchaFormRegistryTest extends TestCase
{
    public function testListsTheSevenFormIds(): void
    {
        $this->assertSame(
            ['contact', 'newsletter', 'suggest', 'forgotpwd', 'register', 'pricealarm', 'invite'],
            (new CaptchaFormRegistry())->getFormIds()
        );
    }

    public function testKnowsWhetherAFormIsRegistered(): void
    {
        $registry = new CaptchaFormRegistry();
        $this->assertTrue($registry->has('contact'));
        $this->assertFalse($registry->has('checkout'));
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Form;

final class CaptchaFormRegistry
{
    private const FORM_IDS = ['contact', 'newsletter', 'suggest', 'forgotpwd', 'register', 'pricealarm', 'invite'];

    /** @return string[] */
    public function getFormIds(): array
    {
        return self::FORM_IDS;
    }

    public function has(string $formId): bool
    {
        return in_array($formId, self::FORM_IDS, true);
    }
}
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 12: `CaptchaServiceInterface` + `CaptchaService`

**Files:**
- Create: `source/Internal/Domain/Captcha/CaptchaServiceInterface.php`
- Create: `source/Internal/Domain/Captcha/CaptchaService.php`
- Test: `tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`

- [ ] **Step 1: Define interface**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha;

use OxidEsales\Eshop\Core\Request;

interface CaptchaServiceInterface
{
    public function isEnabledForForm(string $formId): bool;

    public function renderForForm(string $formId): string;

    public function verifyForForm(string $formId, Request $request): bool;
}
```

- [ ] **Step 2: Failing test** (covers: unprotected → verify true; per-form toggle; consent policy; head-script de-dup)

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaService;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\CaptchaConsentInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider;
use PHPUnit\Framework\TestCase;

class CaptchaServiceTest extends TestCase
{
    private function provider(string $id): CaptchaProviderInterface
    {
        $p = $this->createMock(CaptchaProviderInterface::class);
        $p->method('getId')->willReturn($id);
        $p->method('isConfigured')->willReturn(true);
        $p->method('getHeadScript')->willReturn('<script id="head"></script>');
        $p->method('renderWidget')->willReturn('<div id="widget"></div>');
        $p->method('verify')->willReturn(true);
        return $p;
    }

    private function service(CaptchaProviderInterface $provider, array $opts): CaptchaService
    {
        $config = $this->createMock(CaptchaConfigurationInterface::class);
        $config->method('getActiveProviderId')->willReturn($opts['active'] ?? $provider->getId());
        $config->method('isFormEnabled')->willReturnCallback(fn ($f) => $opts['enabled'] ?? true);

        $consent = $this->createMock(CaptchaConsentInterface::class);
        $consent->method('isConsentGranted')->willReturn($opts['consent'] ?? true);

        $locator = new CaptchaProviderLocator([$provider], new NullCaptchaProvider());
        return new CaptchaService($locator, $config, $consent);
    }

    public function testUnprotectedWhenNoActiveProvider(): void
    {
        $svc = $this->service($this->provider('p'), ['active' => '']);
        $this->assertFalse($svc->isEnabledForForm('contact'));
        $this->assertSame('', $svc->renderForForm('contact'));
        $this->assertTrue($svc->verifyForForm('contact', $this->createMock(Request::class)));
    }

    public function testDisabledFormIsUnprotected(): void
    {
        $svc = $this->service($this->provider('p'), ['enabled' => false]);
        $this->assertSame('', $svc->renderForForm('contact'));
        $this->assertTrue($svc->verifyForForm('contact', $this->createMock(Request::class)));
    }

    public function testEnabledAndConsentedRendersWidgetAndEnforces(): void
    {
        $svc = $this->service($this->provider('p'), ['consent' => true]);
        $html = $svc->renderForForm('contact');
        $this->assertStringContainsString('id="head"', $html);
        $this->assertStringContainsString('id="widget"', $html);
        $this->assertTrue($svc->verifyForForm('contact', $this->createMock(Request::class)));
    }

    public function testConsentRequiredButNotGrantedShowsNoticeAndSkipsVerification(): void
    {
        $svc = $this->service($this->provider('p'), ['consent' => false]);
        $html = $svc->renderForForm('contact');
        $this->assertStringNotContainsString('id="widget"', $html);
        // verify is skipped (true) so a non-consenting user is not blocked
        $this->assertTrue($svc->verifyForForm('contact', $this->createMock(Request::class)));
    }

    public function testHeadScriptEmittedOncePerRequest(): void
    {
        $svc = $this->service($this->provider('p'), ['consent' => true]);
        $first = $svc->renderForForm('contact');
        $second = $svc->renderForForm('newsletter');
        $this->assertStringContainsString('id="head"', $first);
        $this->assertStringNotContainsString('id="head"', $second);
        $this->assertStringContainsString('id="widget"', $second);
    }
}
```

- [ ] **Step 3: Run, expect FAIL.**
- [ ] **Step 4: Implement**

```php
namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\CaptchaConsentInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;

final class CaptchaService implements CaptchaServiceInterface
{
    /** @var CaptchaProviderLocator */
    private $locator;
    /** @var CaptchaConfigurationInterface */
    private $configuration;
    /** @var CaptchaConsentInterface */
    private $consent;
    /** @var bool */
    private $headScriptEmitted = false;

    public function __construct(
        CaptchaProviderLocator $locator,
        CaptchaConfigurationInterface $configuration,
        CaptchaConsentInterface $consent
    ) {
        $this->locator = $locator;
        $this->configuration = $configuration;
        $this->consent = $consent;
    }

    public function isEnabledForForm(string $formId): bool
    {
        return $this->activeProvider()->isConfigured() && $this->configuration->isFormEnabled($formId);
    }

    public function renderForForm(string $formId): string
    {
        if (!$this->isEnabledForForm($formId)) {
            return '';
        }
        if (!$this->consent->isConsentGranted(Registry::getRequest())) {
            return '<div class="o3-captcha-consent-notice">'
                . htmlspecialchars((string) Registry::getLang()->translateString('O3_CAPTCHA_CONSENT_NOTICE'), ENT_QUOTES)
                . '</div>';
        }

        $provider = $this->activeProvider();
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
        if (!$this->consent->isConsentGranted($request)) {
            // No consent → CAPTCHA never ran → do not block a non-consenting user.
            return true;
        }
        return $this->activeProvider()->verify($request, $formId);
    }

    private function activeProvider(): CaptchaProviderInterface
    {
        return $this->locator->getById($this->configuration->getActiveProviderId());
    }
}
```

- [ ] **Step 5: Run, expect PASS.**
- [ ] **Step 6: Commit.**

## Task 13: DI wiring (`services.yaml`)

**Files:**
- Create: `source/Internal/Domain/Captcha/services.yaml`
- Modify: `source/Internal/Container/services.yaml` (import the new file — verify how the project imports domain service files; mirror the Revocation import)

- [ ] **Step 1: Confirm the import mechanism**

Run: `grep -rn "Revocation/services.yaml\|imports" source/Internal/Container/services.yaml`
Expected: shows how `Revocation/services.yaml` is imported. Add the Captcha file the same way.

- [ ] **Step 2: Write `Captcha/services.yaml`**

```yaml
services:
    _defaults:
        autowire: true
        public: false

    # --- pluggable provider seam -------------------------------------------
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider: ~

    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\GoogleReCaptchaV2Provider:
        tags: ['oxid.captcha.provider']

    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\GoogleReCaptchaV3Provider:
        tags: ['oxid.captcha.provider']

    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator:
        arguments:
            $providers: !tagged_iterator oxid.captcha.provider
            $nullProvider: '@OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider'

    # --- verifier (official google/recaptcha library) ----------------------
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\CaptchaVerifierInterface:
        class: OxidEsales\EshopCommunity\Internal\Domain\Captcha\Verifier\GoogleReCaptchaVerifier

    # --- configuration -----------------------------------------------------
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface:
        class: OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfiguration
        public: true

    # --- consent (overridable by a consent-tool module) --------------------
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\CaptchaConsentInterface:
        class: OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\ConfigBasedCaptchaConsent

    # --- form registry -----------------------------------------------------
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Form\CaptchaFormRegistry:
        public: true

    # --- facade (used by ViewConfig + controllers) -------------------------
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface:
        class: OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaService
        public: true
```

- [ ] **Step 3: Import it** in `source/Internal/Container/services.yaml` under `imports:` mirroring the Revocation entry (e.g. `- { resource: ../Domain/Captcha/services.yaml }` — match the exact relative-path style already used).

- [ ] **Step 4: Smoke-test the container** — run the existing unit suite for Internal to ensure the container compiles:
`./docker.sh test --fast tests/Unit/Internal/Domain/Captcha/CaptchaServiceTest.php`
Expected: PASS (no container-compile errors). If `!tagged_iterator` is unsupported by the project's Symfony DI version, fall back to `!tagged` (verify which the codebase uses — `grep -rn "tagged_iterator\|!tagged" source/Internal`).

- [ ] **Step 5: Commit.**

---

# Phase 2 — Storefront rendering

## Task 14: `ViewConfig::getCaptchaWidget()`

**Files:**
- Modify: `source/Core/ViewConfig.php` (add one method)
- Test: `tests/Unit/Core/Captcha/ViewConfigCaptchaTest.php`

- [ ] **Step 1: Failing test** (resolve the service from the container; assert delegation)

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Captcha;

use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface;
use PHPUnit\Framework\TestCase;

class ViewConfigCaptchaTest extends TestCase
{
    public function testGetCaptchaWidgetDelegatesToService(): void
    {
        $service = $this->createMock(CaptchaServiceInterface::class);
        $service->method('renderForForm')->with('contact')->willReturn('<div id="w"></div>');

        $container = ContainerFactory::getInstance()->getContainer();
        $container->set(CaptchaServiceInterface::class, $service);

        $viewConfig = oxNew(ViewConfig::class);
        $this->assertSame('<div id="w"></div>', $viewConfig->getCaptchaWidget('contact'));
    }
}
```

> If `$container->set()` at runtime is not permitted in this codebase's compiled container, instead assert that `getCaptchaWidget` returns a string and move the delegation assertion into an integration test. Check `grep -rn "getContainer()->set(" tests` for the established pattern first.

- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** — add to `source/Core/ViewConfig.php`:

```php
    /**
     * Returns the CAPTCHA widget markup for the given protected form id,
     * or an empty string when CAPTCHA is inactive for that form.
     *
     * @param string $formId One of the ids in CaptchaFormRegistry.
     */
    public function getCaptchaWidget(string $formId): string
    {
        $service = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()
            ->getContainer()
            ->get(\OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface::class);

        return $service->renderForForm($formId);
    }
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 15: Fill the 14 `captcha_form` template blocks

**Files (modify) — for each, replace the empty block with the widget call:**

o3-theme (`source/Application/views/o3-theme/tpl/`) and wave (`source/Application/views/wave/tpl/`), same relative paths:
- `form/contact.tpl` → formId `contact`
- `form/newsletter.tpl` → `newsletter`
- `form/suggest.tpl` → `suggest`
- `form/forgotpwd_email.tpl` → `forgotpwd`
- `form/fieldset/user_billing.tpl` → `register`
- `form/pricealarm.tpl` → `pricealarm`
- `form/privatesales/invite.tpl` → `invite`

- [ ] **Step 1: For every file above**, replace `[{block name="captcha_form"}][{/block}]` with (substituting the correct formId):

```smarty
[{block name="captcha_form"}][{$oViewConf->getCaptchaWidget('contact')}][{/block}]
```

Verify each file still has exactly one `captcha_form` block: `grep -n "captcha_form" <file>`.

- [ ] **Step 2: Manual smoke (docker up)** — with a v2 key configured + the contact form enabled, load `/Kontakt` in both themes and confirm the widget renders inside the form (`./docker.sh start` first). This is a manual check; no automated assertion here.

- [ ] **Step 3: Commit** — `git add source/Application/views && git commit -m "feat(#213): render CAPTCHA widget into the 7 form blocks (o3-theme + wave)" -m "<trailer>"`

---

# Phase 3 — Server-side verification (cross-cutting)

For each controller below, insert the verification block at the **top of the submit method**, before any processing. The block (substitute the form id) is:

```php
        $captchaService = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()
            ->getContainer()
            ->get(\OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface::class);
        if (!$captchaService->verifyForForm('contact', \OxidEsales\Eshop\Core\Registry::getRequest())) {
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay('O3_CAPTCHA_FAILED');
            return false;
        }
```

(Use `return false;` — every target method tolerates a falsy early return to keep the user on the form with input preserved; this matches the reference module's pattern.)

## Task 16: ContactController

**Files:**
- Modify: `source/Application/Controller/ContactController.php` — method `send()` (line ~108)
- Test: `tests/Unit/Application/Controller/Captcha/ContactControllerCaptchaTest.php`

- [ ] **Step 1: Failing test** — set the container `CaptchaServiceInterface` to a mock whose `verifyForForm('contact', …)` returns false; assert `send()` returns false and an error was added.

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Captcha;

use OxidEsales\Eshop\Application\Controller\ContactController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface;
use PHPUnit\Framework\TestCase;

class ContactControllerCaptchaTest extends TestCase
{
    public function testSendIsRejectedWhenCaptchaFails(): void
    {
        $service = $this->createMock(CaptchaServiceInterface::class);
        $service->method('verifyForForm')->willReturn(false);
        ContainerFactory::getInstance()->getContainer()->set(CaptchaServiceInterface::class, $service);

        $controller = oxNew(ContactController::class);
        $this->assertFalse($controller->send());

        $errors = Registry::getSession()->getVariable('Errors');
        $this->assertNotEmpty($errors);
    }
}
```

> Verify the project's idiom for asserting `addErrorToDisplay` (some suites read `Registry::getSession()->getVariable('Errors')['default']`). Mirror `RevocationControllerTest`. Adjust the assertion to match.

- [ ] **Step 2: Run, expect FAIL** (send proceeds today).
- [ ] **Step 3: Insert the verification block** at the top of `send()` with formId `contact`.
- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit.**

## Task 17: SuggestController — `send()`, formId `suggest`
Same pattern as Task 16. Test file `tests/Unit/Application/Controller/Captcha/SuggestControllerCaptchaTest.php`. Steps 1–5 identical, substituting `SuggestController`, `send()`, `'suggest'`.

## Task 18: ForgotPasswordController — `forgotPassword()`, formId `forgotpwd`
Same pattern. Note the method is `forgotPassword()` (camelCase). Test `ForgotPasswordControllerCaptchaTest`.

## Task 19: NewsletterController — `addme()`, formId `newsletter`
Same pattern. Test `NewsletterControllerCaptchaTest`.

## Task 20: PriceAlarmController — `addme()`, formId `pricealarm`
Same pattern. Test `PriceAlarmControllerCaptchaTest`.

## Task 21: InviteController — `send()`, formId `invite`
Same pattern. Test `InviteControllerCaptchaTest`.

## Task 22: UserComponent — `registerUser()`, formId `register`
**Files:** Modify `source/Application/Component/UserComponent.php` `registerUser()` (line ~695); Test `tests/Unit/Application/Component/Captcha/UserComponentCaptchaTest.php`.
Insert the verification block at the top of `registerUser()` with formId `register`, `return false;` on failure (matches existing failed-registration returns — confirm by reading the method's existing error returns). This single hook covers the account page **and** the checkout user step. Steps 1–5 as in Task 16.

---

# Phase 4 — Admin configuration screen

## Task 23: `CaptchaConfigController` (admin)

**Files:**
- Create: `source/Application/Controller/Admin/CaptchaConfigController.php`
- Test: `tests/Unit/Application/Controller/Admin/CaptchaConfigControllerTest.php`

Mirror `source/Application/Controller/Admin/RevocationConfigController.php` (read it first for the exact base class, `render()` return, and save idiom).

- [ ] **Step 1: Read the reference** — `cat source/Application/Controller/Admin/RevocationConfigController.php` to copy its structure (extends `AdminDetailsController` or `AdminController`, template return, `save()` using `Registry::getConfig()->saveShopConfVar()`).

- [ ] **Step 2: Failing test** — assert `render()` returns the template name and that the view data exposes the provider list + active provider.

```php
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\EshopCommunity\Application\Controller\Admin\CaptchaConfigController;
use PHPUnit\Framework\TestCase;

class CaptchaConfigControllerTest extends TestCase
{
    public function testRenderReturnsTemplateAndExposesProviders(): void
    {
        $controller = oxNew(CaptchaConfigController::class);
        $this->assertSame('captcha_config.tpl', $controller->render());

        $providers = $controller->getCaptchaProviders(); // [id => titleIdent], excludes the Null provider
        $this->assertArrayHasKey('google_recaptcha_v2', $providers);
        $this->assertArrayHasKey('google_recaptcha_v3', $providers);
        $this->assertArrayNotHasKey('', $providers);
    }

    public function testProviderFieldsExposeSelectedProviderConfigFields(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn ($n, $d = false) => $n === 'sCaptchaProvider' ? 'google_recaptcha_v3' : $d
        );
        Registry::set(Config::class, $config);

        $controller = oxNew(CaptchaConfigController::class);
        $keys = array_map(fn ($f) => $f->getKey(), $controller->getActiveProviderConfigFields());
        $this->assertContains('scoreThreshold', $keys);

        Registry::set(Config::class, null);
    }
}
```

- [ ] **Step 3: Run, expect FAIL.**
- [ ] **Step 4: Implement**

```php
namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfiguration;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Form\CaptchaFormRegistry;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;

class CaptchaConfigController extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'captcha_config.tpl';

    public function render()
    {
        parent::render();
        return $this->_sThisTemplate;
    }

    /** @return array<string,string> id => title lang ident (excludes the Null provider) */
    public function getCaptchaProviders(): array
    {
        $out = [];
        foreach ($this->locator()->getAll() as $id => $provider) {
            if ($id === '') {
                continue;
            }
            $out[$id] = $provider->getTitle();
        }
        return $out;
    }

    public function getActiveProviderId(): string
    {
        return $this->configuration()->getActiveProviderId();
    }

    /** @return \OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField[] */
    public function getActiveProviderConfigFields(): array
    {
        return $this->locator()->getById($this->getActiveProviderId())->getConfigFields();
    }

    /** @return string[] */
    public function getCaptchaFormIds(): array
    {
        return $this->container()->get(CaptchaFormRegistry::class)->getFormIds();
    }

    public function getProviderSettingValue(string $key): string
    {
        return (string) $this->configuration()->getProviderSetting($this->getActiveProviderId(), $key, '');
    }

    public function isFormEnabled(string $formId): bool
    {
        return $this->configuration()->isFormEnabled($formId);
    }

    public function isConsentRequired(): bool
    {
        return $this->configuration()->isConsentRequired();
    }

    public function save()
    {
        $config = Registry::getConfig();
        $request = Registry::getRequest();
        $shopId = $config->getShopId();

        $provider = (string) $request->getRequestEscapedParameter('sCaptchaProvider');
        $config->saveShopConfVar('str', CaptchaConfiguration::PROVIDER_KEY, $provider, $shopId, 'module:captcha');

        $config->saveShopConfVar(
            'bool',
            CaptchaConfiguration::CONSENT_KEY,
            (bool) $request->getRequestEscapedParameter('blCaptchaRequireConsent'),
            $shopId,
            'module:captcha'
        );

        foreach ($this->getCaptchaFormIds() as $formId) {
            $config->saveShopConfVar(
                'bool',
                CaptchaConfiguration::FORM_PREFIX . $formId,
                (bool) $request->getRequestEscapedParameter('blCaptchaForm_' . $formId),
                $shopId,
                'module:captcha'
            );
        }

        if ($provider !== '') {
            foreach ($this->locator()->getById($provider)->getConfigFields() as $field) {
                $name = CaptchaConfiguration::PROVIDER_SETTING_PREFIX . $provider . '_' . $field->getKey();
                $value = (string) $request->getRequestEscapedParameter('providerField_' . $field->getKey());
                $config->saveShopConfVar('str', $name, $value, $shopId, 'module:captcha');
            }
        }

        parent::save();
    }

    private function container()
    {
        return ContainerFactory::getInstance()->getContainer();
    }

    private function locator(): CaptchaProviderLocator
    {
        return $this->container()->get(CaptchaProviderLocator::class);
    }

    private function configuration(): CaptchaConfigurationInterface
    {
        return $this->container()->get(CaptchaConfigurationInterface::class);
    }
}
```

> `CaptchaProviderLocator` must be `public: true` for `getCaptchaProviders()`; add `public: true` to its service definition in Task 13's yaml (update if not already public).

- [ ] **Step 5: Run, expect PASS.**
- [ ] **Step 6: Commit.**

## Task 24: Admin template `captcha_config.tpl`

**Files:**
- Create: `source/Application/views/admin/tpl/captcha_config.tpl`

- [ ] **Step 1: Read the reference** `source/Application/views/admin/tpl/revocation_config.tpl` to copy the form/save scaffolding (form action, `fnc=save`, token, submit button).

- [ ] **Step 2: Write the template** following that scaffolding, with:
- a `<select name="sCaptchaProvider">` populated from `[{$oView->getCaptchaProviders()}]` (option value = id, label = `[{oxmultilang ident=$titleIdent}]`), preselecting `[{$oView->getActiveProviderId()}]`, plus a "None" option (value `""`).
- a loop over `[{$oView->getActiveProviderConfigFields()}]` rendering each field: `password` → `<input type="password">`, `number` → `<input type="number" step="0.1" min="0" max="1">`, else `<input type="text">`, named `providerField_[{$field->getKey()}]`, value `[{$oView->getProviderSettingValue($field->getKey())}]`, label `[{oxmultilang ident=$field->getLabelIdent()}]`.
- a checkbox `blCaptchaRequireConsent` checked from `[{$oView->isConsentRequired()}]`.
- a loop over `[{$oView->getCaptchaFormIds()}]` rendering a checkbox `blCaptchaForm_[{$formId}]` checked from `[{$oView->isFormEnabled($formId)}]`, label `[{oxmultilang ident="O3_CAPTCHA_FORM_"|cat:$formId}]`.

- [ ] **Step 3: Manual check** — open the admin screen after Task 25 wires the menu; confirm fields render and saving persists. (Manual; no unit test for the template.)
- [ ] **Step 4: Commit.**

## Task 25: `menu.xml` entry + admin lang keys

**Files:**
- Modify: `source/Application/views/admin/menu.xml`
- Modify: `source/Application/views/admin/de/lang.php`
- Modify: `source/Application/views/admin/en/lang.php`

- [ ] **Step 1: Read** the `RevocationConfig` entry in `menu.xml` (`grep -n "RevocationConfig" source/Application/views/admin/menu.xml`) and add a sibling `<SUBMENU>` for `CaptchaConfigController` under the same `<MAINMENU>` (e.g. "Extensions"/"Core Settings"), `id="o3captcha"`, `cl="captcha_config"`.

- [ ] **Step 2: Add lang keys** to both `de/lang.php` and `en/lang.php`:

```php
    'mxo3captcha'                       => 'CAPTCHA',           // de: 'CAPTCHA'
    'O3_CAPTCHA_PROVIDER_NONE'          => 'None (disabled)',   // de: 'Keiner (deaktiviert)'
    'O3_CAPTCHA_PROVIDER_GOOGLE_V2'     => 'Google reCAPTCHA v2 (checkbox)',
    'O3_CAPTCHA_PROVIDER_GOOGLE_V3'     => 'Google reCAPTCHA v3 (invisible/score)',
    'O3_CAPTCHA_SITE_KEY'               => 'Site key',          // de: 'Site Key'
    'O3_CAPTCHA_SECRET_KEY'             => 'Secret key',        // de: 'Secret Key'
    'O3_CAPTCHA_SCORE_THRESHOLD'        => 'Minimum score to pass (0.0–1.0)',
    'O3_CAPTCHA_REQUIRE_CONSENT'        => 'Require consent before loading',
    'O3_CAPTCHA_FORM_contact'           => 'Contact form',
    'O3_CAPTCHA_FORM_newsletter'        => 'Newsletter form',
    'O3_CAPTCHA_FORM_suggest'           => 'Recommend-a-product form',
    'O3_CAPTCHA_FORM_forgotpwd'         => 'Forgot-password form',
    'O3_CAPTCHA_FORM_register'          => 'Registration form',
    'O3_CAPTCHA_FORM_pricealarm'        => 'Price-alarm form',
    'O3_CAPTCHA_FORM_invite'            => 'Invite-a-friend form',
```

Also add the storefront-facing keys to the **theme** lang files (`source/Application/views/o3-theme/{de,en}/...` and wave equivalents — locate with `grep -rln "O3_REVOCATION" source/Application/views/o3-theme` to find the right lang file):

```php
    'O3_CAPTCHA_FAILED'         => 'The security check failed. Please try again.',   // de: 'Die Sicherheitsüberprüfung ist fehlgeschlagen. Bitte versuchen Sie es erneut.'
    'O3_CAPTCHA_CONSENT_NOTICE' => 'Spam protection is disabled until you accept the required cookies.', // de: '...'
```

- [ ] **Step 3: Manual check** — admin menu shows the CAPTCHA entry; storefront error string resolves (no raw ident). Manual.
- [ ] **Step 4: Commit.**

---

# Phase 5 — Docs, dependency note, finish

## Task 26: Module-extension contract doc

**Files:**
- Create: `docs/captcha-provider-modules.md`

- [ ] **Step 1: Write the doc** covering: implement `CaptchaProviderInterface`; declare credentials via `getConfigFields()`; register the service in the module's `services.yaml` tagged `oxid.captcha.provider`; activate; it appears in the admin dropdown. Include a minimal example provider class + `services.yaml` snippet (the tag is `oxid.captcha.provider`). Note that `getTitle()` / `getConfigFields()` labels are lang idents the module supplies in its own admin lang file.
- [ ] **Step 2: Commit.**

## Task 27: Final verification

- [ ] **Step 1:** `./docker.sh start` (worktree container).
- [ ] **Step 2:** `./docker.sh cs-fixer` — fix any style issues, re-run until clean.
- [ ] **Step 3:** `./docker.sh test-all-coverage` — full suite + coverage. Expected: all green, coverage ≥ 90%. If any new class is under-covered, add the missing unit test before proceeding.
- [ ] **Step 4: Manual acceptance** (documented in the spec §12): with real v2 keys then v3 keys, on both themes (wave BS4, o3-theme BS5), mobile + desktop: widget visible on each enabled form; failing v2 (no tick) and low-score v3 are rejected server-side with a visible error + preserved input; valid challenge submits; switching v2↔v3 needs no code change; no JS console errors.
- [ ] **Step 5: Commit** any final fixes.

---

## Self-review checklist (completed by plan author)

- **Spec coverage:** provider seam (T3,4,6,7,8), verifier+lib (T5), config (T9), consent policy (T10,12), form registry (T11), service facade (T12), DI (T13), storefront render (T14,15), 7-controller verify (T16–22), admin screen (T23–25), module-extension doc (T26), dependency add (T5), testing+coverage (T27). All §-sections mapped.
- **Type consistency:** `getProviderSetting($providerId,$key,$default)`, `verify(Request,$formId)`, `renderForForm`/`verifyForForm`, `CaptchaConfigField` getters, `VerificationResult` getters, tag `oxid.captcha.provider`, token fields `g-recaptcha-response` (v2) / `recaptcha_token` (v3) — consistent across tasks.
- **Open verification points flagged inline** (not placeholders): exact DI import style for the services.yaml (T13 Step 1/3), `tagged_iterator` vs `tagged` (T13 Step 4), container `set()` in unit tests vs integration (T14 Step 1 note, T16 Step 1 note), admin base class + save idiom (T23 Step 1), `addErrorToDisplay` assertion idiom (T16 note), theme lang file location (T25 Step 2). Each says exactly what to check and the fallback.
