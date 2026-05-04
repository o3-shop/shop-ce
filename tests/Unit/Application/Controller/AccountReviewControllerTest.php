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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Application\Controller\AccountReviewController;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserRatingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserReviewBridgeInterface;
use Psr\Container\ContainerInterface;

class AccountReviewControllerTest_StubReview extends Review
{
    public static array $listToReturn = [];
    public ?string $loadedForUserId = null;

    public function __construct()
    {
        // Skip parent constructor to avoid Model::init touching DB metadata.
    }

    public function getReviewAndRatingListByUserId($userId)
    {
        $this->loadedForUserId = (string) $userId;
        return self::$listToReturn;
    }
}

class AccountReviewControllerTest extends \OxidTestCase
{
    private const USER_OXID = 'user-42';

    protected function setUp(): void
    {
        parent::setUp();
        AccountReviewControllerTest_StubReview::$listToReturn = [];
    }

    public function testTemplateNameIsAccountReviews(): void
    {
        $controller = $this->getProxyClass(AccountReviewController::class);
        $this->assertSame('page/account/reviews.tpl', $controller->getNonPublicVar('_sThisTemplate'));
    }

    public function testItemsPerPageDefaultIsTen(): void
    {
        $controller = oxNew(AccountReviewController::class);
        $this->assertSame(10, $controller->getItemsPerPage());
    }

    public function testGetReviewListReturnsPaginatedSliceForCurrentUser(): void
    {
        // Build 25 fake review items; with 10 items/page and page=1, the
        // slice must return items at offsets 10..19.
        $items = [];
        for ($i = 0; $i < 25; $i++) {
            $items[(string) $i] = ['id' => $i];
        }
        AccountReviewControllerTest_StubReview::$listToReturn = $items;
        \oxTestModules::addModuleObject(Review::class, new AccountReviewControllerTest_StubReview());

        $user = $this->getMock(User::class, ['getId']);
        $user->expects($this->any())->method('getId')->will($this->returnValue(self::USER_OXID));

        $controller = $this->getMock(
            AccountReviewController::class,
            ['getUser', 'getReviewAndRatingItemsCount']
        );
        $controller->expects($this->any())->method('getUser')->will($this->returnValue($user));
        $controller->expects($this->any())
            ->method('getReviewAndRatingItemsCount')
            ->will($this->returnValue(25));

        $this->setRequestParameter('pgNr', 1);

        $list = $controller->getReviewList();
        $this->assertCount(10, $list);
        $this->assertSame(10, $list['10']['id']);
        $this->assertSame(19, $list['19']['id']);
    }

    public function testGetReviewListClampsActPageToLastAvailable(): void
    {
        // 5 items, 10/page → 1 page total → last index 0.
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[(string) $i] = ['id' => $i];
        }
        AccountReviewControllerTest_StubReview::$listToReturn = $items;
        \oxTestModules::addModuleObject(Review::class, new AccountReviewControllerTest_StubReview());

        $user = $this->getMock(User::class, ['getId']);
        $user->expects($this->any())->method('getId')->will($this->returnValue(self::USER_OXID));

        $controller = $this->getMock(
            AccountReviewController::class,
            ['getUser', 'getReviewAndRatingItemsCount']
        );
        $controller->expects($this->any())->method('getUser')->will($this->returnValue($user));
        $controller->expects($this->any())
            ->method('getReviewAndRatingItemsCount')
            ->will($this->returnValue(5));

        // Submit a wildly out-of-range page number — must be clamped down.
        $this->setRequestParameter('pgNr', 99);

        $list = $controller->getReviewList();
        $this->assertCount(5, $list);
    }

    public function testDeleteReviewAndRatingDelegatesToBothBridges(): void
    {
        $reviewBridge = $this->createMock(UserReviewBridgeInterface::class);
        $reviewBridge->expects($this->once())
            ->method('deleteReview')
            ->with(self::USER_OXID, 'rev-1');

        $ratingBridge = $this->createMock(UserRatingBridgeInterface::class);
        $ratingBridge->expects($this->once())
            ->method('deleteRating')
            ->with(self::USER_OXID, 'rate-1');

        $this->setRequestParameter('reviewId', 'rev-1');
        $this->setRequestParameter('ratingId', 'rate-1');
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());

        $controller = $this->makeControllerWithUserAndContainer(self::USER_OXID, [
            UserReviewBridgeInterface::class => $reviewBridge,
            UserRatingBridgeInterface::class => $ratingBridge,
        ]);

        $controller->deleteReviewAndRating();
    }

    public function testDeleteReviewAndRatingSkipsBridgesIfReviewIdMissing(): void
    {
        $reviewBridge = $this->createMock(UserReviewBridgeInterface::class);
        $reviewBridge->expects($this->never())->method('deleteReview');
        $ratingBridge = $this->createMock(UserRatingBridgeInterface::class);
        $ratingBridge->expects($this->never())->method('deleteRating');

        // No reviewId / ratingId in the request.
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());

        $controller = $this->makeControllerWithUserAndContainer(self::USER_OXID, [
            UserReviewBridgeInterface::class => $reviewBridge,
            UserRatingBridgeInterface::class => $ratingBridge,
        ]);

        $controller->deleteReviewAndRating();
    }

    public function testDeleteReviewAndRatingShortCircuitsForMismatchedSessionToken(): void
    {
        $reviewBridge = $this->createMock(UserReviewBridgeInterface::class);
        $reviewBridge->expects($this->never())->method('deleteReview');
        $ratingBridge = $this->createMock(UserRatingBridgeInterface::class);
        $ratingBridge->expects($this->never())->method('deleteRating');

        $this->setRequestParameter('reviewId', 'rev-1');
        $this->setRequestParameter('ratingId', 'rate-1');
        // Wrong stoken → checkSessionChallenge fails.
        $this->setRequestParameter('stoken', 'wrong-token');

        $controller = $this->makeControllerWithUserAndContainer(self::USER_OXID, [
            UserReviewBridgeInterface::class => $reviewBridge,
            UserRatingBridgeInterface::class => $ratingBridge,
        ]);

        $controller->deleteReviewAndRating();
    }

    public function testGetBreadCrumbReturnsTwoLevels(): void
    {
        $controller = oxNew(AccountReviewController::class);
        $crumbs = $controller->getBreadCrumb();
        $this->assertCount(2, $crumbs);
        foreach ($crumbs as $crumb) {
            $this->assertArrayHasKey('title', $crumb);
            $this->assertArrayHasKey('link', $crumb);
        }
    }

    public function testGetPageNavigationCachesAndReturnsObject(): void
    {
        $controller = $this->getMock(
            AccountReviewController::class,
            ['getReviewAndRatingItemsCount', 'generatePageNavigation']
        );
        $controller->expects($this->any())
            ->method('getReviewAndRatingItemsCount')
            ->will($this->returnValue(15));
        $sentinel = new \stdClass();
        $sentinel->marker = 'navigation';
        $controller->expects($this->once())
            ->method('generatePageNavigation')
            ->will($this->returnValue($sentinel));

        $this->assertSame($sentinel, $controller->getPageNavigation());
    }

    /**
     * @param array<string,object> $services
     */
    private function makeControllerWithUserAndContainer(string $userOxid, array $services): AccountReviewController
    {
        $user = $this->getMock(User::class, ['getId']);
        $user->expects($this->any())->method('getId')->will($this->returnValue($userOxid));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn ($id) => $services[$id] ?? null);
        $container->method('has')->willReturnCallback(fn ($id) => isset($services[$id]));

        $controller = $this->getMock(
            AccountReviewController::class,
            ['getUser', 'getContainer']
        );
        $controller->expects($this->any())->method('getUser')->will($this->returnValue($user));
        $controller->expects($this->any())->method('getContainer')->will($this->returnValue($container));

        return $controller;
    }
}
