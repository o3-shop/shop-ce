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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class controls article assignment to selection lists
 */
class ArticleSelectionAjax extends ListComponentAjax
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
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSLViewName = $this->_getViewName('oxselectlist');
        $sArtViewName = $this->_getViewName('oxarticles');
        $oDb = DatabaseProvider::getDb();

        $sArtId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchArtId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        $sOxid = ($sArtId) ? $sArtId : $sSynchArtId;
        $sQ = "select oxparentid from {$sArtViewName} where oxid = :oxid and oxparentid != '' ";
        $sQ .= "and (select count(oxobjectid) from oxobject2selectlist " .
               "where oxobjectid = :oxobjectid) = 0";
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
        $sParentId = DatabaseProvider::getMaster()->getOne($sQ, [
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
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2selectlist.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sChosenArticles = implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenArt));
            $sQ = "delete from oxobject2selectlist " .
                  "where oxobject2selectlist.oxid in (" . $sChosenArticles . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }

        $articleId = Registry::getRequest()->getRequestEscapedParameter('oxid');
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
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sSLViewName = $this->_getViewName('oxselectlist');
            $aAddSel = $this->_getAll($this->_addFilter("select $sSLViewName.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aAddSel)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
            $database = DatabaseProvider::getMaster();
            foreach ($aAddSel as $sAdd) {
                $oNew = oxNew(BaseModel::class);
                $oNew->init("oxobject2selectlist");
                $sObjectIdField = 'oxobject2selectlist__oxobjectid';
                $sSelectionIdField = 'oxobject2selectlist__oxselnid';
                $sOxSortField = 'oxobject2selectlist__oxsort';

                $oNew->$sObjectIdField = new Field($soxId);
                $oNew->$sSelectionIdField = new Field($sAdd);

                $sSql = "select max(oxsort) + 1 from oxobject2selectlist where oxobjectid = :oxobjectid";

                $oNew->$sOxSortField = new Field((int) $database->getOne($sSql, [
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
