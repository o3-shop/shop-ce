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

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsDate;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserReviewAndRatingBridge;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\ReviewAndRatingObjectTypeException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\UserReviewAndRatingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;

/**
 * Stub Article — load() steers an oxarticles__oxtitle field so the
 * bridge can read it back via the magic property accessor.
 */
class UserReviewAndRatingBridgeTest_StubArticle extends Article
{
    public static string $title = 'Default article title';
    public ?string $loadedWith = null;

    public function __construct($params = null)
    {
        // Skip parent::__construct so init() doesn't reach for DB.
    }

    public function load($oxId)
    {
        $this->loadedWith = (string) $oxId;
        $this->oxarticles__oxtitle = new Field(self::$title);
        return true;
    }
}

class UserReviewAndRatingBridgeTest_StubRecommList extends RecommendationList
{
    public static string $title = 'Default recommendation list';
    public ?string $loadedWith = null;

    public function __construct()
    {
    }

    public function load($oxId)
    {
        $this->loadedWith = (string) $oxId;
        $this->oxrecommlists__oxtitle = new Field(self::$title);
        return true;
    }
}

class UserReviewAndRatingBridgeTest extends \OxidTestCase
{
    public function testGetReviewAndRatingListCountDelegatesToService(): void
    {
        $service = $this->createMock(UserReviewAndRatingServiceInterface::class);
        $service->expects($this->once())
            ->method('getReviewAndRatingListCount')
            ->with('user-7')
            ->willReturn(42);

        $bridge = new UserReviewAndRatingBridge($service);
        $this->assertSame(42, $bridge->getReviewAndRatingListCount('user-7'));
    }

    public function testGetReviewAndRatingListReturnsHydratedArrayForArticleObjects(): void
    {
        UserReviewAndRatingBridgeTest_StubArticle::$title = 'Test Article';
        \oxTestModules::addModuleObject(Article::class, new UserReviewAndRatingBridgeTest_StubArticle());

        $rating = new ReviewAndRating();
        $rating->setReviewText('plain & simple <script>');
        $rating->setObjectType('oxarticle');
        $rating->setObjectId('art-1');
        $rating->setCreatedAt('2026-04-01 12:34:56');

        $service = $this->createMock(UserReviewAndRatingServiceInterface::class);
        $service->method('getReviewAndRatingList')->willReturn(new ArrayCollection([$rating]));

        $list = (new UserReviewAndRatingBridge($service))->getReviewAndRatingList('user-7');
        $this->assertCount(1, $list);
        $resolved = $list[0];

        // Title pulled from the loaded model.
        $this->assertSame('Test Article', $resolved->getObjectTitle());
        // Review text passed through htmlspecialchars (the < > & all encoded).
        $this->assertStringContainsString('&lt;script&gt;', $resolved->getReviewText());
        $this->assertStringContainsString('&amp;', $resolved->getReviewText());
        // Date passed through formatDBDate.
        $this->assertNotEmpty($resolved->getCreatedAt());
    }

    public function testGetReviewAndRatingListResolvesRecommendationListTitle(): void
    {
        UserReviewAndRatingBridgeTest_StubRecommList::$title = 'My Wishlist';
        \oxTestModules::addModuleObject(
            RecommendationList::class,
            new UserReviewAndRatingBridgeTest_StubRecommList()
        );

        $rating = new ReviewAndRating();
        $rating->setReviewText('safe text');
        $rating->setObjectType('oxrecommlist');
        $rating->setObjectId('rec-7');
        $rating->setCreatedAt('2026-01-01 00:00:00');

        $service = $this->createMock(UserReviewAndRatingServiceInterface::class);
        $service->method('getReviewAndRatingList')->willReturn(new ArrayCollection([$rating]));

        $list = (new UserReviewAndRatingBridge($service))->getReviewAndRatingList('user-7');
        $this->assertSame('My Wishlist', $list[0]->getObjectTitle());
    }

    public function testGetReviewAndRatingListThrowsForUnknownObjectType(): void
    {
        $rating = new ReviewAndRating();
        $rating->setObjectType('oxsomething-unsupported');
        $rating->setObjectId('whatever');
        $rating->setReviewText('');
        $rating->setCreatedAt('');

        $service = $this->createMock(UserReviewAndRatingServiceInterface::class);
        $service->method('getReviewAndRatingList')->willReturn(new ArrayCollection([$rating]));

        $this->expectException(ReviewAndRatingObjectTypeException::class);
        (new UserReviewAndRatingBridge($service))->getReviewAndRatingList('user-7');
    }

    public function testGetReviewAndRatingListReturnsEmptyArrayForEmptyCollection(): void
    {
        $service = $this->createMock(UserReviewAndRatingServiceInterface::class);
        $service->method('getReviewAndRatingList')->willReturn(new ArrayCollection([]));

        $this->assertSame([], (new UserReviewAndRatingBridge($service))->getReviewAndRatingList('user-7'));
    }

    public function testFormatsCreatedAtViaUtilsDate(): void
    {
        // Capture what UtilsDate::formatDBDate gets and steer its return value.
        $utilsDate = $this->getMock(UtilsDate::class, ['formatDBDate']);
        $utilsDate->expects($this->once())
            ->method('formatDBDate')
            ->with('2026-04-01 12:34:56')
            ->willReturn('01.04.2026');
        Registry::set(UtilsDate::class, $utilsDate);

        UserReviewAndRatingBridgeTest_StubArticle::$title = 'irrelevant';
        \oxTestModules::addModuleObject(Article::class, new UserReviewAndRatingBridgeTest_StubArticle());

        $rating = new ReviewAndRating();
        $rating->setReviewText('');
        $rating->setObjectType('oxarticle');
        $rating->setObjectId('art-1');
        $rating->setCreatedAt('2026-04-01 12:34:56');

        $service = $this->createMock(UserReviewAndRatingServiceInterface::class);
        $service->method('getReviewAndRatingList')->willReturn(new ArrayCollection([$rating]));

        $result = (new UserReviewAndRatingBridge($service))->getReviewAndRatingList('user-7');
        $this->assertSame('01.04.2026', $result[0]->getCreatedAt());
    }
}
