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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

final class ModuleFilesInstallerTest extends TestCase
{
    use ContainerTrait;

    private $modulePackagePath = __DIR__ . '/../../TestData/TestModule';
    private $packageName = 'TestModule';

    public function tearDown(): void
    {
        $fileSystem = $this->get('oxid_esales.symfony.file_system');
        $fileSystem->remove($this->getTestedModuleInstallPath());
        $fileSystem->remove($this->getModulesPath() . '/custom-test-directory/');

        parent::tearDown();
    }

    public function testModuleNotInstalledByDefault(): void
    {
        $installer = $this->getFilesInstaller();

        $this->assertFalse(
            $installer->isInstalled($this->createPackage())
        );
    }

    public function testModuleIsInstalledAfterInstallProcess(): void
    {
        $installer = $this->getFilesInstaller();
        $package = $this->createPackage();

        $installer->install($package);

        $this->assertTrue($installer->isInstalled($package));
    }

    public function testModuleFilesAreCopiedAfterInstallProcess(): void
    {
        $installer = $this->getFilesInstaller();
        $package = $this->createPackage();

        $installer->install($package);

        $this->assertFileEquals(
            $this->modulePackagePath . '/metadata.php',
            $this->getTestedModuleInstallPath() . '/metadata.php'
        );
    }

    public function testModuleFilesAreCopiedAfterInstallProcessWithCustomTargetDirectory(): void
    {
        $installer = $this->getFilesInstaller();
        $package = $this->createPackage();
        $package->setTargetDirectory('custom-test-directory');

        $installer->install($package);

        $this->assertFileEquals(
            $this->modulePackagePath . '/metadata.php',
            $this->getModulesPath() . '/custom-test-directory/metadata.php'
        );
    }

    public function testModuleFilesAreCopiedAfterInstallProcessWithCustomSourceDirectory(): void
    {
        $installer = $this->getFilesInstaller();

        $package = $this->createPackage();
        $package->setSourceDirectory('CustomSourceDirectory');

        $installer->install($package);

        $this->assertFileEquals(
            $this->modulePackagePath . '/CustomSourceDirectory/metadata.php',
            $this->getTestedModuleInstallPath() . '/metadata.php'
        );
    }

    public function testModuleFilesAreCopiedAfterInstallProcessWithCustomSourceDirectoryAndCustomTargetDirectory(): void
    {
        $installer = $this->getFilesInstaller();

        $package = $this->createPackage();
        $package->setSourceDirectory('CustomSourceDirectory');
        $package->setTargetDirectory('custom-test-directory');

        $installer->install($package);

        $this->assertFileEquals(
            $this->modulePackagePath . '/CustomSourceDirectory/metadata.php',
            $this->getModulesPath() . '/custom-test-directory/metadata.php'
        );
    }

    public function testBlacklistedFilesArePresentWhenEmptyBlacklistFilterIsDefined(): void
    {
        $installer = $this->getFilesInstaller();
        $package = $this->createPackage();
        $package->setBlackListFilters([]);

        $installer->install($package);

        $this->assertFileExists($this->getTestedModuleInstallPath() . '/readme.txt');
    }

    public function testBlacklistedFilesArePresentWhenDifferentBlacklistFilterIsDefined(): void
    {
        $installer = $this->getFilesInstaller();

        $package = $this->createPackage();
        $package->setBlackListFilters(['**/*.pdf']);

        $installer->install($package);

        $this->assertFileExists($this->getTestedModuleInstallPath() . '/readme.txt');
    }

    public function testBlacklistedFilesAreSkippedWhenBlacklistFilterIsDefined(): void
    {
        $installer = $this->getFilesInstaller();

        $package = $this->createPackage();
        $package->setBlackListFilters(['**/*.txt']);

        $installer->install($package);

        $this->assertFileNotExists($this->getTestedModuleInstallPath() . '/readme.txt');
    }

    public function testBlacklistedFilesAreSkippedWhenSingleFileNameBlacklistFilterIsDefined(): void
    {
        $installer = $this->getFilesInstaller();

        $package = $this->createPackage();
        $package->setBlackListFilters(['readme.txt']);

        $installer->install($package);

        $this->assertFileNotExists($this->getTestedModuleInstallPath() . '/readme.txt');
    }

    public function testBlacklistedDirectoryIsSkippedWhenBlacklistFilterIsDefined(): void
    {
        $installer = $this->getFilesInstaller();
        $package = $this->createPackage();
        $package->setBlackListFilters(['BlackListDirectory/**/*']);

        $installer->install($package);

        $this->assertDirectoryExists($this->modulePackagePath . '/BlackListDirectory');
        $this->assertDirectoryNotExists($this->getTestedModuleInstallPath() . '/BlackListDirectory');
    }

    public function testUninstall(): void
    {
        $installer = $this->getFilesInstaller();
        $package = $this->createPackage();
        $installer->install($package);

        $installer->uninstall($package);

        $this->assertFalse($installer->isInstalled($package));
    }

    private function getModulesPath(): string
    {
        return $this->get(ContextInterface::class)->getModulesPath();
    }

    private function getFilesInstaller(): ModuleFilesInstallerInterface
    {
        return $this->get(ModuleFilesInstallerInterface::class);
    }

    private function createPackage(): OxidEshopPackage
    {
        return new OxidEshopPackage($this->packageName, $this->modulePackagePath);
    }

    private function getTestedModuleInstallPath(): string
    {
        return $this->getModulesPath() . DIRECTORY_SEPARATOR . $this->packageName;
    }
}
