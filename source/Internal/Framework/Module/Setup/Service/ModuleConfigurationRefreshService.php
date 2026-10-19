<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;

class ModuleConfigurationRefreshService implements ModuleConfigurationRefreshServiceInterface
{
    /**
     * @var ModuleConfigurationHandlingServiceInterface
     */
    private $moduleConfigurationHandlingService;

    /**
     * @var ModuleServicesActivationServiceInterface
     */
    private $moduleServicesActivationService;

    /**
     * @var ExtensionChainServiceInterface
     */
    private $classExtensionChainService;

    /**
     * @var ProjectConfigurationDaoInterface
     */
    private $projectConfigurationDao;

    /**
     * @var ModuleStateServiceInterface
     */
    private $moduleStateService;

    /**
     * @param ModuleConfigurationHandlingServiceInterface $moduleConfigurationHandlingService
     * @param ModuleServicesActivationServiceInterface   $moduleServicesActivationService
     * @param ExtensionChainServiceInterface              $classExtensionChainService
     * @param ProjectConfigurationDaoInterface            $projectConfigurationDao
     * @param ModuleStateServiceInterface                 $moduleStateService
     */
    public function __construct(
        ModuleConfigurationHandlingServiceInterface $moduleConfigurationHandlingService,
        ModuleServicesActivationServiceInterface $moduleServicesActivationService,
        ExtensionChainServiceInterface $classExtensionChainService,
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        ModuleStateServiceInterface $moduleStateService
    ) {
        $this->moduleConfigurationHandlingService = $moduleConfigurationHandlingService;
        $this->moduleServicesActivationService = $moduleServicesActivationService;
        $this->classExtensionChainService = $classExtensionChainService;
        $this->projectConfigurationDao = $projectConfigurationDao;
        $this->moduleStateService = $moduleStateService;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function refresh(ModuleConfiguration $moduleConfiguration, int $shopId)
    {
        $this->moduleConfigurationHandlingService->handleOnRefresh($moduleConfiguration, $shopId);
        $this->moduleServicesActivationService->activateModuleServices($moduleConfiguration->getId(), $shopId);
        $this->classExtensionChainService->updateChain($shopId);
    }

    /**
     * @param string $moduleId
     *
     * @return int[]
     */
    public function refreshActiveModule(string $moduleId): array
    {
        $refreshedShopIds = [];
        $projectConfiguration = $this->projectConfigurationDao->getConfiguration();

        foreach ($projectConfiguration->getShopConfigurationIds() as $shopId) {
            $shopConfiguration = $projectConfiguration->getShopConfiguration($shopId);

            if (!$shopConfiguration->hasModuleConfiguration($moduleId)) {
                continue;
            }

            if (!$this->moduleStateService->isActive($moduleId, $shopId)) {
                continue;
            }

            $this->refresh($shopConfiguration->getModuleConfiguration($moduleId), $shopId);
            $refreshedShopIds[] = $shopId;
        }

        return $refreshedShopIds;
    }
}
