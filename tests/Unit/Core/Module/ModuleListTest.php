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

/**
 * @group module
 * @package Unit\Core
 */
class ModuleListTest extends \OxidTestCase
{
    /**
     * test setup
     *
     * @return null
     */
    public function setup(): void
    {
        parent::setUp();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxconfig');
        $this->cleanUpTable('oxconfigdisplay');
        $this->cleanUpTable('oxtplblocks');

        parent::tearDown();
    }

    /**
     * oxmodulelist::buildModuleChains() test case, empty
     *
     * @return null
     */
    public function testBuildModuleChainsEmpty()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');

        $aModules = [];
        $aModulesArray = [];
        $this->assertEquals($aModules, $oModuleList->buildModuleChains($aModulesArray));
    }

    /**
     * oxmodulelist::buildModuleChains() test case, single
     *
     * @return null
     */
    public function testBuildModuleChainsSingle()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');

        $aModules = ['oxtest' => 'test/mytest'];
        $aModulesArray = ['oxtest' => ['test/mytest']];
        $this->assertEquals($aModules, $oModuleList->buildModuleChains($aModulesArray));
    }

    /**
     * oxmodulelist::buildModuleChains() test case
     *
     * @return null
     */
    public function testBuildModuleChains()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');

        $aModules = ['oxtest' => 'test/mytest&test1/mytest1'];
        $aModulesArray = ['oxtest' => ['test/mytest', 'test1/mytest1']];
        $this->assertEquals($aModules, $oModuleList->buildModuleChains($aModulesArray));
    }

    /**
     * oxmodulelist::diffModuleArrays() test case, empty
     *
     * @return null
     */
    public function testDiffModuleArraysEmpty()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');

        $aAllModules = [];
        $aRemModules = [];
        $this->assertEquals($aAllModules, $oModuleList->diffModuleArrays($aAllModules, $aRemModules));
    }

    /**
     * oxmodulelist::diffModuleArrays() test case, remove single
     *
     * @return null
     */
    public function testDiffModuleArraysRemoveSingle()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');
        $aAllModules = ['oxtest' => ['test/mytest']];
        $aRemModules = ['oxtest' => 'test/mytest'];
        $aMrgModules = [];
        $this->assertEquals($aMrgModules, $oModuleList->diffModuleArrays($aAllModules, $aRemModules));
    }

    /**
     * oxmodulelist::diffModuleArrays() test case, remove
     *
     * @return null
     */
    public function testDiffModuleArraysRemove()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');
        $aAllModules = ['oxtest' => ['test/mytest']];
        $aRemModules = ['oxtest' => ['test/mytest']];
        $aMrgModules = [];
        $this->assertEquals($aMrgModules, $oModuleList->diffModuleArrays($aAllModules, $aRemModules));
    }

    /**
     * oxmodulelist::diffModuleArrays() test case, remove from chain
     *
     * @return null
     */
    public function testDiffModuleArraysRemoveChain()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');
        $aAllModules = ['oxtest' => ['test/mytest', 'test1/mytest1']];
        $aRemModules = ['oxtest' => ['test1/mytest1']];
        $aMrgModules = ['oxtest' => ['test/mytest']];
        $this->assertEquals($aMrgModules, $oModuleList->diffModuleArrays($aAllModules, $aRemModules));
    }

    /**
     * oxmodulelist::diffModuleArrays() test case, remove from chain and unused key
     *
     * @return null
     */
    public function testDiffModuleArraysRemoveChainAndKey()
    {
        $oModuleList = $this->getProxyClass('oxmodulelist');
        $aAllModules = ['oxtest' => ['test/mytest', 'test1/mytest1'], 'oxtest2' => ['test2/mytest2']];
        $aRemModules = ['oxtest' => ['test/mytest'], 'oxtest2' => ['test2/mytest2']];
        $aMrgModules = ['oxtest' => ['test1/mytest1']];
        $this->assertEquals($aMrgModules, $oModuleList->diffModuleArrays($aAllModules, $aRemModules));
    }

    /**
     * oxmodulelist::getActiveModuleInfo() test case
     *
     * @return null
     */
    public function testGetActiveModuleInfoPathsNotSet()
    {
        $modulePaths = [
            'testExt1' => 'testExt1',
            'testExt2' => 'testExt2',
        ];

        $oModuleList = $this->getMock(\OxidEsales\Eshop\Core\Module\ModuleList::class, ['getModuleConfigParametersByKey']);
        $oModuleList->expects($this->once())->method('getModuleConfigParametersByKey')->with('Paths')->willReturn($modulePaths);

        $this->assertEquals($modulePaths, $oModuleList->getActiveModuleInfo());
    }

    /**
     * oxmodulelist::getDisabledModules() test case
     *
     * @return null
     */
    public function testGetModulePaths()
    {
        $aModulePaths = [
            'testExt1' => 'testExt1/testExt11',
            'testExt2' => 'testExt2',
        ];

        $this->getConfig()->setConfigParam('aModulePaths', $aModulePaths);

        $oModuleList = $this->getProxyClass('oxmodulelist');

        $this->assertEquals($aModulePaths, $oModuleList->getModulePaths());
    }

    /**
     * @return array
     */
    public function providerIsVendorDir()
    {
        return [
            ['module1', false],
            ['vendor1', true],
            ['notVendor', false],
            ['this_directory_does_not_exist', false],
        ];
    }

    /**
     * @param string $vendorDirectoryName
     * @param bool   $isVendor
     * @dataProvider providerIsVendorDir
     */
    public function testIsVendorDir($vendorDirectoryName, $isVendor)
    {
        $structure = [
            'modules' => [
                'module1' => [
                    'metadata.php' => '<?php',
                ],
                'vendor1' => [
                    'module2' => [
                        'metadata.php' => '<?php',
                    ],
                ],
                'notVendor' => [
                    'someDirectory' => [
                        'file.php' => '<?php',
                    ],
                ],
            ],
        ];
        $vfsStream = $this->getVfsStreamWrapper();
        $vfsStream->createStructure($structure);

        $this->getConfig()->setConfigParam('sShopDir', $vfsStream->getRootPath());
        $modulesDir = $this->getConfig()->getModulesDir();
        $moduleList = oxNew('oxModuleList');

        $this->assertSame($isVendor, $moduleList->_isVendorDir($modulesDir . "/$vendorDirectoryName"));
    }

    public function testGetModuleFilesWhenFileWasSet()
    {
        $aModuleFiles = [
            'myext1' => ['title' => 'test title 1'],
        ];
        $this->getConfig()->setConfigParam('aModuleFiles', $aModuleFiles);
        $oModuleList = oxNew('oxModuleList');

        $this->assertSame($aModuleFiles, $oModuleList->getModuleFiles());
    }

    public function testGetModuleFilesWhenFileWasNotSet()
    {
        $this->getConfig()->setConfigParam('aModuleFiles', []);

        $oModuleList = oxNew('oxModuleList');

        $this->assertSame([], $oModuleList->getModuleFiles());
    }

    /**
     * ModuleList::parseModuleChains() test case, empty
     *
     * @return null
     */
    public function testParseModuleChainsEmpty()
    {
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $modules = [];
        $modulesArray = [];
        $this->assertEquals($modulesArray, $moduleList->parseModuleChains($modules));
    }

    /**
     * ModuleList::parseModuleChains() test case, single
     *
     * @return null
     */
    public function testParseModuleChainsSingle()
    {
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $modules = ['oxtest' => 'test/mytest'];
        $modulesArray = ['oxtest' => ['test/mytest']];
        $this->assertEquals($modulesArray, $moduleList->parseModuleChains($modules));
    }

    /**
     * ModuleList::parseModuleChains() test case
     *
     * @return null
     */
    public function testParseModuleChains()
    {
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $modules = ['oxtest' => 'test/mytest&test1/mytest1'];
        $modulesArray = ['oxtest' => ['test/mytest', 'test1/mytest1']];
        $this->assertEquals($modulesArray, $moduleList->parseModuleChains($modules));
    }

    public function testParseModuleChainsHandlesNonArray(): void
    {
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame([], $moduleList->parseModuleChains(null));
        $this->assertSame([], $moduleList->parseModuleChains('not-an-array'));
    }

    public function testGetListExposesInternalAccumulator(): void
    {
        $moduleList = $this->getProxyClass('oxmodulelist');
        $moduleList->setNonPublicVar('_aModules', ['mod-a', 'mod-b']);
        $this->assertSame(['mod-a', 'mod-b'], $moduleList->getList());
    }

    public function testGetModuleVersionsReadsFromConfigParam(): void
    {
        $this->getConfig()->setConfigParam('aModuleVersions', ['mymodule' => '1.2.3']);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame(['mymodule' => '1.2.3'], $moduleList->getModuleVersions());
    }

    public function testGetModulePathsReadsFromConfigParam(): void
    {
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame(['mymodule' => 'mymodule/'], $moduleList->getModulePaths());
    }

    public function testGetActiveModuleInfoReadsAModulePathsViaModuleConfigKey(): void
    {
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        // getActiveModuleInfo() delegates to getModuleConfigParametersByKey(MODULE_KEY_PATHS)
        // which prepends "aModule" → reads aModulePaths.
        $this->assertSame(['mymodule' => 'mymodule/'], $moduleList->getActiveModuleInfo());
    }

    public function testGetModuleConfigParametersByKeyAlwaysReturnsArray(): void
    {
        $this->getConfig()->setConfigParam('aModuleVersions', null);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        // null becomes [] via the (array) cast.
        $this->assertSame([], $moduleList->getModuleConfigParametersByKey('Versions'));
    }

    public function testGetModuleEventsAlwaysReturnsArray(): void
    {
        $this->getConfig()->setConfigParam('aModuleEvents', null);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame([], $moduleList->getModuleEvents());

        $this->getConfig()->setConfigParam('aModuleEvents', ['onActivate' => 'foo']);
        $this->assertSame(['onActivate' => 'foo'], $moduleList->getModuleEvents());
    }

    public function testGetModuleFilesAlwaysReturnsArray(): void
    {
        $this->getConfig()->setConfigParam('aModuleFiles', null);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame([], $moduleList->getModuleFiles());
    }

    public function testGetModuleTemplatesReturnsConfigParamVerbatim(): void
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', ['mymodule' => ['foo.tpl' => 'bar.tpl']]);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame(
            ['mymodule' => ['foo.tpl' => 'bar.tpl']],
            $moduleList->getModuleTemplates()
        );
    }

    public function testGetDisabledModuleClassesReadsAModuleExtensionsAndADisabledModules(): void
    {
        // No disabled modules → empty array.
        $this->getConfig()->setConfigParam('aDisabledModules', []);
        $this->getConfig()->setConfigParam('aModuleExtensions', []);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame([], $moduleList->getDisabledModuleClasses());
    }

    public function testGetModuleReturnsNewModuleInstance(): void
    {
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertInstanceOf(
            \OxidEsales\Eshop\Core\Module\Module::class,
            $moduleList->getModule()
        );
    }

    public function testExtractModulePathsReturnsEmptyForNoModules(): void
    {
        $moduleList = $this->getMock(\OxidEsales\Eshop\Core\Module\ModuleList::class, ['getModulesWithExtendedClass']);
        $moduleList->expects($this->any())->method('getModulesWithExtendedClass')->will($this->returnValue([]));
        $this->assertSame([], $moduleList->extractModulePaths());
    }

    public function testExtractModulePathsExtractsVendorIdsFromExtensionPaths(): void
    {
        $moduleList = $this->getMock(\OxidEsales\Eshop\Core\Module\ModuleList::class, ['getModulesWithExtendedClass']);
        $moduleList->expects($this->any())
            ->method('getModulesWithExtendedClass')
            ->will($this->returnValue([
                'oxarticle' => ['mymodule/Class1', 'othermod/Class2'],
            ]));

        $paths = $moduleList->extractModulePaths();
        $this->assertSame(['mymodule' => 'mymodule', 'othermod' => 'othermod'], $paths);
    }

    public function testIsVendorDirReturnsFalseForNonExistentDir(): void
    {
        $moduleList = $this->getProxyClass('oxmodulelist');
        $this->assertFalse(
            $moduleList->UNITisVendorDir('/this/path/does/not/exist/' . uniqid())
        );
    }

    public function testIsVendorDirReturnsTrueWhenChildHasMetadataPhp(): void
    {
        $tmp = sys_get_temp_dir() . '/o3-shop-vendor-test-' . uniqid();
        mkdir("$tmp/somemodule", 0777, true);
        file_put_contents("$tmp/somemodule/metadata.php", '<?php');

        try {
            $moduleList = $this->getProxyClass('oxmodulelist');
            $this->assertTrue($moduleList->UNITisVendorDir($tmp));
        } finally {
            @unlink("$tmp/somemodule/metadata.php");
            @rmdir("$tmp/somemodule");
            @rmdir($tmp);
        }
    }

    public function testIsVendorDirReturnsFalseForDirWithoutMetadataChildren(): void
    {
        $tmp = sys_get_temp_dir() . '/o3-shop-novendor-' . uniqid();
        mkdir("$tmp/notamodule", 0777, true);
        // Note: no metadata.php inside notamodule.

        try {
            $moduleList = $this->getProxyClass('oxmodulelist');
            $this->assertFalse($moduleList->UNITisVendorDir($tmp));
        } finally {
            @rmdir("$tmp/notamodule");
            @rmdir($tmp);
        }
    }

    public function testSortModulesIsCaseInsensitive(): void
    {
        $a = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getTitle']);
        $a->expects($this->any())->method('getTitle')->will($this->returnValue('Alpha Module'));
        $b = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getTitle']);
        $b->expects($this->any())->method('getTitle')->will($this->returnValue('beta module'));

        $moduleList = $this->getProxyClass('oxmodulelist');
        // 'Alpha' < 'beta' case-insensitively → negative.
        $this->assertLessThan(0, $moduleList->UNITsortModules($a, $b));
        // Reverse → positive.
        $this->assertGreaterThan(0, $moduleList->UNITsortModules($b, $a));
        // Same → zero.
        $this->assertSame(0, $moduleList->UNITsortModules($a, $a));
    }

    public function testGetModuleExtensionsReturnsEmptyArrayForUnknownModuleId(): void
    {
        $moduleList = $this->getProxyClass('oxmodulelist');
        $moduleList->setNonPublicVar('_aModuleExtensions', [
            'mymodule' => ['oxarticle' => ['mymodule/Article']],
        ]);
        $this->assertSame([], $moduleList->getModuleExtensions('not-installed'));
        $this->assertSame(
            ['oxarticle' => ['mymodule/Article']],
            $moduleList->getModuleExtensions('mymodule')
        );
    }

    // Note: the slow path of getModuleExtensions() (which calls a private
    // getActivateModulesWithExtendedClass() helper) cannot be unit-tested
    // because the helper's name and visibility prevent test-side override.
    // It is exercised end-to-end by the integration suite under
    // tests/Integration/Internal/Framework/Module/Setup.

    public function testGetActiveModuleInfoIsAlwaysAnArray(): void
    {
        $this->getConfig()->setConfigParam('aModulePaths', null);
        $moduleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $this->assertSame([], $moduleList->getActiveModuleInfo());
    }
}
