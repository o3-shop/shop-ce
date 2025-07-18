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
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use stdClass;

/**
 * Attribute list manager.
 *
 */
class AttributeList extends ListModel
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('oxattribute');
    }

    /**
     * Load all attributes by article IDs
     *
     * @param array $aIds article id's
     *
     * @return array|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadAttributesByIds($aIds)
    {
        if (!count($aIds)) {
            return;
        }

        $sAttrViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxattribute');
        $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2attribute');

        $oxObjectIdsSql = implode(',', DatabaseProvider::getDb()->quoteArray($aIds));

        $sSelect = "select $sAttrViewName.oxid, $sAttrViewName.oxtitle, {$sViewName}.oxvalue, {$sViewName}.oxobjectid ";
        $sSelect .= "from {$sViewName} left join $sAttrViewName on $sAttrViewName.oxid = {$sViewName}.oxattrid ";
        $sSelect .= "where {$sViewName}.oxobjectid in ( " . $oxObjectIdsSql . " ) ";
        $sSelect .= "order by {$sViewName}.oxpos, $sAttrViewName.oxpos";

        return $this->_createAttributeListFromSql($sSelect);
    }

    /**
     * Fills array with keys and products with value
     *
     * @param string $sSelect SQL select
     *
     * @return array $aAttributes
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "createAttributeListFromSql" in next major
     */
    protected function _createAttributeListFromSql($sSelect) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aAttributes = [];
        $rs = DatabaseProvider::getDb()->select($sSelect);
        if ($rs && $rs->count() > 0) {
            while (!$rs->EOF) {
                if (!isset($aAttributes[$rs->fields[0]])) {
                    $aAttributes[$rs->fields[0]] = new stdClass();
                }

                $aAttributes[$rs->fields[0]]->title = $rs->fields[1];
                if (!isset($aAttributes[$rs->fields[0]]->aProd[$rs->fields[3]])) {
                    $aAttributes[$rs->fields[0]]->aProd[$rs->fields[3]] = new stdClass();
                }
                $aAttributes[$rs->fields[0]]->aProd[$rs->fields[3]]->value = $rs->fields[2];
                $rs->fetchRow();
            }
        }

        return $aAttributes;
    }

    /**
     * Load attributes by article ID
     *
     * @param string $sArticleId article id
     * @param null $sParentId article parent id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadAttributes($sArticleId, $sParentId = null)
    {
        if ($sArticleId) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            $sAttrViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxattribute');
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2attribute');

            $sSelect = "select {$sAttrViewName}.`oxid`, {$sAttrViewName}.`oxtitle`, o2a.`oxvalue` from {$sViewName} as o2a ";
            $sSelect .= "left join {$sAttrViewName} on {$sAttrViewName}.oxid = o2a.oxattrid ";
            $sSelect .= "where o2a.oxobjectid = :oxobjectid and o2a.oxvalue != '' ";
            $sSelect .= "order by o2a.oxpos, {$sAttrViewName}.oxpos";

            $aAttributes = $oDb->getAll($sSelect, [
                ':oxobjectid' => $sArticleId
            ]);

            if ($sParentId) {
                $aParentAttributes = $oDb->getAll($sSelect, [
                    ':oxobjectid' => $sParentId
                ]);
                $aAttributes = $this->_mergeAttributes($aAttributes, $aParentAttributes);
            }

            $this->assignArray($aAttributes);
        }
    }

    /**
     * Load displayable in basket/order attributes by article ID
     *
     * @param string $sArtId article ids
     * @param null $sParentId parent id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadAttributesDisplayableInBasket($sArtId, $sParentId = null)
    {
        if ($sArtId) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            $sAttrViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxattribute');
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2attribute');

            $sSelect = "select o2a.*, {$sAttrViewName}.* from $sViewName as o2a ";
            $sSelect .= "left join {$sAttrViewName} on {$sAttrViewName}.oxid = o2a.oxattrid ";
            $sSelect .= "where o2a.oxobjectid = :oxobjectid and {$sAttrViewName}.oxdisplayinbasket  = 1 and o2a.oxvalue != '' ";
            $sSelect .= "order by o2a.oxpos, {$sAttrViewName}.oxpos";

            $aAttributes = $oDb->getAll($sSelect, [
                ':oxobjectid' => $sArtId
            ]);

            if ($sParentId) {
                $aParentAttributes = $oDb->getAll($sSelect, [
                    ':oxobjectid' => $sParentId
                ]);
                $aAttributes = $this->_mergeAttributes($aAttributes, $aParentAttributes);
            }

            $this->assignArray($aAttributes);
        }
    }

    /**
     * get category attributes by category ID
     *
     * @param string $sCategoryId category Id
     * @param integer $iLang language No
     *
     * @return object;
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCategoryAttributes($sCategoryId, $iLang)
    {
        $aSessionFilter = Registry::getSession()->getVariable('session_attrfilter');

        $oArtList = oxNew(ArticleList::class);
        $oArtList->loadCategoryIDs($sCategoryId, $aSessionFilter);

        // Only if we have articles
        if (count($oArtList) > 0) {
            $oDb = DatabaseProvider::getDb();
            $sArtIds = '';
            foreach (array_keys($oArtList->getArray()) as $sId) {
                if ($sArtIds) {
                    $sArtIds .= ',';
                }
                $sArtIds .= $oDb->quote($sId);
            }

            $sAttTbl = Registry::get(TableViewNameGenerator::class)->getViewName('oxattribute', $iLang);
            $sO2ATbl = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2attribute', $iLang);
            $sC2ATbl = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategory2attribute', $iLang);

            $sSelect = "SELECT DISTINCT att.oxid, att.oxtitle, o2a.oxvalue " .
                       "FROM $sAttTbl as att, $sO2ATbl as o2a ,$sC2ATbl as c2a " .
                       "WHERE att.oxid = o2a.oxattrid AND c2a.oxobjectid = :oxobjectid AND c2a.oxattrid = att.oxid AND o2a.oxvalue !='' AND o2a.oxobjectid IN ($sArtIds) " .
                       "ORDER BY c2a.oxsort , att.oxpos, att.oxtitle, o2a.oxvalue";

            $rs = $oDb->select($sSelect, [
                ':oxobjectid' => $sCategoryId
            ]);

            if ($rs && $rs->count() > 0) {
                while (!$rs->EOF && list($sAttId, $sAttTitle, $sAttValue) = $rs->fields) {
                    if (!$this->offsetExists($sAttId)) {
                        $oAttribute = oxNew(Attribute::class);
                        $oAttribute->setTitle($sAttTitle);

                        $this->offsetSet($sAttId, $oAttribute);
                        $iLang = Registry::getLang()->getBaseLanguage();
                        if (isset($aSessionFilter[$sCategoryId][$iLang][$sAttId])) {
                            $oAttribute->setActiveValue($aSessionFilter[$sCategoryId][$iLang][$sAttId]);
                        }
                    } else {
                        $oAttribute = $this->offsetGet($sAttId);
                    }

                    $oAttribute->addValue($sAttValue);
                    $rs->fetchRow();
                }
            }
        }

        return $this;
    }

    /**
     * Merge attribute arrays
     *
     * @param array $aAttributes       array of attributes
     * @param array $aParentAttributes array of parent article attributes
     *
     * @return array $aAttributes
     * @deprecated underscore prefix violates PSR12, will be renamed to "mergeAttributes" in next major
     */
    protected function _mergeAttributes($aAttributes, $aParentAttributes) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (count($aParentAttributes)) {
            $aAttrIds = [];
            foreach ($aAttributes as $aAttribute) {
                $aAttrIds[] = $aAttribute['OXID'];
            }

            foreach ($aParentAttributes as $aAttribute) {
                if (!in_array($aAttribute['OXID'], $aAttrIds)) {
                    $aAttributes[] = $aAttribute;
                }
            }
        }

        return $aAttributes;
    }
}
