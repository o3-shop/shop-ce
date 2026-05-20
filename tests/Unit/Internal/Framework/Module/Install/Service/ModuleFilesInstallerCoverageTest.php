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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\FinderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ModuleFilesInstallerCoverageTest extends TestCase
{
    private function makeContext(string $modulesPath = '/var/www/source/modules'): BasicContextInterface
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getModulesPath')->willReturn($modulesPath);
        return $context;
    }

    private function makePackage(string $name = 'mymod', string $packagePath = '/var/www/vendor/me/mymod', array $blacklist = []): OxidEshopPackage
    {
        $package = new OxidEshopPackage($name, $packagePath);
        $package->setBlackListFilters($blacklist);
        return $package;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::uninstall
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::getTargetPath
     */
    public function testUninstallRemovesTheModuleTargetDirectory(): void
    {
        $package = $this->makePackage('mymod', '/srv/pkg');
        $expectedTarget = '/var/www/source/modules/mymod';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('remove')
            ->with($expectedTarget);

        $installer = new ModuleFilesInstaller(
            $this->makeContext(),
            $filesystem,
            $this->createMock(FinderFactoryInterface::class)
        );
        $installer->uninstall($package);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::isInstalled
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::getTargetPath
     */
    public function testIsInstalledReportsWhetherTargetDirectoryExists(): void
    {
        $package = $this->makePackage('mymod', '/srv/pkg');
        $expectedTarget = '/var/www/source/modules/mymod';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')
            ->with($expectedTarget)
            ->willReturnOnConsecutiveCalls(true, false);

        $installer = new ModuleFilesInstaller(
            $this->makeContext(),
            $filesystem,
            $this->createMock(FinderFactoryInterface::class)
        );

        $this->assertTrue($installer->isInstalled($package));
        $this->assertFalse($installer->isInstalled($package));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::install
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::getFinder
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::isDirectoryFilter
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::getDirectoryForFilter
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::normalizeFileFilter
     */
    public function testInstallMirrorsSourceWithBlackListAppliedAsDirectoryAndFileFilters(): void
    {
        // Two filters: one ends in /**/* (directory filter), one is a file
        // filter prefixed with **/. The installer must call notPath/notName
        // accordingly via the Finder.
        $package = $this->makePackage('mymod', '/srv/pkg', [
            'tests/**/*',         // directory filter
            '**/*.dist',          // file filter
            'plain.txt',          // file filter (no **/ prefix)
        ]);

        $finder = $this->getMockBuilder(Finder::class)->onlyMethods(['in', 'notPath', 'notName'])->getMock();
        $finder->expects($this->once())->method('in')->with('/srv/pkg');
        $finder->expects($this->once())->method('notPath')->with('tests');
        $notNameInvocations = [];
        $finder->expects($this->exactly(2))
            ->method('notName')
            ->willReturnCallback(function ($filter) use (&$notNameInvocations) {
                $notNameInvocations[] = $filter;
                return $this;
            });

        $finderFactory = $this->createMock(FinderFactoryInterface::class);
        $finderFactory->method('create')->willReturn($finder);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('mirror')
            ->with(
                '/srv/pkg',
                '/var/www/source/modules/mymod',
                $finder,
                ['override' => true]
            );

        $installer = new ModuleFilesInstaller($this->makeContext(), $filesystem, $finderFactory);
        $installer->install($package);

        // notName receives the filter with leading **/ stripped.
        $this->assertSame(['*.dist', 'plain.txt'], $notNameInvocations);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::install
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller::getFinder
     */
    public function testInstallWithoutBlackListAppliesNoFiltersToFinder(): void
    {
        $package = $this->makePackage();

        $finder = $this->getMockBuilder(Finder::class)->onlyMethods(['in', 'notPath', 'notName'])->getMock();
        $finder->expects($this->once())->method('in');
        $finder->expects($this->never())->method('notPath');
        $finder->expects($this->never())->method('notName');

        $finderFactory = $this->createMock(FinderFactoryInterface::class);
        $finderFactory->method('create')->willReturn($finder);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('mirror');

        $installer = new ModuleFilesInstaller($this->makeContext(), $filesystem, $finderFactory);
        $installer->install($package);
    }
}
