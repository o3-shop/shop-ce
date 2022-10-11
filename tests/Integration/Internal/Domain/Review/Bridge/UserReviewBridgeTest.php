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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserReviewBridge;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\ReviewPermissionException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserReviewService;

class UserReviewBridgeTest extends \PHPUnit\Framework\TestCase
{
    public function testDeleteReview()
    {
        $userReviewBridge = $this->getUserReviewBridge();
        $database = DatabaseProvider::getDb();

        $sql = "select oxid from oxreviews where oxid = 'id1'";

        $this->createTestReview();
        $this->assertEquals('id1', $database->getOne($sql));

        $userReviewBridge->deleteReview('user1', 'id1');
        $this->assertFalse($database->getOne($sql));
    }

    public function testDeleteReviewWithNonExistentReviewId()
    {
        $this->expectException(EntryDoesNotExistDaoException::class);

        $userReviewBridge = $this->getUserReviewBridge();
        $userReviewBridge->deleteReview('user1', 'nonExistentId');
    }

    public function testDeleteRatingWithWrongUserId()
    {
        $this->expectException(ReviewPermissionException::class);

        $userReviewBridge = $this->getUserReviewBridge();
        $database = DatabaseProvider::getDb();

        $sql = "select oxid from oxreviews where oxid = 'id1'";

        $this->createTestReview();
        $this->assertEquals('id1', $database->getOne($sql));

        $userReviewBridge->deleteReview('userWithWrongId', 'id1');
    }

    private function getUserReviewBridge()
    {
        return new UserReviewBridge(
            $this->getUserReviewServiceMock()
        );
    }

    private function getUserReviewServiceMock()
    {
        $userReviewServiceMock = $this->getMockBuilder(UserReviewService::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $userReviewServiceMock;
    }

    private function createTestReview()
    {
        $review = oxNew(Review::class);
        $review->setId('id1');
        $review->oxreviews__oxuserid = new Field('user1');
        $review->save();
    }
}
