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

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\MissingServiceException;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\SystemServiceOverwriteException;
use Psr\Container\ContainerInterface;

class DIConfigWrapper
{
    const SERVICE_SECTION = 'services';
    const RESOURCE_KEY = 'resource';
    const IMPORTS_SECTION = 'imports';

    private $sectionDefaults = [self::SERVICE_SECTION => ['_defaults' => ['public' => false, 'autowire' => true]]];

    /**
     * @var array
     */
    private $configArray;

    /**
     * DIConfigWrapper constructor.
     *
     * @param array $configArray
     */
    public function __construct(array $configArray)
    {
        $this->configArray = $configArray;
    }

    /**
     * @param string $importFilePath
     * @return void
     */
    public function addImport(string $importFilePath): void
    {
        $this->addSectionIfMissing($this::IMPORTS_SECTION);
        foreach ($this->getImports() as $import) {
            if ($import[$this::RESOURCE_KEY] === $importFilePath) {
                return;
            }
        }
        $this->configArray[$this::IMPORTS_SECTION][] = [$this::RESOURCE_KEY => $importFilePath];
    }

    /**
     * @return array
     */
    public function getImportFileNames(): array
    {
        $importFileNames = [];
        foreach ($this->getImports() as $import) {
            $importFileNames[] = $import[$this::RESOURCE_KEY];
        }
        return $importFileNames;
    }

    /**
     * @param string $importFilePath
     */
    public function removeImport(string $importFilePath)
    {
        $imports = [];
        foreach ($this->getImports() as $import) {
            if ($import[$this::RESOURCE_KEY] !== $importFilePath) {
                $imports[] = $import;
            }
        }
        $this->configArray[$this::IMPORTS_SECTION] = $imports;
    }

    /**
     * @param string $serviceKey
     *
     * @return bool
     */
    public function hasService(string $serviceKey): bool
    {
        try {
            $this->getService($serviceKey);
        } catch (MissingServiceException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $serviceKey
     *
     * @return DIServiceWrapper
     * @throws MissingServiceException
     */
    public function getService(string $serviceKey): DIServiceWrapper
    {
        /** @var DIServiceWrapper $service */
        foreach ($this->getServices() as $service) {
            if ($service->getKey() === $serviceKey) {
                return $service;
            }
        }
        throw new MissingServiceException("Service $serviceKey not found");
    }

    /**
     * @param DIServiceWrapper $service
     */
    public function addOrUpdateService(DIServiceWrapper $service)
    {
        $this->addSectionIfMissing($this::SERVICE_SECTION);
        $this->configArray[$this::SERVICE_SECTION][$service->getKey()] = $service->getServiceAsArray();
    }

    /**
     * @return array
     */
    public function getConfigAsArray(): array
    {
        $this->cleanUpConfig();

        return $this->configArray;
    }

    /**
     * @param ContainerInterface $container
     *
     * @throws SystemServiceOverwriteException
     */
    public function checkServices(ContainerInterface $container)
    {
        /** @var DIServiceWrapper $service */
        foreach ($this->getServices() as $service) {
            if ($container->has($service->getKey())) {
                throw new SystemServiceOverwriteException($service->getKey() . ' is already defined');
            }
        }
    }

    /**
     * Checks that the service classes configured for a
     * module / package are really usable. It may happen
     * that module code is still in the modules directory
     * but is removed from autoloading. In this case, the
     * services.yaml file should not be evaluated in any
     * way.
     *
     * @return bool
     */
    public function checkServiceClassesCanBeLoaded(): bool
    {
        foreach ($this->getServices() as $service) {
            if (! $service->checkClassExists()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    private function getImports(): array
    {
        if (!array_key_exists($this::IMPORTS_SECTION, $this->configArray)) {
            return [];
        }

        return $this->configArray[$this::IMPORTS_SECTION];
    }

    /**
     * @return DIServiceWrapper[]
     */
    public function getServices(): array
    {
        if (!array_key_exists($this::SERVICE_SECTION, $this->configArray)) {
            return [];
        }
        $services = [];
        foreach ($this->configArray[$this::SERVICE_SECTION] as $serviceId => $serviceArguments) {
            $services[] = new DIServiceWrapper($serviceId, $serviceArguments ?? []);
        }
        return $services;
    }

    /**
     * Removes not activated services and
     * empty import or service sections from the array
     */
    private function cleanUpConfig()
    {
        $this->removeInactiveServices();
        $this->removeEmptySections();
    }

    /**
     * Removes section entries when they are empty
     */
    private function removeEmptySections()
    {
        $sections = [$this::IMPORTS_SECTION, $this::SERVICE_SECTION];
        foreach ($sections as $section) {
            if (
                array_key_exists($section, $this->configArray) &&
                (!$this->configArray[$section] || !count($this->configArray[$section]))
            ) {
                unset($this->configArray[$section]);
            }
        }
    }
    private function removeInactiveServices()
    {
        /** @var DIServiceWrapper $service */
        foreach ($this->getServices() as $service) {
            if ($service->isShopAware() && !$service->hasActiveShops()) {
                $this->removeService($service);
            }
        }
    }

    /**
     * @param DIServiceWrapper $service
     */
    private function removeService(DIServiceWrapper $service)
    {
        unset($this->configArray['services'][$service->getKey()]);
    }

    /**
     * @param string $section
     */
    private function addSectionIfMissing($section)
    {
        if (!array_key_exists($section, $this->configArray)) {
            if (array_key_exists($section, $this->sectionDefaults)) {
                $this->configArray[$section] = $this->sectionDefaults[$section];
            } else {
                $this->configArray[$section] = [];
            }
        }
    }
}
