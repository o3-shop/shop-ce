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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Component\Widget;

use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Tests for oxwCategoryTree class
 */
class CategoryTreeTest extends UnitTestCase
    {
    /**
     * Testing OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree::getDeepLevel()
     *
     * @return null
     */
    public function testGetDeepLevel()
    {
        $categoryTree = oxNew('OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree');
        $categoryTree->setViewParameters(array("deepLevel" => 2));
        $this->assertEquals(2, $categoryTree->getDeepLevel());
    }

    public function testChecksIfContentCategoryNotReturned()
    {
        $categoryTree = oxNew('OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree');

        $this->assertSame(false, $categoryTree->getContentCategory());
    }

    public function testChecksIfContentCategoryReturned()
    {
        $categoryTree = oxNew('OxidEsales\EshopCommunity\Application\Component\Widget\CategoryTree');
        $this->setRequestParameter('oxcid', 'test');

        $this->assertSame('test', $categoryTree->getContentCategory());
    }
}
