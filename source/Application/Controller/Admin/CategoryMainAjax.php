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
use OxidEsales\Eshop\Application\Model\Object2Category;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use Exception;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;

/**
 * Class manages category articles
 */
class CategoryMainAjax extends ListComponentAjax
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
        $sArticleTable = $this->getViewName('oxarticles');
        $sO2CView = $this->getViewName('oxobject2category');

        $sOxid = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchOxid = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $oDb = DatabaseProvider::getDb();

        // category selected or not ?
        if (!$sOxid && $sSynchOxid) {
            // dodger performance
            $sQAdd = ' from ' . $sArticleTable . ' where 1 ';
        } else {
            // copied from oxadminview
            $sJoin = " {$sArticleTable}.oxid={$sO2CView}.oxobjectid ";

            $sSubSelect = '';
            if ($sSynchOxid && $sOxid != $sSynchOxid) {
                $sSubSelect = ' and ' . $sArticleTable . '.oxid not in ( ';
                $sSubSelect .= "select $sArticleTable.oxid from $sO2CView left join $sArticleTable ";
                $sSubSelect .= "on $sJoin where $sO2CView.oxcatnid =  " . $oDb->quote($sSynchOxid) . " ";
                $sSubSelect .= 'and ' . $sArticleTable . '.oxid is not null ) ';
            }

            $sQAdd = " from $sO2CView join $sArticleTable ";
            $sQAdd .= " on $sJoin where $sO2CView.oxcatnid = " . $oDb->quote($sOxid);
            $sQAdd .= " and $sArticleTable.oxid is not null $sSubSelect ";
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
        return $this->addFilter($sQ);
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function addFilter($sQ)
    {
        $sArtTable = $this->getViewName('oxarticles');
        $sQ = parent::addFilter($sQ);

        // display variants or not ?
        if (!Registry::getConfig()->getConfigParam('blVariantsSelection')) {
            $sQ .= " and {$sArtTable}.oxparentid = '' ";
        }

        return $sQ;
    }

    /**
     * Adds article to category
     * Creates new list
     *
     * @throws Exception
     */
    public function addArticle()
    {
        $aArticles = $this->getActionIds('oxarticles.oxid');
        $sCategoryID = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $sShopID = Registry::getConfig()->getShopId();

        DatabaseProvider::getDb()->startTransaction();
        try {
            $database = DatabaseProvider::getDb();
            $sArticleTable = $this->getViewName('oxarticles');

            // adding
            if (Registry::getRequest()->getRequestEscapedParameter('all')) {
                $aArticles = $this->getAll($this->addFilter("select $sArticleTable.oxid " . $this->getQuery()));
            }

            if (is_array($aArticles)) {
                $sO2CView = $this->getViewName('oxobject2category');

                $oNew = oxNew(Object2Category::class);
                $sProdIds = "";
                foreach ($aArticles as $sAdd) {
                    // check, if it's already in, then don't add it again
                    $sSelect = "select 1 from $sO2CView as oxobject2category where oxobject2category.oxcatnid = :oxcatnid "
                               . " and oxobject2category.oxobjectid = :oxobjectid";
                    // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                    if ($database->getOne($sSelect, [':oxcatnid' => $sCategoryID, ':oxobjectid' => $sAdd])) {
                        continue;
                    }

                    $oNew->oxobject2category__oxid = new Field($oNew->setId(md5($sAdd . $sCategoryID . $sShopID)));
                    $oNew->oxobject2category__oxobjectid = new Field($sAdd);
                    $oNew->oxobject2category__oxcatnid = new Field($sCategoryID);
                    $oNew->oxobject2category__oxtime = new Field(time());

                    $oNew->save();

                    if ($sProdIds) {
                        $sProdIds .= ",";
                    }
                    $sProdIds .= $database->quote($sAdd);
                }

                // updating oxtime values
                $this->updateOxTime($sProdIds);

                $this->resetArtSeoUrl($aArticles);
                $this->resetCounter("catArticle", $sCategoryID);
            }
        } catch (Exception $exception) {
            DatabaseProvider::getDb()->rollbackTransaction();
            throw $exception;
        }

        DatabaseProvider::getDb()->commitTransaction();
    }

    /**
     * Updates oxtime value for products
     *
     * @param string $sProdIds product ids: "id1", "id2", "id3"
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateOxTime" in next major
     */
    protected function _updateOxTime($sProdIds) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->updateOxTime($sProdIds);
    }

    /**
     * Updates oxtime value for products
     *
     * @param string $sProdIds product ids: "id1", "id2", "id3"
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function updateOxTime($sProdIds)
    {
        if ($sProdIds) {
            $sO2CView = $this->getViewName('oxobject2category');
            $sSqlShopFilter = $this->getUpdateOxTimeQueryShopFilter();
            $sSqlWhereShopFilter = $this->getUpdateOxTimeSqlWhereFilter();
            $sQ = "update oxobject2category set oxtime = 0 where oxid in (
                      select _tmp.oxid from (
                          select oxobject2category.oxid from (
                              select min(oxtime) as oxtime, oxobjectid from {$sO2CView}
                              where oxobjectid in ( {$sProdIds} ) {$sSqlShopFilter} group by oxobjectid
                          ) as _subtmp
                          left join oxobject2category on oxobject2category.oxtime = _subtmp.oxtime
                           and oxobject2category.oxobjectid = _subtmp.oxobjectid
                           {$sSqlWhereShopFilter}
                      ) as _tmp
                   ) {$sSqlShopFilter}";

            DatabaseProvider::getDb()->execute($sQ);
        }
    }

    /**
     * @return string
     */
    protected function getUpdateOxTimeQueryShopFilter()
    {
        return '';
    }

    /**
     * Return where with "true " as this allows to concat query condition
     * without knowing about other who changes this place (module or different edition).
     *
     * @return string
     */
    protected function getUpdateOxTimeSqlWhereFilter()
    {
        return 'where true ';
    }

    /**
     * Removes article from category
     */
    public function removeArticle()
    {
        $aArticles = $this->getActionIds('oxarticles.oxid');
        $sCategoryID = Registry::getRequest()->getRequestEscapedParameter('oxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sArticleTable = $this->getViewName('oxarticles');
            $aArticles = $this->getAll($this->addFilter("select $sArticleTable.oxid " . $this->getQuery()));
        }

        // adding
        if (is_array($aArticles) && count($aArticles)) {
            $this->removeCategoryArticles($aArticles, $sCategoryID);
        }

        $this->resetArtSeoUrl($aArticles, $sCategoryID);
        $this->resetCounter("catArticle", $sCategoryID);

        //notify services
        $relation = oxNew(Object2Category::class);
        $relation->setCategoryId($sCategoryID);
        $this->dispatchEvent(new AfterModelUpdateEvent($relation));
    }

    /**
     * Delete articles from category (from oxobject2category).
     *
     * @param array $articles
     * @param string $categoryID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function removeCategoryArticles($articles, $categoryID)
    {
        $db = DatabaseProvider::getDb();
        $prodIds = implode(", ", DatabaseProvider::getDb()->quoteArray($articles));

        $delete = "delete from oxobject2category ";
        $where = $this->getRemoveCategoryArticlesQueryFilter($categoryID, $prodIds);


        $sQ = $delete . $where;
        $db->execute($sQ);

        // updating oxtime values
        $this->updateOxTime($prodIds);
    }

    /**
     * Form query filter to remove articles from category.
     *
     * @param string $categoryID
     * @param string $prodIds
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getRemoveCategoryArticlesQueryFilter($categoryID, $prodIds)
    {
        $db = DatabaseProvider::getDb();
        $where = "where oxcatnid=" . $db->quote($categoryID);

        $whereProductIdIn = " oxobjectid in ( {$prodIds} )";
        if (!Registry::getConfig()->getConfigParam('blVariantsSelection')) {
            $whereProductIdIn = "( " . $whereProductIdIn . " OR oxobjectid in (
                                        select oxid from oxarticles where oxparentid in ({$prodIds})
                                        )
            )";
        }
        $where = $where . ' AND ' . $whereProductIdIn;

        return $where;
    }
}
