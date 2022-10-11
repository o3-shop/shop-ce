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

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Review;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;

class ReviewAndRatingMergingService implements ReviewAndRatingMergingServiceInterface
{
    /**
     * Merges Reviews and Ratings to Collection of ReviewAndRating view objects.
     *
     * @param ArrayCollection $reviews
     * @param ArrayCollection $ratings
     *
     * @return ArrayCollection
     */
    public function mergeReviewAndRating(ArrayCollection $reviews, ArrayCollection $ratings)
    {
        $ratingAndReviewList = array_merge(
            $this->getReviewDataWithRating($reviews, $ratings),
            $this->getRatingWithoutReviewData($reviews, $ratings)
        );

        return $this->mapReviewAndRatingList($ratingAndReviewList);
    }

    /**
     * @param ArrayCollection $reviews
     * @param ArrayCollection $ratings
     *
     * @return array
     */
    private function getReviewDataWithRating(ArrayCollection $reviews, ArrayCollection $ratings)
    {
        $reviewList = [];

        foreach ($reviews as $review) {
            $ratingAndReview = [
                'reviewId'      => $review->getId(),
                'text'          => $review->getText(),
                'createdAt'     => $review->getCreatedAt(),
                'objectId'      => $review->getObjectId(),
                'objectType'    => $review->getType(),
                'rating'        => false,
                'ratingId'      => false,
            ];

            foreach ($ratings as $rating) {
                if ($this->isReviewRating($review, $rating)) {
                    $ratingAndReview['rating'] = $rating->getRating();
                    $ratingAndReview['ratingId'] = $rating->getId();

                    break;
                }
            }

            $reviewList[] = $ratingAndReview;
        }

        return $reviewList;
    }

    /**
     * @param ArrayCollection $reviews
     * @param ArrayCollection $ratings
     *
     * @return array
     */
    private function getRatingWithoutReviewData(ArrayCollection $reviews, ArrayCollection $ratings)
    {
        $ratingList = [];

        foreach ($ratings as $rating) {
            if ($this->isRatingWithoutReview($rating, $reviews)) {
                $ratingList[] = [
                    'ratingId'      => $rating->getId(),
                    'reviewId'      => false,
                    'rating'        => $rating->getRating(),
                    'text'          => '',
                    'objectId'      => $rating->getObjectId(),
                    'objectType'    => $rating->getType(),
                    'createdAt'     => $rating->getCreatedAt(),
                ];
            }
        }

        return $ratingList;
    }

    /**
     * Returns true if Rating doesn't belong to any review.
     *
     * @param Rating          $rating
     * @param ArrayCollection $reviews
     *
     * @return bool
     */
    private function isRatingWithoutReview(Rating $rating, ArrayCollection $reviews)
    {
        $withoutReview = true;

        foreach ($reviews as $review) {
            if ($this->isReviewRating($review, $rating)) {
                $withoutReview = false;
                break;
            }
        }

        return $withoutReview;
    }

    /**
     * Returns true if Rating belongs to Review.
     *
     * @param Review $review
     * @param Rating $rating
     *
     * @return bool
     */
    private function isReviewRating(Review $review, Rating $rating)
    {
        return $rating->getType() === $review->getType()
            && $rating->getObjectId() === $review->getObjectId()
            && $rating->getRating() === $review->getRating()
            && $rating->getUserId() === $review->getUserId();
    }

    /**
     * Maps Reviews and Ratings data to Collection of ReviewAndRating view objects.
     *
     * @param array $reviewAndRatingDataList
     *
     * @return ArrayCollection
     */
    private function mapReviewAndRatingList($reviewAndRatingDataList)
    {
        $mappedReviewAndRating = new ArrayCollection();

        foreach ($reviewAndRatingDataList as $reviewAndRatingData) {
            $mappedReviewAndRating[] = $this->mapReviewAndRating($reviewAndRatingData);
        }

        return $mappedReviewAndRating;
    }

    /**
     * Maps Review and Rating data to ReviewAndRating view object.
     *
     * @param array $reviewAndRatingData
     *
     * @return ReviewAndRating
     */
    private function mapReviewAndRating($reviewAndRatingData)
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating
            ->setReviewId($reviewAndRatingData['reviewId'])
            ->setRatingId($reviewAndRatingData['ratingId'])
            ->setRating($reviewAndRatingData['rating'])
            ->setReviewText($reviewAndRatingData['text'])
            ->setObjectId($reviewAndRatingData['objectId'])
            ->setObjectType($reviewAndRatingData['objectType'])
            ->setCreatedAt($reviewAndRatingData['createdAt']);

        return $reviewAndRating;
    }
}
