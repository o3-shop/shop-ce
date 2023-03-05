<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Review\DataMapper;

use OxidEsales\EshopCommunity\Internal\Domain\Review\DataMapper\RatingDataMapper;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;

class RatingDataMapperTest extends \PHPUnit\Framework\TestCase
{
    public function testMapping()
    {
        $mapper = new RatingDataMapper();

        $mappedRating = $this->getMappedRating();
        $dataForMapping = $mapper->getData($mappedRating);

        $rating = new Rating();
        $ratingAfterMapping = $mapper->map($rating, $dataForMapping);

        $this->assertEquals(
            $mappedRating,
            $ratingAfterMapping
        );
    }

    public function testPrimaryKeyGetter()
    {
        $mapper = new RatingDataMapper();
        $mappedRating = $this->getMappedRating();

        $expectedPrimaryKey = [
            'OXID' => 'testId',
        ];

        $this->assertEquals(
            $expectedPrimaryKey,
            $mapper->getPrimaryKey($mappedRating)
        );
    }

    private function getMappedRating()
    {
        $rating = new Rating();
        $rating
            ->setId('testId')
            ->setRating(5)
            ->setUserId('userId')
            ->setObjectId('objectId')
            ->setType('product')
            ->setCreatedAt('time');

        return $rating;
    }
}
