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
}
