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

namespace OxidEsales\EshopCommunity\Tests\Codeception\acceptance;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\ProductNavigation;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceTester;

final class PrivateSalesBasketCest
{
    public function testIfblBasketExcludeEnabledBlocksRootCatChange(AcceptanceTester $I): void
    {
        $I->wantToTest('Test if blBasketExcludeEnabled blocks rootCatChange and continue shopping clears basket.');

        $I->updateConfigInDatabase('blBasketExcludeEnabled', 'true', 'bool');
        $I->clearShopCache();

        $homePage = $I->openShop();

        $basketPage = $homePage->openCategoryPage('Test category 0 [EN] šÄßüл')
            ->openDetailsPage(1)
            ->addProductToBasket(1)
            ->openBasket();

        $productData = [
            'id' => '1000',
            'title' => 'Test product 0 [EN] šÄßüл',
            'amount' => 1,
            'totalPrice' => '50,00 €'
        ];

        $basketPage->seeBasketContains([$productData], '50,00 €');

        $homePage->openCategoryPage('Test category 0 [EN] šÄßüл');
        $I->dontSeeElement('#scRootCatChanged');

        $homePage->openCategoryPage('Kiteboarding');
        $I->waitForElementVisible('#scRootCatChanged', 5);

        $I->click(Translator::translate('CONTINUE_SHOPPING'));
        $I->dontSee('scRootCatChanged');
        $homePage->checkBasketEmpty();
    }

    public function checkIfblBasketExcludeEnabledAlsoClearsByEmptyBasket(AcceptanceTester $I): void
    {
        $I->wantToTest('Test if blBasketExcludeEnabled rootCatChange is no longer blocked by an empty basket.');

        $I->updateConfigInDatabase('blBasketExcludeEnabled', 'true', 'bool');
        $I->clearShopCache();

        $homePage = $I->openShop();

        $homePage->openCategoryPage('Test category 0 [EN] šÄßüл')
            ->openDetailsPage(1)
            ->addProductToBasket(1)
            ->openBasket();

        $homePage->openCategoryPage('Kiteboarding');
        $I->waitForElementVisible('#scRootCatChanged', 5);

        $basket = $homePage->openBasket();
        $basket->updateProductAmount(0);

        $homePage->openCategoryPage('Kiteboarding');
        $I->dontSeeElement('#scRootCatChanged');
    }

    /** @group private_shopping_basket_expiration */
    public function testPrivateShoppingBasketExpiration(AcceptanceTester $I): void
    {
        $I->wantToTest('Test private basket reservation expiration');

        $I->updateInDatabase('oxarticles', ['oxstock' => '2', 'oxstockflag' => '2'], ['oxid' => '1000']);
        $I->updateConfigInDatabase('blPsBasketReservationEnabled', 'true', 'bool');
        $I->updateConfigInDatabase('iPsBasketReservationTimeout', '10', 'str');

        $I->clearShopCache();

        $productNavigation = new ProductNavigation($I);

        $productData = [
            'id' => '1000',
            'title' => 'Test product 0 [EN] šÄßüл',
            'description' => 'Test product 0 short desc [EN] šÄßüл',
            'price' => '50,00 € *'
        ];

        $detailsPage = $productNavigation->openProductDetailsPage($productData['id']);

        $I->see($productData['title']);

        $detailsPage->addProductToBasket(2)->seeCountdownWithinBasket();

        $I->openShop()->searchFor('1000');
        $I->see(Translator::translate('NO_ITEMS_FOUND'));
        //we need to wait for the timeout
        $I->wait(12);

        $homePage = $I->openShop();
        $homePage->checkBasketEmpty();
        $homePage->searchFor('1000');
        $I->dontSee(Translator::translate('NO_ITEMS_FOUND'));
    }
}
