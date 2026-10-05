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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Captcha;

use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Container\ContainerInterface;

class ViewConfigCaptchaTest extends UnitTestCase
{
    public function testGetCaptchaWidgetDelegatesToService(): void
    {
        $service = $this->createMock(CaptchaServiceInterface::class);
        $service->method('renderForForm')->with('contact')->willReturn('<div id="w"></div>');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn ($id) => $id === CaptchaServiceInterface::class ? $service : null);

        $viewConfig = $this->getMock(ViewConfig::class, ['getContainer']);
        $viewConfig->expects($this->once())->method('getContainer')->willReturn($container);

        $this->assertSame('<div id="w"></div>', $viewConfig->getCaptchaWidget('contact'));
    }
}
