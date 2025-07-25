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

use oxDb;
use oxField;
use stdClass;

/**
 * Testing oxAdminDetails class.
 */
class AdminDetailsTest extends \OxidTestCase
{
    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxlinks');
        $this->cleanUpTable('oxorder');
        $this->cleanUpTable('oxcontents');
        $this->cleanUpTable('oxobject2category');

        parent::tearDown();
    }

    /**
     * Test get plain editor.
     */
    public function testGetPlainEditor()
    {
        $this->markTestSkipped('Bug: sEditObjectValue is missing.');
        $oObject = new stdClass();
        $sEditorHtml = "<textarea id='editor_sField' name='sField' style='width:100px; height:100px;'>sEditObjectValue</textarea>";

        $oAdminDetails = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class, ['_getEditValue']);
        $oAdminDetails->expects($this->once())->method('_getEditValue')->with($this->equalTo($oObject), $this->equalTo('sField'))->will($this->returnValue('sEditObjectValue'));
        $this->assertEquals($sEditorHtml, $oAdminDetails->UNITgetPlainEditor(100, 100, $oObject, 'sField'));
    }

    /**
     * Test get edit value then object is not set.
     */
    public function testGetEditValueObjectNotSet()
    {
        $oAdminDetails = oxNew('oxadmindetails');
        $this->assertEquals('', $oAdminDetails->UNITgetEditValue(null, null));
    }

    /**
     * Test get edit value.
     */
    public function testGetEditValue()
    {
        $oObject = new stdClass();
        $oObject->oField1 = new oxField('field1value');

        $oObject->oField2 = new stdClass();
        $oObject->oField2->value = 'field2value';

        $oAdminDetails = oxNew('oxadmindetails');
        $this->assertEquals('', $oAdminDetails->UNITgetEditValue($oObject, 'notExistingField'));
        $this->assertEquals('field1value', $oAdminDetails->UNITgetEditValue($oObject, 'oField1'));
        $this->assertEquals('field2value', $oAdminDetails->UNITgetEditValue($oObject, 'oField2'));
    }

    /**
     * Test get edit value - when smarty parser is off.
     */
    public function testGetEditValue_parseIsOff()
    {
        $oObject = new stdClass();
        $oObject->oField = new oxField('test [{$oViewConf->getCurrentHomeDir()}]');

        $myConfig = $this->getConfig();
        $myConfig->setConfigParam('bl_perfParseLongDescinSmarty', false);
        $sUrl = $this->getConfig()->getCurrentShopURL();

        $oAdminDetails = oxNew('oxadmindetails');
        $this->assertEquals("test $sUrl", $oAdminDetails->UNITgetEditValue($oObject, 'oField'));
    }

    /**
     *  Test updating object folder parameters
     */
    public function testChangeFolder()
    {
        $oListItem = oxNew('oxContent');
        $oListItem->setId('_testId');
        $oListItem->oxcontents__oxloadid = new oxField('_testLoadId');
        $oListItem->save();

        $this->setRequestParameter('oxid', '_testId');
        $this->setRequestParameter('setfolder', 'neu');
        $this->setRequestParameter('folderclass', 'oxcontent');

        $oAdminDetails = $this->getProxyClass('oxadmindetails');
        $oAdminDetails->setNonPublicVar('_oList', $oListItem);
        $oAdminDetails->changeFolder();

        $sSql = "select oxfolder from oxcontents where oxid = '_testId' ";
        $this->assertEquals('neu', oxDb::getDb()->getOne($sSql));
    }

    /**
     *  Test updating object folder parameters - reseting folder
     */
    public function testChangeFolderResetingFolderName()
    {
        $oListItem = oxNew('oxContent');
        $oListItem->setId('_testId');
        $oListItem->oxcontents__oxloadid = new oxField('_testLoadId');
        $oListItem->oxcontents__oxfolder = new oxField('neu', oxField::T_RAW);
        $oListItem->save();

        $this->setRequestParameter('oxid', '_testId');
        $this->setRequestParameter('setfolder', 'CMSFOLDER_NONE');
        $this->setRequestParameter('folderclass', 'oxcontent');

        $oAdminDetails = $this->getProxyClass('oxadmindetails');

        $oAdminDetails->setNonPublicVar('_oList', $oListItem);
        $oAdminDetails->changeFolder();

        $sSql = "select oxfolder from oxcontents where oxid = '_testId' ";
        $this->assertEquals('', oxDb::getDb()->getOne($sSql));
    }

    /**
     *  Test setup navigation.
     */
    public function testSetupNavigation()
    {
        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, ['getBtn', 'getActiveTab']);
        $oNavigation->expects($this->once())->method('getBtn')->with($this->equalTo('xxx'))->will($this->returnValue('bottom_buttons'));
        $oNavigation->expects($this->once())->method('getActiveTab')->with($this->equalTo('xxx'), $this->equalTo(0))->will($this->returnValue('default_edit'));

        $oAdminDetails = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class, ['getNavigation']);
        $oAdminDetails->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));

        $oAdminDetails->UNITsetupNavigation('xxx');
        $this->assertEquals('default_edit', $oAdminDetails->getViewDataElement('default_edit'));
        $this->assertEquals('bottom_buttons', $oAdminDetails->getViewDataElement('bottom_buttons'));
    }

    /**
     *  Test get category tree testing if empty category will be selected.
     */
    public function testGetCategoryTreeTestingIfEmptyCategoryWillBeSelected()
    {
        $oAdminDetails = oxNew('oxadmindetails');
        $sActCatId = $oAdminDetails->UNITgetCategoryTree('xxx', null);
        $oList = $oAdminDetails->getViewDataElement('xxx');
        $oList->rewind();

        $oCat = $oList->current();
        $this->assertEquals('--', $oCat->oxcategories__oxtitle->value);
        $this->assertEquals($sActCatId, $oCat->getId());
    }

    /**
     *  Test get category tree unsetting active category.
     */
    public function testGetCategoryTreeUnsettingActiveCategory()
    {
        $sCatTable = getViewName('oxcategories');
        $sCat = oxDb::getDb()->getOne("select oxid from $sCatTable where oxactive = 1");

        $oAdminDetails = oxNew('oxadmindetails');
        $sActCatId = $oAdminDetails->UNITgetCategoryTree('xxx', null, $sCat);
        $oList = $oAdminDetails->getViewDataElement('xxx');

        foreach ($oList as $oCat) {
            if ($oCat->getId() == $sCat) {
                $this->fail('failed testGetCategoryTreeUnsettingActiveCategory test');
            }
        }
    }

    /**
     *  Test get category tree marking active category.
     */
    public function testGetCategoryTreeMarkingActiveCategory()
    {
        $sCatTable = getViewName('oxcategories');
        $sCat = oxDb::getDb()->getOne("select oxid from $sCatTable where oxactive = 1");

        $oAdminDetails = oxNew('oxadmindetails');
        $sActCatId = $oAdminDetails->UNITgetCategoryTree('xxx', $sCat);
        $oList = $oAdminDetails->getViewDataElement('xxx');

        foreach ($oList as $oCat) {
            if ($oCat->getId() == $sCat && $oCat->selected = 1) {
                return;
            }
        }

        $this->fail('failed testGetCategoryTreeUnsettingActiveCategory test');
    }

    /**
     * Test reseting of number of articles in current shop categories.
     */
    public function testResetNrOfCatArticles()
    {
        $oAdminDetails = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class, ['resetContentCache']);
        $oAdminDetails->expects($this->once())->method('resetContentCache');

        $oAdminDetails->resetNrOfCatArticles();
    }

    /**
     * Test reseting number of articles in current shop vendors.
     */
    public function testResetNrOfVendorArticles()
    {
        $oAdminDetails = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class, ['resetContentCache']);
        $oAdminDetails->expects($this->once())->method('resetContentCache');

        $oAdminDetails->resetNrOfVendorArticles();
    }

    /**
     * Test reseting number of articles in current shop manufacturers.
     */
    public function testResetNrOfManufacturerArticles()
    {
        $oAdminDetails = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class, ['resetContentCache']);
        $oAdminDetails->expects($this->once())->method('resetContentCache');

        $oAdminDetails->resetNrOfManufacturerArticles();
    }

    /**
     * Test reset count of vendor/manufacturer category items
     */
    public function testResetCounts()
    {
        $oAdminDetails = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class, ['resetCounter']);
        $oAdminDetails->expects($this->exactly(2))
            ->method('resetCounter')
            ->withConsecutive(
                [$this->equalTo('vendorArticle'), $this->equalTo('ID1')],
                [$this->equalTo('manufacturerArticle'), $this->equalTo('ID2')]
            );

        $aIds = ['vendor' => ['ID1' => '1'], 'manufacturer' => ['ID2' => '2']];

        $oAdminDetails->UNITresetCounts($aIds);
    }
}
