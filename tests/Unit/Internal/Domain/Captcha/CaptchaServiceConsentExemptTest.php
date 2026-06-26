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
        $consent->method('isConsentGranted')->willReturn(false);
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
