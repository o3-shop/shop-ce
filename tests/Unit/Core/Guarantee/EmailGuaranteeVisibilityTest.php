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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Guarantee;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, issue #219).
 *
 * Covers {@see Email::isDurabilityGuaranteeLabelVisible()}, which gates the
 * order-confirmation-email label block on the shop-level master switch AND
 * at least one ordered article being eligible.
 */
class EmailGuaranteeVisibilityTest extends UnitTestCase
{
    public function testNotVisibleWhenShopSwitchIsOff(): void
    {
        $email = $this->makeEmail(false);

        $this->assertFalse($email->isDurabilityGuaranteeLabelVisible($this->orderWithArticle(true)));
    }

    public function testNotVisibleWhenNoOrderedArticleQualifies(): void
    {
        $email = $this->makeEmail(true);

        $this->assertFalse($email->isDurabilityGuaranteeLabelVisible($this->orderWithArticle(false)));
    }

    public function testVisibleWhenSwitchOnAndAtLeastOneOrderedArticleQualifies(): void
    {
        $email = $this->makeEmail(true);

        $this->assertTrue($email->isDurabilityGuaranteeLabelVisible($this->orderWithArticle(true)));
    }

    private function makeEmail(bool $switchOn): Email
    {
        $viewConfig = $this->createMock(ViewConfig::class);
        $viewConfig->method('getDurabilityGuaranteeLabelVisible')->willReturn($switchOn);

        $email = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getViewConfig'])
            ->getMock();
        $email->method('getViewConfig')->willReturn($viewConfig);

        return $email;
    }

    private function orderWithArticle(bool $eligible): Order
    {
        $article = $this->createMock(Article::class);
        $article->method('isDurabilityGuaranteeLabelEligible')->willReturn($eligible);

        $orderArticle = $this->createMock(OrderArticle::class);
        $orderArticle->method('getArticle')->willReturn($article);

        $order = $this->createMock(Order::class);
        $order->method('getOrderArticles')->willReturn([$orderArticle]);

        return $order;
    }
}
