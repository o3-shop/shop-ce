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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TruncateLogic;
use PHPUnit\Framework\TestCase;

/**
 * Class TruncateLogicTest
 */
class TruncateLogicTest extends TestCase
{

    /** @var TruncateLogic */
    private $truncateLogic;

    public function setUp(): void
    {
        $this->truncateLogic = new TruncateLogic();
    }

    /**
     * @param string $string
     * @param string $expected
     * @param array  $parameters
     *
     * @dataProvider truncateProvider
     */
    public function testTruncate(string $string, string $expected, array $parameters = []): void
    {
        $length = isset($parameters['length']) ? $parameters['length'] : 80;
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '...';
        $breakWords = isset($parameters['breakWords']) ? $parameters['breakWords'] : false;

        $this->assertEquals($expected, $this->truncateLogic->truncate($string, $length, $suffix, $breakWords));
    }

    /**
     * @return array
     */
    public function truncateProvider(): array
    {
        return [
            [
                "Duis iaculis pellentesque felis, et pulvinar elit lacinia at. Suspendisse dapibus pulvinar sem vitae.",
                "Duis iaculis pellentesque felis, et pulvinar elit lacinia at. Suspendisse..."
            ],
            [
                "Duis iaculis &#039;pellentesque&#039; felis, et &quot;pulvinar&quot; elit lacinia at. Suspendisse dapibus pulvinar sem vitae.",
                "Duis iaculis &#039;pellentesque&#039; felis, et &quot;pulvinar&quot; elit lacinia at. Suspendisse..."
            ],
            [
                "&#039;Duis&#039; &#039;iaculis&#039; &#039;pellentesque&#039; felis, et &quot;pulvinar&quot; elit lacinia at. Suspendisse dapibus pulvinar sem vitae.",
                "&#039;Duis&#039; &#039;iaculis&#039; &#039;pellentesque&#039; felis, et &quot;pulvinar&quot; elit lacinia at...."
            ],
        ];
    }

    /**
     * @param string $string
     * @param string $expected
     * @param array  $parameters
     *
     * @dataProvider truncateProviderWithLength
     */
    public function testTruncateWithLength(string $string, string $expected, array $parameters = []): void
    {
        $length = isset($parameters['length']) ? $parameters['length'] : 80;
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '...';
        $breakWords = isset($parameters['breakWords']) ? $parameters['breakWords'] : false;

        $this->assertEquals($expected, $this->truncateLogic->truncate($string, $length, $suffix, $breakWords));
    }

    /**
     * @return array
     */
    public function truncateProviderWithLength(): array
    {
        return [
            [
                "Duis iaculis pellentesque felis, et pulvinar elit.",
                "Duis iaculis...",
                ['length' => 20]
            ],
            [
                "Duis iaculis &#039;pellentesque&#039; felis, et &quot;pulvinar&quot; elit.",
                "Duis iaculis...",
                ['length' => 20]
            ],
            [
                "&#039;Duis&#039; &#039;iaculis&#039; &#039;pellentesque&#039; felis, et &quot;pulvinar&quot; elit.",
                "&#039;Duis&#039; &#039;iaculis&#039;...",
                ['length' => 20]
            ],
        ];
    }

    /**
     * @param string $string
     * @param string $expected
     * @param array  $parameters
     *
     * @dataProvider truncateProviderWithSuffix
     */
    public function testTruncateWithSuffix(string $string, string $expected, array $parameters = []): void
    {
        $length = isset($parameters['length']) ? $parameters['length'] : 80;
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '...';
        $breakWords = isset($parameters['breakWords']) ? $parameters['breakWords'] : false;

        $this->assertEquals($expected, $this->truncateLogic->truncate($string, $length, $suffix, $breakWords));
    }

    /**
     * @return array
     */
    public function truncateProviderWithSuffix(): array
    {
        return [
            [
                "Duis iaculis pellentesque felis, et pulvinar elit lacinia at. Suspendisse dapibus pulvinar sem vitae.",
                "Duis iaculis pellentesque felis, et pulvinar elit lacinia at. Suspendisse (...)",
                ['suffix' => ' (...)']
            ],
        ];
    }

    /**
     * @param string $string
     * @param string $expected
     * @param array  $parameters
     *
     * @dataProvider truncateProviderWithBreakWords
     */
    public function testTruncateWithBreakWords(string $string, string $expected, array $parameters = []): void
    {
        $length = isset($parameters['length']) ? $parameters['length'] : 80;
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '...';
        $breakWords = isset($parameters['breakWords']) ? $parameters['breakWords'] : false;

        $this->assertEquals($expected, $this->truncateLogic->truncate($string, $length, $suffix, $breakWords));
    }

    /**
     * @return array
     */
    public function truncateProviderWithBreakWords(): array
    {
        return [
            [
                "Duis iaculis pellentesque felis, et pulvinar elit lacinia at. Suspendisse dapibus pulvinar sem vitae.",
                "Duis iaculis pellentesque felis, et pulvinar elit lacinia at. Suspendisse dap...",
                ['breakWords' => true]
            ],
        ];
    }
}
