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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;

/**
 * Admin actionss manager.
 * Sets list template, list object class ('oxactions') and default sorting
 * field ('oxactions.oxtitle').
 * Admin Menu: Manage Products -> Actions.
 */
class ModuleList extends \OxidEsales\Eshop\Application\Controller\Admin\AdminListController
{
    /**
     * @var array Loaded modules array
     *
     */
    protected $_aModules = [];


    /**
     * Calls parent::render() and returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData['mylist'] = $this->getInstalledModules();

        return 'module_list.tpl';
    }

    /**
     * @return array
     */
    private function getInstalledModules(): array
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $shopConfiguration = $container->get(ShopConfigurationDaoBridgeInterface::class)->get();

        $modules = [];

        foreach ($shopConfiguration->getModuleConfigurations() as $moduleConfiguration) {
            $module = oxNew(Module::class);
            $module->load($moduleConfiguration->getId());
            $modules[] = $module;
        }

        $modules = $this->sortModulesByTitleAlphabetically($modules);
        $modules = $this->convertModulesToAssociativeArray($modules);

        return $modules;
    }

    /**
     * @param array $modules
     * @return array
     */
    private function sortModulesByTitleAlphabetically(array $modules): array
    {
        usort($modules, function ($a, $b) {
            return strcmp($a->getTitle(), $b->getTitle());
        });

        return $modules;
    }

    /**
     * @param array $modules
     * @return array
     */
    private function convertModulesToAssociativeArray(array $modules): array
    {
        $modulesAssociativeArray = [];

        foreach ($modules as $module) {
            $modulesAssociativeArray[$module->getId()] = $module;
        }

        return $modulesAssociativeArray;
    }
}
