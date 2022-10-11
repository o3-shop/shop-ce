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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

class Object2CategoryTest extends \OxidTestCase
{
    /**
     * Tests setter and getter of product id
     */
    public function testSetGetProductId()
    {
        $oObject2Category = oxNew('oxObject2Category');
        $oObject2Category->setProductId('_testProduct');
        $this->assertEquals('_testProduct', $oObject2Category->getProductId());
        $this->assertEquals('_testProduct', $oObject2Category->oxobject2category__oxobjectid->value);
    }

    /**
     * Tests setter and getter of category id
     */
    public function testSetGetCategoryId()
    {
        $oObject2Category = oxNew('oxObject2Category');
        $oObject2Category->setCategoryId('_testProduct');
        $this->assertEquals('_testProduct', $oObject2Category->getCategoryId());
        $this->assertEquals('_testProduct', $oObject2Category->oxobject2category__oxcatnid->value);
    }
}
