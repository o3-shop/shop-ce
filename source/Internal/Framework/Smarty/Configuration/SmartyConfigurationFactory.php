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

namespace OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyContextInterface;

class SmartyConfigurationFactory implements SmartyConfigurationFactoryInterface
{
    /**
     * @var SmartyContextInterface
     */
    private $context;

    /**
     * @var SmartySettingsDataProviderInterface
     */
    private $settingsDataProvider;

    /**
     * @var SmartySecuritySettingsDataProviderInterface
     */
    private $securitySettingsDataProvider;

    /**
     * @var SmartyResourcesDataProviderInterface
     */
    private $resourcesDataProvider;

    /**
     * @var SmartyPluginsDataProviderInterface
     */
    private $pluginsDataProvider;

    /**
     * @var SmartyPrefiltersDataProviderInterface
     */
    private $prefiltersDataProvider;

    /**
     * SmartyConfigurationFactory constructor.
     *
     * @param SmartyContextInterface                      $context
     * @param SmartySettingsDataProviderInterface         $settingsDataProvider
     * @param SmartySecuritySettingsDataProviderInterface $securitySettingsDataProvider
     * @param SmartyResourcesDataProviderInterface        $resourcesDataProvider
     * @param SmartyPrefiltersDataProviderInterface       $prefiltersDataProvider
     * @param SmartyPluginsDataProviderInterface          $pluginsDataProvider
     */
    public function __construct(
        SmartyContextInterface $context,
        SmartySettingsDataProviderInterface $settingsDataProvider,
        SmartySecuritySettingsDataProviderInterface $securitySettingsDataProvider,
        SmartyResourcesDataProviderInterface $resourcesDataProvider,
        SmartyPrefiltersDataProviderInterface $prefiltersDataProvider,
        SmartyPluginsDataProviderInterface $pluginsDataProvider
    ) {
        $this->context = $context;
        $this->settingsDataProvider = $settingsDataProvider;
        $this->securitySettingsDataProvider = $securitySettingsDataProvider;
        $this->resourcesDataProvider = $resourcesDataProvider;
        $this->prefiltersDataProvider = $prefiltersDataProvider;
        $this->pluginsDataProvider = $pluginsDataProvider;
    }

    /**
     * @return SmartyConfigurationInterface
     */
    public function getConfiguration(): SmartyConfigurationInterface
    {
        $smartyConfiguration = new SmartyConfiguration();
        $smartyConfiguration->setSettings($this->settingsDataProvider->getSettings());
        if ($this->context->getTemplateSecurityMode()) {
            $smartyConfiguration->setSecuritySettings($this->securitySettingsDataProvider->getSecuritySettings());
        }
        $smartyConfiguration->setResources($this->resourcesDataProvider->getResources());
        $smartyConfiguration->setPrefilters($this->prefiltersDataProvider->getPrefilterPlugins());
        $smartyConfiguration->setPlugins($this->pluginsDataProvider->getPlugins());

        return $smartyConfiguration;
    }
}
