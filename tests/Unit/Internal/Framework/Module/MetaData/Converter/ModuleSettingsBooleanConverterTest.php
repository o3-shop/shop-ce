<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Converter;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Converter\ModuleSettingsBooleanConverter;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use PHPUnit\Framework\TestCase;

class ModuleSettingsBooleanConverterTest extends TestCase
{
    public function convertToTrueDataProvider()
    {

        return [
            ['true'],
            ['True'],
            ['1'],
            [1],
            [true],
        ];
    }

    /**
     * @param $value
     * @dataProvider convertToTrueDataProvider
     */
    public function testConvertToTrue($value): void
    {
        $metaData =
            [
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                        'type' => 'bool', 'value' => $value
                    ],
                ]
            ];
        $converter = new ModuleSettingsBooleanConverter();

        $convertedSettings = $converter->convert($metaData);
        $this->assertTrue($convertedSettings[MetaDataProvider::METADATA_SETTINGS][0]['value']);
    }

    public function convertToFalseDataProvider()
    {

        return [
            ['false'],
            ['False'],
            ['0'],
            [0],
            [false],
        ];
    }

    /**
     * @param $value
     * @dataProvider convertToFalseDataProvider
     */
    public function testConvertToFalse($value): void
    {
        $metaData =
            [
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                        'type' => 'bool', 'value' => $value
                    ],
                ]
            ];
        $converter = new ModuleSettingsBooleanConverter();

        $convertedSettings = $converter->convert($metaData);
        $this->assertFalse($convertedSettings[MetaDataProvider::METADATA_SETTINGS][0]['value']);
    }

    public function whenNothingToConvertDataProvider()
    {
        return [
            [[]],
            [
                [
                    MetaDataProvider::METADATA_SETTINGS => [
                        [
                            'type' => 'str', 'value' => 'any'
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @param array $metaData
     * @dataProvider whenNothingToConvertDataProvider
     */
    public function testWhenNothingToConvert(array $metaData): void
    {
        $converter = new ModuleSettingsBooleanConverter();

        $convertedSettings = $converter->convert($metaData);
        $this->assertSame($metaData, $convertedSettings);
    }
}
