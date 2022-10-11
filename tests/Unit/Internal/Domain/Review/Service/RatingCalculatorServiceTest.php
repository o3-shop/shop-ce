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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\RatingCalculatorService;

class RatingCalculatorServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAverage()
    {
        $ratingCalculatorService = new RatingCalculatorService();

        $ratings = new ArrayCollection([
            $this->createRating(5),
            $this->createRating(3),
            $this->createRating(3),
            $this->createRating(2),
        ]);

        $average = $ratingCalculatorService->getAverage($ratings);

        $this->assertEquals(
            3.25,
            $average
        );
    }

    public function testGetAverageForNoRating()
    {
        $ratingCalculatorService = new RatingCalculatorService();

        $ratings = new ArrayCollection([]);

        $average = $ratingCalculatorService->getAverage($ratings);

        $this->assertEquals(
            0,
            $average
        );
    }

    private function createRating($ratingValue)
    {
        $rating = new Rating();
        $rating->setRating($ratingValue);

        return $rating;
    }
}
