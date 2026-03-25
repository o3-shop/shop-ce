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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use oxTestModules;

/**
 * Tests for Shop_Config class
 */
class ShopConfigTest extends \OxidTestCase
{
    public function setup(): void
    {
        $this->setAdminMode(true);

        parent::setUp();
    }

    /**
     * Shop_Config::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Shop_Config');
        $this->assertEquals('shop_config.tpl', $oView->render());
    }

    /**
     * Shop_Config::SaveConfVars() test case
     *
     * @return null
     */
    public function testSaveConfVars()
    {
        $this->setAdminMode(true);
        $this->setRequestParameter('oxid', 'testId');
        $this->setRequestParameter('confbools', ['varnamebool' => true]);
        $this->setRequestParameter('confstrs', ['varnamestr' => 'string']);
        $this->setRequestParameter('confarrs', ['varnamearr' => "a\nb\nc"]);
        $this->setRequestParameter('confaarrs', ['varnameaarr' => "a => b\nc => d"]);
        $this->setRequestParameter('confselects', ['varnamesel' => 'a']);

        // Track saveShopConfVar calls
        oxTestModules::addFunction('oxConfig', 'saveShopConfVar', '{ if (!isset($this->_aSavedVars)) { $this->_aSavedVars = []; } $this->_aSavedVars[] = func_get_args(); }');
        oxTestModules::addFunction('oxConfig', 'getSavedVars', '{ return isset($this->_aSavedVars) ? $this->_aSavedVars : []; }');

        // testing..
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration::class, ['resetContentCache', 'getModuleForConfigVars'], [], '', false);
        $oView->expects($this->once())->method('resetContentCache');
        $oView->expects($this->atLeastOnce())->method('getModuleForConfigVars')
            ->will($this->returnValue('theme:mytheme'));

        $oView->saveConfVars();
    }

    public function testGetModuleForConfigVars()
    {
        $sCl = oxTestModules::publicize('Shop_Config', '_getModuleForConfigVars');
        $oTest = new $sCl();
        $this->assertEquals('', $oTest->p_getModuleForConfigVars());
    }

    /**
     * Shop_Config::Save() test case
     *
     * @return null
     */
    public function testSave()
    {
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration::class, ['saveConfVars']);
        $oView->expects($this->once())->method('saveConfVars');
        $oView->save();
    }

    /**
     * Shop_Config::ArrayToMultiline() test case
     *
     * @return null
     */
    public function testArrayToMultiline()
    {
        // defining parameters
        $aInput = ['a', 'b', 'c'];

        // testing..
        $oView = oxNew('Shop_Config');
        $this->assertEquals("a\nb\nc", $oView->UNITarrayToMultiline($aInput));
    }

    /**
     * Shop_Config::MultilineToArray() test case
     *
     * @return null
     */
    public function testMultilineToArray()
    {
        // defining parameters
        $sMultiline = "a\nb\n\nc";

        // testing..
        $oView = oxNew('Shop_Config');
        $this->assertEquals([0 => 'a', 1 => 'b', 3 => 'c'], $oView->UNITmultilineToArray($sMultiline));
    }

    /**
     * Shop_Config::AarrayToMultiline() test case
     *
     * @return null
     */
    public function testAarrayToMultiline()
    {
        // defining parameters
        $aInput = ['a' => 'b', 'c' => 'd'];

        // testing..
        $oView = oxNew('Shop_Config');
        $this->assertEquals("a => b\nc => d", $oView->UNITaarrayToMultiline($aInput));
    }

    /**
     * Shop_Config::MultilineToAarray() test case
     *
     * @return null
     */
    public function testMultilineToAarray()
    {
        // defining parameters
        $sMultiline = "a => b\nc => d";

        // testing..
        $oView = oxNew('Shop_Config');
        $this->assertEquals(['a' => 'b', 'c' => 'd'], $oView->UNITmultilineToAarray($sMultiline));
    }

    /**
     * _parseConstraint test
     *
     * @return null
     */
    public function testParseConstraint()
    {
        $sCl = oxTestModules::publicize('Shop_Config', '_parseConstraint');
        $oTest = new $sCl();
        $this->assertEquals('', $oTest->p_parseConstraint('sometype', 'asdd'));
        $this->assertEquals('', $oTest->p_parseConstraint('bool', 'asdd'));
        $this->assertEquals('', $oTest->p_parseConstraint('string', 'asdd'));
        $this->assertEquals(['a', 'bc', 'd'], $oTest->p_parseConstraint('select', 'a|bc|d'));
    }

    /**
     * _serializeConstraint test
     *
     * @return null
     */
    public function testSerializeConstraint()
    {
        $sCl = oxTestModules::publicize('Shop_Config', '_serializeConstraint');
        $oTest = new $sCl();
        $this->assertEquals('', $oTest->p_serializeConstraint('sometype', 'asdd'));
        $this->assertEquals('', $oTest->p_serializeConstraint('bool', 'asdd'));
        $this->assertEquals('', $oTest->p_serializeConstraint('string', 'asdd'));
        $this->assertEquals('a|bc|d', $oTest->p_serializeConstraint('select', ['a', 'bc', 'd']));
    }

    /**
     * _serializeConfVar test
     *
     * @return null
     */
    public function testSerializeConfVar()
    {
        $sCl = oxTestModules::publicize('Shop_Config', '_serializeConfVar');
        $oTest = new $sCl();
        $this->assertEquals('1.1', $oTest->p_serializeConfVar('str', 'iMinOrderPrice', '1,1'));
        $this->assertEquals('2,2', $oTest->p_serializeConfVar('str', 'shouldNotChange', '2,2'));
    }

    /**
     * _unserializeConfVar test
     *
     * @return null
     */
    public function testUnserializeConfVar()
    {
        $sCl = oxTestModules::publicize('Shop_Config', '_unserializeConfVar');
        $oTest = new $sCl();
        $this->assertEquals('1.1', $oTest->p_unserializeConfVar('str', 'iMinOrderPrice', '1,1'));
        $this->assertEquals('2,2', $oTest->p_unserializeConfVar('str', 'shouldNotChange', '2,2'));
    }

    /**
     * loadConfVars test
     *
     * @return null
     */
    public function testLoadConfVars()
    {
        $oTest = oxNew('Shop_Config');
        $aDbConfig = $oTest->loadConfVars($this->getConfig()->getShopId(), '');

        $this->assertEquals(
            ['vars', 'constraints', 'grouping'],
            array_keys($aDbConfig)
        );

        $iVarSum = array_sum(array_map('count', $aDbConfig['vars']));
        $this->assertGreaterThan(100, $iVarSum);
        $this->assertEquals($iVarSum, count($aDbConfig['constraints']));
    }
}
