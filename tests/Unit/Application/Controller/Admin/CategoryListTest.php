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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\EshopCommunity\Application\Model\CategoryList;

/**
 * Tests for Category_List class
 */
class CategoryListTest extends \OxidTestCase
{
    /**
     * Category_List::Init() test case
     *
     * @return null
     */
    public function testInit()
    {
        $oView = $this->getMock($this->getProxyClassName('Category_List'), ['authorize']);
        $oView->expects($this->any())->method('authorize')->will($this->returnValue(true));

        $oView->init();
        $this->assertEquals(['oxcategories' => ['oxrootid' => 'desc', 'oxleft' => 'asc']], $oView->getListSorting());
    }

    /**
     * Category_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Category_List');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertTrue($aViewData['cattree'] instanceof CategoryList);

        $this->assertEquals('category_list.tpl', $sTplName);
    }
}
