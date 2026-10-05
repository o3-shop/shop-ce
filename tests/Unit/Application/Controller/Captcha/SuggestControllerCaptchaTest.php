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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Captcha;

use OxidEsales\Eshop\Application\Controller\SuggestController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Container\ContainerInterface;

class SuggestControllerCaptchaTest extends UnitTestCase
{
    public function testSendRejectedWhenCaptchaFails(): void
    {
        Registry::getSession()->deleteVariable('Errors');

        $service = $this->createMock(CaptchaServiceInterface::class);
        $service->method('verifyForForm')->willReturn(false);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn ($id) => $id === CaptchaServiceInterface::class ? $service : null);

        $controller = $this->getMock(SuggestController::class, ['getContainer']);
        $controller->method('getContainer')->willReturn($container);

        $this->assertFalse($controller->send());

        $errors = Registry::getSession()->getVariable('Errors');
        $this->assertNotEmpty($errors);
    }
}
