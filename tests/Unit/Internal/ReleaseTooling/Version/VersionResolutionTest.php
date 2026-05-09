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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Version;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\VersionResolution;
use PHPUnit\Framework\TestCase;

final class VersionResolutionTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $r = new VersionResolution(
            'o3-shop/shop-ce',
            VersionResolution::CASE_USABLE_TAG,
            'v1.6.0',
            ['some note'],
            'v1.6.0'
        );

        $this->assertSame('o3-shop/shop-ce', $r->package());
        $this->assertSame(VersionResolution::CASE_USABLE_TAG, $r->case());
        $this->assertSame('v1.6.0', $r->chosenVersion());
        $this->assertSame(['some note'], $r->notes());
        $this->assertSame('v1.6.0', $r->latestTag());
        $this->assertFalse($r->needsNewTag());
    }

    public function testNeedsNewTagTrueForCutNewTagCase(): void
    {
        $r = new VersionResolution(
            'o3-shop/shop-facts',
            VersionResolution::CASE_NEEDS_NEW_TAG,
            null,
            [],
            'v1.0.4'
        );
        $this->assertTrue($r->needsNewTag());
        $this->assertNull($r->chosenVersion());
        $this->assertSame('v1.0.4', $r->latestTag());
    }

    public function testInvalidCaseRejectedAtConstruction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown VersionResolution case '99'");
        new VersionResolution('o3-shop/shop-ce', 99, 'v1.6.0');
    }

    public function testEmptyChosenVersionRejectedForNonCutCase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a non-empty chosen version');
        new VersionResolution('o3-shop/shop-ce', VersionResolution::CASE_UNCHANGED, '');
    }

    public function testNullChosenVersionRejectedForNonCutCase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a non-empty chosen version');
        new VersionResolution('o3-shop/shop-ce', VersionResolution::CASE_USABLE_TAG, null);
    }

    public function testNonNullChosenVersionRejectedForCutCase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CASE_NEEDS_NEW_TAG must leave chosenVersion null');
        new VersionResolution('o3-shop/shop-ce', VersionResolution::CASE_NEEDS_NEW_TAG, 'v1.6.0');
    }
}
