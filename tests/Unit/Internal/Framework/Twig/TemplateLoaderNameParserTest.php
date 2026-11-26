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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\TemplateLoaderNameParser;
use PHPUnit\Framework\TestCase;

/**
 * Class TemplateLoaderNameParserTest
 */
class TemplateLoaderNameParserTest extends TestCase
{
    /**
     * @var TemplateLoaderNameParser
     */
    private $templateLoaderNameParser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->templateLoaderNameParser = new TemplateLoaderNameParser();
    }

    /**
     * @param string $name
     * @param array  $expected
     *
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TemplateLoaderNameParser::isValidName
     *
     * @dataProvider getInvalidNameTests
     * @dataProvider getValidNameTests
     */
    public function testIsValidName(string $name, array $expected): void
    {
        $this->assertEquals($this->templateLoaderNameParser->isValidName($name), $expected['valid']);
    }

    /**
     * @param string $name
     * @param array  $expected
     *
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TemplateLoaderNameParser::getLoaderName
     *
     * @dataProvider getValidNameTests
     */
    public function testGetLoaderName(string $name, array $expected): void
    {
        $this->assertEquals($this->templateLoaderNameParser->getLoaderName($name), $expected['loaderName']);
    }

    /**
     * @param string $name
     * @param array  $expected
     *
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TemplateLoaderNameParser::getValue
     *
     * @dataProvider getValidNameTests
     */
    public function testGetValue(string $name, array $expected): void
    {
        $this->assertEquals($this->templateLoaderNameParser->getValue($name), $expected['value']);
    }

    /**
     * @param string $name
     * @param array  $expected
     *
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TemplateLoaderNameParser::getParameters
     *
     * @dataProvider getValidNameTests
     */
    public function testGetParameters(string $name, array $expected): void
    {
        $this->assertEquals($this->templateLoaderNameParser->getParameters($name), $expected['parameters']);
    }

    /**
     * @param string $name
     * @param array  $expected
     *
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Twig\TemplateLoaderNameParser::getKey
     *
     * @dataProvider getValidNameTests
     */
    public function testGetKey(string $name, array $expected): void
    {
        $this->assertEquals($this->templateLoaderNameParser->getKey($name), $expected['key']);
    }

    /**
     * @return array
     */
    public function getInvalidNameTests(): array
    {
        $invalidNames = [
            '',
            'foo',
            'foo::bar',
            'foo:bar',
            'foo:bar:foo',
            '?foo',
            '?foo=param&bar=param',
            'foo?bar=param',
            'foo::bar?key=param',
            'foo:bar:foo?key=param',
        ];

        return array_map(
            function ($name) {
                return [$name, ['valid' => false]];
            },
            $invalidNames
        );
    }

    /**
     * @return array
     */
    public function getValidNameTests(): array
    {
        return [
            [
                'foo::bar::xy',
                [
                    'valid' => true,
                    'loaderName' => 'foo',
                    'key' => 'bar',
                    'value' => 'xy',
                    'parameters' => [],
                ],
            ],
            [
                'foo::bar::xy?key=param&anotherKey=anotherParam',
                [
                    'valid' => true,
                    'loaderName' => 'foo',
                    'key' => 'bar',
                    'value' => 'xy',
                    'parameters' => [
                        'key' => 'param',
                        'anotherKey' => 'anotherParam',
                    ],
                ],
            ],
        ];
    }
}
