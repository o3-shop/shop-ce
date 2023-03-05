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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;

/**
 * Admin article main deliveryset manager.
 * There is possibility to change deliveryset name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main Sets.
 */
class ModuleMain extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates deliveryset category tree,
     * passes data to Smarty engine and returns name of template file "deliveryset_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("moduleId")) {
            $sModuleId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("moduleId");
        } else {
            $sModuleId = $this->getEditObjectId();
        }

        $oModule = oxNew(\OxidEsales\Eshop\Core\Module\Module::class);

        if ($sModuleId) {
            if ($oModule->load($sModuleId)) {
                $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getTplLanguage();

                $this->_aViewData["oModule"] = $oModule;
                $this->_aViewData["sModuleName"] = basename($oModule->getInfo("title", $iLang));
                $this->_aViewData["sModuleId"] = str_replace("/", "_", $oModule->getModulePath());
            } else {
                \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay(new \OxidEsales\Eshop\Core\Exception\StandardException('EXCEPTION_MODULE_NOT_LOADED'));
            }
        }

        parent::render();

        return 'module_main.tpl';
    }

    /**
     * Activate module
     *
     * @return null
     */
    public function activateModule()
    {
        if ($this->getConfig()->isDemoShop()) {
            Registry::getUtilsView()->addErrorToDisplay('MODULE_ACTIVATION_NOT_POSSIBLE_IN_DEMOMODE');
            return;
        }

        try {
            $moduleActivationBridge = $this->getContainer()->get(ModuleActivationBridgeInterface::class);
            $moduleActivationBridge->activate(
                $this->getEditObjectId(),
                Registry::getConfig()->getShopId()
            );

            $this->_aViewData['updatenav'] = '1';
        } catch (\Exception $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception);
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
    }

    /**
     * Deactivate module
     *
     * @return null
     */
    public function deactivateModule()
    {
        if ($this->getConfig()->isDemoShop()) {
            Registry::getUtilsView()->addErrorToDisplay('MODULE_ACTIVATION_NOT_POSSIBLE_IN_DEMOMODE');
            return;
        }

        try {
            $moduleActivationBridge = $this->getContainer()->get(ModuleActivationBridgeInterface::class);
            $moduleActivationBridge->deactivate(
                $this->getEditObjectId(),
                Registry::getConfig()->getShopId()
            );

            $this->_aViewData['updatenav'] = '1';
        } catch (\Exception $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception);
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
    }
}
