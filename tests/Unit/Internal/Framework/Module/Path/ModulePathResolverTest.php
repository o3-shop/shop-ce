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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Path;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolver;
use PHPUnit\Framework\TestCase;

class ModulePathResolverTest extends TestCase
{
    public function testGetFullModulePathFromConfiguration()
    {
        $context = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $context
            ->method('getModulesPath')
            ->willReturn('modules');

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('testModuleId')
            ->setPath('modulePath');

        $moduleConfigurationDao = $this->getMockBuilder(ModuleConfigurationDaoInterface::class)->getMock();
        $moduleConfigurationDao
            ->method('get')
            ->with('testModuleId', 1)
            ->willReturn($moduleConfiguration);

        $pathResolver = new ModulePathResolver($moduleConfigurationDao, $context);

        $this->assertSame(
            'modules/modulePath',
            $pathResolver->getFullModulePathFromConfiguration('testModuleId', 1)
        );
    }
}
