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

namespace OxidEsales\EshopCommunity\Core\Module;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Module\Module as EshopModule;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;

/**
 * Modules installer class.
 *
 * @deprecated since v6.4.0 (2019-02-14); Use service "OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface".
 * @internal Do not make a module extension for this class.
 */
class ModuleInstaller extends \OxidEsales\Eshop\Core\Base
{
    /**
     * @var \OxidEsales\Eshop\Core\Module\ModuleCache
     */
    protected $_oModuleCache;

    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Core\Module\ModuleCache             $moduleCache
     * @param \OxidEsales\Eshop\Core\Module\ModuleExtensionsCleaner $moduleCleaner
     */
    public function __construct(\OxidEsales\Eshop\Core\Module\ModuleCache $moduleCache = null, $moduleCleaner = null)
    {
        $this->setModuleCache($moduleCache);
    }

    /**
     * Sets module cache.
     *
     * @param \OxidEsales\Eshop\Core\Module\ModuleCache $oModuleCache
     */
    public function setModuleCache($oModuleCache)
    {
        $this->_oModuleCache = $oModuleCache;
    }

    /**
     * Gets module cache.
     *
     * @return \OxidEsales\Eshop\Core\Module\ModuleCache
     */
    public function getModuleCache()
    {
        return $this->_oModuleCache;
    }

    /**
     * Activate extension by merging module class inheritance information with shop module array
     *
     * @param EshopModule $module
     *
     * @return bool
     */
    public function activate(EshopModule $module)
    {
        $this
            ->getModuleActivationBridge()
            ->activate(
                $module->getId(),
                Registry::getConfig()->getShopId()
            );

        return true;
    }

    /**
     * Deactivate extension by adding disable module class information to disabled module array
     *
     * @param EshopModule $module
     *
     * @return bool
     */
    public function deactivate(EshopModule $module)
    {
        $this
            ->getModuleActivationBridge()
            ->deactivate(
                $module->getId(),
                Registry::getConfig()->getShopId()
            );

        return true;
    }

    /**
     * Get parsed modules
     *
     * @return array
     */
    public function getModulesWithExtendedClass()
    {
        return $this->getConfig()->getModulesWithExtendedClass();
    }

    /**
     * Build module chains from nested array
     *
     * @param array $aModuleArray Module array (nested format)
     *
     * @return array
     */
    public function buildModuleChains($aModuleArray)
    {
        $aModules = [];
        if (is_array($aModuleArray)) {
            foreach ($aModuleArray as $sClass => $aModuleChain) {
                $aModules[$sClass] = implode('&', $aModuleChain);
            }
        }

        return $aModules;
    }

    /**
     * Diff two nested module arrays together so that the values of
     * $aRmModuleArray are removed from $aAllModuleArray
     *
     * @param array $aAllModuleArray All Module array (nested format)
     * @param array $aRemModuleArray Remove Module array (nested format)
     *
     * @return array
     */
    public function diffModuleArrays($aAllModuleArray, $aRemModuleArray)
    {
        if (is_array($aAllModuleArray) && is_array($aRemModuleArray)) {
            foreach ($aAllModuleArray as $sClass => $aModuleChain) {
                if (!is_array($aModuleChain)) {
                    $aModuleChain = [$aModuleChain];
                }
                if (isset($aRemModuleArray[$sClass])) {
                    if (!is_array($aRemModuleArray[$sClass])) {
                        $aRemModuleArray[$sClass] = [$aRemModuleArray[$sClass]];
                    }
                    $aAllModuleArray[$sClass] = [];
                    foreach ($aModuleChain as $sModule) {
                        if (!in_array($sModule, $aRemModuleArray[$sClass])) {
                            $aAllModuleArray[$sClass][] = $sModule;
                        }
                    }
                    if (!count($aAllModuleArray[$sClass])) {
                        unset($aAllModuleArray[$sClass]);
                    }
                } else {
                    $aAllModuleArray[$sClass] = $aModuleChain;
                }
            }
        }

        return $aAllModuleArray;
    }

    /**
     * @return ModuleActivationBridgeInterface
     */
    private function getModuleActivationBridge(): ModuleActivationBridgeInterface
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleActivationBridgeInterface::class);
    }
}
