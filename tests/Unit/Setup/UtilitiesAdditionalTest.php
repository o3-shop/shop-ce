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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup;

use OxidEsales\EshopCommunity\Setup\Core;
use OxidEsales\EshopCommunity\Setup\Language;
use OxidEsales\EshopCommunity\Setup\Utilities;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Covers the Setup/Utilities methods not exercised by UtilitiesTest:
 * removeDir, updateEnvFile, updateConfigFile, updateHtaccessFile,
 * canHtaccessFileBeUpdated, isDemodataPrepared, getActiveEditionDemodataPackage*,
 * getLicenseContent, getRootDirectory, checkDbExists, getSqlDirectory,
 * getSetupDirectory, executeExternal* commands, generateUID.
 */
class UtilitiesAdditionalTest extends \OxidTestCase
{
    /** @var string[] tracks temp paths created by the test for cleanup */
    private $cleanupPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCoreInstanceCache();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanupPaths) as $path) {
            if (is_dir($path)) {
                $this->rrmdir($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }
        $this->cleanupPaths = [];
        $this->resetCoreInstanceCache();
        parent::tearDown();
    }

    private function resetCoreInstanceCache(): void
    {
        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function makeTempDir(string $prefix = 'utils_'): string
    {
        $base = sys_get_temp_dir();
        $dir = $base . '/' . $prefix . bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        $this->cleanupPaths[] = $dir;
        return $dir;
    }

    /**
     * Pre-seed Core's instance cache with a mock Language so getInstance('Language')
     * returns it without trying to read a lang file from disk.
     */
    private function injectLanguageMock(): Language
    {
        $language = $this->getMockBuilder(Language::class)
            ->onlyMethods(['getText'])
            ->getMock();
        $language->method('getText')->willReturnCallback(function ($key) {
            // Most callers in Utilities pass the result through sprintf with one
            // argument, so a plain "%s" template with the key prefix is enough.
            return $key . ': %s';
        });

        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, [Language::class => $language]);

        return $language;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::generateUID
     */
    public function testGenerateUIDProducesMd5HexString(): void
    {
        $utilities = new Utilities();
        $uid = $utilities->generateUID();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $uid);
        $this->assertNotSame($uid, $utilities->generateUID());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::removeDir
     */
    public function testRemoveDirRecursivelyDeletesFilesAndFolders(): void
    {
        $root = $this->makeTempDir();
        mkdir($root . '/sub');
        file_put_contents($root . '/keep.txt', 'a');
        file_put_contents($root . '/sub/file.txt', 'b');

        $utilities = new Utilities();
        $result = $utilities->removeDir($root, true);

        $this->assertTrue((bool) $result);
        $this->assertFalse(is_dir($root . '/sub'));
        $this->assertFalse(file_exists($root . '/keep.txt'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::removeDir
     */
    public function testRemoveDirSkipsListedFilesAndFolders(): void
    {
        $root = $this->makeTempDir();
        mkdir($root . '/keep_dir');
        file_put_contents($root . '/keep_dir/inner.txt', 'x');
        file_put_contents($root . '/keep.txt', 'k');
        file_put_contents($root . '/drop.txt', 'd');

        $utilities = new Utilities();
        $utilities->removeDir($root, true, 0, ['keep.txt'], ['keep_dir']);

        $this->assertFalse(file_exists($root . '/drop.txt'));
        $this->assertTrue(file_exists($root . '/keep.txt'));
        $this->assertTrue(is_dir($root . '/keep_dir'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::removeDir
     */
    public function testRemoveDirInFilesOnlyModeKeepsDirectories(): void
    {
        $root = $this->makeTempDir();
        mkdir($root . '/sub');
        file_put_contents($root . '/sub/file.txt', 'a');
        file_put_contents($root . '/top.txt', 'b');

        $utilities = new Utilities();
        $utilities->removeDir($root, true, 1);

        $this->assertFalse(file_exists($root . '/sub/file.txt'));
        $this->assertFalse(file_exists($root . '/top.txt'));
        $this->assertTrue(is_dir($root . '/sub'), 'sub directory should be kept in mode=1');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::removeDir
     */
    public function testRemoveDirReturnsFalseForMissingPath(): void
    {
        $utilities = new Utilities();
        $this->assertFalse((bool) $utilities->removeDir('/no/such/path/' . uniqid(), true));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateEnvFile
     */
    public function testUpdateEnvFileReplacesPlaceholders(): void
    {
        $shopDir = $this->makeTempDir();
        $envParent = dirname($shopDir);
        $envPath = $envParent . '/.env';
        $this->cleanupPaths[] = $envPath;
        file_put_contents($envPath, "DB_HOST=<sDbHost>\nDB_USER=<sDbUser>\n");

        $this->injectLanguageMock();

        $utilities = new Utilities();
        $utilities->updateEnvFile([
            'sShopDir' => $shopDir,
            'sDbHost' => 'localhost',
            'sDbUser' => 'admin',
        ]);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString('DB_HOST=localhost', $contents);
        $this->assertStringContainsString('DB_USER=admin', $contents);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateEnvFile
     */
    public function testUpdateEnvFileEscapesSingleQuoteCorrectly(): void
    {
        $shopDir = $this->makeTempDir();
        $envParent = dirname($shopDir);
        $envPath = $envParent . '/.env';
        $this->cleanupPaths[] = $envPath;
        file_put_contents($envPath, "DB_PASS=<sDbPass>\n");

        $this->injectLanguageMock();

        $utilities = new Utilities();
        $utilities->updateEnvFile([
            'sShopDir' => $shopDir,
            'sDbPass' => "p'wd",
        ]);

        $contents = file_get_contents($envPath);
        // The replacement step turns a literal `'` in input into `\'`.
        $this->assertStringContainsString("DB_PASS=p\\'wd", $contents);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateEnvFile
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::handleMissingConfigFileException
     */
    public function testUpdateEnvFileThrowsWhenFileMissing(): void
    {
        $shopDir = $this->makeTempDir();
        // No .env in parent directory ⇒ handleMissingConfigFileException trips.
        $this->injectLanguageMock();

        $utilities = new Utilities();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ERROR_COULD_NOT_OPEN_CONFIG_FILE');

        $utilities->updateEnvFile(['sShopDir' => $shopDir]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateConfigFile
     */
    public function testUpdateConfigFileSucceedsWhenFileExists(): void
    {
        $shopDir = $this->makeTempDir();
        $configPath = $shopDir . '/config.inc.php';
        file_put_contents($configPath, '<?php // placeholder');

        $this->injectLanguageMock();

        $utilities = new Utilities();
        $utilities->updateConfigFile(['sShopDir' => $shopDir]);

        $this->assertTrue(file_exists($configPath));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateConfigFile
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::handleMissingConfigFileException
     */
    public function testUpdateConfigFileThrowsWhenFileMissing(): void
    {
        $shopDir = $this->makeTempDir();
        $this->injectLanguageMock();

        $utilities = new Utilities();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ERROR_COULD_NOT_OPEN_CONFIG_FILE');

        $utilities->updateConfigFile(['sShopDir' => $shopDir]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateHtaccessFile
     */
    public function testUpdateHtaccessFileRewritesBasePath(): void
    {
        $shopDir = $this->makeTempDir();
        file_put_contents($shopDir . '/.htaccess', "RewriteEngine On\nRewriteBase /old\n");

        $this->injectLanguageMock();

        $utilities = new Utilities();
        $utilities->updateHtaccessFile(['sShopDir' => $shopDir, 'sBaseUrlPath' => '/myshop']);

        $this->assertStringContainsString('RewriteBase /myshop', file_get_contents($shopDir . '/.htaccess'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateHtaccessFile
     */
    public function testUpdateHtaccessFileFallsBackToEmptyBaseUrlPath(): void
    {
        $shopDir = $this->makeTempDir();
        file_put_contents($shopDir . '/.htaccess', "RewriteBase /\n");

        $this->injectLanguageMock();

        $utilities = new Utilities();
        $utilities->updateHtaccessFile(['sShopDir' => $shopDir]);

        $this->assertStringContainsString('RewriteBase /', file_get_contents($shopDir . '/.htaccess'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::updateHtaccessFile
     */
    public function testUpdateHtaccessFileThrowsWhenFileMissing(): void
    {
        $shopDir = $this->makeTempDir();
        $this->injectLanguageMock();

        $utilities = new Utilities();

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(Utilities::ERROR_COULD_NOT_FIND_FILE);
        $utilities->updateHtaccessFile(['sShopDir' => $shopDir]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::canHtaccessFileBeUpdated
     */
    public function testCanHtaccessFileBeUpdatedReturnsFalseOnException(): void
    {
        $this->injectLanguageMock();

        // Stub default-path params so updateHtaccessFile gets a non-existent
        // shop dir and throws — canHtaccessFileBeUpdated swallows that.
        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['getDefaultPathParams'])
            ->getMock();
        $utilities->method('getDefaultPathParams')->willReturn([
            'sShopDir' => '/no/such/dir/' . uniqid(),
            'sShopURL' => 'https://example.com/myshop',
        ]);

        $this->assertFalse($utilities->canHtaccessFileBeUpdated());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::canHtaccessFileBeUpdated
     */
    public function testCanHtaccessFileBeUpdatedReturnsTrueWhenWriteSucceeds(): void
    {
        $shopDir = $this->makeTempDir();
        file_put_contents($shopDir . '/.htaccess', "RewriteBase /\n");

        $this->injectLanguageMock();

        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['getDefaultPathParams'])
            ->getMock();
        $utilities->method('getDefaultPathParams')->willReturn([
            'sShopDir' => $shopDir,
            'sShopURL' => 'https://example.com/myshop',
        ]);

        $this->assertTrue($utilities->canHtaccessFileBeUpdated());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::isDemodataPrepared
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getActiveEditionDemodataPackageSqlFilePath
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getActiveEditionDemodataPackagePath
     */
    public function testIsDemodataPreparedHappyPath(): void
    {
        $packagePath = $this->makeTempDir('demodata_pkg_');
        $srcDir = $packagePath . '/' . Utilities::DEMODATA_PACKAGE_SOURCE_DIRECTORY;
        mkdir($srcDir);
        file_put_contents($srcDir . '/' . Utilities::DEMODATA_SQL_FILENAME, '-- payload');

        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['getActiveEditionDemodataPackagePath'])
            ->getMock();
        $utilities->method('getActiveEditionDemodataPackagePath')->willReturn($packagePath);

        $this->assertTrue($utilities->isDemodataPrepared());
        $this->assertSame(
            $packagePath . '/' . Utilities::DEMODATA_PACKAGE_SOURCE_DIRECTORY . '/' . Utilities::DEMODATA_SQL_FILENAME,
            $utilities->getActiveEditionDemodataPackageSqlFilePath()
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::isDemodataPrepared
     */
    public function testIsDemodataPreparedFalseWhenSqlFileMissing(): void
    {
        $packagePath = $this->makeTempDir('demodata_pkg_');

        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['getActiveEditionDemodataPackagePath'])
            ->getMock();
        $utilities->method('getActiveEditionDemodataPackagePath')->willReturn($packagePath);

        $this->assertFalse($utilities->isDemodataPrepared());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getActiveEditionDemodataPackagePath
     */
    public function testGetActiveEditionDemodataPackagePathContainsEditionsSegment(): void
    {
        $utilities = new Utilities();
        $path = $utilities->getActiveEditionDemodataPackagePath();
        $this->assertStringContainsString('shop-demodata-', $path);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getLicenseContent
     */
    public function testGetLicenseContentReadsFromRoot(): void
    {
        $rootDir = $this->makeTempDir('licroot_');
        file_put_contents($rootDir . '/' . Utilities::LICENSE_TEXT_FILENAME, 'GPL-3.0 text');

        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['getRootDirectory'])
            ->getMock();
        $utilities->method('getRootDirectory')->willReturn($rootDir);

        $this->assertSame('GPL-3.0 text', $utilities->getLicenseContent('en'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getRootDirectory
     */
    public function testGetRootDirectoryReturnsAnExistingPath(): void
    {
        $utilities = new Utilities();
        $this->assertTrue(is_dir($utilities->getRootDirectory()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getSetupDirectory
     */
    public function testGetSetupDirectoryEndsWithSetup(): void
    {
        $utilities = new Utilities();
        $this->assertStringEndsWith('Setup', rtrim($utilities->getSetupDirectory(), '/'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::getSqlDirectory
     */
    public function testGetSqlDirectoryReturnsNonEmptyPath(): void
    {
        $utilities = new Utilities();
        $this->assertNotEmpty($utilities->getSqlDirectory());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::checkDbExists
     */
    public function testCheckDbExistsReturnsTrueOnSuccessfulQuery(): void
    {
        $database = new class () {
            public function execSql($sql)
            {
                return true;
            }
        };

        $utilities = new Utilities();
        $this->assertTrue($utilities->checkDbExists($database));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::checkDbExists
     */
    public function testCheckDbExistsReturnsFalseWhenQueryThrows(): void
    {
        $database = new class () {
            public function execSql($sql)
            {
                throw new \Exception('connection refused');
            }
        };

        $utilities = new Utilities();
        $this->assertFalse($utilities->checkDbExists($database));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::executeExternalRegenerateViewsCommand
     */
    public function testExecuteExternalRegenerateViewsCommandReturnsBool(): void
    {
        $utilities = new Utilities();
        $this->assertIsBool($utilities->executeExternalRegenerateViewsCommand());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::executeExternalDatabaseMigrationCommand
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::createMigrations
     */
    public function testExecuteExternalDatabaseMigrationCommandDelegatesToMigrations(): void
    {
        $migrations = $this->createMock(\OxidEsales\DoctrineMigrationWrapper\Migrations::class);
        $migrations->expects($this->once())->method('setOutput');
        $migrations->expects($this->once())
            ->method('execute')
            ->with(\OxidEsales\DoctrineMigrationWrapper\Migrations::MIGRATE_COMMAND);

        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['createMigrations'])
            ->getMock();
        $utilities->method('createMigrations')->willReturn($migrations);

        $utilities->executeExternalDatabaseMigrationCommand();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::executeExternalDemodataAssetsInstallCommand
     * @covers \OxidEsales\EshopCommunity\Setup\Utilities::createDemoDataInstaller
     */
    public function testExecuteExternalDemodataAssetsInstallCommandReturnsExitCode(): void
    {
        $installer = $this->getMockBuilder(\OxidEsales\DemoDataInstaller\DemoDataInstaller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $installer->method('execute')->willReturn(7);

        $utilities = $this->getMockBuilder(Utilities::class)
            ->onlyMethods(['createDemoDataInstaller'])
            ->getMock();
        $utilities->method('createDemoDataInstaller')->willReturn($installer);

        $this->assertSame(7, $utilities->executeExternalDemodataAssetsInstallCommand());
    }
}
