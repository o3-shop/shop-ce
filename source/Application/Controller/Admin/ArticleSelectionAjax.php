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
 * Class controls article assignment to selection lists
 */
class ArticleSelectionAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxselectlist', 1, 1, 0],
        ['oxident', 'oxselectlist', 1, 0, 0],
        ['oxvaldesc', 'oxselectlist', 1, 0, 0],
        ['oxid', 'oxselectlist', 0, 0, 1]
    ],
                                 'container2' => [
                                     ['oxtitle', 'oxselectlist', 1, 1, 0],
                                     ['oxident', 'oxselectlist', 1, 0, 0],
                                     ['oxvaldesc', 'oxselectlist', 1, 0, 0],
                                     ['oxid', 'oxobject2selectlist', 0, 0, 1]
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
        $sSLViewName = $this->_getViewName('oxselectlist');
        $sArtViewName = $this->_getViewName('oxarticles');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $sArtId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('oxid');
        $sSynchArtId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        $sOxid = ($sArtId) ? $sArtId : $sSynchArtId;
        $sQ = "select oxparentid from {$sArtViewName} where oxid = :oxid and oxparentid != '' ";
        $sQ .= "and (select count(oxobjectid) from oxobject2selectlist " .
               "where oxobjectid = :oxobjectid) = 0";
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
        $sParentId = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster()->getOne($sQ, [
            ':oxid' => $sOxid,
            ':oxobjectid' => $sOxid
        ]);

        // all selectlists article is in
        $sQAdd = " from oxobject2selectlist left join {$sSLViewName} " .
                 "on {$sSLViewName}.oxid=oxobject2selectlist.oxselnid  " .
                 "where oxobject2selectlist.oxobjectid = " . $oDb->quote($sOxid) . " ";
        if ($sParentId) {
            $sQAdd .= "or oxobject2selectlist.oxobjectid = " . $oDb->quote($sParentId) . " ";
        }
        // all not assigned selectlists
        if ($sSynchArtId) {
            $sQAdd = " from {$sSLViewName}  " .
                     "where {$sSLViewName}.oxid not in ( select oxobject2selectlist.oxselnid {$sQAdd} ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes article selection lists.
     */
    public function removeSel()
    {
        $aChosenArt = $this->_getActionIds('oxobject2selectlist.oxid');
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2selectlist.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sChosenArticles = implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aChosenArt));
            $sQ = "delete from oxobject2selectlist " .
                  "where oxobject2selectlist.oxid in (" . $sChosenArticles . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }

        $articleId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('oxid');
        $this->onArticleSelectionListChange($articleId);
    }

    /**
     * Adds selection lists to article.
     *
     * @throws Exception
     */
    public function addSel()
    {
        $aAddSel = $this->_getActionIds('oxselectlist.oxid');
        $soxId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        // adding
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sSLViewName = $this->_getViewName('oxselectlist');
            $aAddSel = $this->_getAll($this->_addFilter("select $sSLViewName.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aAddSel)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
            $database = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster();
            foreach ($aAddSel as $sAdd) {
                $oNew = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $oNew->init("oxobject2selectlist");
                $sObjectIdField = 'oxobject2selectlist__oxobjectid';
                $sSelectetionIdField = 'oxobject2selectlist__oxselnid';
                $sOxSortField = 'oxobject2selectlist__oxsort';

                $oNew->$sObjectIdField = new \OxidEsales\Eshop\Core\Field($soxId);
                $oNew->$sSelectetionIdField = new \OxidEsales\Eshop\Core\Field($sAdd);

                $sSql = "select max(oxsort) + 1 from oxobject2selectlist where oxobjectid = :oxobjectid";

                $oNew->$sOxSortField = new \OxidEsales\Eshop\Core\Field((int) $database->getOne($sSql, [
                    ':oxobjectid' => $soxId
                ]));
                $oNew->save();
            }

            $this->onArticleSelectionListChange($soxId);
        }
    }

    /**
     * Method is used to bind to article selection list change.
     *
     * @param string $articleId
     */
    protected function onArticleSelectionListChange($articleId)
    {
    }
}
