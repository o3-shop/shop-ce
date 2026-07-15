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

use OxidEsales\Eshop\Application\Controller\ArticleDetailsController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, issue #219).
 *
 * Covers {@see ArticleDetailsController::isDurabilityGuaranteeLabelVisible()},
 * which combines the shop-level master switch with the current product's
 * own eligibility so the theme does not have to combine both checks itself.
 */
class ArticleDetailsControllerGuaranteeTest extends UnitTestCase
{
    public function testNotVisibleWhenShopSwitchIsOff(): void
    {
        $controller = $this->makeController(false, true);

        $this->assertFalse($controller->isDurabilityGuaranteeLabelVisible());
    }

    public function testNotVisibleWhenProductIsNotEligible(): void
    {
        $controller = $this->makeController(true, false);

        $this->assertFalse($controller->isDurabilityGuaranteeLabelVisible());
    }

    public function testVisibleWhenSwitchOnAndProductEligible(): void
    {
        $controller = $this->makeController(true, true);

        $this->assertTrue($controller->isDurabilityGuaranteeLabelVisible());
    }

    private function makeController(bool $switchOn, bool $productEligible): ArticleDetailsController
    {
        $viewConfig = $this->createMock(ViewConfig::class);
        $viewConfig->method('getDurabilityGuaranteeLabelVisible')->willReturn($switchOn);

        $product = $this->createMock(Article::class);
        $product->method('isDurabilityGuaranteeLabelEligible')->willReturn($productEligible);

        $controller = $this->getMockBuilder(ArticleDetailsController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getViewConfig', 'getProduct'])
            ->getMock();
        $controller->method('getViewConfig')->willReturn($viewConfig);
        $controller->method('getProduct')->willReturn($product);

        return $controller;
    }
}
