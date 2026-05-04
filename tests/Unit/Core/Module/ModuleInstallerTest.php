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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\Module\Module;

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

        $aModules = [];
        $aModulesArray = [];
        $this->assertEquals($aModules, $oModuleInstaller->buildModuleChains($aModulesArray));
    }

    /**
     * oxModuleInstaller::buildModuleChains() test case, single
     */
    public function testBuildModuleChainsSingle()
    {
        $oModuleInstaller = oxNew('oxModuleInstaller');

        $aModules = ['oxtest' => 'test/mytest'];
        $aModulesArray = ['oxtest' => ['test/mytest']];
        $this->assertEquals($aModules, $oModuleInstaller->buildModuleChains($aModulesArray));
    }

    /**
     * oxModuleInstaller::buildModuleChains() test case
     */
    public function testBuildModuleChains()
    {
        $oModuleInstaller = oxNew('oxModuleInstaller');

        $aModules = ['oxtest' => 'test/mytest&test1/mytest1'];
        $aModulesArray = ['oxtest' => ['test/mytest', 'test1/mytest1']];
        $this->assertEquals($aModules, $oModuleInstaller->buildModuleChains($aModulesArray));
    }

    public function testBuildModuleChainsTreatsNonArrayAsEmpty(): void
    {
        $oModuleInstaller = oxNew('oxModuleInstaller');
        $this->assertSame([], $oModuleInstaller->buildModuleChains(null));
        $this->assertSame([], $oModuleInstaller->buildModuleChains('not-an-array'));
    }

    public function testGetModuleCacheReturnsConstructorInjection(): void
    {
        $cache = $this->getMockBuilder(\OxidEsales\Eshop\Core\Module\ModuleCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $installer = oxNew('oxModuleInstaller', $cache);
        $this->assertSame($cache, $installer->getModuleCache());
    }

    public function testDiffModuleArraysRemovesMatchingEntriesFromChain(): void
    {
        $installer = oxNew('oxModuleInstaller');

        $all = [
            'oxarticle' => ['vendor/foo/Article', 'vendor/bar/Article'],
            'oxorder'   => ['vendor/foo/Order'],
        ];
        $remove = [
            'oxarticle' => ['vendor/foo/Article'],
        ];

        $diffed = $installer->diffModuleArrays($all, $remove);
        $this->assertSame(['vendor/bar/Article'], array_values($diffed['oxarticle']));
        $this->assertSame(['vendor/foo/Order'], $diffed['oxorder'], 'Untouched class should pass through.');
    }

    public function testDiffModuleArraysCollapsesClassWithEmptyChain(): void
    {
        $installer = oxNew('oxModuleInstaller');
        $all = ['oxarticle' => ['vendor/foo/Article']];
        $remove = ['oxarticle' => 'vendor/foo/Article']; // scalar form

        $diffed = $installer->diffModuleArrays($all, $remove);
        $this->assertArrayNotHasKey('oxarticle', $diffed, 'Empty chain after diff drops the entry.');
    }

    public function testDiffModuleArraysAcceptsScalarChainsOnBothSides(): void
    {
        $installer = oxNew('oxModuleInstaller');
        $all = ['oxorder' => 'vendor/foo/Order'];
        $remove = ['oxorder' => 'vendor/foo/Order'];

        $diffed = $installer->diffModuleArrays($all, $remove);
        $this->assertArrayNotHasKey('oxorder', $diffed);
    }

    public function testDiffModuleArraysReturnsAllWhenRemoveIsEmpty(): void
    {
        $installer = oxNew('oxModuleInstaller');
        $all = ['oxorder' => ['vendor/foo/Order']];

        $diffed = $installer->diffModuleArrays($all, []);
        $this->assertSame(['vendor/foo/Order'], $diffed['oxorder']);
    }

    public function testDiffModuleArraysReturnsAllAsIsWhenAllOrRemoveIsNonArray(): void
    {
        $installer = oxNew('oxModuleInstaller');
        $this->assertSame('passthrough', $installer->diffModuleArrays('passthrough', []));
        $this->assertSame(
            ['oxorder' => 'vendor/foo/Order'],
            $installer->diffModuleArrays(['oxorder' => 'vendor/foo/Order'], 'not-an-array')
        );
    }

    // Note: activate() / deactivate() reach into the container via a
    // PRIVATE accessor (getModuleActivationBridge) that subclasses cannot
    // override — coverage of those two methods is left to the integration
    // tests under tests/Integration/Internal/Framework/Module/Setup.
}
