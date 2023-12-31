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

class PricelistTest extends \OxidTestCase
{
    public $aPrices = array();

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->aPrices[0] = oxNew('oxprice');
        $this->aPrices[0]->setPrice(10);
        $this->aPrices[0]->setVat(5);

        $this->aPrices[1] = oxNew('oxprice');
        $this->aPrices[1]->setPrice(20);
        $this->aPrices[1]->setVat(10);

        $this->aPrices[2] = oxNew('oxprice');
        $this->aPrices[2]->setPrice(30);
        $this->aPrices[2]->setVat(10);

        $this->aPrices[3] = oxNew('oxprice');
        $this->aPrices[3]->setPrice(40);
        $this->aPrices[3]->setVat(20);

        $this->aPrices[4] = oxNew('oxprice');
        $this->aPrices[4]->setPrice(50);
        $this->aPrices[4]->setVat(10);
    }

    /**
     * Brutto price sum getter
     */
    public function testGetBruttoSum()
    {
        $oList = oxNew('oxpricelist');
        $oList->addToPriceList($this->aPrices[0]);
        $oList->addToPriceList($this->aPrices[3]);

        $this->assertEquals(50, $oList->getBruttoSum());
    }

    /**
     * Netto price sum getter
     */
    public function testGetNettoSum()
    {
        $oList = oxNew('oxPriceList');
        $oList->addToPriceList($this->aPrices[0]);
        $oList->addToPriceList($this->aPrices[3]);

        $this->assertEquals(round(10 / 1.05, 2) + round(40 / 1.20, 2), $oList->getNettoSum());
    }

    /**
     * Testing Vat info getter
     */
    public function testGetVatInfo()
    {
        $oList = oxNew('oxpricelist');
        $oList->addToPriceList($this->aPrices[0]);
        $oList->addToPriceList($this->aPrices[1]);
        $oList->addToPriceList($this->aPrices[2]);
        $oList->addToPriceList($this->aPrices[3]);
        $oList->addToPriceList($this->aPrices[4]);

        $aVatInfo = array(5  => 10 - 10 / 1.05,
                          10 => 100 - 100 / 1.1,
                          20 => 40 - 40 / 1.2);

        $this->assertEqualsWithDelta($aVatInfo, $oList->getVatInfo(false), 0.0000001);

        $aVatInfo = array(5  => 10 * 0.05,
                          10 => 100 * 0.1,
                          20 => 40 * 0.2);

        $this->assertEqualsWithDelta($aVatInfo, $oList->getVatInfo(), 0.0000001);
    }

    /**
     * Testing Vat info getter
     */
    public function testGetSum()
    {
        $oList = oxNew('oxPriceList');

        $oPrice = oxNew('oxPrice');
        $oPrice->setNettoPriceMode();
        $oPrice->setPrice(10, 10);
        $oList->addToPriceList($oPrice);

        $oPrice = oxNew('oxPrice');
        $oPrice->setNettoPriceMode();
        $oPrice->setPrice(20, 20);
        $oList->addToPriceList($oPrice);

        $this->assertEqualsWithDelta(35, $oList->getSum(false), 0.0000001);
        $this->assertEqualsWithDelta(30, $oList->getSum(), 0.0000001);
    }


    /**
     * testing price info getter
     */
    public function testGetPriceInfo()
    {
        $oList = oxNew('oxpricelist');
        $oList->addToPriceList($this->aPrices[0]);
        $oList->addToPriceList($this->aPrices[1]);
        $oList->addToPriceList($this->aPrices[2]);
        $oList->addToPriceList($this->aPrices[3]);
        $oList->addToPriceList($this->aPrices[4]);

        $aPriceInfo = array(5  => 10,
                            10 => 100,
                            20 => 40);

        $this->assertEquals($aPriceInfo, $oList->getPriceInfo());
    }

    /**
     * Most used VAT percent getter
     */
    public function testGetMostUsedVatPercent()
    {
        $oList = oxNew('oxpricelist');
        $oList->addToPriceList($this->aPrices[0]);
        $oList->addToPriceList($this->aPrices[1]);
        $oList->addToPriceList($this->aPrices[2]);
        $oList->addToPriceList($this->aPrices[3]);
        $oList->addToPriceList($this->aPrices[4]);

        $this->assertEquals(10, $oList->getMostUsedVatPercent());
    }

    /**
     * Proportional VAT percent getter
     */
    public function testGetProportionalVatPercent()
    {
        $oList = oxNew('oxPriceList');

        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(118, 18);
        $oList->addToPriceList($oPrice);

        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(121, 21);
        $oList->addToPriceList($oPrice);
        $this->assertEquals(19.5, $oList->getProportionalVatPercent());


        $oList = oxNew('oxPriceList');
        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(590, 18);
        $oList->addToPriceList($oPrice);

        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(121, 21);
        $oList->addToPriceList($oPrice);

        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(0, 19);
        $oList->addToPriceList($oPrice);

        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(0, 0);
        $oList->addToPriceList($oPrice);

        $this->assertEquals(18.5, $oList->getProportionalVatPercent());

        $oList = oxNew('oxPriceList');
        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(0, 10);
        $oList->addToPriceList($oPrice);

        $this->assertEquals(0, $oList->getProportionalVatPercent());

        $oList = oxNew('oxPriceList');
        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(0, 0);
        $oList->addToPriceList($oPrice);

        $this->assertEquals(0, $oList->getProportionalVatPercent());

        $oList = oxNew('oxPriceList');
        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(1, 0);
        $oList->addToPriceList($oPrice);

        $this->assertEquals(0, $oList->getProportionalVatPercent());
    }

    /**
     * Price calculator
     */
    public function testCalculateToPrice()
    {
        $oList = oxNew('oxPriceList');

        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(118, 18);
        $oList->addToPriceList($oPrice);


        $oPrice = oxNew('oxPrice');
        $oPrice->setBruttoPriceMode();
        $oPrice->setPrice(121, 21);
        $oList->addToPriceList($oPrice);

        $oPrice = $oList->calculateToPrice();

        $this->assertEquals(200, $oPrice->getNettoPrice());
        $this->assertEquals(239, $oPrice->getBruttoPrice());
        $this->assertEquals(39, $oPrice->getVatValue());
        $this->assertEquals(19.5, $oPrice->getVat());
    }


    public function testGetMostUsedVatPercentIfPriceListNotSet()
    {
        $oList = oxNew('oxPriceList');

        $this->assertNull($oList->getMostUsedVatPercent());
    }
}
