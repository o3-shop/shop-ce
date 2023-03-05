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

namespace OxidEsales\EshopCommunity\Application\Model;

use oxDb;

/**
 * Shop view validator.
 * checks which views are valid / invalid
 *
 */
class ShopViewValidator
{
    protected $_aMultiLangTables = [];

    protected $_aMultiShopTables = [];

    protected $_aLanguages = [];

    protected $_aAllShopLanguages = [];

    protected $_iShopId = null;

    protected $_aAllViews = [];

    protected $_aShopViews = [];

    protected $_aValidShopViews = [];

    /**
     * Sets multi language tables.
     *
     * @param null $aMultiLangTables
     */
    public function setMultiLangTables($aMultiLangTables)
    {
        $this->_aMultiLangTables = $aMultiLangTables;
    }

    /**
     * Returns multi lang tables
     *
     * @return array
     */
    public function getMultiLangTables()
    {
        return $this->_aMultiLangTables;
    }


    /**
     * Sets multi shop tables.
     *
     * @param array $aMultiShopTables
     */
    public function setMultiShopTables($aMultiShopTables)
    {
        $this->_aMultiShopTables = $aMultiShopTables;
    }

    /**
     * Returns multi shop tables
     *
     * @return array
     */
    public function getMultiShopTables()
    {
        return $this->_aMultiShopTables;
    }

    /**
     * Returns list of active languages in shop
     *
     * @param array $aLanguages
     */
    public function setLanguages($aLanguages)
    {
        $this->_aLanguages = $aLanguages;
    }

    /**
     * Gets languages.
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->_aLanguages;
    }

    /**
     * Returns list of active languages in shop
     *
     * @param array $aAllShopLanguages
     */
    public function setAllShopLanguages($aAllShopLanguages)
    {
        $this->_aAllShopLanguages = $aAllShopLanguages;
    }

    /**
     * Gets all shop languages.
     *
     * @return array
     */
    public function getAllShopLanguages()
    {
        return $this->_aAllShopLanguages;
    }


    /**
     * Sets shop id.
     *
     * @param integer $iShopId
     */
    public function setShopId($iShopId)
    {
        $this->_iShopId = $iShopId;
    }

    /**
     * Returns list of available shops
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->_iShopId;
    }

    /**
     * Returns list of all shop views
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAllViews" in next major
     */
    protected function _getAllViews() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (empty($this->_aAllViews)) {
            $this->_aAllViews = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getCol("SHOW TABLES LIKE  'oxv\_%'");
        }

        return $this->_aAllViews;
    }

    /**
     * Checks if given view name belongs to current subshop or is general view
     *
     * @param string $sViewName View name
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isCurrentShopView" in next major
     */
    protected function _isCurrentShopView($sViewName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blResult = false;

        $blEndsWithShopId = preg_match("/[_]([0-9]+)$/", $sViewName, $aMatchEndsWithShopId);
        $blContainsShopId = preg_match("/[_]([0-9]+)[_]/", $sViewName, $aMatchContainsShopId);

        if (
            (!$blEndsWithShopId && !$blContainsShopId) ||
            ($blEndsWithShopId && $aMatchEndsWithShopId[1] == $this->getShopId()) ||
            ($blContainsShopId && $aMatchContainsShopId[1] == $this->getShopId())
        ) {
            $blResult = true;
        }

        return $blResult;
    }


    /**
     * Returns list of shop specific views currently in database
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getShopViews" in next major
     */
    protected function _getShopViews() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (empty($this->_aShopViews)) {
            $this->_aShopViews = [];
            $aAllViews = $this->_getAllViews();

            foreach ($aAllViews as $sView) {
                if ($this->_isCurrentShopView($sView)) {
                    $this->_aShopViews[] = $sView;
                }
            }
        }

        return $this->_aShopViews;
    }

    /**
     * Returns list of valid shop views
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getValidShopViews" in next major
     */
    protected function _getValidShopViews() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (empty($this->_aValidShopViews)) {
            $aTables = $this->getShopTables();
            $this->_aValidShopViews = [];

            foreach ($aTables as $sTable) {
                $this->prepareShopTableViewNames($sTable);
            }
        }

        return $this->_aValidShopViews;
    }

    /**
     * Get list of shop tables
     *
     * @return array
     */
    protected function getShopTables()
    {
        $shopTables = $this->getMultilangTables();

        return $shopTables;
    }

    /**
     * Appends possible table views to $this->_aValidShopViews variable.
     *
     * @param string $tableName
     */
    protected function prepareShopTableViewNames($tableName)
    {
        $this->_aValidShopViews[] = 'oxv_' . $tableName;

        if (in_array($tableName, $this->getMultiLangTables())) {
            foreach ($this->getAllShopLanguages() as $sLang) {
                $this->_aValidShopViews[] = 'oxv_' . $tableName . '_' . $sLang;
            }
        }
    }

    /**
     * Checks if view name is valid according to current config
     *
     * @param string $sViewName View name
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isViewValid" in next major
     */
    protected function _isViewValid($sViewName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return in_array($sViewName, $this->_getValidShopViews());
    }

    /**
     * Returns list of invalid views
     *
     * @return array
     */
    public function getInvalidViews()
    {
        $aInvalidViews = [];
        $aShopViews = $this->_getShopViews();

        foreach ($aShopViews as $sView) {
            if (!$this->_isViewValid($sView)) {
                $aInvalidViews[] = $sView;
            }
        }

        return $aInvalidViews;
    }
}
