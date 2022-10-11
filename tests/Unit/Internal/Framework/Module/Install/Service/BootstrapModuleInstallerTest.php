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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\BootstrapModuleInstaller;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleFilesInstallerInterface;
use PHPUnit\Framework\TestCase;

class BootstrapModuleInstallerTest extends TestCase
{

    public function testInstallTriggersAllInstallers()
    {
        $path = 'packagePath';
        $package = new OxidEshopPackage('dummy', $path);

        $moduleFilesInstaller = $this->getMockBuilder(ModuleFilesInstallerInterface::class)->getMock();
        $moduleFilesInstaller
            ->expects($this->once())
            ->method('install')
            ->with($package);

        $moduleProjectConfigurationInstaller = $this->getMockBuilder(ModuleConfigurationInstallerInterface::class)->getMock();
        $moduleProjectConfigurationInstaller
            ->expects($this->once())
            ->method('install')
            ->with($path);


        $moduleInstaller = new BootstrapModuleInstaller($moduleFilesInstaller, $moduleProjectConfigurationInstaller);
        $moduleInstaller->install($package);
    }

    /**
     * @dataProvider moduleInstallMatrixDataProvider
     *
     * @param bool $filesInstalled
     * @param bool $projectConfigurationInstalled
     * @param bool $moduleInstalled
     */
    public function testIsInstalled(bool $filesInstalled, bool $projectConfigurationInstalled, bool $moduleInstalled)
    {
        $moduleFilesInstaller = $this->getMockBuilder(ModuleFilesInstallerInterface::class)->getMock();
        $moduleFilesInstaller->method('isInstalled')->willReturn($filesInstalled);

        $moduleProjectConfigurationInstaller = $this->getMockBuilder(ModuleConfigurationInstallerInterface::class)->getMock();
        $moduleProjectConfigurationInstaller->method('isInstalled')->willReturn($projectConfigurationInstalled);

        $moduleInstaller = new BootstrapModuleInstaller($moduleFilesInstaller, $moduleProjectConfigurationInstaller);

        $this->assertSame(
            $moduleInstalled,
            $moduleInstaller->isInstalled(new OxidEshopPackage('dummy', 'somePath'))
        );
    }

    public function moduleInstallMatrixDataProvider(): array
    {
        return [
            [
                'filesInstalled'                => false,
                'projectConfigurationInstalled' => false,
                'moduleInstalled'               => false,
            ],
            [
                'filesInstalled'                => true,
                'projectConfigurationInstalled' => false,
                'moduleInstalled'               => false,
            ],
            [
                'filesInstalled'                => false,
                'projectConfigurationInstalled' => true,
                'moduleInstalled'               => false,
            ],
            [
                'filesInstalled'                => true,
                'projectConfigurationInstalled' => true,
                'moduleInstalled'               => true,
            ],
        ];
    }
}
