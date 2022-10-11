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

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserReviewAndRatingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\ReviewAndRatingObjectTypeException;

class UserReviewAndRatingBridge implements UserReviewAndRatingBridgeInterface
{
    /**
     * @var UserReviewAndRatingServiceInterface
     */
    private $userReviewAndRatingService;

    /**
     * UserReviewAndRatingBridge constructor.
     *
     * @param UserReviewAndRatingServiceInterface $userReviewAndRatingService
     */
    public function __construct(UserReviewAndRatingServiceInterface $userReviewAndRatingService)
    {
        $this->userReviewAndRatingService = $userReviewAndRatingService;
    }

    /**
     * Get number of reviews by given user.
     *
     * @param string $userId
     *
     * @return int
     */
    public function getReviewAndRatingListCount($userId)
    {
        return $this
            ->userReviewAndRatingService
            ->getReviewAndRatingListCount($userId);
    }

    /**
     * Returns Collection of User Ratings and Reviews.
     *
     * @param string $userId
     *
     * @return array
     */
    public function getReviewAndRatingList($userId)
    {
        $reviewAndRatingList = $this
            ->userReviewAndRatingService
            ->getReviewAndRatingList($userId);

        $this->prepareRatingAndReviewPropertiesData($reviewAndRatingList);

        return $reviewAndRatingList->toArray();
    }

    /**
     * Prepare RatingAndReview properties data.
     *
     * @param ArrayCollection $reviewAndRatingList
     */
    private function prepareRatingAndReviewPropertiesData($reviewAndRatingList)
    {
        foreach ($reviewAndRatingList as $reviewAndRating) {
            $this->setObjectTitleToReviewAndRating($reviewAndRating);
            $this->formatReviewText($reviewAndRating);
            $this->formatReviewAndRatingDate($reviewAndRating);
        }
    }

    /**
     * Formats Review text.
     *
     * @param ReviewAndRating $reviewAndRating
     */
    private function formatReviewText(ReviewAndRating $reviewAndRating)
    {
        $preparedText = htmlspecialchars($reviewAndRating->getReviewText());

        $reviewAndRating->setReviewText($preparedText);
    }

    /**
     * Formats ReviewAndRating date.
     *
     * @param ReviewAndRating $reviewAndRating
     */
    private function formatReviewAndRatingDate(ReviewAndRating $reviewAndRating)
    {
        $formattedDate = Registry::getUtilsDate()->formatDBDate($reviewAndRating->getCreatedAt());

        $reviewAndRating->setCreatedAt($formattedDate);
    }

    /**
     * Sets object title to ReviewAndRating.
     *
     * @param ReviewAndRating $reviewAndRating
     */
    private function setObjectTitleToReviewAndRating(ReviewAndRating $reviewAndRating)
    {
        $title = $this->getObjectTitle(
            $reviewAndRating->getObjectType(),
            $reviewAndRating->getObjectId()
        );

        $reviewAndRating->setObjectTitle($title);
    }

    /**
     * Returns object title.
     *
     * @param string $type
     * @param string $objectId
     *
     * @return string
     */
    private function getObjectTitle($type, $objectId)
    {
        $objectModel = $this->getObjectModel($type);
        $objectModel->load($objectId);

        $fieldName = $this->getObjectTitleFieldName($type);
        $field = $objectModel->$fieldName;

        return $field->value;
    }

    /**
     * Returns object model.
     *
     * @param string $type
     *
     * @return Article|RecommendationList
     * @throws ReviewAndRatingObjectTypeException
     */
    private function getObjectModel($type)
    {
        if ($type === 'oxarticle') {
            $model = oxNew(Article::class);
        }

        if ($type === 'oxrecommlist') {
            $model = oxNew(RecommendationList::class);
        }

        if (!isset($model)) {
            throw new ReviewAndRatingObjectTypeException();
        }

        return $model;
    }

    /**
     * Returns field name of the object title.
     *
     * @param string $type
     *
     * @return string
     * @throws ReviewAndRatingObjectTypeException
     */
    private function getObjectTitleFieldName($type)
    {
        if ($type === 'oxarticle') {
            $fieldName = 'oxarticles__oxtitle';
        }

        if ($type === 'oxrecommlist') {
            $fieldName = 'oxrecommlists__oxtitle';
        }

        if (!isset($fieldName)) {
            throw new ReviewAndRatingObjectTypeException();
        }

        return $fieldName;
    }
}
