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

    public function testConsentRequiredButNotGrantedShowsNoticeAndBlocksVerification(): void
    {
        $svc = $this->service($this->provider('p'), ['consent' => false]);
        $html = $svc->renderForForm('contact');
        $this->assertStringContainsString('o3-captcha-consent-notice', $html);
        $this->assertStringNotContainsString('id="widget"', $html);
        // Fail closed: without consent the captcha cannot load, so submission is blocked.
        $this->assertFalse($svc->verifyForForm('contact', $this->createMock(Request::class)));
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
