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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\FinalizingModuleActivationEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\BeforeModuleDeactivationEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\FinalizingModuleDeactivationEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleActivationService implements ModuleActivationServiceInterface
{
    /**
     * @var ModuleConfigurationDaoInterface
     */
    private $moduleConfigurationDao;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ModuleConfigurationHandlingServiceInterface
     */
    private $moduleConfigurationHandlingService;

    /**
     * @var ModuleStateServiceInterface
     */
    private $stateService;

    /**
     * @var ExtensionChainServiceInterface
     */
    private $classExtensionChainService;

    /**
     * @var ModuleServicesActivationServiceInterface
     */
    private $moduleServicesActivationService;

    /**
     * ModuleActivationService constructor.
     *
     * @param ModuleConfigurationDaoInterface             $moduleConfigurationDao
     * @param EventDispatcherInterface                    $eventDispatcher
     * @param ModuleConfigurationHandlingServiceInterface $moduleSettingsHandlingService
     * @param ModuleStateServiceInterface                 $stateService
     * @param ExtensionChainServiceInterface              $classExtensionChainService
     * @param ModuleServicesActivationServiceInterface    $moduleServicesActivationService
     */
    public function __construct(
        ModuleConfigurationDaoInterface $moduleConfigurationDao,
        EventDispatcherInterface $eventDispatcher,
        ModuleConfigurationHandlingServiceInterface $moduleSettingsHandlingService,
        ModuleStateServiceInterface $stateService,
        ExtensionChainServiceInterface $classExtensionChainService,
        ModuleServicesActivationServiceInterface $moduleServicesActivationService
    ) {
        $this->moduleConfigurationDao = $moduleConfigurationDao;
        $this->eventDispatcher = $eventDispatcher;
        $this->moduleConfigurationHandlingService = $moduleSettingsHandlingService;
        $this->stateService = $stateService;
        $this->classExtensionChainService = $classExtensionChainService;
        $this->moduleServicesActivationService = $moduleServicesActivationService;
    }


    /**
     * @param string $moduleId
     * @param int    $shopId
     *
     * @throws ModuleSetupException
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateIsAlreadySetException
     */
    public function activate(string $moduleId, int $shopId)
    {
        if ($this->stateService->isActive($moduleId, $shopId) === true) {
            throw new ModuleSetupException('Module with id "' . $moduleId . '" is already active.');
        }

        $moduleConfiguration = $this->moduleConfigurationDao->get($moduleId, $shopId);

        $this->moduleConfigurationHandlingService->handleOnActivation($moduleConfiguration, $shopId);

        $this->moduleServicesActivationService->activateModuleServices($moduleId, $shopId);

        $this->stateService->setActive($moduleId, $shopId);

        $moduleConfiguration->setConfigured(true);
        $this->moduleConfigurationDao->save($moduleConfiguration, $shopId);

        $this->classExtensionChainService->updateChain($shopId);

        $this->eventDispatcher->dispatch(
            FinalizingModuleActivationEvent::NAME,
            new FinalizingModuleActivationEvent($shopId, $moduleId)
        );
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     *
     * @throws ModuleSetupException
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateIsAlreadySetException
     */
    public function deactivate(string $moduleId, int $shopId)
    {
        if ($this->stateService->isActive($moduleId, $shopId) === false) {
            throw new ModuleSetupException('Module with id "' . $moduleId . '" is not active.');
        }

        $this->eventDispatcher->dispatch(
            BeforeModuleDeactivationEvent::NAME,
            new BeforeModuleDeactivationEvent($shopId, $moduleId)
        );

        $moduleConfiguration = $this->moduleConfigurationDao->get($moduleId, $shopId);

        $this->moduleConfigurationHandlingService->handleOnDeactivation($moduleConfiguration, $shopId);

        $this->moduleServicesActivationService->deactivateModuleServices($moduleId, $shopId);

        $this->stateService->setDeactivated($moduleId, $shopId);

        $moduleConfiguration->setConfigured(false);
        $this->moduleConfigurationDao->save($moduleConfiguration, $shopId);

        $this->classExtensionChainService->updateChain($shopId);

        $this->eventDispatcher->dispatch(
            FinalizingModuleDeactivationEvent::NAME,
            new FinalizingModuleDeactivationEvent($shopId, $moduleId)
        );
    }
}
