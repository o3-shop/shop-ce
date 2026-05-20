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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\FileSizeLogic;
use PHPUnit\Framework\TestCase;

class FileSizeLogicTest extends TestCase
{
    /** @dataProvider sizesProvider */
    public function testGetFileSizeFormatsAcrossBoundaries($input, string $expected): void
    {
        $this->assertSame($expected, (new FileSizeLogic())->getFileSize($input));
    }

    public function sizesProvider(): array
    {
        return [
            'bytes'        => [512,                    '512 B'],
            'just KB'      => [1024,                   '1.0 KB'],
            'mid KB'       => [1024 * 5 + 256,         '5.2 KB'],
            'just MB'      => [1024 * 1024,            '1.0 MB'],
            'mid MB'       => [(int) (1024 * 1024 * 7.5), '7.5 MB'],
            'just GB'      => [1024 * 1024 * 1024,     '1.0 GB'],
            'over GB'      => [(int) (1024 * 1024 * 1024 * 2.4), '2.4 GB'],
            'zero'         => [0,                      '0 B'],
        ];
    }
}
