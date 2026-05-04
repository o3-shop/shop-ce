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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use Exception;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ShopMain;
use oxTestModules;

class ShopMainTest extends \OxidTestCase
{
    public function testRender()
    {
        $oView = oxNew('Shop_Main');

        $this->setRequestParameter('oxid', $this->getConfig()->getBaseShopId());
        $this->assertEquals('shop_main.tpl', $oView->render());
    }

    public function testRenderForNewShopReturnsMainTemplateByDefault(): void
    {
        $oView = oxNew(ShopMain::class);
        $this->setRequestParameter('oxid', ShopMain::NEW_SHOP_ID);
        $this->assertEquals('shop_main.tpl', $oView->render());
    }

    public function testRenderUsesAlternativeTemplateWhenRenderNewShopIsOverridden(): void
    {
        $controller = $this->getMock(ShopMain::class, ['renderNewShop']);
        $controller->expects($this->once())
            ->method('renderNewShop')
            ->will($this->returnValue('shop_new.tpl'));

        $this->assertSame('shop_new.tpl', $controller->render());
    }

    public function testRenderExposesEditObjectInViewData(): void
    {
        $controller = oxNew(ShopMain::class);
        $this->setRequestParameter('oxid', $this->getConfig()->getBaseShopId());
        $controller->render();

        $viewData = $controller->getViewData();
        $this->assertSame($this->getConfig()->getBaseShopId(), $viewData['oxid'] ?? null);
        // For an existing base shop, the edit object must be loaded.
        $this->assertNotNull($viewData['edit'] ?? null);
        $this->assertArrayHasKey('IsOXDemoShop', $viewData);
    }

    public function testRenderRespectsSubjLangForViewData(): void
    {
        $controller = oxNew(ShopMain::class);
        $this->setRequestParameter('oxid', $this->getConfig()->getBaseShopId());
        $this->setRequestParameter('subjlang', 1);
        $controller->render();

        $this->assertSame(1, $controller->getViewData()['subjlang'] ?? null);
    }

    public function testSaveSuccess()
    {
        oxTestModules::addFunction('oxshop', 'save', '{ throw new Exception( "save" ); }');

        try {
            $oView = oxNew('Shop_Main');
            $oView->save();
        } catch (Exception $oExcp) {
            $this->assertEquals('save', $oExcp->getMessage(), 'error in Shop_Main::save()');
            return;
        }
        $this->fail('error in Shop_Main::save()');
    }

    public function testSaveAbortsWhenCanCreateShopReturnsFalse(): void
    {
        oxTestModules::addFunction('oxshop', 'save', '{ throw new Exception( "must NOT be called" ); }');

        $controller = $this->getMock(ShopMain::class, ['canCreateShop']);
        $controller->expects($this->once())
            ->method('canCreateShop')
            ->will($this->returnValue(false));

        $this->setRequestParameter('editval', ['oxshops__oxname' => 'whatever']);
        $controller->save(); // must not throw — save() bails before oxShop::save().

        $this->assertNull($controller->getViewData()['updatelist'] ?? null);
    }

    public function testSaveCatchesStandardExceptionAndDelegatesToCheckExceptionType(): void
    {
        oxTestModules::addFunction(
            'oxshop',
            'save',
            '{ throw new \\OxidEsales\\Eshop\\Core\\Exception\\StandardException("boom"); }'
        );

        $captured = null;
        $controller = $this->getMock(ShopMain::class, ['checkExceptionType', 'canCreateShop']);
        $controller->expects($this->any())
            ->method('canCreateShop')
            ->will($this->returnValue(true));
        $controller->expects($this->once())
            ->method('checkExceptionType')
            ->willReturnCallback(function ($exception) use (&$captured) {
                $captured = $exception;
            });

        $this->setRequestParameter('editval', ['oxshops__oxname' => 'whatever']);
        $controller->save();

        $this->assertInstanceOf(StandardException::class, $captured);
        $this->assertNull($controller->getViewData()['updatelist'] ?? null);
    }

    public function testSaveCheckboxFieldsAreNormalisedToZeroWhenAbsent(): void
    {
        $captured = null;
        $controller = $this->getMock(ShopMain::class, ['checkExceptionType', 'canCreateShop', 'updateShopInformation']);
        $controller->expects($this->any())
            ->method('canCreateShop')
            ->willReturnCallback(function ($shopId, $shop) use (&$captured) {
                // Inspect the shop after assign() ran.
                $captured = $shop;
                return false; // stop save before DB write
            });

        // Submit with both checkboxes unchecked.
        $this->setRequestParameter('editval', ['oxshops__oxname' => 'whatever']);
        $controller->save();

        $this->assertNotNull($captured);
        $this->assertSame(0, (int) $captured->oxshops__oxactive->value);
        $this->assertSame(0, (int) $captured->oxshops__oxproductive->value);
    }

    public function testSaveSmtpPasswordResetSentinelClearsValue(): void
    {
        $captured = null;
        $controller = $this->getMock(ShopMain::class, ['checkExceptionType', 'canCreateShop', 'updateShopInformation']);
        $controller->expects($this->any())
            ->method('canCreateShop')
            ->willReturnCallback(function ($shopId, $shop) use (&$captured) {
                $captured = $shop;
                return false;
            });

        $this->setRequestParameter('editval', ['oxshops__oxname' => 'whatever']);
        $this->setRequestParameter('oxsmtppwd', '-');

        $controller->save();

        $this->assertNotNull($captured);
        $this->assertSame('', (string) $captured->oxshops__oxsmtppwd->value);
    }

    public function testGetNonCopyConfigVarsIncludesSerialAndModulePathConfigs(): void
    {
        $controller = $this->getProxyClass(ShopMain::class);
        $vars = $controller->UNITgetNonCopyConfigVars();
        $this->assertContains('aSerials', $vars);
        $this->assertContains('aModulePaths', $vars);
        $this->assertContains('aDisabledModules', $vars);
    }

    public function testNewShopIdConstant(): void
    {
        $this->assertSame('-1', ShopMain::NEW_SHOP_ID);
    }
}
