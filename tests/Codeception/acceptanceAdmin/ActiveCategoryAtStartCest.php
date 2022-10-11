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

use Codeception\Util\Locator;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceAdminTester;

final class ActiveCategoryAtStartCest
{
    /** @param AcceptanceAdminTester $I */
    public function setActiveCategoryAtStart(AcceptanceAdminTester $I): void
    {
        $I->wantToTest('Activate and deactivate category at start');

        $I->clearShopCache();
        $adminPanel = $I->loginAdmin();
        $coreSettings = $adminPanel->openCoreSettings();
        $settingsTab = $coreSettings->openSettingsTab();

        $settingsTab =  $settingsTab->openShopFrontendDropdown();
       
        $I->seeElement("//input[@value='---']");
        $categoryPopup = $settingsTab->openStartCategoryPopup();

        $category = 'Test category 1 [DE] šÄßüл';
        $categoryPopup = $categoryPopup->selectCategory($category);
        $categoryPopup = $categoryPopup->unsetCategory();
        $categoryPopup->selectCategory($category);

        $I->closeTab();
        $I->switchToPreviousTab();
        
        $I->clearShopCache();
        $adminPanel = $I->loginAdmin();
        
        $coreSettings = $adminPanel->openCoreSettings();
        $settingsTab = $coreSettings->openSettingsTab();

        $settingsTab->openShopFrontendDropdown();
        $I->seeElement(Locator::find('input', ['value' => $category]));
    }
}
