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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Review\Bridge;

use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserReviewBridge;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\ReviewPermissionException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserReviewServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;

class UserReviewBridgeTest_StubReview extends Review
{
    public static bool $loadReturns = true;
    public static string $ownerOxid = 'owner-1';

    public bool $deleted = false;

    public function __construct()
    {
    }

    public function load($oxId)
    {
        $this->oxreviews__oxuserid = new Field(self::$ownerOxid);
        return self::$loadReturns;
    }

    public function delete($oxId = null)
    {
        $this->deleted = true;
        return true;
    }
}

class UserReviewBridgeTest extends \OxidTestCase
{
    public function testDeleteReviewDeletesAfterPermissionCheck(): void
    {
        $stub = new UserReviewBridgeTest_StubReview();
        UserReviewBridgeTest_StubReview::$loadReturns = true;
        UserReviewBridgeTest_StubReview::$ownerOxid = 'user-7';
        \oxTestModules::addModuleObject(Review::class, $stub);

        $bridge = new UserReviewBridge($this->createMock(UserReviewServiceInterface::class));
        $bridge->deleteReview('user-7', 'review-1');

        $this->assertTrue($stub->deleted);
    }

    public function testDeleteReviewThrowsPermissionExceptionForOtherUser(): void
    {
        $stub = new UserReviewBridgeTest_StubReview();
        UserReviewBridgeTest_StubReview::$loadReturns = true;
        UserReviewBridgeTest_StubReview::$ownerOxid = 'someone-else';
        \oxTestModules::addModuleObject(Review::class, $stub);

        $bridge = new UserReviewBridge($this->createMock(UserReviewServiceInterface::class));

        $this->expectException(ReviewPermissionException::class);
        $bridge->deleteReview('user-7', 'review-1');
    }

    public function testDeleteReviewThrowsEntryDoesNotExistWhenLoadFails(): void
    {
        $stub = new UserReviewBridgeTest_StubReview();
        UserReviewBridgeTest_StubReview::$loadReturns = false;
        \oxTestModules::addModuleObject(Review::class, $stub);

        $bridge = new UserReviewBridge($this->createMock(UserReviewServiceInterface::class));

        $this->expectException(EntryDoesNotExistDaoException::class);
        $bridge->deleteReview('user-7', 'missing-review');
    }
}
