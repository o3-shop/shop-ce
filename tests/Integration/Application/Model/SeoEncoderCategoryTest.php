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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model;

use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\SeoEncoderCategory;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\EshopCommunity\Core\UtilsObject;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\TestingLibrary\UnitTestCase;

final class SeoEncoderCategoryTest extends UnitTestCase
{
    use ContainerTrait;

    public function testOnDeleteCategoryWillSetDependantRecordsToExpired(): void
    {
        $seoEncoderCategory = oxNew(SeoEncoderCategory::class);
        $category = $this->createTestCategoryWithSeoLinks();

        $seoEncoderCategory->onDeleteCategory($category);

        $this->assertSeoUrlsAreExpired();
    }

    private function assertSeoUrlsAreExpired(): void
    {
        $connection = $this->get(QueryBuilderFactoryInterface::class)->create()->getConnection();
        $expiredRowsCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM `oxseo` WHERE `OXSEOURL` like "www-some-shop/category-%" AND `OXEXPIRED` = "1";');

        $this->assertEquals($this->getCountOfProductsPerCategory(), $expiredRowsCount);
    }

    private function createTestCategoryWithSeoLinks(): Category
    {
        $baseUrl = uniqid('www.some-shop/', true);
        $seoEncoder = oxNew(SeoEncoder::class);
        $utils = new UtilsObject();

        $category = oxNew(Category::class);
        $category->setId($utils->generateUId());
        $category->save();

        $categoryUrl = uniqid('www.some-shop/category-', true);
        $seoEncoder->addSeoEntry(
            $category->getId(),
            1,
            0,
            $baseUrl,
            $categoryUrl,
            'oxcategory'
        );

        $productCount = $this->getCountOfProductsPerCategory();
        while ($productCount > 0) {
            $seoEncoder->addSeoEntry(
                $utils->generateUId(),
                1,
                0,
                $categoryUrl,
                $categoryUrl . uniqid('/product-', true),
                'oxarticle'
            );
            $productCount--;
        }

        return $category;
    }

    private function getCountOfProductsPerCategory(): int
    {
        return 2;
    }
}
