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

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Core\FileSystem\FileSystem;
use OxidEsales\TestingLibrary\UnitTestCase;
use RuntimeException;

class FileSystemTest extends UnitTestCase
{
    /** @var string[] Temp directories created during tests, to be cleaned up */
    private $tempDirs = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tempDirs) as $dir) {
            if (is_dir($dir)) {
                $this->removeDir($dir);
            }
        }
        $this->tempDirs = [];
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Creates a unique temporary directory and registers it for cleanup.
     */
    private function makeTempDir(string $suffix = ''): string
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oxid_fs_test_' . uniqid('', true) . $suffix;
        mkdir($base, 0755, true);
        $this->tempDirs[] = $base;
        return $base;
    }
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

    // -------------------------------------------------------------------------
    // createDirIfNotExists tests
    // -------------------------------------------------------------------------

    public function testCreateDirIfNotExistsReturnsTrueWhenDirectoryAlreadyExists(): void
    {
        $base = $this->makeTempDir();
        $target = $base . DIRECTORY_SEPARATOR . 'existing';
        mkdir($target, 0755, true);

        $fileSystem = oxNew(FileSystem::class);
        $this->assertTrue($fileSystem->createDirIfNotExists($target, $base));
    }

    public function testCreateDirIfNotExistsCreatesNewDirectory(): void
    {
        $base = $this->makeTempDir();
        $target = $base . DIRECTORY_SEPARATOR . 'newdir';

        $fileSystem = oxNew(FileSystem::class);
        $this->assertTrue($fileSystem->createDirIfNotExists($target, $base));
        $this->assertDirectoryExists($target);
    }

    public function testCreateDirIfNotExistsCreatesNestedDirectories(): void
    {
        $base = $this->makeTempDir();
        $target = $base . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c';

        $fileSystem = oxNew(FileSystem::class);
        $this->assertTrue($fileSystem->createDirIfNotExists($target, $base));
        $this->assertDirectoryExists($target);
    }

    public function testCreateDirIfNotExistsRespectsCustomMode(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission modes are not applicable on Windows.');
        }
        $base = $this->makeTempDir();
        $target = $base . DIRECTORY_SEPARATOR . 'modedir';

        $fileSystem = oxNew(FileSystem::class);
        $fileSystem->createDirIfNotExists($target, $base, 0700);

        $this->assertDirectoryExists($target);
        $this->assertSame('0700', substr(sprintf('%o', fileperms($target)), -4));
    }

    public function testCreateDirIfNotExistsThrowsOnEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $base = $this->makeTempDir();
        $fileSystem = oxNew(FileSystem::class);
        $fileSystem->createDirIfNotExists('', $base);
    }

    public function testCreateDirIfNotExistsThrowsWhenBaseDirDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fileSystem = oxNew(FileSystem::class);
        $fileSystem->createDirIfNotExists('/some/path/newdir', '/this/does/not/exist');
    }

    public function testCreateDirIfNotExistsThrowsWhenPathIsOutsideBaseDir(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $base = $this->makeTempDir();
        $fileSystem = oxNew(FileSystem::class);
        // Use a path clearly outside the base dir
        $fileSystem->createDirIfNotExists($base . '/../outside', $base);
    }

    public function testCreateDirIfNotExistsThrowsWhenPathIsExistingFile(): void
    {
        $this->expectException(RuntimeException::class);

        $base = $this->makeTempDir();
        $file = $base . DIRECTORY_SEPARATOR . 'iam_a_file';
        file_put_contents($file, '');

        $fileSystem = oxNew(FileSystem::class);
        $fileSystem->createDirIfNotExists($file, $base);
    }

    public function testCreateDirIfNotExistsNormalizesTrailingSlash(): void
    {
        $base = $this->makeTempDir();
        $target = $base . DIRECTORY_SEPARATOR . 'trailingslash' . DIRECTORY_SEPARATOR;

        $fileSystem = oxNew(FileSystem::class);
        $this->assertTrue($fileSystem->createDirIfNotExists($target, $base));
        $this->assertDirectoryExists(rtrim($target, DIRECTORY_SEPARATOR));
    }

    // -------------------------------------------------------------------------

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
