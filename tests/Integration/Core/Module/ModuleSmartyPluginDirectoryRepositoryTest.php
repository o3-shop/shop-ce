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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Module;

use OxidEsales\Eshop\Core\FileCache;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Module\ModuleSmartyPluginDirectoryRepository;
use OxidEsales\Eshop\Core\Module\ModuleVariablesLocator;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopIdCalculator;
use OxidEsales\Eshop\Core\SubShopSpecificFileCache;
use OxidEsales\Eshop\Core\Module\ModuleSmartyPluginDirectories;
use OxidEsales\TestingLibrary\UnitTestCase;

class ModuleSmartyPluginDirectoryRepositoryTest extends UnitTestCase
{
    public function testSaving()
    {
        $directories = oxNew(
            ModuleSmartyPluginDirectories::class,
            oxNew(Module::class)
        );
        $directories->add(['first', 'second'], 'moduleId');

        $repository = $this->getModuleSmartyPluginDirectoryRepository();
        $repository->save($directories);

        $this->assertEquals(
            $directories,
            $repository->get()
        );
    }

    private function getModuleSmartyPluginDirectoryRepository()
    {
        $moduleVariablesCache = oxNew(FileCache::class);
        $shopIdCalculator = oxNew(ShopIdCalculator::class, $moduleVariablesCache);

        $subShopSpecificCache = oxNew(
            SubShopSpecificFileCache::class,
            $shopIdCalculator
        );

        $moduleVariablesLocator = oxNew(
            ModuleVariablesLocator::class,
            $subShopSpecificCache,
            $shopIdCalculator
        );

        return oxNew(
            ModuleSmartyPluginDirectoryRepository::class,
            Registry::getConfig(),
            $moduleVariablesLocator,
            oxNew(Module::class)
        );
    }
}
