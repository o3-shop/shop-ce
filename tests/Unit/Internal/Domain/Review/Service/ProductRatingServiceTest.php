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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Dao\ProductRatingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Dao\RatingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\ProductRating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ProductRatingService;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\RatingCalculatorServiceInterface;
use PHPUnit\Framework\TestCase;

class ProductRatingServiceTest extends TestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ProductRatingService::__construct
     * @covers \OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ProductRatingService::updateProductRating
     */
    public function testUpdateProductRatingWritesAverageAndCountFromRatingsCollection(): void
    {
        $ratings = new ArrayCollection(['r1', 'r2', 'r3']);

        $ratingDao = $this->createMock(RatingDaoInterface::class);
        $ratingDao->expects($this->once())
            ->method('getRatingsByProductId')
            ->with('product-1')
            ->willReturn($ratings);

        $calculator = $this->createMock(RatingCalculatorServiceInterface::class);
        $calculator->expects($this->once())
            ->method('getAverage')
            ->with($ratings)
            ->willReturn(4.5);

        $productRating = new ProductRating();

        $productRatingDao = $this->createMock(ProductRatingDaoInterface::class);
        $productRatingDao->expects($this->once())
            ->method('getProductRatingById')
            ->with('product-1')
            ->willReturn($productRating);
        $productRatingDao->expects($this->once())
            ->method('update')
            ->with($this->callback(function (ProductRating $stored) {
                $this->assertSame(4.5, $stored->getRatingAverage());
                $this->assertSame(3, $stored->getRatingCount());
                return true;
            }));

        $service = new ProductRatingService($ratingDao, $productRatingDao, $calculator);
        $service->updateProductRating('product-1');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ProductRatingService::updateProductRating
     */
    public function testUpdateProductRatingWritesZeroCountForEmptyRatingCollection(): void
    {
        $ratings = new ArrayCollection([]);

        $ratingDao = $this->createMock(RatingDaoInterface::class);
        $ratingDao->method('getRatingsByProductId')->willReturn($ratings);

        $calculator = $this->createMock(RatingCalculatorServiceInterface::class);
        $calculator->method('getAverage')->with($ratings)->willReturn(0.0);

        $productRating = new ProductRating();
        $productRatingDao = $this->createMock(ProductRatingDaoInterface::class);
        $productRatingDao->method('getProductRatingById')->willReturn($productRating);
        $productRatingDao->expects($this->once())
            ->method('update')
            ->with($this->callback(function (ProductRating $stored) {
                $this->assertSame(0.0, $stored->getRatingAverage());
                $this->assertSame(0, $stored->getRatingCount());
                return true;
            }));

        $service = new ProductRatingService($ratingDao, $productRatingDao, $calculator);
        $service->updateProductRating('no-ratings-product');
    }
}
