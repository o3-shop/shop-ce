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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Converter\MetaDataConverterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataNormalizerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\InvalidMetaDataException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Validator\MetaDataValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;

class MetaDataProviderTest extends TestCase
{
    /** @var string[] paths to remove on teardown */
    private $tempPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempPaths = [];
    }

    private function makeContext(array $bcMap = []): BasicContextInterface
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getBackwardsCompatibilityClassMap')->willReturn($bcMap);
        return $context;
    }

    private function writeMetadataFile(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'meta_');
        rename($tmp, $tmp . '.php');
        $tmp .= '.php';
        file_put_contents($tmp, $contents);
        $this->tempPaths[] = $tmp;
        return $tmp;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::getData
     */
    public function testGetDataRejectsNonReadablePath(): void
    {
        $provider = new MetaDataProvider(
            $this->createMock(MetaDataNormalizerInterface::class),
            $this->makeContext(),
            $this->createMock(MetaDataValidatorInterface::class),
            $this->createMock(MetaDataConverterInterface::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $provider->getData('/no/such/file/' . uniqid() . '.php');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::getData
     */
    public function testGetDataRejectsDirectoryAsPath(): void
    {
        $provider = new MetaDataProvider(
            $this->createMock(MetaDataNormalizerInterface::class),
            $this->makeContext(),
            $this->createMock(MetaDataValidatorInterface::class),
            $this->createMock(MetaDataConverterInterface::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $provider->getData(sys_get_temp_dir());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::getData
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::getNormalizedMetaDataFileContent
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::addFilePathToData
     */
    public function testGetDataReturnsNormalizedDataWithFilePathAttached(): void
    {
        $metaPath = $this->writeMetadataFile(<<<'PHP'
<?php
$sMetadataVersion = '2.1';
$aModule = ['id' => 'mymod', 'title' => 'My Module'];
PHP);

        $validator = $this->createMock(MetaDataValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->with(['id' => 'mymod', 'title' => 'My Module']);

        $converter = $this->createMock(MetaDataConverterInterface::class);
        $converter->method('convert')->willReturnArgument(0);

        $normalizer = $this->createMock(MetaDataNormalizerInterface::class);
        $normalizer->method('normalizeData')->willReturnCallback(function ($data) {
            return $data;
        });

        $provider = new MetaDataProvider($normalizer, $this->makeContext(), $validator, $converter);
        $result = $provider->getData($metaPath);

        $this->assertSame('2.1', $result[MetaDataProvider::METADATA_METADATA_VERSION]);
        $this->assertSame(['id' => 'mymod', 'title' => 'My Module'], $result[MetaDataProvider::METADATA_MODULE_DATA]);
        $this->assertSame($metaPath, $result[MetaDataProvider::METADATA_FILEPATH]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::validateMetaDataFileVariables
     */
    public function testGetDataThrowsInvalidMetaDataExceptionWhenMetadataVersionMissing(): void
    {
        $metaPath = $this->writeMetadataFile(<<<'PHP'
<?php
$aModule = ['id' => 'mymod'];
PHP);

        $provider = new MetaDataProvider(
            $this->createMock(MetaDataNormalizerInterface::class),
            $this->makeContext(),
            $this->createMock(MetaDataValidatorInterface::class),
            $this->createMock(MetaDataConverterInterface::class)
        );

        $this->expectException(InvalidMetaDataException::class);
        $this->expectExceptionMessage('$sMetadataVersion');
        $provider->getData($metaPath);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::validateMetaDataFileVariables
     */
    public function testGetDataThrowsInvalidMetaDataExceptionWhenModuleArrayMissing(): void
    {
        $metaPath = $this->writeMetadataFile(<<<'PHP'
<?php
$sMetadataVersion = '2.1';
PHP);

        $provider = new MetaDataProvider(
            $this->createMock(MetaDataNormalizerInterface::class),
            $this->makeContext(),
            $this->createMock(MetaDataValidatorInterface::class),
            $this->createMock(MetaDataConverterInterface::class)
        );

        $this->expectException(InvalidMetaDataException::class);
        $this->expectExceptionMessage('$aModule');
        $provider->getData($metaPath);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::sanitizeExtendedClasses
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::isBackwardsCompatibleClass
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider::getBackwardsCompatibilityClassMap
     */
    public function testGetDataMapsBackwardsCompatibleShopClassesToCanonicalNames(): void
    {
        $metaPath = $this->writeMetadataFile(<<<'PHP'
<?php
$sMetadataVersion = '2.1';
$aModule = [
    'id' => 'mymod',
    'extend' => [
        'oxArticle' => 'mymod/MyArticle',
    ],
];
PHP);

        $bcMap = ['oxarticle' => 'OxidEsales\\Eshop\\Application\\Model\\Article'];

        $validator = $this->createMock(MetaDataValidatorInterface::class);
        $converter = $this->createMock(MetaDataConverterInterface::class);
        $converter->method('convert')->willReturnArgument(0);
        $normalizer = $this->createMock(MetaDataNormalizerInterface::class);
        $normalizer->method('normalizeData')->willReturnCallback(function ($data) {
            return $data;
        });

        $provider = new MetaDataProvider(
            $normalizer,
            $this->makeContext($bcMap),
            $validator,
            $converter
        );

        $result = $provider->getData($metaPath);
        $this->assertSame(
            ['OxidEsales\\Eshop\\Application\\Model\\Article' => 'mymod/MyArticle'],
            $result[MetaDataProvider::METADATA_MODULE_DATA][MetaDataProvider::METADATA_EXTEND]
        );
    }
}
