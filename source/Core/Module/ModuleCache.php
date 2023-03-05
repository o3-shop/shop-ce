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

/**
 * Module cache events handler class.
 *
 * @deprecated since v6.4.0 (2019-03-22); ModuleCache moved to Internal\Framework\Module package.
 * @internal Do not make a module extension for this class.
 */
class ModuleCache extends \OxidEsales\Eshop\Core\Base
{
    /**
     * @var \OxidEsales\Eshop\Core\Module\Module
     */
    protected $_oModule = null;

    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Core\Module\Module $_oModule
     */
    public function __construct(\OxidEsales\Eshop\Core\Module\Module $_oModule)
    {
        $this->_oModule = $_oModule;
    }

    /**
     * Sets module.
     *
     * @param \OxidEsales\Eshop\Core\Module\Module $oModule
     */
    public function setModule($oModule)
    {
        $this->_oModule = $oModule;
    }

    /**
     * Gets module.
     *
     * @return \OxidEsales\Eshop\Core\Module\Module
     */
    public function getModule()
    {
        return $this->_oModule;
    }

    /**
     * Resets template, language and menu xml cache
     */
    public function resetCache()
    {
        $aTemplates = $this->getModule()->getTemplates();
        $oUtils = Registry::getUtils();
        $oUtils->resetTemplateCache($aTemplates);
        $oUtils->resetLanguageCache();
        $oUtils->resetMenuCache();

        ModuleVariablesLocator::resetModuleVariables();

        $this->_clearApcCache();
    }

    /**
     * Cleans PHP APC cache
     * @deprecated underscore prefix violates PSR12, will be renamed to "clearApcCache" in next major
     */
    protected function _clearApcCache() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            apc_clear_cache();
        }
    }
}
