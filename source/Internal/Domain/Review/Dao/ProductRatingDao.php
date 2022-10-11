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

use OxidEsales\EshopCommunity\Internal\Framework\Dao\InvalidObjectIdDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataMapper\ProductRatingDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\ProductRating;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;

class ProductRatingDao implements ProductRatingDaoInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var ProductRatingDataMapperInterface
     */
    private $productRatingMapper;

    /**
     * @param QueryBuilderFactoryInterface     $queryBuilderFactory
     * @param ProductRatingDataMapperInterface $productRatingMapper
     */
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ProductRatingDataMapperInterface $productRatingMapper
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->productRatingMapper = $productRatingMapper;
    }

    /**
     * @param ProductRating $productRating
     */
    public function update(ProductRating $productRating)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->update('oxarticles')
            ->set('OXRATING', ':OXRATING')
            ->set('OXRATINGCNT', ':OXRATINGCNT')
            ->where('OXID = :OXID')
            ->setParameters($this->productRatingMapper->getData($productRating));

        $queryBuilder->execute();
    }

    /**
     * @param string $productId
     *
     * @return ProductRating
     * @throws InvalidObjectIdDaoException
     */
    public function getProductRatingById($productId)
    {
        $this->validateProductId($productId);

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select([
                'OXID',
                'OXRATING',
                'OXRATINGCNT'
            ])
            ->from('oxarticles')
            ->where('oxid = :productId')
            ->setMaxResults(1)
            ->setParameter('productId', $productId);

        return $this->productRatingMapper->map(
            new ProductRating(),
            $queryBuilder->execute()->fetch()
        );
    }

    /**
     * @param string $productId
     *
     * @throws InvalidObjectIdDaoException
     */
    private function validateProductId($productId)
    {
        if (empty($productId) || !is_string($productId)) {
            throw new InvalidObjectIdDaoException();
        }
    }
}
