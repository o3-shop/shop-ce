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
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ReviewAndRatingMergingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserRatingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserReviewAndRatingService;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserReviewServiceInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;

class UserReviewAndRatingServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testReviewAndRatingListSorting()
    {
        $reviewAndRatingMergingServiceMock = $this
            ->getMockBuilder(ReviewAndRatingMergingServiceInterface::class)
            ->getMock();

        $reviewAndRatingMergingServiceMock
            ->method('mergeReviewAndRating')
            ->willReturn($this->getUnsortedReviewAndRatingList());

        $userReviewAndRatingService = new UserReviewAndRatingService(
            $this->getUserReviewServiceMock(),
            $this->getUserRatingServiceMock(),
            $reviewAndRatingMergingServiceMock
        );

        $this->assertEquals(
            $this->getSortedReviewAndRatingList(),
            $userReviewAndRatingService->getReviewAndRatingList(1)
        );
    }

    public function testReviewAndRatingListCount()
    {
        $reviewAndRatingMergingServiceMock = $this
            ->getMockBuilder(ReviewAndRatingMergingServiceInterface::class)
            ->getMock();

        $reviewAndRatingMergingServiceMock
            ->method('mergeReviewAndRating')
            ->willReturn($this->getUnsortedReviewAndRatingList());

        $userReviewAndRatingService = new UserReviewAndRatingService(
            $this->getUserReviewServiceMock(),
            $this->getUserRatingServiceMock(),
            $reviewAndRatingMergingServiceMock
        );

        $this->assertEquals(
            $this->getSortedReviewAndRatingList()->count(),
            $userReviewAndRatingService->getReviewAndRatingListCount(1)
        );
    }

    private function getUserReviewServiceMock()
    {
        $userReviewService = $this
            ->getMockBuilder(UserReviewServiceInterface::class)
            ->getMock();

        $userReviewService
            ->method('getReviews')
            ->willReturn(new ArrayCollection());

        return $userReviewService;
    }

    private function getUserRatingServiceMock()
    {
        $userRatingService = $this
            ->getMockBuilder(UserRatingServiceInterface::class)
            ->getMock();

        $userRatingService
            ->method('getRatings')
            ->willReturn(new ArrayCollection());

        return $userRatingService;
    }

    private function getUnsortedReviewAndRatingList()
    {
        return new ArrayCollection([
            $this->getFirstReviewAndRating(),
            $this->getThirdReviewAndRating(),
            $this->getSecondReviewAndRating(),
        ]);
    }

    private function getSortedReviewAndRatingList()
    {
        return new ArrayCollection([
            $this->getThirdReviewAndRating(),
            $this->getSecondReviewAndRating(),
            $this->getFirstReviewAndRating(),
        ]);
    }

    private function getFirstReviewAndRating()
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating->setCreatedAt('2011-02-16 15:21:20');

        return $reviewAndRating;
    }

    private function getSecondReviewAndRating()
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating->setCreatedAt('2017-02-16 15:21:20');

        return $reviewAndRating;
    }

    private function getThirdReviewAndRating()
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating->setCreatedAt('2018-02-16 15:21:20');

        return $reviewAndRating;
    }
}
