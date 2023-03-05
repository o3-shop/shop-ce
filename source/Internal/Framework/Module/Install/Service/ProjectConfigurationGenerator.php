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

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;

class ProjectConfigurationGenerator implements ProjectConfigurationGeneratorInterface
{
    /**
     * @var ProjectConfigurationDaoInterface
     */
    private $projectConfigurationDao;

    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * DefaultProjectConfigurationGenerator constructor.
     * @param ProjectConfigurationDaoInterface $projectConfigurationDao
     * @param BasicContextInterface            $context
     */
    public function __construct(
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        BasicContextInterface $context
    ) {
        $this->projectConfigurationDao = $projectConfigurationDao;
        $this->context = $context;
    }

    /**
     * Generates default project configuration.
     */
    public function generate(): void
    {
        $this->projectConfigurationDao->save($this->createProjectConfiguration());
    }

    /**
     * @return ProjectConfiguration
     */
    private function createProjectConfiguration(): ProjectConfiguration
    {
        $projectConfiguration = new ProjectConfiguration();

        foreach ($this->context->getAllShopIds() as $shopId) {
            $projectConfiguration->addShopConfiguration($shopId, new ShopConfiguration());
        }

        return $projectConfiguration;
    }
}
