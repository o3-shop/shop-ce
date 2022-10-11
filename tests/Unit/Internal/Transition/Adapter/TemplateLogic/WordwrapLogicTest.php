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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\WordwrapLogic;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Class WordwrapLogicTest
 */
class WordwrapLogicTest extends UnitTestCase
{

    /** @var WordwrapLogic */
    private $wordWrapLogic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordWrapLogic = new WordwrapLogic();
    }

    /**
     * Provides data for testWordWrapWithNonAscii
     *
     * @return array
     */
    public function nonAsciiProvider(): array
    {
        return [
            ["HÖ\nHÖ", "HÖ HÖ", 2],
            ["HÖ\na\nHÖ\na", "HÖa HÖa", 2, "\n", true],
            ["HÖa\na\nHÖa\na", "HÖaa HÖaa", 3, "\n", true],
            ["HÖa\nHÖa", "HÖa HÖa", 2]
        ];
    }

    /**
     * @param string $expected
     * @param string $string
     * @param int    $length
     * @param string $wrapper
     * @param bool   $cut
     *
     * @dataProvider nonAsciiProvider
     */
    public function testWordWrapWithNonAscii($expected, $string, $length = 80, $wrapper = "\n", $cut = false)
    {
        $this->assertEquals($expected, $this->wordWrapLogic->wordWrap($string, $length, $wrapper, $cut));
    }

    /**
     * Provides data for testWordWrapAscii
     *
     * @return array
     */
    public function asciiProvider(): array
    {
        return [
            ["aaa\naaa", 'aaa aaa', 2],
            ["aa\na\naa\na", 'aaa aaa', 2, "\n", true],
            ["aaa\naaa a", 'aaa aaa a', 5],
            ["aaa\naaa", 'aaa aaa', 5, "\n", true],
            ["  \naaa\n  \naaa", '   aaa    aaa', 2],
            ["  \naa\na \n \naa\na", '   aaa    aaa', 2, "\n", true],
            ["  \naaa  \n aaa", '   aaa    aaa', 5],
            ["  \naaa  \n aaa", '   aaa    aaa', 5, "\n", true],
            [
                "Pellentesq\nue nisl\nnon\ncondimentu\nm cursus.\n \nconsectetu\nr a diam\nsit.\n finibus\ndiam eu\nlibero\nlobortis.\neu   ex  \nsit",
                "Pellentesque nisl non condimentum cursus.\n  consectetur a diam sit.\n finibus diam eu libero lobortis.\neu   ex   sit",
                10,
                "\n",
                true
            ]
        ];
    }

    /**
     * @param string $expected
     * @param string $string
     * @param int    $length
     * @param string $wrapper
     * @param bool   $cut
     *
     * @dataProvider asciiProvider
     */
    public function testWordWrapAscii($expected, $string, $length = 80, $wrapper = "\n", $cut = false)
    {
        $this->assertEquals($expected, $this->wordWrapLogic->wordWrap($string, $length, $wrapper, $cut));
    }
}
