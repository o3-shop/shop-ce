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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Install;

use org\bovigo\vfs\vfsStream;
use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\FinderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstaller;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
class ModuleFilesInstallerTest extends TestCase
{
    public function testCopyDispatchesCallsToTheFileSystemService()
    {
        $packageName = 'myvendor/mymodule';
        $packagePath = '/var/www/vendor/myvendor/mymodule';
        $blackListFilters = [
            "documentation/**/*.*",
            "CHANGELOG.md",
            "composer.json",
            "CONTRIBUTING.md",
            "README.md"
        ];

        $package = new OxidEshopPackage($packageName, $packagePath);
        $package->setTargetDirectory('myvendor/myinstallationpath');
        $package->setSourceDirectory('src/mymodule');
        $package->setBlackListFilters($blackListFilters);

        vfsStream::setup();
        $context = $this->getContext();

        $finder = $this->getMockBuilder(Finder::class)->getMock();
        $finder
            ->expects($this->once())
            ->method('in')
            ->with('/var/www/vendor/myvendor/mymodule/src/mymodule')
            ->willReturn($finder);

        $finder
            ->expects($this->exactly(count($blackListFilters)))
            ->method('notName')
            ->willReturn($finder);

        $finderFactory = $this->getMockBuilder(FinderFactoryInterface::class)->getMock();
        $finderFactory->method('create')->willReturn($finder);

        $fileSystem = $this->getMockBuilder(Filesystem::class)->getMock();
        $fileSystem
            ->expects($this->once())
            ->method('mirror')
            ->with(
                $packagePath . DIRECTORY_SEPARATOR . 'src/mymodule',
                $context->getModulesPath() . DIRECTORY_SEPARATOR . 'myvendor/myinstallationpath',
                $finder,
                ['override' => true]
            );

        $moduleCopyService = new ModuleFilesInstaller($context, $fileSystem, $finderFactory);
        $moduleCopyService->install($package);
    }

    /**
     * @return BasicContextInterface
     */
    private function getContext(): BasicContextInterface
    {
        $context = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $context->method('getModulesPath')->willReturn(vfsStream::url('root/source/modules'));
        return $context;
    }
}
