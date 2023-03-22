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

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\FileCache;
use OxidEsales\Eshop\Core\Registry;

/**
 * Selects module variables from database or cache.
 *
 * @internal Do not make a module extension for this class.
 */
class ModuleVariablesLocator
{
    /** @var array Static cache for module information variables. */
    protected static $moduleVariables = [];

    /** @var FileCache */
    private $fileCache;

    /** @var ShopIdCalculator */
    private $shopIdCalculator;

    /**
     * @param FileCache        $fileCache
     * @param ShopIdCalculator $shopIdCalculator
     */
    public function __construct($fileCache, $shopIdCalculator)
    {
        $this->fileCache = $fileCache;
        $this->shopIdCalculator = $shopIdCalculator;
    }

    /**
     * Retrieves module configuration variable for the base shop.
     * Currently getModuleVar() is expected to be called with one of the values: aModules | aDisabledModules | aModulePaths
     * This method is independent from oxConfig functionality.
     *
     * @param string $name Configuration array name
     *
     * @return array
     */
    public function getModuleVariable($name)
    {
        if (isset(self::$moduleVariables[$name])) {
            return self::$moduleVariables[$name];
        }
        $cache = $this->getFileCache();

        //first try to get it from cache
        $value = $cache->getFromCache($name);

        if (is_null($value)) {
            $value = $this->getModuleVarFromDB($name);
            $cache->setToCache($name, $value);
        }

        self::$moduleVariables[$name] = $value;

        return $value;
    }

    /**
     * Sets module information variable. The variable is set statically and is not saved for future.
     *
     * @param string $name  Configuration array name
     * @param array  $value Module name values
     */
    public function setModuleVariable($name, $value)
    {
        if (is_null($value)) {
            self::$moduleVariables = null;
        } else {
            self::$moduleVariables[$name] = $value;
        }

        $this->getFileCache()->setToCache($name, $value);
    }

    /**
     * Resets previously set module information.
     *
     * @static
     */
    public static function resetModuleVariables()
    {
        self::$moduleVariables = [];
        FileCache::clearCache();
    }

    /**
     * Returns configuration key. This method is independent from oxConfig functionality.
     *
     * @return string
     */
    protected function getConfigurationKey()
    {
        if (Registry::instanceExists(\OxidEsales\Eshop\Core\ConfigFile::class)) {
            $config = Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class);
        } else {
            $config = new \OxidEsales\Eshop\Core\ConfigFile(getShopBasePath() . '/config.inc.php');
            Registry::set(\OxidEsales\Eshop\Core\ConfigFile::class, $config);
        }
        return $config->getVar('sConfigKey') ?: Config::DEFAULT_CONFIG_KEY;
    }

    /**
     * Returns shop module variable value directly from database.
     *
     * @param string $name Module variable name
     *
     * @return string
     */
    protected function getModuleVarFromDB($name)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster();

        $shopId = $this->getShopIdCalculator()->getShopId();

        $query = "SELECT oxvarvalue FROM oxconfig WHERE oxvarname = :oxvarname AND oxshopid = :oxshopid";
        $value = $masterDb->getOne($query, [
            ':oxvarname' => $name,
            ':oxshopid'  => $shopId
        ]);

        return unserialize($value);
    }

    /**
     * @return FileCache
     */
    protected function getFileCache()
    {
        return $this->fileCache;
    }

    /**
     * @return ShopIdCalculator
     */
    protected function getShopIdCalculator()
    {
        return $this->shopIdCalculator;
    }
}
