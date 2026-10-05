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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\GenericImport\ImportObject;

use OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Category;

class CategoryTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Category::preAssignObject
     */
    public function testPreAssignObjectFillsMissingParentIdWithRoot(): void
    {
        $category = new Category();
        $shopObject = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);

        $method = new \ReflectionMethod($category, 'preAssignObject');
        $method->setAccessible(true);

        $result = $method->invoke($category, $shopObject, ['OXID' => 'cat-1', 'OXPARENTID' => ''], false);

        $this->assertSame('oxrootid', $result['OXPARENTID']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Category::preAssignObject
     */
    public function testPreAssignObjectKeepsExplicitParentId(): void
    {
        $category = new Category();
        $shopObject = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);

        $method = new \ReflectionMethod($category, 'preAssignObject');
        $method->setAccessible(true);

        $result = $method->invoke($category, $shopObject, ['OXID' => 'cat-2', 'OXPARENTID' => 'parent-id-7'], false);

        $this->assertSame('parent-id-7', $result['OXPARENTID']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Category::preAssignObject
     */
    public function testPreAssignObjectRewritesShopIdToCurrent(): void
    {
        $category = new Category();
        $shopObject = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);

        $method = new \ReflectionMethod($category, 'preAssignObject');
        $method->setAccessible(true);

        $result = $method->invoke(
            $category,
            $shopObject,
            ['OXID' => 'cat-3', 'OXSHOPID' => '999', 'OXPARENTID' => 'p'],
            false
        );

        $expected = (string) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
        $this->assertSame($expected, (string) $result['OXSHOPID']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Category::preAssignObject
     */
    public function testGetBaseTableName(): void
    {
        $category = new Category();
        $this->assertSame('oxcategories', $category->getBaseTableName());
    }
}
