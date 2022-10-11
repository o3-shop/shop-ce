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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use oxException;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Core\Module\ModuleTemplateBlockContentReader;
use OxidEsales\Eshop\Core\Module\ModuleTemplateBlockPathFormatter;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group module
 * @package Unit\Core\Module
 */
class ModuleTemplateBlockContentReaderTest extends UnitTestCase
{
    public function testCanCreateClass()
    {
        oxNew(ModuleTemplateBlockContentReader::class);
    }

    public function testGetContentThrowExceptionWithoutPathFormatter()
    {
        $this->expectException(oxException::class);

        $content = oxNew(ModuleTemplateBlockContentReader::class);
        $content->getContent(null);
    }

    public function testGetContentDoesNotThrowExceptionWhenValidArgumentProvided()
    {
        $pathFormatter = $this->getPathFormatterStub('pathToFile', 'some content');

        $content = oxNew(ModuleTemplateBlockContentReader::class);
        $content->getContent($pathFormatter);
    }

    public function providerGetContentReturnContentFromFileWhichWasProvided()
    {
        return [
            ['pathToFile', 'some content'],
            ['pathToFile', 'some other content'],
        ];
    }

    /**
     * @param $filePath
     * @param $content
     *
     * @dataProvider providerGetContentReturnContentFromFileWhichWasProvided
     */
    public function testGetContentReturnContentFromFileWhichWasProvided($filePath, $content)
    {
        $pathFormatter = $this->getPathFormatterStub($filePath, $content);

        $contentGetter = oxNew(ModuleTemplateBlockContentReader::class);

        $this->assertSame($content, $contentGetter->getContent($pathFormatter));
    }

    public function testGetContentThrowsExceptionWhenFileDoesNotExist()
    {
        $vfsStreamWrapper = $this->getVfsStreamWrapper();

        $filePath = $vfsStreamWrapper->getRootPath() . DIRECTORY_SEPARATOR . 'someFile';

        $exceptionMessage = "Template block file (%s) was not found for module '%s'.";
        $this->expectException(oxException::class);
        $this->expectExceptionMessage(sprintf($exceptionMessage, $filePath, 'myModuleId'));

        $pathFormatter = $this->getMock(ModuleTemplateBlockPathFormatter::class, ['getPath', 'getModuleId']);
        $pathFormatter->method('getPath')->willReturn($filePath);
        $pathFormatter->method('getModuleId')->willReturn('myModuleId');

        $content = oxNew(ModuleTemplateBlockContentReader::class);
        $content->getContent($pathFormatter);
    }

    public function testGetContentThrowsExceptionWhenFileIsNotReadable()
    {
        $notReadableMode = 000;
        $vfsStreamWrapper = $this->getVfsStreamWrapper();
        $filePath = $vfsStreamWrapper->createFile('pathToFile', 'some content');
        chmod($filePath, $notReadableMode);

        $exceptionMessage = "Template block file (%s) is not readable for module '%s'.";
        $this->expectException(oxException::class);
        $this->expectExceptionMessage(sprintf($exceptionMessage, $filePath, 'myModuleId'));

        $pathFormatter = $this->getMock(ModuleTemplateBlockPathFormatter::class, ['getPath', 'getModuleId']);
        $pathFormatter->method('getPath')->willReturn($filePath);
        $pathFormatter->method('getModuleId')->willReturn('myModuleId');

        $content = oxNew(ModuleTemplateBlockContentReader::class);
        $content->getContent($pathFormatter);
    }

    /**
     * @param $filePath
     * @param $content
     *
     * @return MockObject
     */
    private function getPathFormatterStub($filePath, $content)
    {
        $vfsStreamWrapper = $this->getVfsStreamWrapper();
        $filePath = $vfsStreamWrapper->createFile($filePath, $content);

        $pathFormatter = $this->getMock(ModuleTemplateBlockPathFormatter::class, ['getPath']);
        $pathFormatter->method('getPath')->willReturn($filePath);

        return $pathFormatter;
    }
}
