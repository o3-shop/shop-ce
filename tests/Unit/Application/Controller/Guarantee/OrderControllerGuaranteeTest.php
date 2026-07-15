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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Guarantee;

use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, issue #219).
 *
 * Covers {@see OrderController::isDurabilityGuaranteeLabelVisible()}, which
 * gates the order-final-page mandatory information (§ 312j Abs. 2 BGB n.F.)
 * on the shop-level master switch AND at least one basket item being
 * eligible for the durability-guarantee label.
 */
class OrderControllerGuaranteeTest extends UnitTestCase
{
    public function testNotVisibleWhenShopSwitchIsOff(): void
    {
        $controller = $this->makeController(false, $this->basketWithEligibleItem());

        $this->assertFalse($controller->isDurabilityGuaranteeLabelVisible());
    }

    public function testNotVisibleWhenBasketIsEmpty(): void
    {
        $basket = $this->createMock(Basket::class);
        $basket->method('getContents')->willReturn([]);

        $controller = $this->makeController(true, $basket);

        $this->assertFalse($controller->isDurabilityGuaranteeLabelVisible());
    }

    public function testNotVisibleWhenNoBasketItemQualifies(): void
    {
        $controller = $this->makeController(true, $this->basketWithEligibleItem(false));

        $this->assertFalse($controller->isDurabilityGuaranteeLabelVisible());
    }

    public function testNotVisibleWhenThereIsNoBasket(): void
    {
        $controller = $this->makeController(true, false);

        $this->assertFalse($controller->isDurabilityGuaranteeLabelVisible());
    }

    public function testVisibleWhenSwitchOnAndAtLeastOneItemQualifies(): void
    {
        $controller = $this->makeController(true, $this->basketWithEligibleItem(true));

        $this->assertTrue($controller->isDurabilityGuaranteeLabelVisible());
    }

    /**
     * @param Basket|false $basket
     */
    private function makeController(bool $switchOn, $basket): OrderController
    {
        $viewConfig = $this->createMock(ViewConfig::class);
        $viewConfig->method('getDurabilityGuaranteeLabelVisible')->willReturn($switchOn);

        $controller = $this->getMockBuilder(OrderController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getViewConfig', 'getBasket'])
            ->getMock();
        $controller->method('getViewConfig')->willReturn($viewConfig);
        $controller->method('getBasket')->willReturn($basket);

        return $controller;
    }

    private function basketWithEligibleItem(bool $eligible = true): Basket
    {
        $article = $this->createMock(Article::class);
        $article->method('isDurabilityGuaranteeLabelEligible')->willReturn($eligible);

        $basketItem = $this->createMock(BasketItem::class);
        $basketItem->method('getArticle')->willReturn($article);

        $basket = $this->createMock(Basket::class);
        $basket->method('getContents')->willReturn([$basketItem]);

        return $basket;
    }
}
