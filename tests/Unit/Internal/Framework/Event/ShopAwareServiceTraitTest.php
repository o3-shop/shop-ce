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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Event\ShopAwareServiceTrait;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;

class ShopAwareServiceTraitTest extends TestCase
{
    public function testIsActiveReturnsTrueWhenCurrentShopIdIsListed(): void
    {
        $service = $this->makeService();
        $context = $this->createMock(ContextInterface::class);
        $context->method('getCurrentShopId')->willReturn(1);

        $service->setContext($context);
        // Active shops are stored as strings (DI provides them that way).
        $service->setActiveShops(['1', '2', '3']);

        $this->assertTrue($service->isActive());
    }

    public function testIsActiveReturnsFalseWhenCurrentShopIdIsNotListed(): void
    {
        $service = $this->makeService();
        $context = $this->createMock(ContextInterface::class);
        $context->method('getCurrentShopId')->willReturn(99);

        $service->setContext($context);
        $service->setActiveShops(['1', '2', '3']);

        $this->assertFalse($service->isActive());
    }

    public function testIsActiveCoercesShopIdToStringForComparison(): void
    {
        // Setter receives integer-typed shop id from production code; the
        // strval(...) inside the trait must coerce it to match string list.
        $service = $this->makeService();
        $context = $this->createMock(ContextInterface::class);
        $context->method('getCurrentShopId')->willReturn(2);

        $service->setContext($context);
        $service->setActiveShops(['2']); // string

        $this->assertTrue($service->isActive());
    }

    private function makeService(): object
    {
        return new class () {
            use ShopAwareServiceTrait;
        };
    }
}
