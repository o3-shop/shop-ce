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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setting\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use PHPUnit\Framework\TestCase;

class SettingChangedEventTest extends TestCase
{
    public function testGettersReturnConstructorArguments(): void
    {
        $event = new SettingChangedEvent('mySetting', 7, 'mymodule');

        $this->assertSame('mySetting', $event->getSettingName());
        $this->assertSame(7, $event->getShopId());
        $this->assertSame('mymodule', $event->getModuleId());
    }

    public function testNameConstantMatchesClass(): void
    {
        $this->assertSame(SettingChangedEvent::class, SettingChangedEvent::NAME);
    }
}
