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

use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages vendor assignment to articles
 */
class VendorMainAjax extends ListComponentAjax
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
    protected $_aColumns = ['container1' => [ // field , table,       visible, multilanguage, ident
            ['oxartnum', 'oxarticles', 1, 0, 0],
            ['oxtitle', 'oxarticles', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxarticles', 0, 0, 1],
        ],
        'container2' => [
            ['oxartnum', 'oxarticles', 1, 0, 0],
            ['oxtitle', 'oxarticles', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxarticles', 0, 0, 1],
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // looking for table/view
        $sArtTable = $this->_getViewName('oxarticles');
        $sO2CView = $this->_getViewName('oxobject2category');
        $oDb = DatabaseProvider::getDb();
        $oConfig = Registry::getConfig();
        $oRequest = Registry::getRequest();
        
        $sVendorId = $oRequest->getRequestEscapedParameter('oxid');
        $sSynchVendorId = $oRequest->getRequestEscapedParameter('synchoxid');

        // vendor selected or not ?
        if (!$sVendorId) {
            $sQAdd = ' from ' . $sArtTable . ' where ' . $sArtTable . '.oxshopid="' . $oConfig->getShopId() . '" and 1 ';
            $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArtTable.oxparentid = '' and $sArtTable.oxvendorid != " . $oDb->quote($sSynchVendorId);
        } else {
            // selected category ?
            if ($sSynchVendorId && $sSynchVendorId != $sVendorId) {
                $sQAdd = " from $sO2CView left join $sArtTable on ";
                $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? " ( $sArtTable.oxid = $sO2CView.oxobjectid or $sArtTable.oxparentid = oxobject2category.oxobjectid )" : " $sArtTable.oxid = $sO2CView.oxobjectid ";
                $sQAdd .= 'where ' . $sArtTable . '.oxshopid="' . $oConfig->getShopId() . '" and ' . $sO2CView . '.oxcatnid = ' . $oDb->quote($sVendorId) . ' and ' . $sArtTable . '.oxvendorid != ' . $oDb->quote($sSynchVendorId);
            } else {
                $sQAdd = " from $sArtTable where $sArtTable.oxvendorid = " . $oDb->quote($sVendorId);
            }

            $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArtTable.oxparentid = '' ";
        }

        return $sQAdd;
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "addFilter" in next major
     */
    protected function _addFilter($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArtTable = $this->_getViewName('oxarticles');
        $sQ = parent::_addFilter($sQ);

        // display variants or not ?
        $sQ .= Registry::getConfig()->getConfigParam('blVariantsSelection') ? ' group by ' . $sArtTable . '.oxid ' : '';

        return $sQ;
    }

    /**
     * Removes article from Vendor
     */
    public function removeVendor()
    {
        $oRequest = Registry::getRequest();
        $aRemoveArt = $this->_getActionIds('oxarticles.oxid');

        if ($oRequest->getRequestEscapedParameter('all')) {
            $sArtTable = $this->_getViewName('oxarticles');
            $aRemoveArt = $this->_getAll($this->_addFilter("select $sArtTable.oxid " . $this->_getQuery()));
        }

        if (is_array($aRemoveArt)) {
            $sSelect = "update oxarticles set oxvendorid = null where "
                . $this->onVendorActionArticleUpdateConditions($aRemoveArt);
            DatabaseProvider::getDb()->Execute($sSelect);

            $this->resetCounter("vendorArticle", $oRequest->getRequestEscapedParameter('oxid'));

            $this->onVendorAction($oRequest->getRequestEscapedParameter('oxid'));
        }
    }

    /**
     * Adds article to Vendor config
     */
    public function addVendor()
    {
        $oRequest = Registry::getRequest();

        $aAddArticle = $this->_getActionIds('oxarticles.oxid');
        $soxId = $oRequest->getRequestEscapedParameter('synchoxid');

        if ($oRequest->getRequestEscapedParameter('all')) {
            $sArtTable = $this->_getViewName('oxarticles');
            $aAddArticle = $this->_getAll($this->_addFilter("select $sArtTable.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aAddArticle)) {
            $oDb = DatabaseProvider::getDb();
            $sSelect = "update oxarticles set oxvendorid = " . $oDb->quote($soxId) . " where "
                . $this->onVendorActionArticleUpdateConditions($aAddArticle);

            $oDb->Execute($sSelect);
            $this->resetCounter("vendorArticle", $soxId);

            $this->onVendorAction($soxId);
        }
    }

    /**
     * Condition for updating oxarticles on add / remove vendor actions.
     *
     * @param array $articleIds
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function onVendorActionArticleUpdateConditions($articleIds)
    {
        return 'oxid in (' . implode(", ", DatabaseProvider::getDb()->quoteArray($articleIds)) . ')';
    }

    /**
     * Additional actions on vendor add/remove.
     *
     * @param string $vendorOxid
     */
    protected function onVendorAction($vendorOxid)
    {
    }
}
