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

use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserRatingBridge;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\RatingPermissionException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserRatingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;

class UserRatingBridgeTest_StubRating extends Rating
{
    public static bool $loadReturns = true;
    public static string $ownerOxid = 'owner-1';

    public bool $derivedFlag = true;
    public bool $deleted = false;

    public function __construct()
    {
        // Skip parent::__construct so init() doesn't reach for DB metadata.
    }

    public function load($oxId)
    {
        $this->oxratings__oxuserid = new Field(self::$ownerOxid);
        return self::$loadReturns;
    }

    public function setIsDerived($value)
    {
        $this->derivedFlag = (bool) $value;
    }

    public function delete($oxId = null)
    {
        $this->deleted = true;
        return true;
    }
}

class UserRatingBridgeTest extends \OxidTestCase
{
    public function testDeleteRatingDeletesAfterPermissionAndDerivedReset(): void
    {
        $stub = new UserRatingBridgeTest_StubRating();
        UserRatingBridgeTest_StubRating::$loadReturns = true;
        UserRatingBridgeTest_StubRating::$ownerOxid = 'user-7';
        \oxTestModules::addModuleObject(Rating::class, $stub);

        $bridge = new UserRatingBridge($this->createMock(UserRatingServiceInterface::class));
        $bridge->deleteRating('user-7', 'rating-1');

        $this->assertFalse($stub->derivedFlag, 'sub-shop delete protection must be cleared.');
        $this->assertTrue($stub->deleted);
    }

    public function testDeleteRatingThrowsPermissionExceptionWhenUserDoesNotOwnIt(): void
    {
        $stub = new UserRatingBridgeTest_StubRating();
        UserRatingBridgeTest_StubRating::$loadReturns = true;
        UserRatingBridgeTest_StubRating::$ownerOxid = 'someone-else';
        \oxTestModules::addModuleObject(Rating::class, $stub);

        $bridge = new UserRatingBridge($this->createMock(UserRatingServiceInterface::class));

        $this->expectException(RatingPermissionException::class);
        $bridge->deleteRating('user-7', 'rating-1');
    }

    public function testDeleteRatingThrowsEntryDoesNotExistWhenLoadFails(): void
    {
        $stub = new UserRatingBridgeTest_StubRating();
        UserRatingBridgeTest_StubRating::$loadReturns = false;
        \oxTestModules::addModuleObject(Rating::class, $stub);

        $bridge = new UserRatingBridge($this->createMock(UserRatingServiceInterface::class));

        $this->expectException(EntryDoesNotExistDaoException::class);
        $bridge->deleteRating('user-7', 'missing-rating');
    }
}
