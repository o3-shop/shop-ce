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

namespace OxidEsales\EshopCommunity\Tests\Acceptance\Frontend;

use OxidEsales\EshopCommunity\Tests\Acceptance\FrontendTestCase;

/** Frontend: product information/ details related tests */
class ProductInfoFrontendTest extends FrontendTestCase
{
    /**
     * Check is Compare options works corectly
     * TODO: bug in flow theme: #6979
     *
     * @group product
     * @group product_list
     */
    public function testCompareInFrontend()
    {
        $this->openShop();
        $this->clickAndWait('toCmp_newItems_1');
        $this->searchFor("1");
        $this->clickAndWait('toCmp_searchList_1');
        $this->clickAndWait("link=Kiteboarding");
        $this->clickAndWait("link=Kites");
        $this->clickAndWait('toCmp_productList_1');
        $this->click("servicesTrigger");
        $this->waitForItemAppear("services");
        $this->clickAndWait("//ul[@id='services']/li[4]/a");
        $this->assertElementPresent('productPrice_1');
        $this->assertElementPresent("//a[text()='Test product 0 [EN] šÄßüл ']");
        $this->assertElementPresent("productPrice_2");
        $this->assertElementPresent("//a[text()='Kite CORE GTS ']");
        $this->assertElementPresent("productPrice_3");
        $this->assertElementPresent("//a[text()='Harness MADTRIXX ']");

        $this->clickAndWait("link=%HOME%");
        $this->clickAndWait('removeCmp_newItems_1');
        $this->searchFor("1");
        $this->clickAndWait('removeCmp_searchList_1');
        $this->clickAndWait("link=Kiteboarding");
        $this->clickAndWait("link=Kites");
        $this->clickAndWait('removeCmp_productList_1');
        $this->click("servicesTrigger");
        $this->waitForItemAppear("services");
        $this->clickAndWAit("//ul[@id='services']/li[4]/a");
        $this->assertElementNotPresent('productPrice_1');
        $this->assertElementNotPresent('productPrice_2');
        $this->assertElementNotPresent('productPrice_3');

        $this->assertTextPresent("%MESSAGE_SELECT_AT_LEAST_ONE_PRODUCT%");
    }
}
