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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use Exception;
use oxField;
use oxTestModules;
use stdClass;

class CmpCategoriesTest extends \OxidTestCase
{
    public static $oCL = null;

    public function tearDown(): void
    {
        self::$oCL = null;
        parent::tearDown();
    }

    public function testInitReturnsInOrderStep()
    {
        $this->markTestSkipped('Bug: Error: Call to member function setManufacturerTree() on null');
        $oActView = $this->getMock('stdClass', ['getIsOrderStep']);
        $oActView->expects($this->once())->method('getIsOrderStep')->will($this->returnValue(true));

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getTopActiveView']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('blDisableNavBars'))->will($this->returnValue(true));
        $oCfg->expects($this->once())->method('getTopActiveView')->will($this->returnValue($oActView));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['_getActCat', 'getConfig']);
        $o->expects($this->never())->method('_getActCat');
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));

        $o->init();
    }

    public function testInitReturnsInOrderStepCfgOff()
    {
        $this->markTestSkipped('Bug: Error: Call to a member function setManufacturerTree() on null');
        $oActView = $this->getMock('stdClass', ['getIsOrderStep']);
        $oActView->expects($this->never())->method('getIsOrderStep')->will($this->returnValue(true));

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getTopActiveView']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('blDisableNavBars'))->will($this->returnValue(false));
        $oCfg->expects($this->never())->method('getTopActiveView')->will($this->returnValue($oActView));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['_getActCat', 'getConfig']);
        $o->expects($this->once())->method('_getActCat')->will($this->throwException(new Exception('passed: OK')));
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));

        try {
            $o->init();
        } catch (Exception $e) {
            $this->assertEquals('passed: OK', $e->getMessage());

            return;
        }
        $this->fail('no exception is thrown');
    }

    public function testInitReturnsNoOrderStep()
    {
        $this->markTestSkipped('Bug: Error: Call to member function setManufacturerTree() on null');

        $oActView = $this->getMock('stdClass', ['getIsOrderStep']);
        $oActView->expects($this->once())->method('getIsOrderStep')->will($this->returnValue(false));

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getTopActiveView']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('blDisableNavBars'))->will($this->returnValue(true));

        $oCfg->expects($this->once())->method('getTopActiveView')->will($this->returnValue($oActView));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['_getActCat', 'getConfig']);
        $o->expects($this->once())->method('_getActCat')->will($this->throwException(new Exception('passed: OK')));
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));

        try {
            $o->init();
        } catch (Exception $e) {
            $this->assertEquals('passed: OK', $e->getMessage());

            return;
        }
        $this->fail('no exception is thrown');
    }

    public function testInitLoadManufacturerTree()
    {
        $this->markTestSkipped('Bug: test is not working as expected.');

        $oActView = $this->getMock('stdClass', ['getIsOrderStep']);
        $oActView->expects($this->once())->method('getIsOrderStep')->will($this->returnValue(false));

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getTopActiveView']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('blDisableNavBars'))->will($this->returnValue(true));
        $oCfg->expects($this->at(1))->method('getTopActiveView')->will($this->returnValue($oActView));
        $oCfg->expects($this->at(2))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(true));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['_getActCat', 'getConfig', '_loadManufacturerTree']);
        $o->expects($this->once())->method('_getActCat')->will($this->returnValue('actcat..'));
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->expects($this->once())->method('_loadManufacturerTree')->with($this->equalTo('manid'))->will($this->throwException(new Exception('passed: OK')));

        $this->setRequestParameter('mnid', 'manid');
        try {
            $o->init();
        } catch (Exception $e) {
            $this->assertEquals('passed: OK', $e->getMessage());

            return;
        }
        $this->fail('no exception is thrown');
    }

    public function testInitLoadCategoryTree()
    {
        $this->markTestSkipped('Bug: test is not working as expected.');

        $oActView = $this->getMock('stdClass', ['getIsOrderStep']);
        $oActView->expects($this->once())->method('getIsOrderStep')->will($this->returnValue(false));

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getTopActiveView']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('blDisableNavBars'))->will($this->returnValue(true));
        $oCfg->expects($this->at(1))->method('getTopActiveView')->will($this->returnValue($oActView));
        $oCfg->expects($this->at(2))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(false));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['_getActCat', 'getConfig', '_loadManufacturerTree', '_loadCategoryTree']);
        $o->expects($this->once())->method('_getActCat')->will($this->returnValue('actcat..'));
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->expects($this->never())->method('_loadManufacturerTree');
        $o->expects($this->once())->method('_loadCategoryTree')->with($this->equalTo('actcat..'))->will($this->throwException(new Exception('passed: OK')));

        try {
            $o->init();
        } catch (Exception $e) {
            $this->assertEquals('passed: OK', $e->getMessage());

            return;
        }
        $this->fail('no exception is thrown');
    }

    public function testInitChecksTopNaviConfigParamAndSkipsGetMoreCat()
    {
        $this->markTestSkipped('Bug: test is not working as expected.');

        $oActView = $this->getMock('stdClass', ['getIsOrderStep']);
        $oActView->expects($this->once())->method('getIsOrderStep')->will($this->returnValue(false));

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getTopActiveView']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('blDisableNavBars'))->will($this->returnValue(true));
        $oCfg->expects($this->at(1))->method('getTopActiveView')->will($this->returnValue($oActView));
        $oCfg->expects($this->at(2))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(false));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['_getActCat', 'getConfig', '_loadManufacturerTree', '_loadCategoryTree']);
        $o->expects($this->once())->method('_getActCat')->will($this->returnValue('actcat..'));
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->expects($this->never())->method('_loadManufacturerTree');
        $o->expects($this->once())->method('_loadCategoryTree')->with($this->equalTo('actcat..'));

        $o->init();
    }

    public function testGetProductNoAnid()
    {
        $oParent = $this->getMock('stdClass', ['getViewProduct']);
        $oParent->expects($this->never())->method('getViewProduct')->will($this->returnValue(false));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, []);
        $o->setParent($oParent);

        $this->setRequestParameter('anid', '');

        $this->assertSame(null, $o->getProduct());
    }

    public function testGetProductWithAnidAndGetViewProduct()
    {
        $this->setRequestParameter('anid', 'lalala');

        $oParent = $this->getMock('stdClass', ['getViewProduct']);
        $oParent->expects($this->once())->method('getViewProduct')->will($this->returnValue('asd'));

        $o = oxNew('oxcmp_categories');
        $o->setParent($oParent);

        $this->assertEquals('asd', $o->getProduct());
    }

    public function testGetProductWithAnidLoadsArticle()
    {
        $this->setRequestParameter('anid', 'lalala');

        oxTestModules::addFunction('oxarticle', 'load($id)', '{$this->setId($id); return "lalala" == $id;}');

        $oExpectArticle = oxNew('oxArticle');
        $this->assertEquals(true, $oExpectArticle->load('lalala'));

        $oParent = $this->getMock('stdClass', ['getViewProduct', 'setViewProduct']);
        $oParent->expects($this->once())->method('getViewProduct')->will($this->returnValue(null));
        $oParent->expects($this->once())->method('setViewProduct')->with($this->equalTo($oExpectArticle))->will($this->returnValue(null));

        $o = oxNew('oxcmp_categories');
        $o->setParent($oParent);

        $this->assertEquals('lalala', $o->getProduct()->getId());
    }

    public function testGetProductWithAnidLoadArticleFails()
    {
        $this->setRequestParameter('anid', 'blah');

        oxTestModules::addFunction('oxarticle', 'load($id)', '{$this->setId($id); return "lalala" == $id;}');

        $oParent = $this->getMock('stdClass', ['getViewProduct', 'setViewProduct']);
        $oParent->expects($this->once())->method('getViewProduct')->will($this->returnValue(null));
        $oParent->expects($this->never())->method('setViewProduct');

        $o = oxNew('oxcmp_categories');
        $o->setParent($oParent);

        $this->assertSame(null, $o->getProduct());
    }

    public function testGetActCatLoadDefault()
    {
        $this->markTestSkipped('Bug: string does not match');
        $oActShop = new stdClass();
        $oActShop->oxshops__oxdefcat = new oxField('default category');

        $oCfg = $this->getMock('stdClass', ['getActiveShop']);
        $oCfg->expects($this->once())->method('getActiveShop')->will($this->returnValue($oActShop));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig', 'getProduct', '_addAdditionalParams']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->expects($this->once())->method('getProduct')->will($this->returnValue(null));
        $o->expects($this->never())->method('_addAdditionalParams');

        $this->setRequestParameter('mnid', null);
        $this->setRequestParameter('cnid', null);

        $this->assertEquals('default category', $o->UNITgetActCat());
    }

    public function testGetActCatLoadDefaultoxroot()
    {
        $this->markTestSkipped('Bug: sequence is not identical to null');
        $oActShop = new stdClass();
        $oActShop->oxshops__oxdefcat = new oxField('oxrootid');

        $oCfg = $this->getMock('stdClass', ['getActiveShop']);
        $oCfg->expects($this->once())->method('getActiveShop')->will($this->returnValue($oActShop));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig', 'getProduct', '_addAdditionalParams']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->expects($this->once())->method('getProduct')->will($this->returnValue(null));
        $o->expects($this->never())->method('_addAdditionalParams');

        $this->setRequestParameter('mnid', null);
        $this->setRequestParameter('cnid', null);

        $this->assertSame(null, $o->UNITgetActCat());
    }

    public function testGetActCatWithProduct()
    {
        $this->markTestSkipped('Bug: test is not working as expected.');

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getProduct', '_addAdditionalParams']);
        $o->expects($this->once())->method('getProduct')->will($this->returnValue('product'));
        $o->expects($this->once())->method('_addAdditionalParams')->with(
            $this->equalTo('product'),
            $this->equalTo(null),
            $this->equalTo('mnid')
        );

        $this->setRequestParameter('mnid', 'mnid');
        $this->setRequestParameter('cnid', 'cnid');

        $this->assertSame(null, $o->UNITgetActCat());
    }

    public function testGetActCatWithProductAltBranches()
    {
        $this->markTestSkipped('Bug: test is not working as expected.');

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getProduct', '_addAdditionalParams']);
        $o->expects($this->once())->method('getProduct')->will($this->returnValue('product'));
        $o->expects($this->once())->method('_addAdditionalParams')->with(
            $this->equalTo('product'),
            $this->equalTo('cnid'),
            $this->equalTo('')
        );

        $this->setRequestParameter('mnid', '');
        $this->setRequestParameter('cnid', 'cnid');

        $this->assertSame(null, $o->UNITgetActCat());
    }

    public function testLoadCategoryTree()
    {
        $oCategoryList = $this->getMock(\OxidEsales\Eshop\Application\Model\CategoryList::class, ['buildTree', 'getClickCat']);
        $oCategoryList->expects($this->once())->method('buildTree')->with($this->equalTo('act cat'));

        oxTestModules::addModuleObject('oxCategoryList', $oCategoryList);

        $oParent = $this->getMock('stdclass', ['setCategoryTree', 'setActiveCategory']);
        $oParent->expects($this->once())->method('setCategoryTree')
            ->with($this->equalTo($oCategoryList));

        $o = oxNew('oxcmp_categories');

        $o->setParent($oParent);

        $this->assertNull($o->UNITloadCategoryTree('act cat'));
    }

    public function testLoadManufacturerTreeIsNotNeeded()
    {
        $this->markTestSkipped('Bug: test is not working as expected.');

        oxTestModules::addFunction('oxUtilsObject', 'oxNew($cl)', '{if ("oxmanufacturerlist" == $cl) return \Unit\Application\Controller\CmpCategoriesTest::$oCL; return parent::oxNew($cl);}');

        $oCfg = $this->getMock('stdClass', ['getConfigParam']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(false));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));

        $this->assertNull($o->UNITloadManufacturerTree('act Manufacturer'));
    }

    public function testLoadManufacturerTree()
    {
        $this->markTestSkipped('Bug: Method not called.');

        self::$oCL = $this->getMock('stdclass', ['buildManufacturerTree', 'getClickManufacturer']);
        self::$oCL->expects($this->once())->method('buildManufacturerTree')
            ->with(
                $this->equalTo('manufacturerlist'),
                $this->equalTo('act Manufacturer'),
                $this->equalTo('passitthru1')
            );
        self::$oCL->expects($this->once())->method('getClickManufacturer')->will($this->returnValue('returned click Manufacturer'));

        $oParent = $this->getMock('stdclass', ['setManufacturerTree', 'setActManufacturer']);
        $oParent->expects($this->once())->method('setManufacturerTree')
            ->with(
                $this->equalTo(self::$oCL)
            );
        $oParent->expects($this->once())->method('setActManufacturer')
            ->with(
                $this->equalTo('returned click Manufacturer')
            );

        $oCfg = $this->getMock('stdClass', ['getConfigParam', 'getShopHomeURL']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(true));
        $oCfg->expects($this->at(1))->method('getShopHomeURL')->will($this->returnValue('passitthru1'));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig', 'getManufacturerList']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->expects($this->any())->method('getManufacturerList')->will($this->returnValue(self::$oCL));

        $o->setParent($oParent);

        $this->assertNull($o->UNITloadManufacturerTree('act Manufacturer'));
    }

    public function testRenderEverythingOff()
    {
        $this->markTestSkipped('Bug: Method not called.');

        $oCfg = $this->getMock('stdClass', ['getConfigParam']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(false));

        $oParent = $this->getMock('stdClass', ['getManufacturerTree', 'getCategoryTree']);
        $oParent->expects($this->never())->method('getManufacturerTree');

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->setParent($oParent);

        $this->assertNull($o->render());
    }

    public function testRenderMenufactList()
    {
        $this->markTestSkipped('Bug: Method not called.');

        $oCfg = $this->getMock('stdClass', ['getConfigParam']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(true));

        $oMTree = $this->getMock('stdClass', ['getRootCat']);
        $oMTree->expects($this->at(0))->method('getRootCat')->will($this->returnValue('root Manufacturer cat'));

        $oParent = $this->getMock('stdClass', ['setManufacturerlist', 'setRootManufacturer']);
        $oParent->expects($this->once())->method('setManufacturerlist')->with($this->equalTo($oMTree));
        $oParent->expects($this->once())->method('setRootManufacturer')->with($this->equalTo('root Manufacturer cat'));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->setManufacturerTree($oMTree);
        $o->setParent($oParent);

        $this->assertNull($o->render());
    }

    public function testRenderCategoryList()
    {
        $this->markTestSkipped('Bug: Method not called.');

        $oCfg = $this->getMock('stdClass', ['getConfigParam']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(false));

        $oCTree = $this->getMock('stdClass', []);

        $oParent = $this->getMock('stdClass', ['setManufacturerTree']);
        $oParent->expects($this->never())->method('setManufacturerTree');

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getConfig']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->setParent($oParent);
        $o->setCategoryTree($oCTree);

        $this->assertSame($oCTree, $o->render());
    }

    public function testRenderCategoryListTopNavi()
    {
        $this->markTestSkipped('Bug: Method not called.');

        $oCfg = $this->getMock('stdClass', ['getConfigParam']);
        $oCfg->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('bl_perfLoadManufacturerTree'))->will($this->returnValue(false));

        $oCTree = $this->getMock('stdClass', []);

        $oParent = $this->getMock('stdClass', ['getManufacturerTree']);
        $oParent->expects($this->never())->method('getManufacturerTree');

        $sClass = oxTestModules::addFunction('oxcmp_categories', '__set($name, $v)', '{$name = str_replace("UNIT_", "_", $name); $this->$name = $v; }');

        $o = $this->getMock($sClass, ['getConfig']);
        $o->expects($this->once())->method('getConfig')->will($this->returnValue($oCfg));
        $o->UNIT_oMoreCat = 'more category';
        $o->setParent($oParent);
        $o->setCategoryTree($oCTree);

        $this->assertSame($oCTree, $o->render());
    }

    /**
     * Testing oxcmp_categories::_addAdditionalParams()
     *
     * @return null
     */
    public function testAddAdditionalParamsSearch()
    {
        $this->setRequestParameter('searchparam', 'testSearchParam');
        $this->setRequestParameter('searchcnid', 'testSearchCnid');
        $this->setRequestParameter('searchvendor', 'testSearchVendor');
        $this->setRequestParameter('searchmanufacturer', 'testSearchManufacturer');
        $this->setRequestParameter('listtype', 'search');

        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['setListType', 'setCategoryId']);
        $oParent->expects($this->once())->method('setListType')->with($this->equalTo('search'));
        $oParent->expects($this->once())->method('setCategoryId')->with($this->equalTo('testCatId'));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getParent']);
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParent));
        $this->assertEquals('testCatId', $oCmp->UNITaddAdditionalParams(oxNew('oxarticle'), 'testCatId', 'testManId', 'testVendorId'));
    }

    /**
     * Testing oxcmp_categories::_addAdditionalParams()
     *
     * @return null
     */
    public function testAddAdditionalParamsManufacturer()
    {
        $this->getConfig()->setConfigParam('bl_perfLoadManufacturerTree', true);

        $this->setRequestParameter('searchparam', null);
        $this->setRequestParameter('searchcnid', null);
        $this->setRequestParameter('searchvendor', null);
        $this->setRequestParameter('searchmanufacturer', null);
        $this->setRequestParameter('listtype', null);

        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['setListType', 'setCategoryId']);
        $oParent->expects($this->once())->method('setListType')->with($this->equalTo('manufacturer'));
        $oParent->expects($this->once())->method('setCategoryId')->with($this->equalTo('testManId'));

        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, ['getManufacturerId']);
        $oProduct->expects($this->once())->method('getManufacturerId')->will($this->returnValue('testManId'));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getParent']);
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParent));
        $this->assertEquals('testManId', $oCmp->UNITaddAdditionalParams($oProduct, 'testCatId', 'testManId', 'testVendorId'));
    }

    /**
     * Testing oxcmp_categories::_addAdditionalParams()
     *
     * @return null
     */
    public function testAddAdditionalParamsVendor()
    {
        $this->setRequestParameter('searchparam', null);
        $this->setRequestParameter('searchcnid', null);
        $this->setRequestParameter('searchvendor', null);
        $this->setRequestParameter('searchmanufacturer', null);
        $this->setRequestParameter('listtype', null);

        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['setListType', 'setCategoryId']);
        $oParent->expects($this->once())->method('setListType')->with($this->equalTo('vendor'));
        $oParent->expects($this->once())->method('setCategoryId')->with($this->equalTo('v_testVendorId'));

        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, ['getVendorId', 'getManufacturerId']);
        $oProduct->expects($this->once())->method('getVendorId')->will($this->returnValue('testVendorId'));
        $oProduct->expects($this->once())->method('getManufacturerId')->will($this->returnValue('_testManId'));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getParent']);
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParent));
        $this->assertEquals('v_testVendorId', $oCmp->UNITaddAdditionalParams($oProduct, 'v_testVendorId', 'testManId', 'v_testVendorId'));
    }

    /**
     * Testing oxcmp_categories::_addAdditionalParams()
     *
     * @return null
     */
    public function testAddAdditionalParamsDefaultCat()
    {
        $this->setRequestParameter('searchparam', null);
        $this->setRequestParameter('searchcnid', null);
        $this->setRequestParameter('searchvendor', null);
        $this->setRequestParameter('searchmanufacturer', null);
        $this->setRequestParameter('listtype', null);

        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['setListType', 'setCategoryId']);
        $oParent->expects($this->once())->method('setListType')->with($this->equalTo(null));
        $oParent->expects($this->once())->method('setCategoryId')->with($this->equalTo('testCatId'));

        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, ['getCategoryIds']);
        $oProduct->expects($this->once())->method('getCategoryIds')->will($this->returnValue(['testCatId']));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getParent']);
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParent));
        $this->assertEquals('testCatId', $oCmp->UNITaddAdditionalParams($oProduct, null, null, null));
    }

    /**
     * Testing oxcmp_categories::_addAdditionalParams()
     *
     * @return null
     */
    public function testAddAdditionalParamsDefaultManufacturer()
    {
        $this->setRequestParameter('searchparam', null);
        $this->setRequestParameter('searchcnid', null);
        $this->setRequestParameter('searchvendor', null);
        $this->setRequestParameter('searchmanufacturer', null);
        $this->setRequestParameter('listtype', null);

        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['setListType', 'setCategoryId']);
        $oParent->expects($this->once())->method('setListType')->with($this->equalTo('manufacturer'));
        $oParent->expects($this->once())->method('setCategoryId')->with($this->equalTo('testManId'));

        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, ['getCategoryIds', 'getManufacturerId']);
        $oProduct->expects($this->once())->method('getCategoryIds')->will($this->returnValue(false));
        $oProduct->expects($this->once())->method('getManufacturerId')->will($this->returnValue('testManId'));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getParent']);
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParent));
        $this->assertEquals('testManId', $oCmp->UNITaddAdditionalParams($oProduct, null, null, null));
    }

    /**
     * Testing oxcmp_categories::_addAdditionalParams()
     *
     * @return null
     */
    public function testAddAdditionalParamsDefaultVendor()
    {
        $this->setRequestParameter('searchparam', null);
        $this->setRequestParameter('searchcnid', null);
        $this->setRequestParameter('searchvendor', null);
        $this->setRequestParameter('searchmanufacturer', null);
        $this->setRequestParameter('listtype', null);

        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['setListType', 'setCategoryId']);
        $oParent->expects($this->once())->method('setListType')->with($this->equalTo('vendor'));
        $oParent->expects($this->once())->method('setCategoryId')->with($this->equalTo('testVendorId'));

        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, ['getCategoryIds', 'getManufacturerId', 'getVendorId']);
        $oProduct->expects($this->once())->method('getCategoryIds')->will($this->returnValue(false));
        $oProduct->expects($this->once())->method('getManufacturerId')->will($this->returnValue(false));
        $oProduct->expects($this->once())->method('getVendorId')->will($this->returnValue('testVendorId'));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\CategoriesComponent::class, ['getParent']);
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParent));
        $this->assertEquals('testVendorId', $oCmp->UNITaddAdditionalParams($oProduct, null, null, null));
    }
}
