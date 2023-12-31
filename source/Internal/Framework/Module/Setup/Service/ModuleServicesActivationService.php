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

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIConfigWrapper;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIServiceWrapper;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\NoServiceYamlException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\ServicesYamlConfigurationErrorEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ServicesYamlConfigurationError;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\PathUtil\Path;

class ModuleServicesActivationService implements ModuleServicesActivationServiceInterface
{

    /**
     * @var ProjectYamlDaoInterface $dao
     */
    private $dao;

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    public $eventDispatcher;

    /**
     * @var ModulePathResolverInterface
     */
    private $modulePathResolver;

    /**
     * @var ModuleStateServiceInterface
     */
    private $moduleStateService;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * ModuleServicesActivationService constructor.
     *
     * @param ProjectYamlDaoInterface     $dao
     * @param EventDispatcherInterface    $eventDispatcher
     * @param ModulePathResolverInterface $modulePathResolver
     * @param ModuleStateServiceInterface $moduleStateService
     * @param ContextInterface            $context
     */
    public function __construct(
        ProjectYamlDaoInterface $dao,
        EventDispatcherInterface $eventDispatcher,
        ModulePathResolverInterface $modulePathResolver,
        ModuleStateServiceInterface $moduleStateService,
        ContextInterface $context
    ) {
        $this->dao = $dao;
        $this->eventDispatcher = $eventDispatcher;
        $this->modulePathResolver = $modulePathResolver;
        $this->moduleStateService = $moduleStateService;
        $this->context = $context;
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     * @return void
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\MissingServiceException
     */
    public function activateModuleServices(string $moduleId, int $shopId)
    {
        $moduleConfigFile = $this->getModuleServicesFilePath($moduleId, $shopId);
        try {
            $moduleConfig = $this->getModuleConfig($moduleConfigFile);
        } catch (NoServiceYamlException $e) {
            return;
        };

        $projectConfig = $this->dao->loadProjectConfigFile();
        $projectConfig->addImport($this->getRelativeModuleConfigFilePath($moduleConfigFile));

        /** @var DIServiceWrapper $service */
        foreach ($moduleConfig->getServices() as $service) {
            if (!$service->isShopAware()) {
                continue;
            }
            if ($projectConfig->hasService($service->getKey())) {
                $service = $projectConfig->getService($service->getKey());
            }
            $service->addActiveShops([$shopId]);
            $projectConfig->addOrUpdateService($service);
        }

        $this->dao->saveProjectConfigFile($projectConfig);
    }


    /**
     * @param string $moduleId
     * @param int    $shopId
     * @return void
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\MissingServiceException
     */
    public function deactivateModuleServices(string $moduleId, int $shopId)
    {
        $moduleConfigFile = $this->getModuleServicesFilePath($moduleId, $shopId);
        try {
            $moduleConfig = $this->getModuleConfig($moduleConfigFile);
        } catch (NoServiceYamlException $e) {
            return;
        } catch (ServicesYamlConfigurationError $e) {
            // it could never have been activated so there is nothing to deactivate
            // and we can safely ignore this deactivation request
            return;
        }

        $projectConfig = $this->dao->loadProjectConfigFile();

        $this->cleanupShopAwareServices($projectConfig, $moduleConfig, $shopId);

        if ($this->isLastActiveShop($moduleId, $shopId)) {
            $projectConfig->removeImport($this->getRelativeModuleConfigFilePath($moduleConfigFile));
        }

        $this->dao->saveProjectConfigFile($projectConfig);
    }

    private function isLastActiveShop(string $moduleId, int $currentShopId): bool
    {
        foreach ($this->context->getAllShopIds() as $shopId) {
            if ($shopId === $currentShopId) {
                continue;
            }
            if ($this->moduleStateService->isActive($moduleId, $shopId)) {
                return false;
            }
        }
        return true;
    }

    private function cleanupShopAwareServices(
        DIConfigWrapper $projectConfig,
        DIConfigWrapper $moduleConfig,
        int $shopId
    ) {
        foreach ($moduleConfig->getServices() as $service) {
            if ($service->isShopAware() && $projectConfig->hasService($service->getKey())) {
                $service = $projectConfig->getService($service->getKey());
                $service->removeActiveShops([$shopId]);
                $projectConfig->addOrUpdateService($service);
            }
        }
    }

    /**
     * @param string $moduleConfigFile
     *
     * @return DIConfigWrapper
     * @throws NoServiceYamlException
     * @throws ServicesYamlConfigurationError
     */
    private function getModuleConfig(string $moduleConfigFile): DIConfigWrapper
    {
        if (!file_exists($moduleConfigFile)) {
            throw new NoServiceYamlException();
        }

        $moduleConfig = $this->dao->loadDIConfigFile($moduleConfigFile);
        if (!$moduleConfig->checkServiceClassesCanBeLoaded()) {
            $this->eventDispatcher->dispatch(
                ServicesYamlConfigurationErrorEvent::NAME,
                new ServicesYamlConfigurationErrorEvent(
                    'Service class can not be loaded',
                    $moduleConfigFile
                )
            );
            throw new ServicesYamlConfigurationError();
        }

        return $moduleConfig;
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     *
     * @return string
     */
    private function getModuleServicesFilePath(string $moduleId, int $shopId): string
    {
        return $this->modulePathResolver->getFullModulePathFromConfiguration($moduleId, $shopId)
            . DIRECTORY_SEPARATOR . 'services.yaml';
    }

    /**
     * @param string $moduleConfigFile
     * @return string
     */
    private function getRelativeModuleConfigFilePath(string $moduleConfigFile): string
    {
        return Path::makeRelative(
            $moduleConfigFile,
            Path::getDirectory($this->context->getGeneratedServicesFilePath())
        );
    }
}
