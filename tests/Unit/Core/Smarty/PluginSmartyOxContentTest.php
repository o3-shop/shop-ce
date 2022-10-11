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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Smarty;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Core\ShopIdCalculator;
use OxidTestCase;
use oxTestModules;
use PHPUnit\Framework\MockObject\MockObject;
use Smarty;

$filePath = Registry::getConfig()->getConfigParam('sShopDir') . 'Core/Smarty/Plugin/function.oxcontent.php';
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    require_once __DIR__ . '/../../../../source/Core/Smarty/Plugin/function.oxcontent.php';
}

final class PluginSmartyOxContentTest extends OxidTestCase
{
    public function testGetContentWhenShopIsNotProductiveAndContentDoesNotExist(): void
    {
        oxTestModules::addFunction(
            "oxconfig",
            "getActiveShop",
            "{ \$oShop = oxNew('oxShop');; \$oShop->oxshops__oxproductive = new oxField();  return \$oShop;}"
        );

        $aParams['ident'] = 'testident';
        $oSmarty = new Smarty();

        $sText = "<b>content not found ! check ident(" . $aParams['ident'] . ") !</b>";

        $this->assertEquals($sText, smarty_function_oxcontent($aParams, $oSmarty));
    }

    public function testGetContentNoParamsPassedShopIsProductive(): void
    {
        $smarty = $this->createMock(Smarty::class);
        $this->assertEquals(
            "<b>content not found ! check ident(undefined) !</b>",
            smarty_function_oxcontent(array(), $smarty)
        );
    }

    public function testGetContentLoadByIdent(): void
    {
        $sShopId = ShopIdCalculator::BASE_SHOP_ID;

        $aParams['ident'] = 'oxsecurityinfo';
        $oSmarty = $this->getMock("Smarty", array("fetch"));
        $oSmarty->expects($this->once())->method('fetch')
            ->with($this->equalTo('ox:oxsecurityinfooxcontent0' . $sShopId))
            ->willReturn('testvalue');

        $message = "Content not found! check ident(" . $aParams['ident'] . ") !";

        $this->assertEquals('testvalue', smarty_function_oxcontent($aParams, $oSmarty), $message);
    }

    public function testGetContentLoadByIdentLangChange(): void
    {
        $sShopId = ShopIdCalculator::BASE_SHOP_ID;

        $aParams['ident'] = 'oxsecurityinfo';
        $oSmarty = $this->getMock("smarty", array("fetch"));
        $oSmarty->expects($this->once())->method('fetch')
            ->with($this->equalTo('ox:oxsecurityinfooxcontent1' . $sShopId))
            ->willReturn('testvalue');

        $message = "Content not found! check ident(" . $aParams['ident'] . ") !";

        oxTestModules::addFunction('oxLang', 'getBaseLanguage', '{return 1;}');

        $this->assertEquals('testvalue', smarty_function_oxcontent($aParams, $oSmarty), $message);
    }

    public function testGetContentLoadByOxId(): void
    {
        $sShopId = ShopIdCalculator::BASE_SHOP_ID;
        $aParams['oxid'] = 'f41427a099a603773.44301043';
        $aParams['assign'] = true;

        /** @var MockObject|Smarty $oSmarty */
        $oSmarty = $this->createPartialMock("smarty", ['fetch', 'assign']);
        $oSmarty->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo('ox:f41427a099a603773.44301043oxcontent0' . $sShopId))
            ->willReturn('testvalue');
        $oSmarty->expects($this->once())->method('assign')->with($this->equalTo(true));

        smarty_function_oxcontent($aParams, $oSmarty);
    }
}
