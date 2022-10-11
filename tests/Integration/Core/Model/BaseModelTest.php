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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Model;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Application\Model\Article;

/**
 * Class OxidEsales\EshopCommunity\Core\Model\BaseModelTest
 */
class BaseModelTest extends UnitTestCase
{
    public function testFunctionIsPropertyLoadedReturnsFalseWhenPropertyIsNotLoadedAndIsField()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $this->assertFalse($model->isPropertyLoaded($fieldName));
    }

    public function testFunctionIsPropertyLoadedReturnsTrueWhenPropertyIsLoadedAndIsField()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $model->$fieldName;

        $this->assertTrue($model->isPropertyLoaded($fieldName));
    }

    public function testLazyLoadingMagicIssetReturnsTrueWhenPropertyIsNotLoadedAndIsField()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $this->assertTrue(isset($model->$fieldName));
    }

    public function testLazyLoadingMagicIssetLoadsPropertyWhenPropertyIsNotLoadedAndIsField()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $this->assertTrue(isset($model->$fieldName));
    }

    public function testLazyLoadingMagicIssetReturnsTrueWhenPropertyIsLoadedAndIsField()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $model->$fieldName;

        $this->assertTrue(isset($model->$fieldName));
    }

    public function testLazyLoadingMagicIssetOnValueOfFieldReturnsTrueWhenFieldIsNotLoaded()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $this->assertTrue(isset($model->$fieldName->value));
    }

    public function testLazyLoadingMagicIssetOnValueOfFieldReturnsTrueWhenFieldIsLoaded()
    {
        $model      = $this->getModelWithLazyLoading();
        $fieldName  = $this->getTestFieldNameOfModelWithLazyLoading();

        $model->$fieldName;

        $this->assertTrue(isset($model->$fieldName->value));
    }

    public function testLazyLoadingMagicIssetOnValueOfPropertyReturnsFalseWhenPropertyIsNotFieldAndNotLoaded()
    {
        $model = $this->getModelWithLazyLoading();

        $this->assertFalse(isset($model->someProperty->value));
    }

    private function getModelWithLazyLoading()
    {
        $model = oxNew(Article::class);
        $model->init("oxarticles");
        $model->load(2000);

        return $model;
    }

    private function getTestFieldNameOfModelWithLazyLoading()
    {
        return 'oxarticles__oxartnum';
    }
}
