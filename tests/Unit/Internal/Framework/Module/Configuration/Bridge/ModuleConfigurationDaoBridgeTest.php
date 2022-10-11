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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridge;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;

class ModuleConfigurationDaoBridgeTest extends TestCase
{
    public function testGet(): void
    {
        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context
            ->method('getCurrentShopId')
            ->willReturn(1789);

        $moduleConfigurationDao = $this->getMockBuilder(ModuleConfigurationDaoInterface::class)->getMock();
        $moduleConfigurationDao
            ->expects($this->once())
            ->method('get')
            ->with('testModuleId', 1789);

        $shopEnvironmentConfigurationDao =
            $this->getMockBuilder(ShopEnvironmentConfigurationDaoInterface::class)->getMock();

        $bridge = new ModuleConfigurationDaoBridge($context, $moduleConfigurationDao, $shopEnvironmentConfigurationDao);
        $bridge->get('testModuleId');
    }

    public function testSave(): void
    {
        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context
            ->method('getCurrentShopId')
            ->willReturn(1799);

        $moduleConfiguration = new ModuleConfiguration();

        $moduleConfigurationDao = $this->getMockBuilder(ModuleConfigurationDaoInterface::class)->getMock();
        $moduleConfigurationDao
            ->expects($this->once())
            ->method('save')
            ->with($moduleConfiguration, 1799);

        $shopEnvironmentConfigurationDao =
            $this->getMockBuilder(ShopEnvironmentConfigurationDaoInterface::class)->getMock();
        $shopEnvironmentConfigurationDao->expects($this->once())->method('remove');

        $bridge = new ModuleConfigurationDaoBridge($context, $moduleConfigurationDao, $shopEnvironmentConfigurationDao);
        $bridge->save($moduleConfiguration);
    }
}
