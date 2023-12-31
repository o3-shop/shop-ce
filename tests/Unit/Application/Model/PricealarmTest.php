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

use \oxField;
use \oxRegistry;

class PricealarmTest extends \OxidTestCase
{

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->delete('testalarm');

        parent::tearDown();
    }

    public function testInsert()
    {
        oxAddClassModule('modOxUtilsDate', 'oxUtilsDate');
        \OxidEsales\Eshop\Core\Registry::getUtilsDate()->UNITSetTime(100);

        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->save();

        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals('1970-01-01 00:00:00', $oAlarm->oxpricealarm__oxinsert->value);
    }

    public function testGetFPrice()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxartid = new oxField('1672', oxField::T_RAW);
        $oAlarm->oxpricealarm__oxcurrency = new oxField('EUR', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals('23,00', $oAlarm->getFPrice());
    }

    public function testGetPrice()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxartid = new oxField('1672', oxField::T_RAW);
        $oAlarm->oxpricealarm__oxcurrency = new oxField('EUR', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals('23.00', $oAlarm->getPrice());
    }

    public function testGetArticle()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxartid = new oxField('1672', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals('1672', $oAlarm->getArticle()->getId());
    }

    public function testGetTitle()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxartid = new oxField('1672', oxField::T_RAW);
        $oAlarm->save();
        $oProduct = oxNew("oxArticle");
        $oProduct->load('1672');
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals($oProduct->oxarticles__oxtitle->value, $oAlarm->getTitle());
    }

    public function testGetPriceAlarmCurrency()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxcurrency = new oxField('EUR', oxField::T_RAW);
        $oAlarm->save();
        $oThisCurr = $this->getConfig()->getCurrencyObject('EUR');
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals($oThisCurr, $oAlarm->getPriceAlarmCurrency());
    }

    public function testGetPriceAlarmCurrencyNotSet()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->save();
        $oDefCurr = $this->getConfig()->getActShopCurrencyObject();
        $oThisCurr = $this->getConfig()->getCurrencyObject($oDefCurr->name);
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals($oThisCurr, $oAlarm->getPriceAlarmCurrency());
        $this->assertEquals($oThisCurr->name, $oAlarm->oxpricealarm__oxcurrency->value);
    }

    public function testGetFProposedPrice()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxprice = new oxField('12.36', oxField::T_RAW);
        $oAlarm->oxpricealarm__oxcurrency = new oxField('EUR', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->load('testalarm');
        $this->assertEquals('12,36', $oAlarm->getFProposedPrice());
    }

    public function testGetPriceAlarmStatus()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxprice = new oxField('13', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = $this->getMock(\OxidEsales\Eshop\Application\Model\PriceAlarm::class, array('getPrice'));
        $oAlarm->expects($this->once())->method('getPrice')->will($this->returnValue("15"));
        $oAlarm->load('testalarm');
        $this->assertEquals(0, $oAlarm->getPriceAlarmStatus());
    }

    public function testGetPriceAlarmStatusSendEmail()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxprice = new oxField('13', oxField::T_RAW);
        $oAlarm->oxpricealarm__oxsended = new oxField('2010-10-10 00:00:00', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = $this->getMock(\OxidEsales\Eshop\Application\Model\PriceAlarm::class, array('getPrice'));
        $oAlarm->expects($this->once())->method('getPrice')->will($this->returnValue("15"));
        $oAlarm->load('testalarm');
        $this->assertEquals(2, $oAlarm->getPriceAlarmStatus());
    }

    public function testGetPriceAlarmStatusChangedPrice()
    {
        $oAlarm = oxNew('oxpricealarm');
        $oAlarm->setId('testalarm');
        $oAlarm->oxpricealarm__oxprice = new oxField('13', oxField::T_RAW);
        $oAlarm->save();

        $oAlarm = $this->getMock(\OxidEsales\Eshop\Application\Model\PriceAlarm::class, array('getPrice'));
        $oAlarm->expects($this->once())->method('getPrice')->will($this->returnValue("12"));
        $oAlarm->load('testalarm');
        $this->assertEquals(1, $oAlarm->getPriceAlarmStatus());
    }
}
