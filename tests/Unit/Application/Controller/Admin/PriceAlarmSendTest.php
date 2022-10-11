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

use \oxTestModules;

/**
 * Tests for PriceAlarm_Send class
 */
class PriceAlarmSendTest extends \OxidTestCase
{

    /**
     * PriceAlarm_Send::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\PriceAlarmSend::class, array("_setupNavigation"));
        $oView->expects($this->once())->method('_setupNavigation');
        $this->assertEquals('pricealarm_done.tpl', $oView->render());
    }

    /**
     * PriceAlarm_Send::SetupNavigation() test case
     *
     * @return null
     */
    public function testSetupNavigation()
    {
        // testing..
        $sNode = "pricealarm_list";
        $this->setRequestParameter("menu", $sNode);
        $this->setRequestParameter('actedit', 1);

        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, array("getTabs", "getActiveTab"));
        $oNavigation->expects($this->any())->method('getActiveTab')->will($this->returnValue("testEdit"));
        $oNavigation->expects($this->once())->method('getTabs')->with($this->equalTo($sNode), $this->equalTo(1))->will($this->returnValue("editTabs"));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\PriceAlarmSend::class, array("getNavigation"));
        $oView->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));

        $oView->UNITsetupNavigation($sNode);
        $this->assertEquals("editTabs", $oView->getViewDataElement("editnavi"));
        $this->assertEquals("testEdit", $oView->getViewDataElement("actlocation"));
        $this->assertEquals("testEdit", $oView->getViewDataElement("default_edit"));
        $this->assertEquals(1, $oView->getViewDataElement("actedit"));
    }

    /**
     * PriceAlarm_Send::SendeMail() test case
     *
     * @return null
     */
    public function testSendeMail()
    {
        $oAlarm = $this->getMock(\OxidEsales\Eshop\Application\Model\PriceAlarm::class, array('save', 'load'));
        $oAlarm->expects($this->once())->method('load')
            ->with($this->equalTo("paid"))
            ->will($this->returnValue(true));
        $oAlarm->expects($this->once())->method('save')
            ->will($this->returnValue(true));

        oxTestModules::addModuleObject('oxpricealarm', $oAlarm);

        $oEmail = $this->getMock(\OxidEsales\Eshop\Core\Email::class, array('sendPricealarmToCustomer'));
        $oEmail->expects($this->once())->method('sendPricealarmToCustomer')
            ->with($this->equalTo("info@example.com"), $this->isInstanceOf('\OxidEsales\EshopCommunity\Application\Model\PriceAlarm'))
            ->will($this->returnValue(true));

        oxTestModules::addModuleObject('oxemail', $oEmail);

        // testing..
        $oView = oxNew('PriceAlarm_Send');
        $oView->sendeMail("info@example.com", "", "paid", "");
    }
}
