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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\MetaData\Dao;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Converter\MetaDataConverterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\ModuleIdNotValidException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataNormalizer;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\InvalidMetaDataException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\MetaDataValidatorInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class MetaDataProviderTest extends TestCase
{
    use ContainerTrait;

    /** @var MetaDataNormalizer */
    private $metaDataNormalizerStub;

    /** @var BasicContextInterface */
    private $contextStub;

    /** @var MetaDataValidatorInterface */
    private $validatorStub;

    public function testGetDataThrowsExceptionOnNonExistingFile()
    {
        $metaDataProvider = $this->createMetaDataProvider();

        $this->expectException(\InvalidArgumentException::class);
        $metaDataProvider->getData('non existing file');
    }

    public function testGetDataThrowsExceptionOnDirectory()
    {
        $metaDataProvider = $this->createMetaDataProvider();
        $this->expectException(\InvalidArgumentException::class);
        $metaDataProvider->getData(__DIR__);
    }

    /**
     * @dataProvider missingMetaDataVariablesDataProvider
     * @param string $metaDataContent
     */
    public function testGetDataThrowsExceptionOnMissingMetaDataVariables(string $metaDataContent)
    {
        $metaDataFilePath = $this->getPathToTemporaryFile();
        if (false === file_put_contents($metaDataFilePath, $metaDataContent)) {
            throw new \RuntimeException('Could not write to ' . $metaDataFilePath);
        }
        $metaDataProvider = $this->createMetaDataProvider();

        $this->expectException(InvalidMetaDataException::class);
        $metaDataProvider->getData($metaDataFilePath);
    }

    /**
     * @return string
     */
    private function getPathToTemporaryFile(): string
    {
        $temporaryFileHandle = tmpfile();

        return stream_get_meta_data($temporaryFileHandle)['uri'];
    }

    /**
     * @return array
     */
    public function missingMetaDataVariablesDataProvider(): array
    {
        return [
            ['<?php '],
            ['<?php $aModule = [];'],
            ['<?php $sMetadataVersion = "2.0";'],
        ];
    }

    public function testGetDataProvidesConfiguredMetadataId()
    {
        $moduleId = 'test_module';
        $metaDataContent = '<?php
            $sMetadataVersion = "2.0";
            $aModule = ["id" => "test_module"];
        ';

        $metaDataFilePath = $this->getPathToTemporaryFile();
        if (false === file_put_contents($metaDataFilePath, $metaDataContent)) {
            throw new \RuntimeException('Could not write to ' . $metaDataFilePath);
        }
        $metaDataProvider = $this->createMetaDataProvider();
        $metaData = $metaDataProvider->getData($metaDataFilePath);

        $this->assertEquals(
            $moduleId,
            $metaData[MetaDataProvider::METADATA_MODULE_DATA][MetaDataProvider::METADATA_ID]
        );
    }

    public function testGetDataThrowsExceptionIfMetaDataIsNotConfigured()
    {
        $this->expectException(ModuleIdNotValidException::class);
        $metaDataFilePath = $this->getPathToTemporaryFile();
        $metaDataContent = '<?php
            $sMetadataVersion = "2.0";
            $aModule = [];
        ';
        if (false === file_put_contents($metaDataFilePath, $metaDataContent)) {
            throw new \RuntimeException('Could not write to ' . $metaDataFilePath);
        }

        $metaDataProvider = new MetaDataProvider(
            $this->metaDataNormalizerStub,
            $this->contextStub,
            $this->get(MetaDataValidatorInterface::class),
            $this->get(MetaDataConverterInterface::class)
        );
        $metaDataProvider->getData($metaDataFilePath);
    }

    public function testGetDataConvertsBackwardsCompatibleClasses()
    {
        $metaDataFilePath = $this->getPathToTemporaryFile();
        $metaDataContent = '<?php
            $sMetadataVersion = "2.0";
            $aModule = [
                "id" => "MyModuleId",
                "extend" => [
                    "oxarticle"                 => \VendorNamespace\VendorClass1::class,
                    "OXORDER"                   => "VendorNamespace\\VendorClass2",
                    "EShopNamespace\\UserClass" => \VendorNamespace\VendorClass3::class,
                ]
            ];
        ';
        if (false === file_put_contents($metaDataFilePath, $metaDataContent)) {
            throw new \RuntimeException('Could not write to ' . $metaDataFilePath);
        }

        $basicContext = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $basicContext->method('getBackwardsCompatibilityClassMap')->willReturn(
            [
                "oxarticle" => "EShopNamespace\\ArticleClass",
                "oxorder"   => "EShopNamespace\\OrderClass",
            ]
        );
        $metaDataProvider = new MetaDataProvider(
            $this->metaDataNormalizerStub,
            $basicContext,
            $this->validatorStub,
            $this->get(MetaDataConverterInterface::class)
        );
        $metaData = $metaDataProvider->getData($metaDataFilePath);

        $this->assertEquals(
            [
                "EShopNamespace\\ArticleClass" => "VendorNamespace\\VendorClass1",
                "EShopNamespace\\OrderClass"   => "VendorNamespace\\VendorClass2",
                "EShopNamespace\\UserClass"    => "VendorNamespace\\VendorClass3",
            ],
            $metaData['moduleData']['extend']
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->metaDataNormalizerStub = $this->getMockBuilder(MetaDataNormalizer::class)->getMock();
        $this->metaDataNormalizerStub->method('normalizeData')->willReturnArgument(0);
        $this->contextStub = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $this->validatorStub = $this->getMockBuilder(MetaDataValidatorInterface::class)->getMock();
    }

    /**
     * @return MetaDataProvider
     */
    private function createMetaDataProvider(): MetaDataProvider
    {
        $metaDataProvider = new MetaDataProvider(
            $this->metaDataNormalizerStub,
            $this->contextStub,
            $this->validatorStub,
            $this->get(MetaDataConverterInterface::class)
        );
        return $metaDataProvider;
    }
}
