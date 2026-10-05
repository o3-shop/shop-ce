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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Consent;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\ConfigBasedCaptchaConsent;
use PHPUnit\Framework\TestCase;

class ConfigBasedCaptchaConsentTest extends TestCase
{
    // Real Cookiebot `CookieConsent` value shape: PHP has already URL-decoded the
    // raw cookie ($_COOKIE) so commas are literal here. reCAPTCHA is filed under a
    // category, so the merchant's marker is the category boolean (e.g. 'marketing:true'),
    // NOT a literal 'recaptcha' key. Substring-contains distinguishes ':true' from ':false'.
    private const COOKIEBOT_ACCEPTED =
        "{stamp:'abc==',necessary:true,preferences:true,statistics:true,marketing:true,method:'explicit',ver:1,utc:1782201155329,region:'de'}";
    private const COOKIEBOT_DECLINED =
        "{stamp:'abc==',necessary:true,preferences:false,statistics:false,marketing:false,method:'explicit',ver:1,utc:1782201155329,region:'de'}";

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
