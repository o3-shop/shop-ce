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
use Exception;

/**
 * Class manages article select lists configuration
 */
class SelectListMainAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
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
        ['oxean', 'oxarticles', 0, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1]
    ],
                                 'container2' => [
                                     ['oxartnum', 'oxarticles', 1, 0, 0],
                                     ['oxtitle', 'oxarticles', 1, 1, 0],
                                     ['oxean', 'oxarticles', 0, 0, 0],
                                     ['oxmpn', 'oxarticles', 0, 0, 0],
                                     ['oxprice', 'oxarticles', 0, 0, 0],
                                     ['oxstock', 'oxarticles', 0, 0, 0],
                                     ['oxid', 'oxobject2selectlist', 0, 0, 1],
                                     ['oxid', 'oxarticles', 0, 0, 1]
                                 ],
                                 'container3' => [
                                     ['oxtitle', 'oxselectlist', 1, 1, 0],
                                     ['oxsort', 'oxobject2selectlist', 1, 0, 0],
                                     ['oxident', 'oxselectlist', 0, 0, 0],
                                     ['oxvaldesc', 'oxselectlist', 0, 0, 0],
                                     ['oxid', 'oxselectlist', 0, 0, 1]
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

        // looking for table/view
        $sArtTable = $this->_getViewName('oxarticles');
        $sO2CView = $this->_getViewName('oxobject2category');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sSelId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('oxid');
        $sSynchSelId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sSelId) {
            // performance
            $sQAdd = " from $sArtTable where 1 ";
            $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArtTable.oxparentid = '' ";
        } else {
            // selected category ?
            if ($sSynchSelId && $sSelId != $sSynchSelId) {
                $sQAdd = " from $sO2CView as oxobject2category left join $sArtTable on ";
                $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? " ( $sArtTable.oxid=oxobject2category.oxobjectid or $sArtTable.oxparentid=oxobject2category.oxobjectid ) " : " $sArtTable.oxid=oxobject2category.oxobjectid ";
                $sQAdd .= " where oxobject2category.oxcatnid = " . $oDb->quote($sSelId);
            } else {
                $sQAdd = " from $sArtTable left join oxobject2selectlist on $sArtTable.oxid=oxobject2selectlist.oxobjectid ";
                $sQAdd .= " where oxobject2selectlist.oxselnid = " . $oDb->quote($sSelId);
            }
        }

        if ($sSynchSelId && $sSynchSelId != $sSelId) {
            // performance
            $sQAdd .= " and $sArtTable.oxid not in ( select oxobject2selectlist.oxobjectid from oxobject2selectlist ";
            $sQAdd .= " where oxobject2selectlist.oxselnid = " . $oDb->quote($sSynchSelId) . " ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes article from Selection list
     */
    public function removeArtFromSel()
    {
        $aChosenArt = $this->_getActionIds('oxobject2selectlist.oxid');

        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sQ = parent::_addFilter("delete oxobject2selectlist.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sQ = "delete from oxobject2selectlist where oxobject2selectlist.oxid in (" . implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aChosenArt)) . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds article to Selection list
     *
     * @throws Exception
     */
    public function addArtToSel()
    {
        $aAddArticle = $this->_getActionIds('oxarticles.oxid');
        $soxId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sArtTable = $this->_getViewName('oxarticles');
            $aAddArticle = $this->_getAll(parent::_addFilter("select $sArtTable.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aAddArticle)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster();
            foreach ($aAddArticle as $sAdd) {
                $oNewGroup = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $oNewGroup->init("oxobject2selectlist");
                $oNewGroup->oxobject2selectlist__oxobjectid = new \OxidEsales\Eshop\Core\Field($sAdd);
                $oNewGroup->oxobject2selectlist__oxselnid = new \OxidEsales\Eshop\Core\Field($soxId);
                $oNewGroup->oxobject2selectlist__oxsort = new \OxidEsales\Eshop\Core\Field((int) $database->getOne("select max(oxsort) + 1 from oxobject2selectlist where oxobjectid = :oxobjectid", [':oxobjectid' => $sAdd]));
                $oNewGroup->save();

                $this->onArticleAddToSelectionList($sAdd);
            }
        }
    }

    /**
     * Method is used for overriding.
     *
     * @param string $articleId
     */
    protected function onArticleAddToSelectionList($articleId)
    {
    }
}
