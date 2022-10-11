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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\EshopCommunity\Core\FileSystem\FileSystem;

class FileSystemTest extends UnitTestCase
{
    public function testCombinePathsReturnEmptyPathWhenCalledWithoutParameters()
    {
        $fileSystem = oxNew(FileSystem::class);

        $actualConnectedPath = $fileSystem->combinePaths();
        $this->assertSame('', $actualConnectedPath);
    }

    public function testCombinePathsJoinAllParametersToSingleString()
    {
        $fileSystem = oxNew(FileSystem::class);

        $actualConnectedPath = $fileSystem->combinePaths('path1');
        $expectedPath = 'path1';
        $this->assertSame($expectedPath, $actualConnectedPath);

        $actualConnectedPath = $fileSystem->combinePaths('path1', 'path2');
        $expectedPath = 'path1/path2';
        $this->assertSame($expectedPath, $actualConnectedPath);

        $actualConnectedPath = $fileSystem->combinePaths('path1', 'path2', 'path3');
        $expectedPath = 'path1/path2/path3';
        $this->assertSame($expectedPath, $actualConnectedPath);
    }

    public function testCombinePathsReturnPathWithoutBackslash()
    {
        $fileSystem = oxNew(FileSystem::class);

        $actualConnectedPath = $fileSystem->combinePaths('path1/');
        $expectedPath = 'path1';
        $this->assertSame($expectedPath, $actualConnectedPath);
    }

    public function testCombinePathsJoinsWithSingleBackSlashEvenWhenParameterAlreadyHasBackSlash()
    {
        $fileSystem = oxNew(FileSystem::class);

        $actualConnectedPath = $fileSystem->combinePaths('path1/', 'path2/', 'path3/');
        $expectedPath = 'path1/path2/path3';
        $this->assertSame($expectedPath, $actualConnectedPath);
    }

    /**
     * Test for isReadable method
     */
    public function testIsReadable()
    {
        $filePath = 'somedir/somefile.txt';

        $vfsStreamWrapper = $this->getVfsStreamWrapper();
        $vfsStreamWrapper->createFile('somedir/somefile.txt', '');
        $testDir = $vfsStreamWrapper->getRootPath();

        $fileSystem = oxNew(FileSystem::class);
        $this->assertTrue($fileSystem->isReadable($testDir . '/' . $filePath));

        if (version_compare(PHP_VERSION, '5.5') >= 0) {
            chmod($testDir . '/' . $filePath, 0000);
            $this->assertFalse($fileSystem->isReadable($testDir . '/' . $filePath));
        }

        $this->assertFalse($fileSystem->isReadable($testDir . '/notexists.txt'));
    }
}
