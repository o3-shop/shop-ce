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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Tag;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\BumpLevel;
use PHPUnit\Framework\TestCase;

class BumpLevelTest extends TestCase
{
    /** @dataProvider validKindProvider */
    public function testFromStringAcceptsKindKeywords(string $raw, string $expectedKind): void
    {
        $level = BumpLevel::fromString($raw);
        $this->assertSame($expectedKind, $level->kind());
        $this->assertNull($level->exactVersion());
        $this->assertFalse($level->isExact());
    }

    public function validKindProvider(): array
    {
        return [
            ['patch', BumpLevel::KIND_PATCH],
            ['minor', BumpLevel::KIND_MINOR],
            ['major', BumpLevel::KIND_MAJOR],
        ];
    }

    /** @dataProvider validExactProvider */
    public function testFromStringAcceptsExactVersions(string $version): void
    {
        $level = BumpLevel::fromString($version);
        $this->assertSame(BumpLevel::KIND_EXACT, $level->kind());
        $this->assertSame($version, $level->exactVersion());
        $this->assertTrue($level->isExact());
    }

    public function validExactProvider(): array
    {
        return [
            ['v1.0.0'],
            ['v0.1.0'],
            ['v1.6.1-RC1'],
            ['v2.0.0-alpha.1'],
            ['v10.20.30'],
        ];
    }

    /** @dataProvider invalidProvider */
    public function testFromStringRejectsMalformedInput(string $raw): void
    {
        $this->expectException(InvalidArgumentException::class);
        BumpLevel::fromString($raw);
    }

    public function invalidProvider(): array
    {
        return [
            'empty' => [''],
            'unknown keyword' => ['huge'],
            'missing v prefix' => ['1.0.0'],
            'two-segment' => ['v1.0'],
            'four-segment' => ['v1.0.0.0'],
            'random' => ['bogus'],
            'whitespace inside' => ['v 1.0.0'],
        ];
    }

    public function testStaticFactoriesProduceCorrectKinds(): void
    {
        $this->assertSame(BumpLevel::KIND_PATCH, BumpLevel::patch()->kind());
        $this->assertSame(BumpLevel::KIND_MINOR, BumpLevel::minor()->kind());
        $this->assertSame(BumpLevel::KIND_MAJOR, BumpLevel::major()->kind());
        $this->assertSame(BumpLevel::KIND_EXACT, BumpLevel::exact('v3.2.1')->kind());
    }

    public function testExactFactoryRejectsMalformed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BumpLevel::exact('not-a-version');
    }
}
