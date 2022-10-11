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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Domain\Review\Bridge;

use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserRatingBridge;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\RatingPermissionException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserRatingService;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserRatingServiceInterface;

class UserRatingBridgeTest extends \PHPUnit\Framework\TestCase
{
    public function testDeleteRating()
    {
        $this->createTestRating();

        $userRatingBridge = $this->getUserRatingBridge();
        $userRatingBridge->deleteRating('testUserId', 'testRatingId');

        $this->assertFalse(
            $this->ratingExists('testRatingId')
        );
    }

    public function testDeleteRatingForSubShop()
    {
        $this->createTestRatingForSubShop();

        $userRatingBridge = $this->getUserRatingBridge();
        $userRatingBridge->deleteRating('testUserId', 'testSubShopRatingId');

        $this->assertFalse(
            $this->ratingExists('testSubShopRatingId')
        );
    }

    public function testDeleteRatingWithNonExistentRatingId()
    {
        $this->expectException(EntryDoesNotExistDaoException::class);

        $userRatingBridge = $this->getUserRatingBridge();
        $userRatingBridge->deleteRating('testUserId', 'nonExistentId');
    }

    public function testDeleteRatingWithWrongUserId()
    {
        $this->expectException(RatingPermissionException::class);

        $this->createTestRating();

        $userRatingBridge = $this->getUserRatingBridge();
        $userRatingBridge->deleteRating('userWithWrongId', 'testRatingId');
    }

    private function ratingExists($id)
    {
        $rating = oxNew(Rating::class);

        return $rating->load($id) !== false;
    }

    private function getUserRatingBridge()
    {
        return new UserRatingBridge(
            $this->getUserRatingServiceMock()
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserRatingServiceInterface
     */
    private function getUserRatingServiceMock()
    {
        $userRatingServiceMock = $this->getMockBuilder(UserRatingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $userRatingServiceMock;
    }

    private function createTestRating()
    {
        $rating = oxNew(Rating::class);
        $rating->setId('testRatingId');
        $rating->oxratings__oxuserid = new Field('testUserId');
        $rating->save();
    }

    private function createTestRatingForSubShop()
    {
        $rating = oxNew(Rating::class);
        $rating->setId('testSubShopRatingId');
        $rating->oxratings__oxuserid = new Field('testUserId');
        $rating->oxratings__oxshopid = new Field(5);
        $rating->save();
    }
}
