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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Escaper;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Escaper\EscaperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Escaper\HexEntityEscaper;
use Twig\Environment;

/**
 * Class HexEntityEscaperTest
 */
class HexEntityEscaperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EscaperInterface */
    private $escaper;

    /** @var Environment */
    private $environment;

    public function setUp(): void
    {
        parent::setUp();
        $this->escaper = new HexEntityEscaper();
        $this->environment = $this->createMock(Environment::class);
    }

    /**
     * @return array
     */
    public function escapeProvider(): array
    {
        return [
            [
                "A 'quote' is <b>bold</b>",
                '&#x41;&#x20;&#x27;&#x71;&#x75;&#x6f;&#x74;&#x65;&#x27;&#x20;&#x69;&#x73;&#x20;&#x3c;&#x62;&#x3e;&#x62;&#x6f;&#x6c;&#x64;&#x3c;&#x2f;&#x62;&#x3e;',
            ],
        ];
    }

    /**
     * @param string $string
     * @param string $expected
     *
     * @dataProvider escapeProvider
     */
    public function testEscape($string, $expected)
    {
        $this->assertEquals($expected, $this->escaper->escape($this->environment, $string, 'UTF-8'));
    }

    public function testGetStrategy()
    {
        $this->assertEquals('hexentity', $this->escaper->getStrategy());
    }
}
