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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use OxidEsales\EshopCommunity\Core\Model\ListModel;

use \oxField;

//class should be named Unit_oxSimpleVariantListTest
//but GAAAH IT DOES NOT WORK somehow, just because of the name??????
//T2009-04-20
class SimplevariantlistTest extends \OxidTestCase
{
    public function testSetParent()
    {
        $oSubj = $this->getProxyClass("oxSimpleVariantList");
        $oSubj->setParent("testString");
        $this->assertEquals("testString", $oSubj->getNonPublicVar("_oParent"));
    }

    public function testAssignElement()
    {
        $sParent = "someString";
        $aDbFields = array("field1" => "val1");

        $oListObjectMock = $this->getMock(\OxidEsales\Eshop\Application\Model\SimpleVariant::class, array('setParent'));
        $oListObjectMock->expects($this->once())->method('setParent')->with($sParent);

        $oSubj = $this->getProxyClass("oxSimpleVariantList");
        $oSubj->setNonPublicVar("_oParent", $sParent);
        $oSubj->UNITassignElement($oListObjectMock, $aDbFields);
    }

    //bug #441 test case for lists
    public function testParentPriceIsLoadedForVariant()
    {
        if ($this->getTestConfig()->getShopEdition() === 'EE') {
            $sArtId = '1661';
            $sVariantId = '1661-01';
            $sArtPrice = 13.9;
        } else {
            $sArtId = '2077';
            $sVariantId = '8a142c4100e0b2f57.59530204';
            $sArtPrice = 19;
        }

        $oParent = $this->getProxyClass("oxArticle");
        $oParent->setInList();
        $oParent->load($sArtId);
        $oVariantList = $oParent->getVariants();

        $this->assertTrue($oVariantList instanceof ListModel);
        $this->assertTrue($oVariantList->offsetExists($sVariantId));

        $oVariant = $oVariantList->offsetGet($sVariantId);
        $oVariant->oxarticles__oxprice = new oxField(0);
        $oVariant->setPrice(null);

        $this->assertEquals($sArtPrice, $oVariant->getPrice()->getBruttoPrice());
    }
}
