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
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages delivery articles
 */
class DeliveryArticlesAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [ 
            // field , table, visible, multilanguage, ident
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
            ['oxid', 'oxobject2delivery', 0, 0, 1],
        ],
    ];

    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_blAllowExtColumns = true;

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getQuery();
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getQuery()
    {
        $myConfig = Registry::getConfig();
        $oDb = DatabaseProvider::getDb();

        // looking for table/view
        $sArtTable = $this->getViewName('oxarticles');
        $sO2CView = $this->getViewName('oxobject2category');

        $sDelId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchDelId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sDelId) {
            // performance
            $sQAdd = " from $sArtTable where 1 ";
            $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? '' : "and $sArtTable.oxparentid = '' ";
        } else {
            // selected category ?
            if ($sSynchDelId && $sDelId != $sSynchDelId) {
                $sQAdd = " from $sO2CView left join $sArtTable on ";
                $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? " ( $sArtTable.oxid=$sO2CView.oxobjectid or $sArtTable.oxparentid=$sO2CView.oxobjectid)" : " $sArtTable.oxid=$sO2CView.oxobjectid ";
                $sQAdd .= "where $sO2CView.oxcatnid = " . $oDb->quote($sDelId);
            } else {
                $sQAdd = ' from oxobject2delivery left join ' . $sArtTable . ' on ' . $sArtTable . '.oxid=oxobject2delivery.oxobjectid ';
                $sQAdd .= 'where oxobject2delivery.oxdeliveryid = ' . $oDb->quote($sDelId) . ' and oxobject2delivery.oxtype = "oxarticles" ';
            }
        }

        if ($sSynchDelId && $sSynchDelId != $sDelId) {
            $sQAdd .= 'and ' . $sArtTable . '.oxid not in ( ';
            $sQAdd .= 'select oxobject2delivery.oxobjectid from oxobject2delivery ';
            $sQAdd .= 'where oxobject2delivery.oxdeliveryid = ' . $oDb->quote($sSynchDelId) . ' and oxobject2delivery.oxtype = "oxarticles" ) ';
        }

        return $sQAdd;
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     */
    /*protected function _addFilter( $sQ ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArtTable = $this->_getViewName('oxarticles');
        $sQ = parent::_addFilter( $sQ );

        // display variants or not ?
        $sQ .= Registry::getConfig()->getConfigParam( 'blVariantsSelection' ) ? ' group by '.$sArtTable.'.oxid ' : '';
        return $sQ;
    }*/

    /**
     * Removes article from delivery configuration
     */
    public function removeArtFromDel()
    {
        $aChosenArt = $this->getActionIds('oxobject2delivery.oxid');
        // removing all
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = parent::addFilter("delete oxobject2delivery.* " . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sQ = "delete from oxobject2delivery where oxobject2delivery.oxid in (" . implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenArt)) . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds article to delivery configuration
     */
    public function addArtToDel()
    {
        $aChosenArt = $this->getActionIds('oxarticles.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sArtTable = $this->getViewName('oxarticles');
            $aChosenArt = $this->getAll($this->addFilter("select $sArtTable.oxid " . $this->getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aChosenArt)) {
            foreach ($aChosenArt as $sChosenArt) {
                $oObject2Delivery = oxNew(BaseModel::class);
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new Field($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new Field($sChosenArt);
                $oObject2Delivery->oxobject2delivery__oxtype = new Field("oxarticles");
                $oObject2Delivery->save();
            }
        }
    }
}
