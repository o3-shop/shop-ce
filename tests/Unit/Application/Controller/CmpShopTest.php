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
use \oxException;
use OxidEsales\EshopCommunity\Core\Registry;
use \oxTestModules;

/**
 * oxcmp_shop tests
 */
class CmpShopTest extends \OxidTestCase
{

    /**
     * Testing oxcmp_shop::render()
     */
    public function testRenderNoActiveShop()
    {
        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, array("getClassName"));
        $oView->expects($this->once())->method('getClassName')->will($this->returnValue("test"));

        $oShop = oxNew('oxShop');
        $oShop->oxshops__oxactive = new oxField(0);

        $oUtils = $this->getMock(\OxidEsales\Eshop\Core\Utils::class, array('showOfflinePage'));
        $oUtils->expects($this->once())->method('showOfflinePage');
        Registry::set(\OxidEsales\Eshop\Core\Utils::class, $oUtils);

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array("getConfigParam", "getActiveView", "getActiveShop"));
        $oConfig->expects($this->once())->method('getActiveView')->will($this->returnValue($oView));
        $oConfig->expects($this->any())->method('getConfigParam')->will($this->returnValue(false));
        $oConfig->expects($this->once())->method('getActiveShop')->will($this->returnValue($oShop));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\ShopComponent::class, array("getConfig", "isAdmin"), array(), '', false);
        $oCmp->expects($this->once())->method('getConfig')->will($this->returnValue($oConfig));
        $oCmp->expects($this->once())->method('isAdmin')->will($this->returnValue(false));

        $oCmp->render();
    }
}
