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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Validator;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataKeyException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataSchemataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataValueTypeException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataVersionException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\MetaDataSchemaValidator;
use PHPUnit\Framework\TestCase;

class MetaDataSchemaValidatorTest extends TestCase
{
    private $metaDataSchemata;
    private $metaDataSchemaVersion20;
    private $metaDataSchemaVersion21;

    public function testValidateThrowsExceptionOnUnsupportedMetaDataVersion()
    {
        $metaDataToValidate = [];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $this->expectException(UnsupportedMetaDataVersionException::class);
        $validator->validate('path/to/metadata.php', '1.2', $metaDataToValidate);
    }

    public function testValidateUnsupportedMetaDataKey()
    {
        $this->expectException(UnsupportedMetaDataKeyException::class);

        $metaDataToValidate = [
            'smartyPluginDirectories' => [],
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    /**
     * This test covers metaData sections like 'blocks' or 'settings', which have their own well defined subKeys
     */
    public function testValidateUnsupportedMetaDataSubKey()
    {
        $this->expectException(UnsupportedMetaDataKeyException::class);

        $metaDataToValidate = [
            '20only'   => 'value',
            'section1' => [
                [
                    'subkey1' => 'value1',
                    'subkey2' => 'value1',
                ],
                [
                    'subkey1'        => 'value2',
                    'unsupportedKey' => 'value2',
                ],
            ]
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    /**
     * This test covers metaData sections like 'extend', or 'templates', which have their custom subKeys
     */
    public function testExcludedSectionItemValidation()
    {
        $metaDataToValidate = [
            '20only'                                             => 'value',
            'section1'                                           => [
                [
                    'subKey1' => 'value1',
                    'subKey2' => 'value1',
                ],
                [
                    'subKey1' => 'value2',
                    'subKey2' => 'value2',
                ],
            ],
            MetaDataProvider::METADATA_EXTEND                    => [
                'excludedsubkey1' => 'value2',
                'excludedsubkey2' => 'value2',
            ],
            MetaDataProvider::METADATA_TEMPLATES                 => [
                'excludedsectionkey1' => 'value1',
                'excludedsectionkey2' => [
                    'excludedsubkey1' => 'value2',
                    'excludedsubkey2' => 'value2',
                ]
            ]
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    public function testValidateIsCaseSensitive()
    {
        $this->expectException(UnsupportedMetaDataKeyException::class);

        $metaDataToValidate = [
            '20ONLY'   => 'value', // This UPPERCASE key will not validate
            'section1' => [
                [
                    'subkey1' => 'value1',
                    'subkey2' => 'value1',
                ],
                [
                    'subkey1' => 'value2',
                    'subkey2' => 'value2',
                ],
            ]
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    public function testValidateThrowsExceptionOnUnsupportedMetaDataValueType()
    {
        $metaDataToValidate = [
            '20only' => new \stdClass(),
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $this->expectException(UnsupportedMetaDataValueTypeException::class);
        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    public function testValidateThrowsNoExceptionOnIncompleteFirstLevel()
    {
        $metaDataToValidate = [
            // missing '20only'        => 'value',
            'section1' => [
                [
                    'subKey1' => 'value1',
                    'subKey2' => 'value1'
                ],
            ]
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    public function testValidateThrowsNoExceptionOnIncompleteSecondLevel()
    {
        $metaDataToValidate = [
            '20only'   => 'value',
            'section1' => [
                [
                    // missing 'subKey1' => 'value1',
                    'subKey2' => 'value1'
                ],
            ]
        ];

        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);
        $validator = new MetaDataSchemaValidator($metaDataSchemata);

        $validator->validate('path/to/metadata.php', '2.0', $metaDataToValidate);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->metaDataSchemaVersion20 = [
            '20only',
            'section1' =>
                ['subKey1',
                 'subKey2',
                ],
            'extend',
            'templates',
        ];
        $this->metaDataSchemaVersion21 = [
            '21only',
            'section1' =>
                ['subKey1',
                 'subKey2',
                ],
            'extend',
            'templates',
        ];
        $this->metaDataSchemata = [
            '2.0' => $this->metaDataSchemaVersion20,
            '2.1' => $this->metaDataSchemaVersion21,
        ];
    }
}
