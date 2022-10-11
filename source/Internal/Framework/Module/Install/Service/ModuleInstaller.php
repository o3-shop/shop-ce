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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;

class ModuleInstaller implements ModuleInstallerInterface
{
    /**
     * @var BootstrapModuleInstaller
     */
    private $bootstrapModuleInstaller;

    /**
     * @var ModuleActivationServiceInterface
     */
    private $moduleActivationService;

    /**
     * @var ModuleConfigurationDaoInterface
     */
    private $moduleConfigurationDao;

    /**
     * @var ShopConfigurationDaoInterface
     */
    private $shopConfigurationDao;

    /**
     * @var ModuleStateServiceInterface
     */
    private $moduleStateService;

    /**
     * ModuleInstaller constructor.
     * @param BootstrapModuleInstaller $bootstrapModuleInstaller
     * @param ModuleActivationServiceInterface $moduleActivationService
     * @param ModuleConfigurationDaoInterface $moduleConfigurationDao
     * @param ShopConfigurationDaoInterface $shopConfigurationDao
     * @param ModuleStateServiceInterface $moduleStateService
     */
    public function __construct(
        BootstrapModuleInstaller $bootstrapModuleInstaller,
        ModuleActivationServiceInterface $moduleActivationService,
        ModuleConfigurationDaoInterface $moduleConfigurationDao,
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ModuleStateServiceInterface $moduleStateService
    ) {
        $this->bootstrapModuleInstaller = $bootstrapModuleInstaller;
        $this->moduleActivationService = $moduleActivationService;
        $this->moduleConfigurationDao = $moduleConfigurationDao;
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->moduleStateService = $moduleStateService;
    }

    /**
     * @param OxidEshopPackage $package
     */
    public function install(OxidEshopPackage $package): void
    {
        $this->bootstrapModuleInstaller->install($package);
    }

    /**
     * @param OxidEshopPackage $package
     *
     * @throws ModuleSetupException
     */
    public function uninstall(OxidEshopPackage $package): void
    {
        $moduleConfiguration = $this->moduleConfigurationDao->get($package->getPackageSourcePath());
        $this->deactivateModule($moduleConfiguration->getId());

        $this->bootstrapModuleInstaller->uninstall($package);
    }

    /**
     * @param OxidEshopPackage $package
     *
     * @return bool
     */
    public function isInstalled(OxidEshopPackage $package): bool
    {
        return $this->bootstrapModuleInstaller->isInstalled($package);
    }

    /**
     * @param string $moduleId
     *
     * @throws ModuleSetupException
     */
    private function deactivateModule(string $moduleId): void
    {
        foreach ($this->shopConfigurationDao->getAll() as $shopId => $shopConfiguration) {
            if (
                $shopConfiguration->hasModuleConfiguration($moduleId)
                && $this->moduleStateService->isActive($moduleId, $shopId)
            ) {
                $this->moduleActivationService->deactivate($moduleId, $shopId);
            }
        }
    }
}
