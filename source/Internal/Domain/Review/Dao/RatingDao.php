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

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Dao;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataMapper\RatingDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;

class RatingDao implements RatingDaoInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var RatingDataMapperInterface
     */
    private $ratingDataMapper;

    /**
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param RatingDataMapperInterface    $ratingDataMapper
     */
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        RatingDataMapperInterface $ratingDataMapper
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->ratingDataMapper = $ratingDataMapper;
    }

    /**
     * Returns User Ratings.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function getRatingsByUserId($userId)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('r.*')
            ->from('oxratings', 'r')
            ->where('r.oxuserid = :userId')
            ->orderBy('r.oxtimestamp', 'DESC')
            ->setParameter('userId', $userId);

        return $this->mapRatings($queryBuilder->execute()->fetchAll());
    }

    /**
     * @param Rating $rating
     */
    public function delete(Rating $rating)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxratings')
            ->where('oxid = :id')
            ->setParameter('id', $rating->getId())
            ->execute();
    }

    /**
     * Returns Ratings for a product.
     *
     * @param string $productId
     *
     * @return ArrayCollection
     */
    public function getRatingsByProductId($productId)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('r.*')
            ->from('oxratings', 'r')
            ->where('r.oxobjectid = :productId')
            ->andWhere('r.oxtype = :productType')
            ->orderBy('r.oxtimestamp', 'DESC')
            ->setParameters(
                [
                    'productId'     => $productId,
                    'productType'   => 'oxarticle',
                ]
            );

        return $this->mapRatings($queryBuilder->execute()->fetchAll());
    }

    /**
     * Maps rating data from database to Ratings Collection.
     *
     * @param array $ratingsData
     *
     * @return ArrayCollection
     */
    private function mapRatings($ratingsData)
    {
        $ratings = new ArrayCollection();

        foreach ($ratingsData as $ratingData) {
            $rating = new Rating();
            $ratings->add($this->ratingDataMapper->map($rating, $ratingData));
        }

        return $ratings;
    }
}
