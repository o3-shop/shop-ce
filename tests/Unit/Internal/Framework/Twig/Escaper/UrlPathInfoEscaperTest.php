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
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Escaper\UrlPathInfoEscaper;
use Twig\Environment;

/**
 * Class UrlPathInfoEscaperTest
 */
class UrlPathInfoEscaperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EscaperInterface */
    private $escaper;

    /** @var Environment */
    private $environment;

    public function setUp(): void
    {
        parent::setUp();
        $this->escaper = new UrlPathInfoEscaper();
        $this->environment = $this->createMock(Environment::class);
    }

    /**
     * @return array
     */
    public function escapeProvider(): array
    {
        return [
            [
                'http://hans:geheim@www.example.org:80/demo/example.cgi?land=de&stadt=aa',
                'http%3A//hans%3Ageheim%40www.example.org%3A80/demo/example.cgi%3Fland%3Dde%26stadt%3Daa',
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
        $this->assertEquals('urlpathinfo', $this->escaper->getStrategy());
    }
}
