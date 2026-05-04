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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\EshopCommunity\Core\DynamicPropertiesTrait;
use PHPUnit\Framework\TestCase;

class DynamicPropertiesTraitTest extends TestCase
{
    private function makeContainer(): object
    {
        return new class () {
            use DynamicPropertiesTrait;
        };
    }

    public function testSetAndGetRoundtrip(): void
    {
        $obj = $this->makeContainer();
        $obj->foo = 'bar';
        $this->assertSame('bar', $obj->foo);

        $obj->n = 42;
        $this->assertSame(42, $obj->n);
    }

    public function testIssetReturnsTrueOnlyForKeysWithExplicitlySetValues(): void
    {
        $obj = $this->makeContainer();
        $this->assertFalse(isset($obj->absent));
        $obj->present = 'x';
        $this->assertTrue(isset($obj->present));
    }

    public function testUnsetRemovesProperty(): void
    {
        $obj = $this->makeContainer();
        $obj->temp = 'value';
        $this->assertTrue(isset($obj->temp));
        unset($obj->temp);
        $this->assertFalse(isset($obj->temp));
    }

    public function testGetEmitsNoticeAndReturnsNullForUnknownProperty(): void
    {
        $obj = $this->makeContainer();
        // PHP 7+ converts the trigger_error notice to an exception under
        // the testing-library's strict error handler. Catch the notice
        // message to confirm it identifies the missing property.
        $captured = null;
        set_error_handler(function ($severity, $message) use (&$captured) {
            $captured = $message;
            return true;
        }, E_USER_NOTICE | E_NOTICE);

        try {
            $value = $obj->never_set;
        } finally {
            restore_error_handler();
        }

        $this->assertNull($value);
        $this->assertNotNull($captured);
        $this->assertStringContainsString('never_set', $captured);
    }
}
