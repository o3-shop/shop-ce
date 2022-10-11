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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller\Admin;

use oxDb;
use OxidEsales\Eshop\Application\Controller\Admin\DiscountItemAjax;
use OxidEsales\TestingLibrary\UnitTestCase;

final class DiscountItemAjaxTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        oxDb::getDb()->execute("insert into oxdiscount set oxid='_testO2DRemove1', oxitmartid = '_testObjectRemove1', oxsort = '1900'");
        oxDb::getDb()->execute("insert into oxdiscount set oxid='_testO2DRemove2', oxitmartid = '_testObjectRemove2', oxsort = '1910'");
        oxDb::getDb()->execute("insert into oxdiscount set oxid='_testO2DRemove3', oxitmartid = '_testObjectRemove3', oxsort = '1920'");
        oxDb::getDb()->execute("insert into oxdiscount set oxid='_testO2DRemove4', oxitmartid = ''");
    }

    protected function tearDown(): void
    {
        parent::cleanUpTable('oxdiscount');

        parent::tearDown();
    }

    public function testRemoveDiscArt(): void
    {
        /** @see DiscountItemAjax::$_aColumns */
        $productIdColumnIndex = 6;
        $_POST['cmpid'] = $this->getContainerIdForAssignedItemsList();
        $_POST["_$productIdColumnIndex"] = ['_testObjectRemove1', '_testObjectRemove2'];
        $_POST['oxid'] = '_testO2DRemove1';
        $this->assertEquals(
            3,
            oxDb::getDb()->getOne("select count(oxid) from oxdiscount where oxid like '_test%' and oxitmartid != ''")
        );

        oxNew(DiscountItemAjax::class)->removeDiscArt();

        $this->assertEquals(
            2,
            oxDb::getDb()->getOne("select count(oxid) from oxdiscount where oxid like '_test%' and oxitmartid != ''")
        );
    }

    public function testAddDiscArt(): void
    {
        /** @see DiscountItemAjax::$_aColumns */
        $productIdColumnIndex = 6;
        $_POST['cmpid'] = $this->getContainerIdForUnassignedItemsList();
        $_POST["_$productIdColumnIndex"] = ['_testArticleAdd1', '_testArticleAdd2'];
        $_POST['synchoxid'] = '_testO2DRemove4';
        $this->assertEquals(
            3,
            oxDb::getDb()->getOne("select count(oxid) from oxdiscount where oxid like '_test%' and oxitmartid != ''")
        );

        oxNew(DiscountItemAjax::class)->addDiscArt();

        $this->assertEquals(
            4,
            oxDb::getDb()->getOne("select count(oxid) from oxdiscount where oxid like '_test%' and oxitmartid != ''")
        );
    }

    private function getContainerIdForUnassignedItemsList(): string
    {
        /** @see DiscountItemAjax::$_aColumns */
        return 'container1';
    }

    private function getContainerIdForAssignedItemsList(): string
    {
        /** @see DiscountItemAjax::$_aColumns */
        return 'container2';
    }
}
