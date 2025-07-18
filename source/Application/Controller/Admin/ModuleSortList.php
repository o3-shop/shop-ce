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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ClassExtensionChainBridgeInterface;

/**
 * Extensions sorting list handler.
 * Admin Menu: Extensions -> Module -> Installed Shop Modules.
 */
class ModuleSortList extends AdminDetailsController
{
    /**
     * It is unsafe to use a backslash as HTML id in conjunction with UI.sortable, so it will be replaced in the
     * view and restored in the controller
     */
    const BACKSLASH_REPLACEMENT = '---';

    /**
     * Executes parent method parent::render(), loads active and disabled extensions,
     * checks if there are some deleted and registered modules and returns name of template file "module_sortlist.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $oModuleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);

        $classExtensionsChain = $this
            ->getShopConfiguration()
            ->getClassExtensionsChain();

        $sanitizedExtendClass = [];
        foreach ($classExtensionsChain as $extendedClass => $classChain) {
            $sanitizedKey = str_replace("\\", self::BACKSLASH_REPLACEMENT, $extendedClass);
            $sanitizedExtendClass[$sanitizedKey] = $classChain;
        }

        $this->_aViewData["aExtClasses"] = $sanitizedExtendClass;
        $this->_aViewData["aDisabledModules"] = $oModuleList->getDisabledModuleClasses();

        // checking if there are any deleted extensions
        if (!Registry::getSession()->getVariable("blSkipDeletedExtChecking")) {
            $aDeletedExt = $oModuleList->getDeletedExtensions();

            if (!empty($aDeletedExt)) {
                $this->_aViewData["aDeletedExt"] = $aDeletedExt;
            }
        }

        return 'module_sortlist.tpl';
    }

    /**
     * Saves updated aModules config var
     */
    public function save()
    {
        $classExtensionsChainFromRequest = json_decode(
            Registry::getRequest()->getRequestEscapedParameter('aModules'),
            true
        );

        $sanitizedClassExtensionsChain = $this->sanitizeClassExtensionsChain($classExtensionsChainFromRequest);

        $container = $this->getContainer();
        $shopConfigurationDao = $container->get(ShopConfigurationDaoBridgeInterface::class);
        $shopConfiguration = $shopConfigurationDao->get();

        $chain = $shopConfiguration->getClassExtensionsChain();
        $chain->setChain($sanitizedClassExtensionsChain);

        $shopConfiguration->setClassExtensionsChain($chain);

        $shopConfigurationDao->save($shopConfiguration);

        $container->get(ClassExtensionChainBridgeInterface::class)->updateChain(
            Registry::getConfig()->getShopId()
        );
    }

    /**
     * Removes extension metadata from Shop
     *
     * @return void
     */
    public function remove()
    {
        //if user selected not to update modules, skipping all updates
        if (Registry::getRequest()->getRequestEscapedParameter('noButton')) {
            Registry::getSession()->setVariable("blSkipDeletedExtChecking", true);

            return;
        }

        $oModuleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
        $oModuleList->cleanup();
    }

    /**
     * @param array $chain
     * @return array
     */
    private function sanitizeClassExtensionsChain(array $chain): array
    {
        $sanitizedClassExtensionsChain = [];

        foreach ($chain as $key => $value) {
            $sanitizedKey = str_replace(self::BACKSLASH_REPLACEMENT, "\\", $key);
            $sanitizedClassExtensionsChain[$sanitizedKey] = $value;
        }

        return $sanitizedClassExtensionsChain;
    }

    /**
     * @return ShopConfiguration
     */
    private function getShopConfiguration(): ShopConfiguration
    {
        return $this->getContainer()->get(ShopConfigurationDaoBridgeInterface::class)->get();
    }
}
