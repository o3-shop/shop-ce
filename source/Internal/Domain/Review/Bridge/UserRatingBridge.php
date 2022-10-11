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

use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserRatingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\RatingPermissionException;
use OxidEsales\Eshop\Application\Model\Rating;

class UserRatingBridge implements UserRatingBridgeInterface
{
    /**
     * @var UserRatingServiceInterface
     */
    private $userRatingService;

    /**
     * UserRatingBridge constructor.
     *
     * @param UserRatingServiceInterface $userRatingService
     */
    public function __construct(
        UserRatingServiceInterface $userRatingService
    ) {
        $this->userRatingService = $userRatingService;
    }

    /**
     * Delete a Rating.
     *
     * @param string $userId
     * @param string $ratingId
     *
     * @throws RatingPermissionException
     * @throws EntryDoesNotExistDaoException
     */
    public function deleteRating($userId, $ratingId)
    {
        $rating = $this->getRatingById($ratingId);

        $this->validateUserPermissionsToManageRating($rating, $userId);

        $rating = $this->disableSubShopDeleteProtectionForRating($rating);
        $rating->delete();
    }

    /**
     * @param Rating $rating
     *
     * @return Rating
     */
    private function disableSubShopDeleteProtectionForRating(Rating $rating)
    {
        $rating->setIsDerived(false);

        return $rating;
    }

    /**
     * @param Rating $rating
     * @param string $userId
     *
     * @throws RatingPermissionException
     */
    private function validateUserPermissionsToManageRating(Rating $rating, $userId)
    {
        if ($rating->oxratings__oxuserid->value !== $userId) {
            throw new RatingPermissionException();
        }
    }

    /**
     * @param string $ratingId
     *
     * @return Rating
     * @throws EntryDoesNotExistDaoException
     */
    private function getRatingById($ratingId)
    {
        $rating = oxNew(Rating::class);
        $doesRatingExist = $rating->load($ratingId);

        if (!$doesRatingExist) {
            throw new EntryDoesNotExistDaoException();
        }

        return $rating;
    }
}
