# Adding a Third-Party CAPTCHA Provider as a Module

## Overview

O3-Shop ships a pluggable CAPTCHA layer **in core**: the provider seam (`CaptchaProviderInterface`), the provider locator + service, the admin configuration screen, the storefront form hooks, and a no-op `NullCaptchaProvider`. Core deliberately ships **no** concrete third-party provider, so it carries no external CAPTCHA dependency and stays PHP 7.4-compatible.

Providers themselves ship as **modules**. Google reCAPTCHA v2 and v3 are provided by the separate **`o3-shop/recaptcha-module`** (PHP 8.0+, because it depends on `google/recaptcha`) — the reference implementation of this guide. Any module can add its own provider without touching core; the admin then selects it from a dropdown and configures its credentials through the standard admin interface.

The extension point is `CaptchaProviderInterface`. Implement it, register the service with the `oxid.captcha.provider` tag, and the rest happens automatically.

---

## Implement `CaptchaProviderInterface`

The interface lives at:

```
OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface
```

It defines seven methods:

| Method | Return type | Purpose |
|---|---|---|
| `getId()` | `string` | A unique, stable machine identifier for your provider (e.g. `'acme_captcha'`). Used as a key in config storage — **never change it after release**. |
| `getTitle()` | `string` | A translation ident shown in the admin dropdown (e.g. `'ACME_CAPTCHA_PROVIDER_TITLE'`). |
| `isConfigured()` | `bool` | Return `true` when all required credentials are present. The admin UI uses this to warn the shop owner. |
| `getConfigFields()` | `CaptchaConfigField[]` | Declare the admin config fields your provider needs (site key, secret, etc.). |
| `getHeadScript()` | `?string` | Return an HTML `<script>` tag that loads your provider's JavaScript, or `null` if none is needed. |
| `renderWidget(string $formId)` | `string` | Return the HTML markup injected into every protected form. |
| `verify(Request $request, string $formId)` | `bool` | Read the token from the request and perform the server-side verification. Return `true` on success. |

### Declaring config fields with `CaptchaConfigField`

```php
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;

new CaptchaConfigField(
    string $key,        // field key, e.g. 'siteKey'
    string $labelIdent, // admin lang ident, e.g. 'ACME_CAPTCHA_SITE_KEY'
    string $type,       // one of the TYPE_* constants (see below)
    string $default = '' // optional default value
)
```

Available type constants on `CaptchaConfigField`:

| Constant | Value | Use for |
|---|---|---|
| `TYPE_TEXT` | `'text'` | Plain text input (e.g. site key) |
| `TYPE_PASSWORD` | `'password'` | Masked input (e.g. secret key) |
| `TYPE_NUMBER` | `'number'` | Numeric input (e.g. threshold score) |
| `TYPE_CHECKBOX` | `'checkbox'` | Boolean toggle |

---

## A Minimal Example Provider

```php
<?php

declare(strict_types=1);

namespace Acme\CaptchaModule\Provider;

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;

final class AcmeCaptchaProvider implements CaptchaProviderInterface
{
    public const ID = 'acme_captcha';

    public function __construct(
        private readonly CaptchaConfigurationInterface $configuration
    ) {
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getTitle(): string
    {
        return 'ACME_CAPTCHA_PROVIDER_TITLE';
    }

    public function getConfigFields(): array
    {
        return [
            new CaptchaConfigField('siteKey', 'ACME_CAPTCHA_SITE_KEY', CaptchaConfigField::TYPE_TEXT),
            new CaptchaConfigField('secretKey', 'ACME_CAPTCHA_SECRET_KEY', CaptchaConfigField::TYPE_PASSWORD),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function getHeadScript(): ?string
    {
        if ($this->siteKey() === '') {
            return null;
        }
        // Best practice: add integrity="sha384-..." crossorigin="anonymous" when your provider
        // publishes a stable SRI hash for the script. Pinning a hash protects against CDN
        // compromise. Omit it only when the provider serves a versioned URL with no published hash.
        return '<script src="https://captcha.acme.example/api.js" async defer></script>';
    }

    public function renderWidget(string $formId): string
    {
        if ($this->siteKey() === '') {
            return '';
        }
        return '<div class="acme-captcha" data-sitekey="' . htmlspecialchars($this->siteKey(), ENT_QUOTES) . '"></div>';
    }

    public function verify(Request $request, string $formId): bool
    {
        $token = trim((string) $request->getRequestEscapedParameter('acme-captcha-token'));
        if ($token === '') {
            return false;
        }

        // Call your provider's verification API here.
        // Return true on success, false on failure.
        $response = file_get_contents(
            'https://captcha.acme.example/verify?secret=' . urlencode($this->secretKey())
            . '&token=' . urlencode($token)
        );
        $data = json_decode((string) $response, true);

        return isset($data['success']) && $data['success'] === true;
    }

    private function siteKey(): string
    {
        return (string) $this->configuration->getProviderSetting(self::ID, 'siteKey', '');
    }

    private function secretKey(): string
    {
        return (string) $this->configuration->getProviderSetting(self::ID, 'secretKey', '');
    }
}
```

Note: inject `CaptchaConfigurationInterface` (not `Registry::getConfig()`) to read provider settings. The configuration layer namespaces keys automatically (see the Config section below).

---

## Register via `services.yaml`

In your module, create a `services.yaml` **at the module root** — `<moduleRoot>/services.yaml`. This is the only path module activation loads (see `ModuleServicesActivationService::getModuleServicesFilePath()`); a file anywhere else (e.g. `metadata/services.yaml`) is silently ignored and your provider tag is never collected. Tag the provider service:

```yaml
services:
    _defaults:
        autowire: true
        public: false

    Acme\CaptchaModule\Provider\AcmeCaptchaProvider:
        tags: ['oxid.captcha.provider']
```

`autowire: true` means Symfony will inject `CaptchaConfigurationInterface` (and any other typed constructor dependencies) automatically. The service itself stays private; the tag is the only registration needed.

When the module is activated, `CaptchaProviderLocator` collects all services tagged `oxid.captcha.provider` and makes them available to the core. Your provider will appear in the admin CAPTCHA provider dropdown immediately — no further wiring required.

### Declaring dependencies (and PHP version)

If your provider needs a third-party library, declare it — and any matching `php` constraint — in your module's `composer.json` `require`, **not** in core. This is the whole reason providers are modules: it keeps core dependency-light and PHP 7.4-compatible. The reference `o3-shop/recaptcha-module` does exactly this:

```json
{
    "name": "o3-shop/recaptcha-module",
    "type": "oxideshop-module",
    "require": {
        "php": ">=8.0",
        "google/recaptcha": "^1.3"
    }
}
```

`google/recaptcha` requires PHP 8.0+, so the module declares `php: ">=8.0"`; shops on PHP 7.4 simply can't install/activate it, while core itself stays 7.4-clean.

---

## Config & Language

### Config key namespacing

Credentials are stored under the config key pattern:

```
sCaptcha_<providerId>_<fieldKey>
```

For the example above, field `siteKey` of provider `acme_captcha` is stored as `sCaptcha_acme_captcha_siteKey`. This namespacing is handled by `CaptchaConfigurationInterface::getProviderSetting()` — always use that method rather than reading raw config, so keys never collide with other providers.

### Language identifiers

The provider title and field labels appear in the **admin** screen, so supply them in your module's admin lang file at `Application/views/admin/<lang>/module_options.php` — the path OXID loads for module admin idents (see `Core/Language.php`):

```php
$aLang = [
    'ACME_CAPTCHA_PROVIDER_TITLE' => 'Acme CAPTCHA',
    'ACME_CAPTCHA_SITE_KEY'       => 'Site Key',
    'ACME_CAPTCHA_SECRET_KEY'     => 'Secret Key',
];
```

- `getTitle()` returns the ident shown in the provider dropdown.
- Each field's `$labelIdent` is shown as the label next to the input in the admin config form.

---

## Consent Integration

By default, script loading is consent-gated. The core ships `CaptchaConsentInterface` with a binding to `ConfigBasedCaptchaConsent`, which checks a shop config flag before allowing scripts to be injected.

If your shop uses a custom consent tool (e.g. a cookie banner), override the `CaptchaConsentInterface` binding in your consent module's `services.yaml`:

```yaml
services:
    OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\CaptchaConsentInterface:
        class: YourVendor\ConsentModule\Captcha\MyConsentAdapter
```

As a CAPTCHA provider author, you should **not** load remote scripts unconditionally in `getHeadScript()` or emit tracking pixels in `renderWidget()`. Return `null` / empty string when consent has not been given, and let the consent adapter drive the injection. The core checks consent before calling these methods when the consent gate is active.
