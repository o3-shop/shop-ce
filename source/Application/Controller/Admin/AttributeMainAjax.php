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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use oxRegistry;
use oxDb;
use oxField;

/**
 * Class manages article attributes
 */
class AttributeMainAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_blAllowExtColumns = true;

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
        ['oxartnum', 'oxarticles', 1, 0, 0],
        ['oxtitle', 'oxarticles', 1, 1, 0],
        ['oxean', 'oxarticles', 1, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1]
    ],
                                 'container2' => [
                                     ['oxartnum', 'oxarticles', 1, 0, 0],
                                     ['oxtitle', 'oxarticles', 1, 1, 0],
                                     ['oxean', 'oxarticles', 1, 0, 0],
                                     ['oxmpn', 'oxarticles', 0, 0, 0],
                                     ['oxprice', 'oxarticles', 0, 0, 0],
                                     ['oxstock', 'oxarticles', 0, 0, 0],
                                     ['oxid', 'oxobject2attribute', 0, 0, 1]
                                 ]
    ];

    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = $this->getConfig();
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $sArticleTable = $this->_getViewName('oxarticles');
        $sOCatView = $this->_getViewName('oxobject2category');
        $sOAttrView = $this->_getViewName('oxobject2attribute');

        $sDelId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('oxid');
        $sSynchDelId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sDelId) {
            // performance
            $sQAdd = " from $sArticleTable where 1 ";
            $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArticleTable.oxparentid = '' ";
        } elseif ($sSynchDelId && $sDelId != $sSynchDelId) {
            // selected category ?
            $blVariantsSelectionParameter = $myConfig->getConfigParam('blVariantsSelection');
            $sSqlIfTrue = " ( {$sArticleTable}.oxid=oxobject2category.oxobjectid " .
                          "or {$sArticleTable}.oxparentid=oxobject2category.oxobjectid)";
            $sSqlIfFalse = " {$sArticleTable}.oxid=oxobject2category.oxobjectid ";
            $sVariantSelectionSql = $blVariantsSelectionParameter ? $sSqlIfTrue : $sSqlIfFalse;
            $sQAdd = " from {$sOCatView} as oxobject2category left join {$sArticleTable} on {$sVariantSelectionSql}" .
                     " where oxobject2category.oxcatnid = " . $oDb->quote($sDelId) . " ";
        } else {
            $sQAdd = " from {$sOAttrView} left join {$sArticleTable} " .
                     "on {$sArticleTable}.oxid={$sOAttrView}.oxobjectid " .
                     "where {$sOAttrView}.oxattrid = " . $oDb->quote($sDelId) .
                     " and {$sArticleTable}.oxid is not null ";
        }

        if ($sSynchDelId && $sSynchDelId != $sDelId) {
            $sQAdd .= " and {$sArticleTable}.oxid not in ( select {$sOAttrView}.oxobjectid from {$sOAttrView} " .
                      "where {$sOAttrView}.oxattrid = " . $oDb->quote($sSynchDelId) . " ) ";
        }

        return $sQAdd;
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addFilter" in next major
     */
    protected function _addFilter($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sQ = parent::_addFilter($sQ);

        // display variants or not ?
        if ($this->getConfig()->getConfigParam('blVariantsSelection')) {
            $sQ .= ' group by ' . $this->_getViewName('oxarticles') . '.oxid ';

            $oStr = getStr();
            if ($oStr->strpos($sQ, "select count( * ) ") === 0) {
                $sQ = "select count( * ) from ( {$sQ} ) as _cnttable";
            }
        }

        return $sQ;
    }

    /**
     * Removes article from Attribute list
     */
    public function removeAttrArticle()
    {
        $aChosenCat = $this->_getActionIds('oxobject2attribute.oxid');

        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sO2AttributeView = $this->_getViewName('oxobject2attribute');

            $sQ = parent::_addFilter("delete $sO2AttributeView.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCat)) {
            $sChosenCategories = implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aChosenCat));
            $sQ = "delete from oxobject2attribute where oxobject2attribute.oxid in (" . $sChosenCategories . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds article to Attribute list
     */
    public function addAttrArticle()
    {
        $aAddArticle = $this->_getActionIds('oxarticles.oxid');
        $soxId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        // adding
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sArticleTable = $this->_getViewName('oxarticles');
            $aAddArticle = $this->_getAll($this->_addFilter("select $sArticleTable.oxid " . $this->_getQuery()));
        }

        $oAttribute = oxNew(\OxidEsales\Eshop\Application\Model\Attribute::class);

        if ($oAttribute->load($soxId) && is_array($aAddArticle)) {
            foreach ($aAddArticle as $sAdd) {
                $oNewGroup = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $oNewGroup->init("oxobject2attribute");
                $oNewGroup->oxobject2attribute__oxobjectid = new \OxidEsales\Eshop\Core\Field($sAdd);
                $oNewGroup->oxobject2attribute__oxattrid = new \OxidEsales\Eshop\Core\Field($oAttribute->oxattribute__oxid->value);
                $oNewGroup->save();

                $this->onArticleAddToAttributeList($sAdd);
            }
        }
    }

    /**
     * Method used to overload.
     *
     * @param string $articleId
     */
    protected function onArticleAddToAttributeList($articleId)
    {
    }
}
