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

use \oxField;
use \oxRegistry;

/**
 * Tests for VoucherSerie_Export class
 */
class VoucherSerieExportTest extends \OxidTestCase
{

    /**
     * Cleanup
     *
     * @return null
     */
    public function tearDown(): void
    {
        // cleanup
        $this->cleanUpTable("oxvouchers");
        $this->cleanUpTable("oxvoucherseries");

        parent::tearDown();
    }

    /**
     * VoucherSerie_Export::getDownloadUrl() test case
     *
     * @return null
     */
    public function testGetDownloadUrl()
    {
        $myConfig = $this->getConfig();

        $myConfig->setConfigParam("sAdminSSLURL", "sAdminSSLURL");
        \OxidEsales\Eshop\Core\Registry::getUtilsUrl()->setAdminMode(true);
        $sUrl = \OxidEsales\Eshop\Core\Registry::getUtilsUrl()->processUrl('sAdminSSLURL/index.php');

        // ssl
        $oView = oxNew('VoucherSerie_Export');
        $this->assertEquals($sUrl . '&amp;cl=voucherserie_export&amp;fnc=download', $oView->getDownloadUrl());

        $myConfig->setConfigParam("sAdminSSLURL", null);
        $sUrl = $myConfig->getConfigParam('sShopURL') . $myConfig->getConfigParam('sAdminDir');
        $sUrl = \OxidEsales\Eshop\Core\Registry::getUtilsUrl()->processUrl($sUrl . '/index.php');

        // non ssl
        $oView = oxNew('VoucherSerie_Export');
        $this->assertEquals($sUrl . '&amp;cl=voucherserie_export&amp;fnc=download', $oView->getDownloadUrl());
    }

    /**
     * VoucherSerie_Export::_getExportFileName() test case
     *
     * @return null
     */
    public function testGetExportFileName()
    {
        $oView = oxNew('VoucherSerie_Export');
        $oView->UNITgetExportFileName();

        $this->assertNotNull(oxRegistry::getSession()->getVariable("sExportFileName"));
    }

    /**
     * VoucherSerie_Export::_getExportFilePath() test case
     *
     * @return null
     */
    public function testGetExportFilePath()
    {
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieExport::class, array("_getExportFileName"));
        $oView->expects($this->once())->method('_getExportFileName')->will($this->returnValue("testName"));

        $this->assertEquals($this->getConfig()->getConfigParam('sShopDir') . "/export/" . "testName", $oView->UNITgetExportFilePath());
    }

    /**
     * VoucherSerie_Export::exportVouchers() test case
     *
     * @return null
     */
    public function testExportVouchers()
    {
        // test voucherserie
        $oVoucherSerie = oxNew("oxvoucherserie");
        $oVoucherSerie->setId("_testvoucherserie");

        // inserting test voucher
        $oVoucher = oxNew("oxBase");
        $oVoucher->init("oxvouchers");
        $oVoucher->setId("_testvoucher");
        $oVoucher->oxvouchers__oxvoucherserieid = new oxField("_testvoucherserie");
        $oVoucher->oxvouchers__oxvouchernr = new oxField("_testvoucher");
        $oVoucher->save();

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieExport::class, array("write", "_getVoucherSerie"));
        $oView->expects($this->at(0))->method('_getVoucherSerie')->will($this->returnValue($oVoucherSerie));
        $oView->expects($this->at(1))->method('write')->with($this->equalTo("Gutscheine"));
        $oView->expects($this->at(2))->method('write')->with($this->equalTo("_testvoucher"));
        $this->assertEquals(1, $oView->exportVouchers(0));
    }
}
