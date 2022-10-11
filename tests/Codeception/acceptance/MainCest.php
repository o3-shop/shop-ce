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

namespace OxidEsales\EshopCommunity\Tests\Codeception;

use OxidEsales\Codeception\Page\Home;
use OxidEsales\Codeception\Page\Account\UserOrderHistory;
use OxidEsales\Codeception\Module\Translation\Translator;

class MainCest
{
    public function frontPageWorks(AcceptanceTester $I)
    {
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);
        $I->see(Translator::translate("HOME"));
    }

    /**
     * @param AcceptanceTester $I
     */
    public function shopBrowsing(AcceptanceTester $I)
    {
        // open start page
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);

        $I->see(Translator::translate("HOME"));
        $I->see(Translator::translate('START_BARGAIN_HEADER'));

        // open category
        $I->click('Test category 0 [EN] šÄßüл', '#navigation');
        $I->waitForElement('h1', 10);
        $I->see('Test category 0 [EN] šÄßüл', 'h1');

        // check if subcategory exists
        $I->see('Test category 1 [EN] šÄßüл', '#moreSubCat_1');

        //open Details page
        $I->click('#productList_1');

        // login to shop
        $orderHistoryPage = new UserOrderHistory($I);
        $I->amOnPage($orderHistoryPage->URL);
        $I->waitForElement('h1', 10);
        $I->see(Translator::translate('LOGIN'), 'h1');

        $I->fillField($orderHistoryPage->loginUserNameField,'example_test@oxid-esales.dev');
        $I->fillField($orderHistoryPage->loginUserPasswordField,'useruser');
        $I->click($orderHistoryPage->loginButton);

        $I->see(Translator::translate('ORDER_HISTORY'), 'h1');
    }
}
