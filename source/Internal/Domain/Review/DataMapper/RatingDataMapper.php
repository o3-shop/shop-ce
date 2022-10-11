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

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\DataMapper;

use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;

class RatingDataMapper implements RatingDataMapperInterface
{
    /**
     * @param Rating $rating
     * @param array  $data
     *
     * @return Rating
     */
    public function map(Rating $rating, array $data): Rating
    {
        $rating
            ->setId($data['OXID'])
            ->setRating($data['OXRATING'])
            ->setObjectId($data['OXOBJECTID'])
            ->setUserId($data['OXUSERID'])
            ->setType($data['OXTYPE'])
            ->setCreatedAt($data['OXTIMESTAMP']);

        return $rating;
    }

    /**
     * @param Rating $rating
     *
     * @return array
     */
    public function getData(Rating $rating): array
    {
        return [
            'OXID'        => $rating->getId(),
            'OXRATING'    => $rating->getRating(),
            'OXOBJECTID'  => $rating->getObjectId(),
            'OXUSERID'    => $rating->getUserId(),
            'OXTYPE'      => $rating->getType(),
            'OXTIMESTAMP' => $rating->getCreatedAt(),
        ];
    }

    /**
     * @param Rating $object
     *
     * @return array
     */
    public function getPrimaryKey(Rating $object): array
    {
        return [
            'OXID' => $object->getId(),
        ];
    }
}
