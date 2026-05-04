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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ShopDefaultCategoryAjax;

/**
 * Stub Shop captures load/save and exposes the field value the controller
 * wrote, so we can verify (un)assignCat without round-tripping through
 * the oxshops table.
 */
class ShopDefaultCategoryAjaxTest_StubShop
{
    /** @var bool steered per-test */
    public static bool $loadReturns = true;

    /** @var string|null the oxid that was passed into load() */
    public ?string $loadedWith = null;

    public bool $saved = false;

    public ?Field $oxshops__oxdefcat = null;

    public function load($oxId)
    {
        $this->loadedWith = (string) $oxId;
        return self::$loadReturns;
    }

    public function save()
    {
        $this->saved = true;
        return true;
    }
}

class ShopDefaultCategoryAjaxTest extends \OxidTestCase
{
    public function testGetQueryBuildsActiveCategoryClause(): void
    {
        $this->setRequestParameter('editlanguage', 0);

        $controller = oxNew(ShopDefaultCategoryAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('from ', $sql);
        $this->assertStringContainsString('oxcategories', $sql);
        // Active snippet always references oxactive in some form.
        $this->assertMatchesRegularExpression('/oxactive\s*=/', $sql);
    }

    public function testColumnsConfigExposesCategoryFields(): void
    {
        $controller = $this->getProxyClass(ShopDefaultCategoryAjax::class);
        $columns = $controller->getNonPublicVar('_aColumns');
        $this->assertArrayHasKey('container1', $columns);
        $idents = array_column($columns['container1'], 0);
        $this->assertContains('oxtitle', $idents);
        $this->assertContains('oxdesc', $idents);
        $this->assertContains('oxid', $idents);
    }

    public function testUnassignCatClearsDefaultCategoryWhenShopExists(): void
    {
        $stubShop = new ShopDefaultCategoryAjaxTest_StubShop();
        ShopDefaultCategoryAjaxTest_StubShop::$loadReturns = true;
        \oxTestModules::addModuleObject(Shop::class, $stubShop);

        $this->setRequestParameter('oxid', 'shop-7');

        $controller = oxNew(ShopDefaultCategoryAjax::class);
        $controller->unassignCat();

        $this->assertSame('shop-7', $stubShop->loadedWith);
        $this->assertTrue($stubShop->saved);
        $this->assertInstanceOf(Field::class, $stubShop->oxshops__oxdefcat);
        $this->assertSame('', (string) $stubShop->oxshops__oxdefcat->value);
    }

    public function testUnassignCatDoesNothingWhenShopDoesNotLoad(): void
    {
        $stubShop = new ShopDefaultCategoryAjaxTest_StubShop();
        ShopDefaultCategoryAjaxTest_StubShop::$loadReturns = false;
        \oxTestModules::addModuleObject(Shop::class, $stubShop);

        $this->setRequestParameter('oxid', 'unknown-shop');

        $controller = oxNew(ShopDefaultCategoryAjax::class);
        $controller->unassignCat();

        $this->assertSame('unknown-shop', $stubShop->loadedWith);
        $this->assertFalse($stubShop->saved);
        $this->assertNull($stubShop->oxshops__oxdefcat);
    }

    public function testAssignCatSetsChosenCategoryWhenShopExists(): void
    {
        $stubShop = new ShopDefaultCategoryAjaxTest_StubShop();
        ShopDefaultCategoryAjaxTest_StubShop::$loadReturns = true;
        \oxTestModules::addModuleObject(Shop::class, $stubShop);

        $this->setRequestParameter('oxid', 'shop-7');
        $this->setRequestParameter('oxcatid', 'cat-electronics');

        $controller = oxNew(ShopDefaultCategoryAjax::class);
        $controller->assignCat();

        $this->assertSame('shop-7', $stubShop->loadedWith);
        $this->assertTrue($stubShop->saved);
        $this->assertSame('cat-electronics', (string) $stubShop->oxshops__oxdefcat->value);
    }

    public function testAssignCatDoesNothingWhenShopDoesNotLoad(): void
    {
        $stubShop = new ShopDefaultCategoryAjaxTest_StubShop();
        ShopDefaultCategoryAjaxTest_StubShop::$loadReturns = false;
        \oxTestModules::addModuleObject(Shop::class, $stubShop);

        $this->setRequestParameter('oxid', 'unknown-shop');
        $this->setRequestParameter('oxcatid', 'cat-electronics');

        $controller = oxNew(ShopDefaultCategoryAjax::class);
        $controller->assignCat();

        $this->assertFalse($stubShop->saved);
        $this->assertNull($stubShop->oxshops__oxdefcat);
    }
}
