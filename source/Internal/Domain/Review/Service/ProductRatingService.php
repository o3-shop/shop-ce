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

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Review\Dao\RatingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Dao\ProductRatingDaoInterface;

class ProductRatingService implements ProductRatingServiceInterface
{
    /**
     * @var RatingDaoInterface
     */
    private $ratingDao;

    /**
     * @var ProductRatingDaoInterface
     */
    private $productRatingDao;

    /**
     * @var RatingCalculatorServiceInterface
     */
    private $ratingCalculator;

    /**
     * ProductRatingService constructor.
     *
     * @param RatingDaoInterface               $ratingDao
     * @param ProductRatingDaoInterface        $productRatingDao
     * @param RatingCalculatorServiceInterface $ratingCalculator
     */
    public function __construct(
        RatingDaoInterface $ratingDao,
        ProductRatingDaoInterface $productRatingDao,
        RatingCalculatorServiceInterface $ratingCalculator
    ) {
        $this->ratingDao = $ratingDao;
        $this->productRatingDao = $productRatingDao;
        $this->ratingCalculator = $ratingCalculator;
    }

    /**
     * @param string $productId
     */
    public function updateProductRating($productId)
    {
        $ratings = $this
            ->ratingDao
            ->getRatingsByProductId($productId);

        $ratingAverage = $this
            ->ratingCalculator
            ->getAverage($ratings);

        $ratingCount = $ratings->count();

        $productRating = $this->productRatingDao->getProductRatingById($productId);
        $productRating
            ->setRatingAverage($ratingAverage)
            ->setRatingCount($ratingCount);

        $this->productRatingDao->update($productRating);
    }
}
