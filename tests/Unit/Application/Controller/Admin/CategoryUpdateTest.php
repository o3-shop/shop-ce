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

use \oxTestModules;

/**
 * Tests for Category_Update class
 */
class CategoryUpdateTest extends \OxidTestCase
{

    /**
     * Category_Update::GetCatListUpdateInfo() test case
     *
     * @return null
     */
    public function testGetCatListUpdateInfo()
    {
        // testing..
        $oCategoryList = $this->getMock(\OxidEsales\Eshop\Application\Model\CategoryList::class, array("getUpdateInfo"));
        $oCategoryList->expects($this->once())->method('getUpdateInfo');

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\CategoryUpdate::class, array("_getCategoryList"));
        $oView->expects($this->once())->method('_getCategoryList')->will($this->returnValue($oCategoryList));
        $oView->getCatListUpdateInfo();
    }

    /**
     * Category_Update::_getCategoryList() test case
     *
     * @return null
     */
    public function testGetCategoryList()
    {
        oxTestModules::addFunction('oxCategoryList', 'updateCategoryTree', '{}');

        $oView = oxNew('Category_Update');
        $this->assertTrue($oView->UNITgetCategoryList() instanceof CategoryList);
    }

    /**
     * Category_Update::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Category_Update');
        $this->assertEquals('category_update.tpl', $oView->render());
    }
}
