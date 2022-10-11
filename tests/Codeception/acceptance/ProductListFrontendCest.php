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

namespace OxidEsales\EshopCommunity\Tests\Codeception;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Step\ProductNavigation;
use OxidEsales\Codeception\Module\Translation\Translator;

final class ProductListFrontendCest
{
    /**
     * Product list. check category filter reset button functionality
     * @group product_list
     * @group frontend
     */
    public function testCategoryFilterReset(AcceptanceTester $I)
    {
        $I->wantToTest('category filter reset button functionality');

        $homePage = $I->openShop();
        $I->waitForPageLoad();
        $homePage->openCategoryPage('Kiteboarding');
        $I->waitForPageLoad();
        $homePage->openCategoryPage('Kites');
        $I->waitForPageLoad();
        $I->seeElement("//form[@id='filterList']");

        $I->click("//*[@id='filterList']/div[@class='btn-group'][1]/button");
        $I->waitForText("Freeride");
        $I->click("Freeride");
        $I->waitForPageLoad();
        $I->seeElement("//*[@id='resetFilter']/button");
        $I->click("//*[@id='resetFilter']/button");
        $I->waitForPageLoad();

        $I->click("//*[@id='filterList']/div[@class='btn-group'][2]/button");
        $I->waitForText("kite");
        $I->click("kite");
        $I->waitForPageLoad();
        $I->seeElement("//*[@id='resetFilter']/button");

        $I->click("//*[@id='resetFilter']/button");
        $I->waitForPageLoad();
        $I->dontSeeElement("//*[@id='resetFilter']/button");
    }
}
