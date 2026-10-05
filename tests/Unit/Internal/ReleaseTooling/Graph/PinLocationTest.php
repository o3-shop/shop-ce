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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Graph;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph\PinLocation;
use PHPUnit\Framework\TestCase;

final class PinLocationTest extends TestCase
{
    public function testRequireKeyAccepted(): void
    {
        $pin = new PinLocation('o3-shop/o3-shop', PinLocation::KEY_REQUIRE, '^v1.6.0');
        $this->assertSame('o3-shop/o3-shop', $pin->parentPackage());
        $this->assertSame('require', $pin->key());
        $this->assertSame('^v1.6.0', $pin->constraint());
    }

    public function testRequireDevKeyAccepted(): void
    {
        $pin = new PinLocation('o3-shop/o3-shop', PinLocation::KEY_REQUIRE_DEV, '^1.2.0');
        $this->assertSame('require-dev', $pin->key());
    }

    public function testUnknownKeyRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown composer.json key 'suggest'");
        new PinLocation('o3-shop/o3-shop', 'suggest', '^v1.6.0');
    }

    public function testEmptyKeyRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PinLocation('o3-shop/o3-shop', '', '^v1.6.0');
    }
}
