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

namespace OxidEsales\EshopCommunity\Setup;

use OxidEsales\Eshop\Core\Edition\EditionPathProvider;
use OxidEsales\Facts\Facts;
use oxSystemComponentException;

/**
 * Core setup class, setup instance holder
 */
class Core
{
    /**
     * Keeps instance cache
     *
     * @var array
     */
    protected static $_aInstances = [];

    /**
     * Returns requested instance object
     *
     * @param string $sInstanceName instance name
     *
     * @return Core
     */
    public function getInstance($sInstanceName)
    {
        if (strpos($sInstanceName, '\\') === false) {
            $sInstanceName = $this->getClass($sInstanceName);
        }
        if (!isset(Core::$_aInstances[$sInstanceName])) {
            Core::$_aInstances[$sInstanceName] = new $sInstanceName();
        }

        return Core::$_aInstances[$sInstanceName];
    }

    /**
     * Only used for convenience in UNIT tests by doing so we avoid
     * writing extended classes for testing protected or private methods
     *
     * @param string $sMethod Methods name
     * @param array  $aArgs   Argument array
     *
     * @throws oxSystemComponentException Throws an exception if the called method does not exist or is not accessable in current class
     *
     * @return string
     */
    public function __call($sMethod, $aArgs)
    {
        if (defined('OXID_PHP_UNIT')) {
            if (substr($sMethod, 0, 4) == "UNIT") {
                $sMethod = str_replace("UNIT", "_", $sMethod);
            }
            if (method_exists($this, $sMethod)) {
                return call_user_func_array([& $this, $sMethod], $aArgs);
            }
        }

        throw new \OxidEsales\Eshop\Core\Exception\SystemComponentException("Function '$sMethod' does not exist or is not accessible! (" . get_class($this) . ")" . PHP_EOL);
    }

    /**
     * Methods returns class according edition.
     *
     * @param string $sInstanceName
     *
     * @return string
     */
    protected function getClass($sInstanceName)
    {
        $facts = new Facts();
        $class =  'OxidEsales\\EshopCommunity\\Setup\\' . $sInstanceName;

        return $class;
    }

    /**
     * @return Setup
     */
    protected function getSetupInstance()
    {
        return $this->getInstance("Setup");
    }

    /**
     * @return Language
     */
    protected function getLanguageInstance()
    {
        return $this->getInstance("Language");
    }

    /**
     * @return Utilities
     */
    protected function getUtilitiesInstance()
    {
        return $this->getInstance("Utilities");
    }

    /**
     * @return Session
     */
    protected function getSessionInstance()
    {
        return $this->getInstance("Session");
    }

    /**
     * @return Database
     */
    protected function getDatabaseInstance()
    {
        return $this->getInstance("Database");
    }

    /**
     * Return true if user already decided to overwrite database.
     *
     * @return bool
     */
    protected function userDecidedOverwriteDB()
    {
        $userDecidedOverwriteDatabase = false;

        $overwriteCheck = $this->getUtilitiesInstance()->getRequestVar("ow", "get");
        $session = $this->getSessionInstance();

        if (isset($overwriteCheck) || $session->getSessionParam('blOverwrite')) {
            $userDecidedOverwriteDatabase = true;
        }

        return $userDecidedOverwriteDatabase;
    }

    /**
     * Return true if user already decided to ignore database recommended version related warnings.
     *
     * @return bool
     */
    protected function userDecidedIgnoreDBWarning()
    {
        $userDecidedIgnoreDBWarning = false;

        $overwriteCheck = $this->getUtilitiesInstance()->getRequestVar("owrec", "get");
        $session = $this->getSessionInstance();

        if (isset($overwriteCheck) || $session->getSessionParam('blIgnoreDbRecommendations')) {
            $userDecidedIgnoreDBWarning = true;
        }

        return $userDecidedIgnoreDBWarning;
    }

    /**
     * Check if class exists.
     * Ignore autoloader exceptions which might appear if database does not exist.
     *
     * @param string $className
     *
     * @return bool
     */
    private function classExists($className)
    {
        try {
            $classExists = class_exists($className);
        } catch (\Exception $e) {
            return false;
        }

        return $classExists;
    }
}
