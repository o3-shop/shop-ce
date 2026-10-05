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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Config\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Event\ShopConfigurationChangedEvent;
use PHPUnit\Framework\TestCase;

class ShopConfigurationChangedEventTest extends TestCase
{
    public function testGettersReturnConstructorArguments(): void
    {
        $event = new ShopConfigurationChangedEvent('blShowFinalStep', 7);

        $this->assertSame('blShowFinalStep', $event->getConfigurationVariable());
        $this->assertSame(7, $event->getShopId());
    }

    public function testNameConstantMatchesClass(): void
    {
        $this->assertSame(ShopConfigurationChangedEvent::class, ShopConfigurationChangedEvent::NAME);
    }
}
