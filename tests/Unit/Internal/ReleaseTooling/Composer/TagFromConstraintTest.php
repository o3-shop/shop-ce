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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Composer;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\TagFromConstraint;
use PHPUnit\Framework\TestCase;

class TagFromConstraintTest extends TestCase
{
    /** @dataProvider resolvableProvider */
    public function testResolvesToCanonicalVPrefixedTag(string $input, string $expected): void
    {
        $this->assertSame($expected, TagFromConstraint::resolve($input));
    }

    public function resolvableProvider(): array
    {
        return [
            'exact with v'           => ['v1.6.0',           'v1.6.0'],
            'exact without v'        => ['1.6.0',            'v1.6.0'],
            'caret with v'           => ['^v1.3.0',          'v1.3.0'],
            'caret without v'        => ['^1.3.0',           'v1.3.0'],
            'tilde with v'           => ['~v2.6.34',         'v2.6.34'],
            'tilde without v'        => ['~2.6.34',          'v2.6.34'],
            'pre-release with v'     => ['v1.6.1-RC1',       'v1.6.1-RC1'],
            'pre-release without v'  => ['1.6.1-RC1',        'v1.6.1-RC1'],
            'caret pre-release'      => ['^v1.6.1-RC1',      'v1.6.1-RC1'],
            'whitespace padded'      => ['  ^v1.3.0  ',      'v1.3.0'],
            'large numbers'          => ['v10.20.30',        'v10.20.30'],
        ];
    }

    /** @dataProvider unresolvableProvider */
    public function testReturnsNullForUnresolvableShape(string $input): void
    {
        $this->assertNull(TagFromConstraint::resolve($input));
    }

    public function unresolvableProvider(): array
    {
        return [
            'wildcard'        => ['*'],
            'star with v'     => ['v*'],
            'dev branch'      => ['dev-master'],
            'dev branch v'    => ['dev-b-1.6'],
            'two-segment'     => ['v1.6'],
            'four-segment'    => ['v1.6.0.1'],
            'range'           => ['>=1.0,<2.0'],
            'or'              => ['^1.0.0 || ^2.0.0'],
            'empty'           => [''],
            'whitespace only' => ['   '],
            'random'          => ['huh'],
        ];
    }
}
