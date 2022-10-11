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

use \oxField;
use oxOrderFile;
use \oxTestModules;
use OxidEsales\EshopCommunity\Application\Model\OrderFile;

/**
 * Tests for Account_downloads class
 */
class AccountdownloadsTest extends \OxidTestCase
{

    /**
     * Testing Account_Downloads::getBreadCrumb()
     *
     * @return null
     */
    public function testGetBreadCrumb()
    {
        $oAccDownloads = oxNew('Account_Downloads');
        $this->assertEquals(2, count($oAccDownloads->getBreadCrumb()));
    }


    /**
     * Testing Account_Downloads::getDownloadError()
     *
     * @return null
     */
    public function testGetDownloadError()
    {
        $this->setRequestParameter('download_error', 'aaa');

        $oAccDownloads = oxNew('Account_Downloads');

        $this->assertEquals('aaa', $oAccDownloads->getDownloadError());
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testGetArticleList()
    {
        $oUser = $this->getMock(\OxidEsales\Eshop\Application\Model\User::class, array('getId'));
        $oUser->expects($this->any())->method('getId')->will($this->returnValue("userId"));

        $oFileOrder = oxNew("oxorderfile");
        $oFileOrder->oxorderfiles__oxorderarticleid = new oxField("testArtNr");
        $oFileOrder->oxorderfiles__oxordernr = new oxField("testOrder");
        $oFileOrder->oxorderfiles__oxorderdate = new oxField("2011-11-11 11:11:11");
        $oFileOrder->oxorderfiles__oxarticletitle = new oxField("testArtTitle");

        $oOrderFileList = $this->getMock(\OxidEsales\Eshop\Application\Model\OrderFileList::class, array('loadUserFiles'));
        $oOrderFileList->expects($this->any())->method('loadUserFiles')->will($this->returnValue("orderfilelist"));
        $oOrderFileList[] = $oFileOrder;
        oxTestModules::addModuleObject('oxOrderFileList', $oOrderFileList);

        $oAccDownloads = $this->getMock(\OxidEsales\Eshop\Application\Controller\AccountDownloadsController::class, array('getUser'));
        $oAccDownloads->expects($this->any())->method('getUser')->will($this->returnValue($oUser));

        $aOrderFilesList = $oAccDownloads->getOrderFilesList();
        $this->assertEquals("testOrder", $aOrderFilesList["testArtNr"]["oxordernr"]);
        $this->assertEquals("2011-11-11 11:11", $aOrderFilesList["testArtNr"]["oxorderdate"]);
        $this->assertEquals("testArtTitle", $aOrderFilesList["testArtNr"]["oxarticletitle"]);
        $this->assertTrue($aOrderFilesList["testArtNr"]["oxorderfiles"][0] instanceof OrderFile);
    }

    /**
     * Test get article list.
     *
     * @return null
     */
    public function testGetArticleListIsSet()
    {
        $oAccDownloads = $this->getProxyClass('Account_Downloads');
        $oAccDownloads->setNonPublicVar('_oOrderFilesList', "testOrder");
        $this->assertEquals("testOrder", $oAccDownloads->getOrderFilesList());
    }
}
