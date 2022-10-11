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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\DataMapper\MetaDataMapper;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\MetaDataSchemaValidatorInterface;
use PHPUnit\Framework\TestCase;

class MetaDataMapperTest extends TestCase
{
    private $metaDataValidatorStub;

    /**
     * @dataProvider missingMetaDataKeysDataProvider
     *
     * @param array $invalidData
     */
    public function testFromDataWillThrowExceptionOnInvalidParameterFormat(array $invalidData)
    {
        $this->expectException(\InvalidArgumentException::class);
        $metaDataDataMapper = new MetaDataMapper($this->metaDataValidatorStub);
        $metaDataDataMapper->fromData($invalidData);
    }

    public function missingMetaDataKeysDataProvider(): array
    {
        return [
            'all mandatory keys are missing'    => [[]],
            'key metaDataVersion is missing'    => [[MetaDataProvider::METADATA_MODULE_DATA => '']],
            'key moduleData version is missing' => [[MetaDataProvider::METADATA_METADATA_VERSION => '']],
        ];
    }

    public function testMetadataFilesMapping()
    {
        $metadata = [
            MetaDataProvider::METADATA_METADATA_VERSION => '0',
            MetaDataProvider::METADATA_FILEPATH         => '',
            MetaDataProvider::METADATA_MODULE_DATA      => [
                MetaDataProvider::METADATA_ID       => 'id',
                MetaDataProvider::METADATA_FILES    => [
                    'name' => 'path',
                ]
            ]
        ];
        $metaDataDataMapper = new MetaDataMapper($this->metaDataValidatorStub);
        $moduleConfiguration = $metaDataDataMapper->fromData($metadata);

        $classes = [];

        foreach ($moduleConfiguration->getClassesWithoutNamespace() as $class) {
            $classes[$class->getShopClass()] = $class->getModuleClass();
        }

        $this->assertSame(
            [
                'name' => 'path',
            ],
            $classes
        );
    }

    public function testSettingPositionIsConvertedToInt(): void
    {
        $metaDataDataMapper = new MetaDataMapper($this->metaDataValidatorStub);
        $moduleConfiguration = $metaDataDataMapper->fromData(
            [
                'metaDataVersion' => '1.1',
                'metaDataFilePath' => 'sdasd',
                'moduleData' => [
                    'id' => 'some',
                    'settings' => [
                        [
                            'name'  => 'setting',
                            'type'  => 'bool',
                            'value' => 'true',
                            'position' => '2'
                        ],
                    ]
                ]
            ]
        );

        $this->assertSame(
            2,
            $moduleConfiguration->getModuleSetting('setting')->getPositionInGroup()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->metaDataValidatorStub = $this->getMockBuilder(MetaDataSchemaValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metaDataValidatorStub->method('validate');
    }
}
