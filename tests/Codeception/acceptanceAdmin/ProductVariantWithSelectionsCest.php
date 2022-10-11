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

namespace OxidEsales\EshopCommunity\Tests\CodeceptionAdmin;

use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceAdminTester;

final class ProductVariantWithSelectionsCest
{
    /** @param AcceptanceAdminTester $I */
    public function selectionInheritanceByProductVariant(AcceptanceAdminTester $I): void
    {
        $I->wantToTest('product variant inherits selections from its parent');

        $I->retry(3, 2000);

        $admin = $I->loginAdmin();
        $products = $admin->openProducts();
        $productsMainPage = $products->switchLanguage('Deutsch');

        $parentMainPage = $productsMainPage->find($productsMainPage->searchNumberInput, '1002');
        $parentSelectionPage = $parentMainPage->openSelectionTab();
        $parentAssignSelections = $parentSelectionPage->openAssignSelectionListPopup();
        $parentAssignSelections->assignSelectionByTitle('test selection list [DE] šÄßüл');
        $I->closeTab();

        $parentVariantPage = $parentSelectionPage->openVariantsTab();
        $variantMainPage = $parentVariantPage->openEditProductVariant(1);
        $I->seeInField($variantMainPage->numberInput, '1002-1');

        $variantSelectionPage = $variantMainPage->openSelectionTab();
        $variantAssignSelections = $variantSelectionPage->openAssignSelectionListPopup();
        $I->retrySee('test selection list [DE] šÄßüл', $variantAssignSelections->assignedList);
        $I->closeTab();
    }
}
