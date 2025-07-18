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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;

/**
 * Content list manager.
 * Collects list of content
 *
 */
class ContentList extends ListModel
{
    /**
     * Information content type
     *
     * @var int
     */
    const TYPE_INFORMATION_CONTENTS = 0;

    /**
     * Main menu list type
     *
     * @var int
     */
    const TYPE_MAIN_MENU_LIST = 1;

    /**
     * Main menu list type
     *
     * @var int
     */
    const TYPE_CATEGORY_MENU = 2;

    /**
     * Service list.
     *
     * @var int
     */
    const TYPE_SERVICE_LIST = 3;

    /**
     * List of services.
     *
     * @var array
     */
    protected $_aServiceKeys = ['oximpressum', 'oxagb', 'oxsecurityinfo', 'oxdeliveryinfo', 'oxrightofwithdrawal', 'oxorderinfo', 'oxcredits'];

    /**
     * Sets service keys.
     *
     * @param array $aServiceKeys
     */
    public function setServiceKeys($aServiceKeys)
    {
        $this->_aServiceKeys = $aServiceKeys;
    }

    /**
     * Gets services keys.
     *
     * @return array
     */
    public function getServiceKeys()
    {
        return $this->_aServiceKeys;
    }

    /**
     * Class constructor, initiates parent constructor (parent::oxList()).
     */
    public function __construct()
    {
        parent::__construct('oxcontent');
    }

    /**
     * Loads main menue entries and generates list with links
     */
    public function loadMainMenulist()
    {
        $this->_load(self::TYPE_MAIN_MENU_LIST);
    }

    /**
     * Load Array of Menue items and change keys of aList to catid
     */
    public function loadCatMenues()
    {
        $this->_load(self::TYPE_CATEGORY_MENU);
        $aArray = [];

        if ($this->count()) {
            foreach ($this as $oContent) {
                // add into category tree
                if (!isset($aArray[$oContent->getCategoryId()])) {
                    $aArray[$oContent->getCategoryId()] = [];
                }

                $aArray[$oContent->oxcontents__oxcatid->value][] = $oContent;
            }
        }

        $this->_aArray = $aArray;
    }

    /**
     * Get data from db
     *
     * @param integer $iType - type of content
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromDb" in next major
     */
    protected function _loadFromDb($iType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSql = $this->_getSQLByType($iType);
        $aData = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll($sSql);

        return $aData;
    }

    /**
     * Load category list data
     *
     * @param integer $type - type of content
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "load" in next major
     */
    protected function _load($type) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $data = $this->_loadFromDb($type);
        $this->assignArray($data);
    }

    /**
     * Load category list data.
     */
    public function loadServices()
    {
        $this->_load(self::TYPE_SERVICE_LIST);
        $this->_extractListToArray();
    }

    /**
     * Extract oxContentList object to associative array with oxloadid as keys.
     * @deprecated underscore prefix violates PSR12, will be renamed to "extractListToArray" in next major
     */
    protected function _extractListToArray() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aExtractedContents = [];
        foreach ($this as $oContent) {
            $aExtractedContents[$oContent->getLoadId()] = $oContent;
        }

        $this->_aArray = $aExtractedContents;
    }

    /**
     * Creates SQL by type.
     *
     * @param integer $iType type.
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSQLByType" in next major
     */
    protected function _getSQLByType($iType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSQLAdd = '';
        $oDb = DatabaseProvider::getDb();
        $sSQLType = " AND `oxtype` = " . $oDb->quote($iType);

        if ($iType == self::TYPE_CATEGORY_MENU) {
            $sSQLAdd = " AND `oxcatid` IS NOT NULL AND `oxsnippet` = '0'";
        }

        if ($iType == self::TYPE_SERVICE_LIST) {
            $sIdents = implode(", ", DatabaseProvider::getDb()->quoteArray($this->getServiceKeys()));
            $sSQLAdd = " AND OXLOADID IN (" . $sIdents . ")";
            $sSQLType = '';
        }
        $sViewName = $this->getBaseObject()->getViewName();
        $sSql = "SELECT * FROM {$sViewName} WHERE `oxactive` = '1' $sSQLType AND `oxshopid` = " . $oDb->quote($this->_sShopID) . " $sSQLAdd ORDER BY `oxloadid`";

        return $sSql;
    }
}
