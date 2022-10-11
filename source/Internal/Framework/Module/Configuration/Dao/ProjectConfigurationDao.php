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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ProjectConfigurationIsEmptyException;
use Symfony\Component\Filesystem\Filesystem;

class ProjectConfigurationDao implements ProjectConfigurationDaoInterface
{
    /**
     * @var ShopConfigurationDaoInterface
     */
    private $shopConfigurationDao;

    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * ProjectConfigurationDao constructor.
     * @param ShopConfigurationDaoInterface $shopConfigurationDao
     * @param BasicContextInterface $context
     * @param Filesystem $fileSystem
     */
    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        BasicContextInterface $context,
        Filesystem $fileSystem
    ) {
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->context = $context;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @return ProjectConfiguration
     * @throws ProjectConfigurationIsEmptyException
     */
    public function getConfiguration(): ProjectConfiguration
    {
        if ($this->isConfigurationEmpty()) {
            throw new ProjectConfigurationIsEmptyException('Project configuration cannot be empty.');
        }

        return $this->getConfigurationFromStorage();
    }

    /**
     * @param ProjectConfiguration $configuration
     */
    public function save(ProjectConfiguration $configuration)
    {
        $this->shopConfigurationDao->deleteAll();

        foreach ($configuration->getShopConfigurations() as $shopId => $shopConfiguration) {
            $this->shopConfigurationDao->save($shopConfiguration, $shopId);
        }
    }

    /**
     * @return bool
     */
    public function isConfigurationEmpty(): bool
    {
        return $this->projectConfigurationDirectoryExists() === false;
    }

    /**
     * @return ProjectConfiguration
     */
    private function getConfigurationFromStorage(): ProjectConfiguration
    {
        $projectConfiguration = new ProjectConfiguration();

        foreach ($this->shopConfigurationDao->getAll() as $shopId => $shopConfiguration) {
            $projectConfiguration->addShopConfiguration(
                $shopId,
                $shopConfiguration
            );
        }

        return $projectConfiguration;
    }

    private function projectConfigurationDirectoryExists(): bool
    {
        return $this->fileSystem->exists(
            $this->context->getProjectConfigurationDirectory()
        );
    }
}
