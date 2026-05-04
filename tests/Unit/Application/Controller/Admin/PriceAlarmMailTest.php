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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\EshopCommunity\Application\Controller\Admin\PriceAlarmMail;

/**
 * Tests for PriceAlarmMail admin controller.
 *
 * The controller's render() walks oxpricealarm rows that haven't been
 * sent yet and counts the ones whose stored target price is at or above
 * the article's current brutto price — that count drives the "send mail"
 * batch counter on the admin form.
 *
 * The loop has three branches that all need coverage:
 *   1. Article not seen before → load + cache its brutto price + compare
 *   2. Article already cached (same article seen earlier) → cheap compare
 *   3. Article fails to load → skip silently
 */
class PriceAlarmMailTest extends \OxidTestCase
{
    private const TEST_ARTICLE_OXID = '_priceAlarmTestArticle';
    private const SECOND_ARTICLE_OXID = '_priceAlarmTestArticle2';
    private const SHOP_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $shopId = $this->getConfig()->getShopId();
        $this->addToDatabase(
            "REPLACE INTO oxarticles SET oxid='" . self::TEST_ARTICLE_OXID . "', oxshopid='$shopId',"
                . " oxprice=10.00, oxtitle='_priceAlarmTestTitle', oxactive=1",
            'oxarticles'
        );
        $this->addToDatabase(
            "REPLACE INTO oxarticles SET oxid='" . self::SECOND_ARTICLE_OXID . "', oxshopid='$shopId',"
                . " oxprice=20.00, oxtitle='_priceAlarmTestTitle2', oxactive=1",
            'oxarticles'
        );
        $this->addTeardownSql("DELETE FROM oxarticles WHERE oxid LIKE '_priceAlarmTestArticle%'");
        $this->addTeardownSql("DELETE FROM oxpricealarm WHERE oxshopid='$shopId'");
    }

    public function testRenderReturnsPriceAlarmMailTemplate(): void
    {
        $controller = oxNew(PriceAlarmMail::class);
        $this->assertEquals('pricealarm_mail.tpl', $controller->render());
    }

    public function testIAllCntIsZeroWithNoUnsentAlarms(): void
    {
        $controller = oxNew(PriceAlarmMail::class);
        $controller->render();

        $this->assertSame(0, $controller->getViewData()['iAllCnt']);
    }

    public function testIAllCntCountsAlarmsWhereTargetPriceIsMet(): void
    {
        $shopId = $this->getConfig()->getShopId();
        // Target 15 ≥ article brutto 10 → counted.
        $this->insertAlarm($shopId, self::TEST_ARTICLE_OXID, 15.00);
        // Target 5 < article brutto 10 → not counted.
        $this->insertAlarm($shopId, self::TEST_ARTICLE_OXID, 5.00);
        // Different article: target 25 ≥ article brutto 20 → counted.
        $this->insertAlarm($shopId, self::SECOND_ARTICLE_OXID, 25.00);

        $controller = oxNew(PriceAlarmMail::class);
        $controller->render();

        $this->assertSame(
            2,
            $controller->getViewData()['iAllCnt'],
            'Two of three alarms should be counted (one fails the price gate).'
        );
    }

    public function testCachedArticlePathIsExercisedForRepeatedArticleId(): void
    {
        $shopId = $this->getConfig()->getShopId();
        // First row: article gets loaded + cached.
        $this->insertAlarm($shopId, self::TEST_ARTICLE_OXID, 15.00);
        // Second row, same article: must come from cache (no second load).
        $this->insertAlarm($shopId, self::TEST_ARTICLE_OXID, 12.00);
        // Third row, same article: cache hit, target below price, NOT counted.
        $this->insertAlarm($shopId, self::TEST_ARTICLE_OXID, 5.00);

        $controller = oxNew(PriceAlarmMail::class);
        $controller->render();

        // 15 ≥ 10 → ✓; 12 ≥ 10 → ✓; 5 ≥ 10 → ✗
        $this->assertSame(2, $controller->getViewData()['iAllCnt']);
    }

    public function testUnloadableArticleIsSilentlySkipped(): void
    {
        $shopId = $this->getConfig()->getShopId();
        $this->insertAlarm($shopId, '_priceAlarmTestArticleMissing', 99.00);
        $this->insertAlarm($shopId, self::TEST_ARTICLE_OXID, 15.00);

        $controller = oxNew(PriceAlarmMail::class);
        $controller->render();

        // Only the loadable article counts; missing-id row is skipped.
        $this->assertSame(1, $controller->getViewData()['iAllCnt']);
    }

    private function insertAlarm(int $shopId, string $articleOxid, float $price): void
    {
        $oxid = uniqid('_priceAlarm_', true);
        $this->addToDatabase(
            "REPLACE INTO oxpricealarm SET oxid='" . $oxid . "', oxshopid='$shopId',"
                . " oxprice=$price, oxartid='$articleOxid',"
                . " oxsended='000-00-00 00:00:00'",
            'oxpricealarm'
        );
        $this->addTeardownSql("DELETE FROM oxpricealarm WHERE oxid='$oxid'");
    }
}
