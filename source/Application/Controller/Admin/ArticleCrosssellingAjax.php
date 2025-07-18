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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class controls article crossselling configuration
 */
class ArticleCrosssellingAjax extends ListComponentAjax
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
            ['oxid', 'oxobject2article', 0, 0, 1],
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
        $myConfig = Registry::getConfig();
        $sArticleTable = $this->getViewName('oxarticles');
        $sView = $this->getViewName('oxobject2category');

        $sSelId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchSelId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $oDb = DatabaseProvider::getDb();

        // category selected or not ?
        if (!$sSelId) {
            $sQAdd = " from {$sArticleTable} where 1 ";
            $sQAdd .= $myConfig->getConfigParam('blVariantsSelection') ? '' : " and {$sArticleTable}.oxparentid = '' ";
        } elseif ($sSynchSelId && $sSelId != $sSynchSelId) {
            // selected category ?
            $blVariantsSelectionParameter = $myConfig->getConfigParam('blVariantsSelection');
            $sSqlIfTrue = " ({$sArticleTable}.oxid=oxobject2category.oxobjectid " .
                          "or {$sArticleTable}.oxparentid=oxobject2category.oxobjectid)";
            $sSqlIfFalse = " {$sArticleTable}.oxid=oxobject2category.oxobjectid ";
            $sVariantsSelectionSnippet = $blVariantsSelectionParameter ? $sSqlIfTrue : $sSqlIfFalse;

            $sQAdd = " from {$sView} as oxobject2category left join {$sArticleTable} on {$sVariantsSelectionSnippet}" .
                     " where oxobject2category.oxcatnid = " . $oDb->quote($sSelId) . " ";
        } elseif ($myConfig->getConfigParam('blBidirectCross')) {
            $sQAdd = " from oxobject2article " .
                     " inner join {$sArticleTable} on ( oxobject2article.oxobjectid = {$sArticleTable}.oxid " .
                     " or oxobject2article.oxarticlenid = {$sArticleTable}.oxid ) " .
                     " where ( oxobject2article.oxarticlenid = " . $oDb->quote($sSelId) .
                     " or oxobject2article.oxobjectid = " . $oDb->quote($sSelId) . " ) " .
                     " and {$sArticleTable}.oxid != " . $oDb->quote($sSelId) . " ";
        } else {
            $sQAdd = " from oxobject2article left join {$sArticleTable} " .
                     "on oxobject2article.oxobjectid={$sArticleTable}.oxid " .
                     " where oxobject2article.oxarticlenid = " . $oDb->quote($sSelId) . " ";
        }

        if ($sSynchSelId && $sSynchSelId != $sSelId) {
            if ($myConfig->getConfigParam('blBidirectCross')) {
                $sSubSelect = "select {$sArticleTable}.oxid from oxobject2article " .
                              "left join {$sArticleTable} on (oxobject2article.oxobjectid={$sArticleTable}.oxid " .
                              "or oxobject2article.oxarticlenid={$sArticleTable}.oxid) " .
                              "where (oxobject2article.oxarticlenid = " . $oDb->quote($sSynchSelId) .
                              " or oxobject2article.oxobjectid = " . $oDb->quote($sSynchSelId) . " )";
            } else {
                $sSubSelect = "select {$sArticleTable}.oxid from oxobject2article " .
                              "left join {$sArticleTable} on oxobject2article.oxobjectid={$sArticleTable}.oxid " .
                              "where oxobject2article.oxarticlenid = " . $oDb->quote($sSynchSelId) . " ";
            }

            $sSubSelect .= " and {$sArticleTable}.oxid IS NOT NULL ";
            $sQAdd .= " and {$sArticleTable}.oxid not in ( $sSubSelect ) ";
        }

        // #1513C/#1826C - skip references, to not existing articles
        $sQAdd .= " and {$sArticleTable}.oxid IS NOT NULL ";

        // skipping self from list
        $sId = ($sSynchSelId) ? $sSynchSelId : $sSelId;
        $sQAdd .= " and {$sArticleTable}.oxid != " . $oDb->quote($sId) . " ";

        return $sQAdd;
    }

    /**
     * Removing article from cross-selling list
     */
    public function removeArticleCross()
    {
        $aChosenArt = $this->getActionIds('oxobject2article.oxid');
        // removing all
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter("delete oxobject2article.* " . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sChosenArticles = implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenArt));
            $sQ = "delete from oxobject2article where oxobject2article.oxid in (" . $sChosenArticles . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adding article to cross-selling list
     */
    public function addArticleCross()
    {
        $aChosenArt = $this->getActionIds('oxarticles.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sArtTable = $this->getViewName('oxarticles');
            $aChosenArt = $this->getAll(parent::addFilter("select $sArtTable.oxid " . $this->getQuery()));
        }

        $oArticle = oxNew(Article::class);
        if ($oArticle->load($soxId) && $soxId && $soxId != "-1" && is_array($aChosenArt)) {
            foreach ($aChosenArt as $sAdd) {
                $oNewGroup = oxNew(BaseModel::class);
                $oNewGroup->init('oxobject2article');
                $oNewGroup->oxobject2article__oxobjectid = new Field($sAdd);
                $oNewGroup->oxobject2article__oxarticlenid = new Field($oArticle->oxarticles__oxid->value);
                $oNewGroup->oxobject2article__oxsort = new Field(0);
                $oNewGroup->save();
            }

            $this->onArticleAddingToCrossSelling($oArticle);
        }
    }

    /**
     * Method is used to overload and add additional actions.
     *
     * @param Article $article
     */
    protected function onArticleAddingToCrossSelling($article)
    {
    }
}
