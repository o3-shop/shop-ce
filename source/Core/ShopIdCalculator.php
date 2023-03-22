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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Calculates Shop id from request data or shop url.
 *
 * @internal Do not make a module extension for this class.
 */
class ShopIdCalculator
{
    /** Shop id which is used for CE/PE eShops. */
    const BASE_SHOP_ID = 1;

    /** @var array */
    private static $urlMap;

    /** @var FileCache */
    private $variablesCache;

    /**
     * @param FileCache $variablesCache
     */
    public function __construct($variablesCache)
    {
        $this->variablesCache = $variablesCache;
    }

    /**
     * Returns active shop id. This method works independently from other classes.
     *
     * @return string
     */
    public function getShopId()
    {
        return static::BASE_SHOP_ID;
    }

    /**
     * Returns configuration key. This method is independent from oxConfig functionality.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getConfKey" in next major
     */
    protected function _getConfKey() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     * Returns shop url to id map from config.
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getShopUrlMap" in next major
     */
    protected function _getShopUrlMap() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        //get from static cache
        if (isset(self::$urlMap)) {
            return self::$urlMap;
        }

        //get from file cache
        $aMap = $this->getVariablesCache()->getFromCache("urlMap");
        if (!is_null($aMap)) {
            self::$urlMap = $aMap;

            return $aMap;
        }

        $aMap = [];

        $sSelect = "SELECT oxshopid, oxvarname, oxvarvalue " .
            "FROM oxconfig WHERE oxvarname in ('aLanguageURLs','sMallShopURL','sMallSSLShopURL')";

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster();
        $oRs = $masterDb->select($sSelect);

        if ($oRs && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $iShp = (int) $oRs->fields[0];
                $sVar = $oRs->fields[1];
                $sURL = $oRs->fields[2];

                if ($sVar == 'aLanguageURLs') {
                    $aUrls = unserialize($sURL);
                    if (is_array($aUrls) && count($aUrls)) {
                        $aUrls = array_filter($aUrls);
                        $aUrls = array_fill_keys($aUrls, $iShp);
                        $aMap = array_merge($aMap, $aUrls);
                    }
                } elseif ($sURL) {
                    $aMap[$sURL] = $iShp;
                }

                $oRs->fetchRow();
            }
        }

        //save to cache
        $this->getVariablesCache()->setToCache("urlMap", $aMap);
        self::$urlMap = $aMap;

        return $aMap;
    }

    /**
     * @return FileCache
     */
    protected function getVariablesCache()
    {
        return $this->variablesCache;
    }
}
