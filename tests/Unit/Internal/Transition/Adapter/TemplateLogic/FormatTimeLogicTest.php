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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\FormatTimeLogic;
use PHPUnit\Framework\TestCase;

class FormatTimeLogicTest extends TestCase
{
    /** @dataProvider secondsProvider */
    public function testGetFormattedTimeReturnsHhMmSs(int $seconds, string $expected): void
    {
        $this->assertSame($expected, (new FormatTimeLogic())->getFormattedTime($seconds));
    }

    public function secondsProvider(): array
    {
        return [
            'zero'           => [0,         '00:00:00'],
            'just seconds'   => [9,         '00:00:09'],
            'just minutes'   => [125,       '00:02:05'],
            'one hour'       => [3600,      '01:00:00'],
            'over an hour'   => [3 * 3600 + 17 * 60 + 42, '03:17:42'],
            'two-digit hour' => [10 * 3600, '10:00:00'],
        ];
    }
}
