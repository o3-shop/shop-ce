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

use Exception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Article attributes manager.
 * Collects and keeps attributes of chosen article.
 *
 */
class Attribute extends MultiLanguageModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxattribute';

    /**
     * Selected attribute value
     *
     * @var string
     */
    protected $_sActiveValue = null;

    /**
     * Attribute title
     *
     * @var string
     */
    protected $_sTitle = null;

    /**
     * Attribute values
     *
     * @var array
     */
    protected $_aValues = null;

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxattribute');
    }

    /**
     * Removes attributes from articles, returns true on success.
     *
     * @param null $sOXID Object ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($sOXID = null)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }

        if (!$this->canDeleteAttribute($sOXID)) {
            return false;
        }

        // remove attributes from articles also
        $oDb = DatabaseProvider::getDb();
        $sDelete = "delete from oxobject2attribute where oxattrid = :oxattrid";
        $oDb->execute($sDelete, [
            ':oxattrid' => $sOXID
        ]);

        // #657 ADDITIONAL removes attribute connection to category
        $sDelete = "delete from oxcategory2attribute where oxattrid = :oxattrid";
        $oDb->execute($sDelete, [
            ':oxattrid' => $sOXID
        ]);

        return parent::delete($sOXID);
    }

    /**
     * Assigns attribute to variant
     *
     * @param array $aMDVariants article IDs with selection-list values
     * @param array $aSelTitle selection list titles
     * @throws Exception
     */
    public function assignVarToAttribute($aMDVariants, $aSelTitle)
    {
        $myLang = Registry::getLang();
        $aConfLanguages = $myLang->getLanguageIds();
        $sAttrId = $this->_getAttrId($aSelTitle[0]);
        if (!$sAttrId) {
            $sAttrId = $this->_createAttribute($aSelTitle);
        }
        foreach ($aMDVariants as $sVarId => $oValue) {
            if (strpos($sVarId, "mdvar_") === 0) {
                foreach ($oValue as $sId) {
                    $sVarId = substr($sVarId, 6);
                    $oNewAssign = oxNew(BaseModel::class);
                    $oNewAssign->init("oxobject2attribute");
                    $sNewId = Registry::getUtilsObject()->generateUID();
                    if ($oNewAssign->load($sId)) {
                        $oNewAssign->oxobject2attribute__oxobjectid = new Field($sVarId);
                        $oNewAssign->setId($sNewId);
                        $oNewAssign->save();
                    }
                }
            } else {
                $oNewAssign = oxNew(MultiLanguageModel::class);
                $oNewAssign->setEnableMultilang(false);
                $oNewAssign->init("oxobject2attribute");
                $oNewAssign->oxobject2attribute__oxobjectid = new Field($sVarId);
                $oNewAssign->oxobject2attribute__oxattrid = new Field($sAttrId);
                foreach ($aConfLanguages as $sKey => $sLang) {
                    $sPrefix = $myLang->getLanguageTag($sKey);
                    $oNewAssign->{'oxobject2attribute__oxvalue' . $sPrefix} = new Field($oValue[$sKey]->name);
                }
                $oNewAssign->save();
            }
        }
    }

    /**
     * Searches for attribute by oxtitle. If exists returns attribute id
     *
     * @param string $sSelTitle selection list title
     *
     * @return false|string attribute id or false
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAttrId" in next major
     */
    protected function _getAttrId($sSelTitle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDB();
        $sAttViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxattribute');

        return $oDb->getOne("select oxid from $sAttViewName where LOWER(oxtitle) = :oxtitle ", [
            ':oxtitle' => Str::getStr()->strtolower($sSelTitle)
        ]);
    }

    /**
     * Checks if attribute exists
     *
     * @param array $aSelTitle selection list title
     *
     * @return string attribute id
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "createAttribute" in next major
     */
    protected function _createAttribute($aSelTitle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myLang = Registry::getLang();
        $aConfLanguages = $myLang->getLanguageIds();
        $oAttr = oxNew(MultiLanguageModel::class);
        $oAttr->setEnableMultilang(false);
        $oAttr->init('oxattribute');
        foreach ($aConfLanguages as $sKey => $sLang) {
            $sPrefix = $myLang->getLanguageTag($sKey);
            $oAttr->{'oxattribute__oxtitle' . $sPrefix} = new Field($aSelTitle[$sKey]);
        }
        $oAttr->save();

        return $oAttr->getId();
    }

    /**
     * Returns all oxobject2attribute Ids of article
     *
     * @param string $sArtId article ids
     *
     * @return array|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getAttributeAssigns($sArtId)
    {
        if ($sArtId) {
            $oDb = DatabaseProvider::getDb();

            $sSelect = "select o2a.oxid from oxobject2attribute as o2a ";
            $sSelect .= "where o2a.oxobjectid = :oxobjectid order by o2a.oxpos";

            $aIds = [];
            $rs = $oDb->select($sSelect, [
                ':oxobjectid' => $sArtId
            ]);
            if ($rs && $rs->count() > 0) {
                while (!$rs->EOF) {
                    $aIds[] = $rs->fields[0];
                    $rs->fetchRow();
                }
            }

            return $aIds;
        }
    }


    /**
     * Set attribute title
     *
     * @param string $sTitle - attribute title
     */
    public function setTitle($sTitle)
    {
        $this->_sTitle = Str::getStr()->htmlspecialchars($sTitle);
    }

    /**
     * Get attribute Title
     *
     * @return String
     */
    public function getTitle()
    {
        return $this->_sTitle;
    }

    /**
     * Add attribute value
     *
     * @param string $sValue - attribute value
     */
    public function addValue($sValue)
    {
        $this->_aValues[] = Str::getStr()->htmlspecialchars($sValue);
    }

    /**
     * Set attribute selected value
     *
     * @param string $sValue - attribute value
     */
    public function setActiveValue($sValue)
    {
        $this->_sActiveValue = Str::getStr()->htmlspecialchars($sValue);
    }

    /**
     * Get attribute Selected value
     *
     * @return String
     */
    public function getActiveValue()
    {
        return $this->_sActiveValue;
    }

    /**
     * Get attribute values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->_aValues;
    }

    /**
     * Checks if possible to delete attribute.
     *
     * @param string $oxId
     *
     * @return bool
     */
    protected function canDeleteAttribute($oxId)
    {
        $canDelete = true;
        if (!$oxId) {
            $canDelete = false;
        }

        return $canDelete;
    }
}
