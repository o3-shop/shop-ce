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
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Object2Category;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages category articles order
 */
class CategoryOrderAjax extends ListComponentAjax
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
            ['oxpos', 'oxobject2category', 1, 0, 0],
            ['oxean', 'oxarticles', 0, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxarticles', 0, 0, 1],
        ],
         'container2' => [
             ['oxartnum', 'oxarticles', 1, 0, 0],
             ['oxtitle', 'oxarticles', 1, 1, 0],
             ['oxean', 'oxarticles', 0, 0, 0],
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
        // looking for table/view
        $sArtTable = $this->getViewName('oxarticles');
        $sO2CView = $this->getViewName('oxobject2category');
        $oDb = DatabaseProvider::getDb();

        // category selected or not ?
        if ($sSynchOxid = Registry::getRequest()->getRequestEscapedParameter('synchoxid')) {
            $sQAdd = " from $sArtTable left join $sO2CView on $sArtTable.oxid=$sO2CView.oxobjectid where $sO2CView.oxcatnid = " . $oDb->quote($sSynchOxid);
            if ($aSkipArt = Registry::getSession()->getVariable('neworder_sess')) {
                $sQAdd .= " and $sArtTable.oxid not in ( " . implode(", ", DatabaseProvider::getDb()->quoteArray($aSkipArt)) . " ) ";
            }
        } else {
            // which fields to load ?
            $sQAdd = " from $sArtTable where ";
            if ($aSkipArt = Registry::getSession()->getVariable('neworder_sess')) {
                $sQAdd .= " $sArtTable.oxid in ( " . implode(", ", DatabaseProvider::getDb()->quoteArray($aSkipArt)) . " ) ";
            } else {
                $sQAdd .= " 1 = 0 ";
            }
        }

        return $sQAdd;
    }

    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSorting" in next major
     */
    protected function _getSorting() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getSorting();
    }

    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getSorting()
    {
        $sOrder = '';
        if (Registry::getRequest()->getRequestEscapedParameter('synchoxid')) {
            $sOrder = parent::getSorting();
        } elseif (($aSkipArt = Registry::getSession()->getVariable('neworder_sess'))) {
            $sOrderBy = '';
            $sArtTable = $this->getViewName('oxarticles');
            $sSep = '';
            foreach ($aSkipArt as $sId) {
                $sOrderBy = " $sArtTable.oxid=" . DatabaseProvider::getDb()->quote($sId) . " " . $sSep . $sOrderBy;
                $sSep = ", ";
            }
            $sOrder = "order by " . $sOrderBy;
        }

        return $sOrder;
    }

    /**
     * Removes article from list for sorting in category
     */
    public function removeCatOrderArticle()
    {
        $aRemoveArt = $this->getActionIds('oxarticles.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $aSkipArt = Registry::getSession()->getVariable('neworder_sess');

        if (is_array($aRemoveArt) && is_array($aSkipArt)) {
            foreach ($aRemoveArt as $sRem) {
                if (($iKey = array_search($sRem, $aSkipArt)) !== false) {
                    unset($aSkipArt[$iKey]);
                }
            }
            Registry::getSession()->setVariable('neworder_sess', $aSkipArt);

            $sArticleTable = $this->getViewName('oxarticles');
            $sO2CView = $this->getViewName('oxobject2category');

            // checking if all articles were moved from one
            $sSelect = "select 1 from $sArticleTable left join $sO2CView on $sArticleTable.oxid=$sO2CView.oxobjectid ";
            $sSelect .= "where $sO2CView.oxcatnid = :oxcatnid";
            if (count($aSkipArt)) {
                $sSelect .= " and $sArticleTable.oxparentid = '' and $sArticleTable.oxid ";
                $sSelect .= "not in ( " . implode(", ", DatabaseProvider::getDb()->quoteArray($aSkipArt)) . " ) ";
            }

            // simply echoing "1" if some items found, and 0 if nothing was found
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
            echo (int) DatabaseProvider::getMaster()->getOne($sSelect, [
                ':oxcatnid' => $soxId
            ]);
        }
    }

    /**
     * Adds article to list for sorting in category
     */
    public function addCatOrderArticle()
    {
        $aAddArticle = $this->getActionIds('oxarticles.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        $aOrdArt = Registry::getSession()->getVariable('neworder_sess');
        if (!is_array($aOrdArt)) {
            $aOrdArt = [];
        }

        if (is_array($aAddArticle)) {
            // storing newly ordered article seq.
            foreach ($aAddArticle as $sAdd) {
                if (!in_array($sAdd, $aOrdArt)) {
                    $aOrdArt[] = $sAdd;
                }
            }
            Registry::getSession()->setVariable('neworder_sess', $aOrdArt);

            $sArticleTable = $this->getViewName('oxarticles');
            $sO2CView = $this->getViewName('oxobject2category');

            // checking if all articles were moved from one
            $sSelect = "select 1 from $sArticleTable left join $sO2CView on $sArticleTable.oxid=$sO2CView.oxobjectid ";
            $sSelect .= "where $sO2CView.oxcatnid = :oxcatnid and $sArticleTable.oxparentid = '' and $sArticleTable.oxid ";
            $sSelect .= "not in ( " . implode(", ", DatabaseProvider::getDb()->quoteArray($aOrdArt)) . " ) ";

            // simply echoing "1" if some items found, and 0 if nothing was found
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
            echo (int) DatabaseProvider::getMaster()->getOne($sSelect, [
                ':oxcatnid' => $soxId
            ]);
        }
    }

    /**
     * Saves category articles ordering.
     *
     * @return void
     * @throws DatabaseConnectionException
     */
    public function saveNewOrder()
    {
        $oCategory = oxNew(Category::class);
        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        if ($oCategory->load($sId)) {
            //Disable editing for derived items
            if ($oCategory->isDerived()) {
                return;
            }

            $this->resetContentCache();

            $aNewOrder = Registry::getSession()->getVariable("neworder_sess");
            if (is_array($aNewOrder) && count($aNewOrder)) {
                $sO2CView = $this->getViewName('oxobject2category');
                $sSelect = "select * from $sO2CView where $sO2CView.oxcatnid = :oxcatnid and $sO2CView.oxobjectid in (" . implode(", ", DatabaseProvider::getDb()->quoteArray($aNewOrder)) . " )";
                $oList = oxNew(ListModel::class);
                $oList->init($this->getObject2CategoryClass(), 'oxobject2category');
                $oList->selectString($sSelect, [
                    ':oxcatnid' => $oCategory->getId()
                ]);

                // setting new position
                foreach ($oList as $oObj) {
                    if (($iNewPos = array_search($oObj->oxobject2category__oxobjectid->value, $aNewOrder)) !== false) {
                        $oObj->oxobject2category__oxpos->setValue($iNewPos);
                        $oObj->save();
                    }
                }

                Registry::getSession()->setVariable('neworder_sess', null);
            }

            $this->onCategoryChange($sId);
        }
    }

    /**
     * Removes category articles ordering set by saveneworder() method.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function remNewOrder()
    {
        $oCategory = oxNew(Category::class);
        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        if ($oCategory->load($sId)) {
            //Disable editing for derived items
            if ($oCategory->isDerived()) {
                return;
            }

            $oDb = DatabaseProvider::getDb();
            $sSqlShopFilter = $this->updateQueryFilterForResetCategoryArticlesOrder();

            $sSelect = "update oxobject2category set oxpos = '0' where oxobject2category.oxcatnid = :id {$sSqlShopFilter}";
            $oDb->execute($sSelect, [':id' => $oCategory->getId()]);

            Registry::getSession()->setVariable('neworder_sess', null);

            $this->onCategoryChange($sId);
        }
    }

    /**
     * @return string
     */
    protected function updateQueryFilterForResetCategoryArticlesOrder()
    {
        return '';
    }

    /**
     * @param string $categoryId
     */
    protected function onCategoryChange($categoryId)
    {
    }

    private function getObject2CategoryClass(): string
    {
        return Registry::getUtilsObject()->getClassName(Object2Category::class);
    }
}
