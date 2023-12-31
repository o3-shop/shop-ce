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

use oxRegistry;
use oxDb;
use oxField;
use oxUtilsView;

/**
 * News manager.
 * Performs news text collection. News may be sorted by user categories (only
 * these user may read news), etc.
 * @deprecated 6.5.6 "News" feature will be removed completely
 */
class News extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel
{
    /**
     * User group object (default null).
     *
     * @var object
     */
    protected $_oGroups = null;

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxnews';

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxnews');
    }

    /**
     * Assigns object data.
     *
     * @param string $dbRecord database record to be assigned
     */
    public function assign($dbRecord)
    {
        parent::assign($dbRecord);

        // convert date's to international format
        if ($this->oxnews__oxdate) {
            $this->oxnews__oxdate->setValue(\OxidEsales\Eshop\Core\Registry::getUtilsDate()->formatDBDate($this->oxnews__oxdate->value));
        }
    }

    /**
     * Returns list of user groups assigned to current news object
     *
     * @return oxlist
     */
    public function getGroups()
    {
        if ($this->_oGroups == null && $sOxid = $this->getId()) {
            // usergroups
            $this->_oGroups = oxNew('oxlist', 'oxgroups');
            $sViewName = getViewName("oxgroups", $this->getLanguage());
            $sSelect = "select {$sViewName}.* from {$sViewName}, oxobject2group ";
            $sSelect .= "where oxobject2group.oxobjectid = :oxobjectid ";
            $sSelect .= "and oxobject2group.oxgroupsid={$sViewName}.oxid ";
            $this->_oGroups->selectString($sSelect, [
                ':oxobjectid' => $sOxid
            ]);
        }

        return $this->_oGroups;
    }

    /**
     * Checks if this object is in group, returns true on success.
     *
     * @param string $sGroupID user group ID
     *
     * @return bool
     */
    public function inGroup($sGroupID)
    {
        $blResult = false;
        $aGroups = $this->getGroups();
        foreach ($aGroups as $oObject) {
            if ($oObject->_sOXID == $sGroupID) {
                $blResult = true;
                break;
            }
        }

        return $blResult;
    }

    /**
     * Deletes object information from DB, returns true on success.
     *
     * @param string $sOxid Object ID (default null)
     *
     * @return bool
     */
    public function delete($sOxid = null)
    {
        if (!$sOxid) {
            $sOxid = $this->getId();
        }
        if (!$sOxid) {
            return false;
        }

        if ($blDelete = parent::delete($sOxid)) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("delete from oxobject2group where oxobject2group.oxobjectid = :oxobjectid", [
                ':oxobjectid' => $sOxid
            ]);
        }

        return $blDelete;
    }

    /**
     * Updates object information in DB.
     * @deprecated underscore prefix violates PSR12, will be renamed to "update" in next major
     */
    protected function _update() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->oxnews__oxdate->setValue(\OxidEsales\Eshop\Core\Registry::getUtilsDate()->formatDBDate($this->oxnews__oxdate->value, true));

        parent::_update();
    }

    /**
     * Inserts object details to DB, returns true on success.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$this->oxnews__oxdate || \OxidEsales\Eshop\Core\Registry::getUtilsDate()->isEmptyDate($this->oxnews__oxdate->value)) {
            // if date field is empty, assigning current date
            $this->oxnews__oxdate = new \OxidEsales\Eshop\Core\Field(date('Y-m-d'));
        } else {
            $this->oxnews__oxdate = new \OxidEsales\Eshop\Core\Field(\OxidEsales\Eshop\Core\Registry::getUtilsDate()->formatDBDate($this->oxnews__oxdate->value, true));
        }

        return parent::_insert();
    }

    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFieldData" in next major
     */
    protected function _setFieldData($sFieldName, $sValue, $iDataType = \OxidEsales\Eshop\Core\Field::T_TEXT) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        switch (strtolower($sFieldName)) {
            case 'oxlongdesc':
            case 'oxnews__oxlongdesc':
                $iDataType = \OxidEsales\Eshop\Core\Field::T_RAW;
                break;
        }
        return parent::_setFieldData($sFieldName, $sValue, $iDataType);
    }

    /**
     * get long description, parsed through smarty
     *
     * @return string
     */
    public function getLongDesc()
    {
        /** @var \OxidEsales\Eshop\Core\UtilsView $oUtilsView */
        $oUtilsView = \OxidEsales\Eshop\Core\Registry::getUtilsView();
        return $oUtilsView->parseThroughSmarty($this->oxnews__oxlongdesc->getRawValue(), $this->getId() . $this->getLanguage(), null, true);
    }
}
