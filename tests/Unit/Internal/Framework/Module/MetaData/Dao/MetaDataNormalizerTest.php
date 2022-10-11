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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataNormalizer;
use PHPUnit\Framework\TestCase;

class MetaDataNormalizerTest extends TestCase
{
    public function testNormalizeMetaData()
    {
        $metaData =
            [
                'id'          => 'value1',
                'settings'    => [
                    [
                        'constraints' => 'value1',
                    ],
                ],
            ];
        $expectedNormalizedData = [
            'id'          => 'value1',
            'settings'    => [
                [
                    'constraints' => ['value1'],
                ],
            ],
        ];

        $metaDataNormalizer = new MetaDataNormalizer();
        $normalizedData = $metaDataNormalizer->normalizeData($metaData);

        $this->assertEquals($expectedNormalizedData, $normalizedData);
    }

    public function testNormalizerConvertsModuleSettingConstraintsToArray()
    {
        $metadata = [
            'settings' => [
                ['constraints' => '1|2|3'],
                ['constraints' => 'le|la|les'],
            ]
        ];

        $this->assertSame(
            [
                'settings' => [
                    ['constraints' => ['1', '2', '3']],
                    ['constraints' => ['le', 'la', 'les']],
                ]
            ],
            (new MetaDataNormalizer())->normalizeData($metadata)
        );
    }

    /**
     * @dataProvider multiLanguageFieldDataProvider
     */
    public function testNormalizerConvertsMultiLanguageFieldToArrayWithDefaultLanguageIfItIsString(string $fieldName, string $value)
    {
        $metadata = [
            $fieldName => $value,
        ];

        $this->assertSame(
            [
                $fieldName => [
                    'en' => $value,
                ]
            ],
            (new MetaDataNormalizer())->normalizeData($metadata)
        );
    }

    /**
     * @dataProvider multiLanguageFieldDataProvider
     */
    public function testNormalizerConvertsMultiLanguageFieldToArrayWithCustomLanguageIfItIsStringAndLangOptionIsSet(string $fieldName, string $value)
    {
        $metadata = [
            $fieldName => $value,
            'lang'  => 'esperanto',
        ];

        $this->assertSame(
            [
                $fieldName => [
                    'esperanto' => $value,
                ],
                'lang'  => 'esperanto',
            ],
            (new MetaDataNormalizer())->normalizeData($metadata)
        );
    }

    public function multiLanguageFieldDataProvider(): array
    {
        return [
            ['title', 'some value'],
            ['description', 'some value'],
        ];
    }
}
