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

use OxidEsales\Codeception\Step\ProductNavigation;
use OxidEsales\Codeception\Step\Start;
use OxidEsales\Codeception\Module\Translation\Translator;

class WishListCest
{
    /**
     * @group myAccount
     * @group wishList
     *
     * @param AcceptanceTester $I
     */
    public function addProductToUserWishList(AcceptanceTester $I)
    {
        $productNavigation = new ProductNavigation($I);
        $I->wantToTest('if product compare functionality is enabled');

        $productData = [
            'id' => '1000',
            'title' => 'Test product 0 [EN] šÄßüл',
            'description' => 'Test product 0 short desc [EN] šÄßüл',
            'price' => '50,00 € *'
        ];

        $userData = $this->getExistingUserData();

        $I->openShop()->loginUser($userData['userLoginName'], $userData['userPassword']);

        //open details page
        $detailsPage = $productNavigation->openProductDetailsPage($productData['id']);
        $I->see($productData['title']);

        $detailsPage->openAccountMenu()
            ->checkWishListItemCount(0)
            ->closeAccountMenu()
            ->addToWishList()
            ->openAccountMenu()
            ->checkWishListItemCount(1)
            ->closeAccountMenu();

        $userAccountPage = $detailsPage->openAccountPage();
        $I->see(Translator::translate('MY_WISH_LIST'));
        $I->see(Translator::translate('PRODUCT').' 1');

        $userAccountPage->logoutUserInAccountPage()->login($userData['userLoginName'], $userData['userPassword']);
        $I->see(Translator::translate('MY_WISH_LIST'));
        $I->see(Translator::translate('PRODUCT').' 1');

        $userAccountPage->openWishListPage()
            ->seeProductData($productData)
            ->openProductDetailsPage(1);
        $I->see($productData['title'], $detailsPage->productTitle);

        $wishListPage = $detailsPage->openUserWishListPage()
            ->addProductToBasket(1, 2);
        $I->see(2, $wishListPage->miniBasketMenuElement);
        $wishListPage = $wishListPage->removeProductFromList(1);

        $I->see(Translator::translate('PAGE_TITLE_ACCOUNT_NOTICELIST'), $wishListPage->headerTitle);
        $I->see(Translator::translate('WISH_LIST_EMPTY'));

        $wishListPage->openAccountMenu()
            ->checkWishListItemCount(0)
            ->closeAccountMenu();
    }

    /**
     * @group myAccount
     * @group wishList
     *
     * @param AcceptanceTester $I
     */
    public function addVariantToUserWishList(AcceptanceTester $I)
    {
        $productNavigation = new ProductNavigation($I);
        $start = new Start($I);
        $I->wantToTest('user wish list functionality, if a variant of product was added');

        $I->updateConfigInDatabase('blUseMultidimensionVariants', true, 'bool');

        $productData = [
            'id' => '10014',
            'title' => '14 EN product šÄßüл',
            'description' => '13 EN description šÄßüл',
            'price' => 'from 15,00 €'
        ];

        $userData = $this->getExistingUserData();

        try {
            $start->loginOnStartPage($userData['userLoginName'], $userData['userPassword']);

            //open details page
            $detailsPage = $productNavigation->openProductDetailsPage($productData['id']);
            $I->see('14 EN product šÄßüл');
            //add parent to wish list
            $wishListPage = $detailsPage->addToWishList()
                ->selectVariant(1, 'S')
                ->selectVariant(2, 'black')
                ->selectVariant(3, 'lether')
                ->addToWishList()
                ->openAccountMenu()
                ->checkWishListItemCount(2)
                ->closeAccountMenu()
                ->openUserWishListPage()
                ->seeProductData($productData);
    
            //assert variant
            $productData = [
                'id' => '10014-1-1',
                'title' => '14 EN product šÄßüл S | black | lether',
                'description' => '',
                'price' => '25,00 €'
            ];
            $wishListPage->seeProductData($productData, 2);
    
            $wishListPage->removeProductFromList(2)
                ->removeProductFromList(1);
    
            $I->see(Translator::translate('PAGE_TITLE_ACCOUNT_NOTICELIST'), $wishListPage->headerTitle);
            $I->see(Translator::translate('WISH_LIST_EMPTY'));
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            $I->updateConfigInDatabase('blUseMultidimensionVariants', false, 'bool');
        }
    }

    private function getExistingUserData()
    {
        return \Codeception\Util\Fixtures::get('existingUser');
    }

}
