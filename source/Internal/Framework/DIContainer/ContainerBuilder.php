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

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDao;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ProjectYamlImportService;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class ContainerBuilder
{
    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @param BasicContextInterface $context
     */
    public function __construct(BasicContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @return SymfonyContainerBuilder
     * @throws \Exception
     */
    public function getContainer(): SymfonyContainerBuilder
    {
        $symfonyContainer = new SymfonyContainerBuilder();
        $symfonyContainer->addCompilerPass(new RegisterListenersPass(EventDispatcherInterface::class));
        $symfonyContainer->addCompilerPass(new AddConsoleCommandPass());
        $this->loadEditionServices($symfonyContainer);
        $this->loadProjectServices($symfonyContainer);

        return $symfonyContainer;
    }

    /**
     * Loads a 'project.yaml' file if it can be found in the shop directory.
     *
     * @param SymfonyContainerBuilder $symfonyContainer
     * @throws \Exception
     */
    private function loadProjectServices(SymfonyContainerBuilder $symfonyContainer)
    {
        $editionServices = array_merge($symfonyContainer->getDefinitions(), $symfonyContainer->getAliases());
        $loader = new YamlFileLoader($symfonyContainer, new FileLocator());
        try {
            $this->cleanupProjectYaml();
            $loader->load($this->context->getGeneratedServicesFilePath());
        } catch (FileLocatorFileNotFoundException $exception) {
            // In case generated services file not found, do nothing.
        }
        try {
            $loader->load($this->context->getConfigurableServicesFilePath());
        } catch (FileLocatorFileNotFoundException $exception) {
            // In case manually created services file not found, do nothing.
        }
        $this->makeProjectServicesPublic($symfonyContainer, $editionServices);
    }

    /**
     * Symfony 3.4 made services public by default, Symfony 5.4 makes them private.
     * OXID 6.4-era modules fetch their own services by id
     * (ContainerFactory::getInstance()->getContainer()->get(...)), so every service
     * a module or project file defines is made public again, as it was under 3.4.
     *
     * ponytail: this also overrides an explicit "public: false". Symfony 5.4's
     * YamlFileLoader drops the marker for a "_defaults" flag, so an explicit
     * private flag cannot be told apart from none. Private only restricts get(),
     * so making such a service public is harmless.
     *
     * @param SymfonyContainerBuilder $symfonyContainer
     * @param array                   $editionServices definitions and aliases loaded before the project files
     */
    private function makeProjectServicesPublic(SymfonyContainerBuilder $symfonyContainer, array $editionServices)
    {
        $editionServiceIds = array_flip(array_map('spl_object_id', $editionServices));
        $allServices = array_merge($symfonyContainer->getDefinitions(), $symfonyContainer->getAliases());
        foreach ($allServices as $service) {
            if (!isset($editionServiceIds[spl_object_id($service)])) {
                $service->setPublic(true);
            }
        }
    }

    /**
     * Removes imports from modules that have deleted on the file system.
     */
    private function cleanupProjectYaml()
    {
        $projectYamlDao = new ProjectYamlDao($this->context, new Filesystem());
        $yamlImportService = new ProjectYamlImportService($projectYamlDao, $this->context);
        $yamlImportService->removeNonExistingImports();
    }

    /**
     * @param SymfonyContainerBuilder $symfonyContainer
     * @throws \Exception
     */
    private function loadEditionServices(SymfonyContainerBuilder $symfonyContainer)
    {
        foreach ($this->getEditionsRootPaths() as $path) {
            $servicesLoader = new YamlFileLoader($symfonyContainer, new FileLocator($path));
            $servicesLoader->load('Internal/services.yaml');
        }
    }

    /**
     * @return array
     */
    private function getEditionsRootPaths(): array
    {
        $allEditionPaths = [
            BasicContext::COMMUNITY_EDITION => [
                $this->context->getCommunityEditionSourcePath(),
            ],
        ];

        return $allEditionPaths[$this->context->getEdition()];
    }
}
