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

use OxidEsales\Facts\Facts;

if (!function_exists('getInstallPath')) {
    /**
     * Returns shop installation directory
     *
     * @return string
     */
    function getInstallPath()
    {
        return "../";
    }
}

if (!function_exists('getSystemReqCheck')) {
    /**
     * Returns class responsible for system requirements check
     *
     * @return oxSysRequirements
     */
    function getSystemReqCheck()
    {
        $facts = new Facts();
        $systemRequirements = new \OxidEsales\EshopCommunity\Core\SystemRequirements();

        return $systemRequirements;
    }
}

if (!function_exists('getCountryList')) {
    /**
     * Includes country list for setup
     *
     * @return null
     */
    function getCountryList()
    {
        $cePath = (new Facts())->getCommunityEditionSourcePath();
        $aCountries = [];
        $relativePath = 'Application/Controller/Admin/ShopCountries.php';

        include "$cePath/$relativePath";

        return $aCountries;
    }
}

if (!function_exists('getLocation')) {
    /**
     * Includes country list for setup
     *
     * @return null
     */
    function getLocation()
    {
        $cePath = (new Facts())->getCommunityEditionSourcePath();
        $aLocationCountries = [];
        $relativePath = 'Application/Controller/Admin/ShopCountries.php';

        include "$cePath/$relativePath";

        return $aLocationCountries;
    }
}

if (!function_exists('getLanguages')) {
    /**
     * Includes country list for setup
     *
     * @return null
     */
    function getLanguages()
    {
        $cePath = (new Facts())->getCommunityEditionSourcePath();
        $aLanguages = [];
        $relativePath = 'Application/Controller/Admin/ShopCountries.php';

        include "$cePath/$relativePath";

        return $aLanguages;
    }
}

if (!function_exists('getDefaultFileMode')) {
    /**
     * Returns mode which must be set for files or folders
     *
     * @return int
     */
    function getDefaultFileMode()
    {
        return 0755;
    }
}

if (!function_exists('getDefaultConfigFileMode')) {
    /**
     * Returns mode which must be set for config file
     *
     * @return int
     */
    function getDefaultConfigFileMode()
    {
        return 0444;
    }
}

if (!function_exists('getVendorDirectory')) {
    /**
     * Returns vendors directory
     *
     * @return string
     */
    function getVendorDirectory()
    {
        return VENDOR_PATH;
    }
}

if (!class_exists("Conf", false)) {
    /**
     * Config key loader class
     *
     * @deprecated since v6.5.0 (2019-11-28); Class will be removed
     * because MySQL 8 removed ENCODE and DECODE methods
     *
     */
    class Conf // phpcs:ignore
    {
        /**
         * Conf constructor.
         */
        public function __construct()
        {
            $config = new \OxidEsales\EshopCommunity\Core\ConfigFile(getShopBasePath() . "/config.inc.php");
            $this->sConfigKey = $config->getVar('sConfigKey') ?: \OxidEsales\EshopCommunity\Core\Config::DEFAULT_CONFIG_KEY;
        }
    }
}
