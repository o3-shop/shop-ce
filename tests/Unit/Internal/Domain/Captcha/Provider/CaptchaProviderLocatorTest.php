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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Captcha\Provider;

use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider;
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
