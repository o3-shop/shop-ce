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

final class CMSPageChangeIdentCest
{
    /**
     * @var string
     */
    private $testCmsPageIdent = '_test_oxstdfooter';

    /**
     * @var string
     */
    private $cmsPageDemoIdent = 'oxstdfooter';

    /** @param AcceptanceTester $I */
    public function _after(AcceptanceTester $I)
    {
        $I->updateInDatabase(
            'oxcontents',
            ['OXLOADID' => $this->cmsPageDemoIdent],
            ['OXLOADID' => $this->testCmsPageIdent]
        );
    }

    /**
     * @group todo_add_clean_cache_after_database_update
     * @param AcceptanceTester $I
     */
    public function CMSPageChangeIdent(AcceptanceTester $I): void
    {
        $I->clearShopCache();
        $I->openShop();

        $cmsPageContent = $I->grabFromDatabase(
            'oxcontents',
            'OXCONTENT_1',
            ['OXLOADID' => $this->cmsPageDemoIdent]
        );

        $I->see(strip_tags($cmsPageContent));

        $I->updateInDatabase(
            'oxcontents',
            ['OXLOADID' => $this->testCmsPageIdent],
            ['OXLOADID' => $this->cmsPageDemoIdent]
        );

        $I->clearShopCache();
        $I->openShop();

        $I->dontSee(strip_tags($cmsPageContent));
    }
}
