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
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Review;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ReviewAndRatingMergingService;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;

class ReviewAndRatingMergingServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testMergingReviewWithRatingAndRatingWithReview()
    {
        $reviewAndRatingMergingService = new ReviewAndRatingMergingService();

        $reviews = new ArrayCollection([
            $this->getReviewWithRating(),
        ]);

        $ratings = new ArrayCollection([
            $this->getRatingWithReview(),
        ]);

        $reviewAndRatingList = $reviewAndRatingMergingService->mergeReviewAndRating(
            $reviews,
            $ratings
        );

        $expectedReviewAndRatingList = new ArrayCollection([
            $this->getReviewAndRatingViewObjectWithReviewAndWithRating(),
        ]);

        $this->assertEquals(
            $expectedReviewAndRatingList,
            $reviewAndRatingList
        );
    }

    public function testMergingReviewWithoutRatingAndRatingWithoutReview()
    {
        $reviewAndRatingMergingService = new ReviewAndRatingMergingService();

        $reviews = new ArrayCollection([
            $this->getReviewWithoutRating(),
        ]);

        $ratings = new ArrayCollection([
            $this->getRatingWithoutReview(),
        ]);

        $reviewAndRatingList = $reviewAndRatingMergingService->mergeReviewAndRating(
            $reviews,
            $ratings
        );

        $expectedReviewAndRatingList = new ArrayCollection([
            $this->getReviewAndRatingViewObjectWithReviewAndWithoutRating(),
            $this->getReviewAndRatingViewObjectWithoutReviewAndWithRating(),
        ]);

        $this->assertEquals(
            $expectedReviewAndRatingList,
            $reviewAndRatingList
        );
    }

    private function getReviewWithRating()
    {
        $review = new Review();
        $review
            ->setId('reviewId1')
            ->setRating(5)
            ->setObjectId('1')
            ->setUserId('firstUserId')
            ->setText('With');

        return $review;
    }

    private function getReviewWithoutRating()
    {
        $review = new Review();

        $review
            ->setId('reviewId2')
            ->setRating(0)
            ->setObjectId('1')
            ->setUserId('firstUserId')
            ->setText('Without');

        return $review;
    }

    private function getRatingWithReview()
    {
        $rating = new Rating();

        $rating
            ->setId('ratingId1')
            ->setRating(5)
            ->setUserId('firstUserId')
            ->setObjectId('1');

        return $rating;
    }

    private function getRatingWithoutReview()
    {
        $rating = new Rating();

        $rating
            ->setId('ratingId2')
            ->setRating(5)
            ->setUserId('secondUserId')
            ->setObjectId('1');

        return $rating;
    }

    private function getReviewAndRatingViewObjectWithReviewAndWithRating()
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating
            ->setReviewId('reviewId1')
            ->setRatingId('ratingId1')
            ->setRating(5)
            ->setObjectId('1')
            ->setReviewText('With');

        return $reviewAndRating;
    }

    private function getReviewAndRatingViewObjectWithReviewAndWithoutRating()
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating
            ->setReviewId('reviewId2')
            ->setRatingId(false)
            ->setRating(false)
            ->setObjectId('1')
            ->setReviewText('Without');

        return $reviewAndRating;
    }

    private function getReviewAndRatingViewObjectWithoutReviewAndWithRating()
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating
            ->setReviewId(false)
            ->setRatingId('ratingId2')
            ->setRating(5)
            ->setObjectId('1')
            ->setReviewText(false);

        return $reviewAndRating;
    }
}
