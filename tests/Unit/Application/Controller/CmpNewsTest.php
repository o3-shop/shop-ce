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

use OxidEsales\EshopCommunity\Application\Model\NewsList;

/**
 * oxcmp_news tests
 */
class CmpNewsTest extends \OxidTestCase
{

    /**
     * Testing oxcmp_news::render()
     *
     * @return null
     */
    public function testRenderDisabledNavBars()
    {
        $this->getConfig()->setConfigParam("bl_perfLoadNews", false);

        $oCmp = oxNew('oxcmp_news');
        $this->assertNull($oCmp->render());
    }

    /**
     * Testing oxcmp_news::render()
     *
     * @return null
     */
    public function testRenderPerfLoadNewsOnlyStart()
    {
        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, array("getIsOrderStep", "getClassName"));
        $oView->expects($this->never())->method('getIsOrderStep');
        $oView->expects($this->once())->method('getClassName')->will($this->returnValue("test"));

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array("getConfigParam", "getActiveView"));
        $oConfig->expects($this->at(0))->method('getActiveView')->will($this->returnValue($oView));
        $oConfig->expects($this->at(1))->method('getConfigParam')->with($this->equalTo("bl_perfLoadNews"))->will($this->returnValue(true));
        $oConfig->expects($this->at(2))->method('getConfigParam')->with($this->equalTo("blDisableNavBars"))->will($this->returnValue(false));
        $oConfig->expects($this->at(3))->method('getConfigParam')->with($this->equalTo("bl_perfLoadNewsOnlyStart"))->will($this->returnValue(true));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\NewsComponent::class, array("getConfig"), array(), '', false);
        $oCmp->expects($this->once())->method('getConfig')->will($this->returnValue($oConfig));
        $this->assertNull($oCmp->render());
    }

    /**
     * Testing oxcmp_news::render()
     *
     * @return null
     */
    public function testRender()
    {
        $this->getConfig()->setConfigParam("bl_perfLoadNews", true);
        $this->getConfig()->setConfigParam("blDisableNavBars", false);
        $this->getConfig()->setConfigParam("bl_perfLoadNewsOnlyStart", false);

        $oCmp = oxNew('oxcmp_news');
        $this->assertTrue($oCmp->render() instanceof newslist);
    }
}
