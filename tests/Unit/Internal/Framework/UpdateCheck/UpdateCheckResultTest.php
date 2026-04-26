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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\UpdateCheck;

use OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckResult;
use PHPUnit\Framework\TestCase;

class UpdateCheckResultTest extends TestCase
{
    public function testEmptyReturnsDefaults(): void
    {
        $result = UpdateCheckResult::empty();

        $this->assertFalse($result->isCoreUpdateAvailable());
        $this->assertSame('', $result->getLatestCoreVersion());
        $this->assertSame('', $result->getUpdateLink());
        $this->assertSame([], $result->getOutdatedModules());
    }

    public function testConstructorSetsAllFields(): void
    {
        $modules = [
            ['id' => 'my-module', 'latest_version' => '2.0.0', 'url' => 'https://example.com/my-module'],
        ];

        $result = new UpdateCheckResult(true, 'v1.6.0', 'https://example.com/update', $modules);

        $this->assertTrue($result->isCoreUpdateAvailable());
        $this->assertSame('v1.6.0', $result->getLatestCoreVersion());
        $this->assertSame('https://example.com/update', $result->getUpdateLink());
        $this->assertCount(1, $result->getOutdatedModules());
        $this->assertSame('my-module', $result->getOutdatedModules()[0]['id']);
    }

    public function testDefaultConstructorMatchesEmpty(): void
    {
        $default = new UpdateCheckResult();
        $empty = UpdateCheckResult::empty();

        $this->assertEquals($default->isCoreUpdateAvailable(), $empty->isCoreUpdateAvailable());
        $this->assertEquals($default->getLatestCoreVersion(), $empty->getLatestCoreVersion());
        $this->assertEquals($default->getUpdateLink(), $empty->getUpdateLink());
        $this->assertEquals($default->getOutdatedModules(), $empty->getOutdatedModules());
    }
}
