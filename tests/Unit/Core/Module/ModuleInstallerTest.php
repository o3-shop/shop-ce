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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Module\ModuleInstaller;
use OxidEsales\EshopCommunity\Core\Exception\ModuleValidationException;
use OxidEsales\EshopCommunity\Core\Exception\StandardException;

/**
 * @group module
 * @package Unit\Core
 */
class ModuleInstallerTest extends \OxidTestCase
{
    /**
     * oxModuleInstaller::buildModuleChains() test case, empty
     */
    public function testBuildModuleChainsEmpty()
    {
        $oModuleInstaller = oxNew('oxModuleInstaller');

        $aModules = array();
        $aModulesArray = array();
        $this->assertEquals($aModules, $oModuleInstaller->buildModuleChains($aModulesArray));
    }

    /**
     * oxModuleInstaller::buildModuleChains() test case, single
     */
    public function testBuildModuleChainsSingle()
    {
        $oModuleInstaller = oxNew('oxModuleInstaller');

        $aModules = array('oxtest' => 'test/mytest');
        $aModulesArray = array('oxtest' => array('test/mytest'));
        $this->assertEquals($aModules, $oModuleInstaller->buildModuleChains($aModulesArray));
    }

    /**
     * oxModuleInstaller::buildModuleChains() test case
     */
    public function testBuildModuleChains()
    {
        $oModuleInstaller = oxNew('oxModuleInstaller');

        $aModules = array('oxtest' => 'test/mytest&test1/mytest1');
        $aModulesArray = array('oxtest' => array('test/mytest', 'test1/mytest1'));
        $this->assertEquals($aModules, $oModuleInstaller->buildModuleChains($aModulesArray));
    }
}
