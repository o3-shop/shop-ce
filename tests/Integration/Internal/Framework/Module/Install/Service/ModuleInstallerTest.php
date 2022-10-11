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

declare(strict_types=1);

namespace Integration\Internal\Framework\Module\Install\Service;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class ModuleInstallerTest extends TestCase
{
    use ContainerTrait;

    private $moduleId = 'myTestModule';

    public function testUninstallNotActiveModule(): void
    {
        $package = $this->getOxidEshopPackage();
        $this->installModule();

        $moduleInstaller = $this->get(ModuleInstallerInterface::class);
        $moduleInstaller->uninstall($package);

        $this->assertFalse(
            $moduleInstaller->isInstalled($package)
        );
    }

    public function testUninstallActiveModule(): void
    {
        $package = $this->getOxidEshopPackage();
        $this->installModule();
        $this->activateTestModule();

        $moduleInstaller = $this->get(ModuleInstallerInterface::class);
        $moduleInstaller->uninstall($package);

        $this->assertFalse(
            $moduleInstaller->isInstalled($package)
        );

        $this->assertFalse(
            $this->get(ModuleStateServiceInterface::class)->isActive($this->moduleId, 1)
        );
    }

    public function testUninstallModuleWithCustomSourcePath(): void
    {
        $package = new OxidEshopPackage('withCustomSource', __DIR__ . '/Fixtures/testModuleWithCustomSource');
        $package->setSourceDirectory('customSourcePath');
        $package->setTargetDirectory('oeTest/customSourcePath');

        $moduleInstaller = $this->get(ModuleInstallerInterface::class);
        $moduleInstaller->install($package);

        $moduleInstaller->uninstall($package);

        $this->assertFalse(
            $moduleInstaller->isInstalled($package)
        );
    }

    private function installModule(): void
    {
        $installService = $this->get(ModuleInstallerInterface::class);
        $package = $this->getOxidEshopPackage();
        $package->setTargetDirectory('oeTest/' . $this->moduleId);
        $installService->install($package);
    }

    private function activateTestModule(): void
    {
        $this
            ->get(ModuleActivationBridgeInterface::class)
            ->activate($this->moduleId, Registry::getConfig()->getShopId());
    }

    /**
     * @return OxidEshopPackage
     */
    private function getOxidEshopPackage(): OxidEshopPackage
    {
        return new OxidEshopPackage($this->moduleId, __DIR__ . '/Fixtures/' . $this->moduleId);
    }
}
