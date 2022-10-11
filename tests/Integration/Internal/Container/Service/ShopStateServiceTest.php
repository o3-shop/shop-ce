<?php declare(strict_types=1);
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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Container\Service;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateService;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class ShopStateServiceTest extends TestCase
{
    use ContainerTrait;

    public function testIsLaunchedReturnsTrue()
    {
        $this->assertTrue(
            $this->get(ShopStateServiceInterface::class)->isLaunched()
        );
    }

    public function testIsLaunchedReturnsFalseIfUnifiedNamespaceAreNotGenerated()
    {
        $shopStateService = new ShopStateService(
            $this->get(BasicContextInterface::class),
            'fakeNamespace'
        );

        $this->assertFalse(
            $shopStateService->isLaunched()
        );
    }

    public function testIsLaunchedReturnsTrueIfUnifiedNamespaceAreGenerated()
    {
        $shopStateService = new ShopStateService(
            $this->get(BasicContextInterface::class),
            Registry::class
        );

        $this->assertTrue(
            $shopStateService->isLaunched()
        );
    }

    public function testIsLaunchedReturnsFalseIfConfigFileDoesNotExist()
    {
        $context = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $context
            ->method('getConfigFilePath')
            ->willReturn('nonExistentFilePath');

        $shopStateService = new ShopStateService(
            $context,
            Registry::class
        );

        $this->assertFalse(
            $shopStateService->isLaunched()
        );
    }

    public function testIsLaunchedReturnsTrueIfConfigFileExists()
    {
        $shopStateService = new ShopStateService(
            $this->get(BasicContextInterface::class),
            Registry::class
        );

        $this->assertTrue(
            $shopStateService->isLaunched()
        );
    }

    public function testIsLaunchedReturnsFalseIfConfigIsWrong()
    {
        $context = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $context
            ->method('getConfigFilePath')
            ->willReturn(__DIR__ . '/Fixtures/wrong_config.inc.php');

        $shopStateService = new ShopStateService(
            $context,
            Registry::class
        );

        $this->assertFalse(
            $shopStateService->isLaunched()
        );
    }

    public function testIsLaunchedReturnsFalseIfConfigTableDoesNotExist()
    {
        $context = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $context
            ->method('getConfigTableName')
            ->willReturn('nonExistentTable');

        $shopStateService = new ShopStateService(
            $context,
            Registry::class
        );

        $this->assertFalse(
            $shopStateService->isLaunched()
        );
    }
}
