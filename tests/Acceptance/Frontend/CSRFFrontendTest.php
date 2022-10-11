<?php declare(strict_types=1);
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

/**
 * Test csrf token matching.
 */
class CSRFFrontendTest extends FrontendTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearCookies();
    }

    public function testAddToBasketWithoutCSRFToken(): void
    {
        $this->openShop();
        $this->loginInFrontend('example_test@oxid-esales.dev', 'useruser');

        $this->assertBasketIsEmpty();
        $this->addToBasketWithoutCSRFToken();

        $this->assertTextPresent('%ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN%');
    }

    public function testAddToBasketWithCSRFToken(): void
    {
        $this->openShop();
        $this->loginInFrontend('example_test@oxid-esales.dev', 'useruser');

        $this->assertBasketIsEmpty();
        $this->openArticle(1000);
        $this->clickAndWait('toBasket');
        $this->assertBasketIsNotEmpty();
    }

    public function testGuestAddToBasket(): void
    {
        $this->assertBasketIsEmpty();
        $this->addToBasket(1000);
        $this->assertBasketIsNotEmpty();
    }

    private function assertBasketIsEmpty(): void
    {
        $this->open($this->_getShopUrl(['cl' => 'basket']));
        $this->assertEquals('%YOU_ARE_HERE%: / %PAGE_TITLE_BASKET%', $this->getText('breadCrumb'));
        $this->assertTextPresent('%BASKET_EMPTY%');
    }

    private function assertBasketIsNotEmpty(): void
    {
        $this->open($this->_getShopUrl(['cl' => 'basket']));
        $this->assertEquals('%YOU_ARE_HERE%: / %PAGE_TITLE_BASKET%', $this->getText('breadCrumb'));
        $this->assertTextNotPresent('%BASKET_EMPTY%');
    }

    private function addToBasketWithoutCSRFToken(): void
    {
        $data = [
            'actcontrol' => 'start',
            'lang'       => '1',
            'cl'         => 'start',
            'fnc'        => 'tobasket',
            'aid'        => 'dc5ffdf380e15674b56dd562a7cb6aec',
            'anid'       => 'dc5ffdf380e15674b56dd562a7cb6aec',
            'am'         => 1
        ];
        $url = $this->_getShopUrl($data);

        $this->open($url);
    }
}
