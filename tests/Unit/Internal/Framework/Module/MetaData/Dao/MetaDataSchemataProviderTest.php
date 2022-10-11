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

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataSchemataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataVersionException;
use PHPUnit\Framework\TestCase;

/**
 * Class MetaDataSchemataProviderTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Dao
 */
class MetaDataSchemataProviderTest extends TestCase
{
    private $metaDataSchemata;
    private $schemaVersion20;
    private $schemaVersion21;

    public function testGetMetaDataSchemata()
    {
        $metaDataSchemata = new MetaDataSchemataProvider($this->metaDataSchemata);

        $actualSchemata = $metaDataSchemata->getMetaDataSchemata();

        $this->assertEquals($this->metaDataSchemata, $actualSchemata);
    }

    public function testGetMetadataSchemaForVersion()
    {
        $metaDataSchema = new MetaDataSchemataProvider($this->metaDataSchemata);
        $actualSchema20 = $metaDataSchema->getMetaDataSchemaForVersion('2.0');
        $actualSchema21 = $metaDataSchema->getMetaDataSchemaForVersion('2.1');

        $this->assertEquals($this->schemaVersion20, $actualSchema20);
        $this->assertEquals($this->schemaVersion21, $actualSchema21);
    }

    public function testGetFlippedMetadataSchemaForVersionThrowsExceptionOnUnsupportedVersion()
    {
        $unsupportedVersion = '0.0';
        $metaDataSchema = new MetaDataSchemataProvider($this->metaDataSchemata);

        $this->expectException(UnsupportedMetaDataVersionException::class);
        $metaDataSchema->getFlippedMetaDataSchemaForVersion($unsupportedVersion);
    }

    public function testGetFlippedMetadataSchemaForVersion()
    {
        $expectedSchema20 = [
            '20only'    => 0,
            'subSchema' => [
                'subKey1' => 0,
                'subKey2' => 1
            ],
        ];
        $metaDataSchema = new MetaDataSchemataProvider($this->metaDataSchemata);

        $actualSchema20 = $metaDataSchema->getFlippedMetaDataSchemaForVersion('2.0');

        $this->assertSame($expectedSchema20, $actualSchema20);
    }

    public function testGetMetadataSchemaForVersionThrowsExceptionOnUnsupportedVersion()
    {
        $unsupportedVersion = '0.0';
        $metaDataSchema = new MetaDataSchemataProvider($this->metaDataSchemata);

        $this->expectException(UnsupportedMetaDataVersionException::class);
        $metaDataSchema->getMetaDataSchemaForVersion($unsupportedVersion);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaVersion20 = [
            '20only',
            'subSchema' =>
                ['subKey1',
                 'subKey2',
                ],
        ];
        $this->schemaVersion21 = [
            '21only',
            'subSchema' =>
                ['subKey1',
                 'subKey2',
                ],
        ];
        $this->metaDataSchemata = [
            '2.0' => $this->schemaVersion20,
            '2.1' => $this->schemaVersion21,
        ];
    }
}
