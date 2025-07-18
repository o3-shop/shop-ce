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
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class controls article assignment to accessoires
 */
class ArticleAccessoriesAjax extends ListComponentAjax
{
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_blAllowExtColumns = true;

    /**
     * Container ID
     *
     * @var string
     */
    private $containerId;

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [ 
            // field , table,         visible, multilanguage, ident
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
            ['oxsort', 'oxaccessoire2article', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxaccessoire2article', 0, 0, 1],
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
        $oxidId = Registry::getConfig()->getRequestEscapedParameter('oxid');
        $synchId = Registry::getConfig()->getRequestEscapedParameter('synchoxid');
        $db = DatabaseProvider::getDb();
        $this->containerId = Registry::getConfig()->getRequestEscapedParameter('cmpid');

        $articleTable = $this->_getViewName('oxarticles');
        $object2categoryTable = $this->_getViewName('oxobject2category');

        // category selected or not ?
        if (!$oxidId) {
            $outputQuery = " from {$articleTable} where 1 ";
            $outputQuery .= $myConfig->getConfigParam('blVariantsSelection') ? '' : " and {$articleTable}.oxparentid = '' ";
        } else {
            // selected category ?
            if ($synchId && $oxidId != $synchId) {
                $blVariantsSelectionParameter = $myConfig->getConfigParam('blVariantsSelection');
                $trueResponse = " ( {$articleTable}.oxid=$object2categoryTable.oxobjectid " .
                                "or {$articleTable}.oxparentid=$object2categoryTable.oxobjectid )";
                $failResponse = " {$articleTable}.oxid=$object2categoryTable.oxobjectid ";
                $variantSelectionSql = $blVariantsSelectionParameter ? $trueResponse : $failResponse;

                $outputQuery = " from $object2categoryTable left join {$articleTable} on {$variantSelectionSql}" .
                               " where $object2categoryTable.oxcatnid = " . $db->quote($oxidId) . " ";
            } else {
                $outputQuery = " from oxaccessoire2article left join {$articleTable} " .
                               "on oxaccessoire2article.oxobjectid={$articleTable}.oxid " .
                               " where oxaccessoire2article.oxarticlenid = " . $db->quote($oxidId) . " ";
            }
        }

        if ($synchId && $synchId != $oxidId) {
            // performance
            $subSelect = ' select oxaccessoire2article.oxobjectid from oxaccessoire2article ';
            $subSelect .= " where oxaccessoire2article.oxarticlenid = " . $db->quote($synchId) . " ";
            $outputQuery .= " and {$articleTable}.oxid not in ( $subSelect )";
        }

        // skipping self from list
        $sId = ($synchId) ? $synchId : $oxidId;
        $outputQuery .= " and {$articleTable}.oxid != " . $db->quote($sId) . " ";

        // creating AJAX component
        return $outputQuery;
    }

    /**
     * override default sorting and replace it with OXSORT field
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSorting" in next major
     */
    protected function _getSorting() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->getSorting();
    }

    /**
     * override default sorting and replace it with OXSORT field
     *
     * @return string
     */
    protected function getSorting()
    {
        if ($this->containerId == 'container2') {
            return ' order by _2,_0';
        } else {
            return ' order by _' . $this->_getSortCol() . ' ' . $this->_getSortDir() . ' ';
        }
    }

    /**
     * Removing article form accessoires article list
     */
    public function removeArticleAcc()
    {
        $aChosenArt = $this->_getActionIds('oxaccessoire2article.oxid');
        // removing all
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter("delete oxaccessoire2article.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sChosenArticles = implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenArt));
            $sQ = "delete from oxaccessoire2article where oxaccessoire2article.oxid in ({$sChosenArticles}) ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adding article to accessoires article list
     */
    public function addArticleAcc()
    {
        $oArticle = oxNew(Article::class);
        $aChosenArt = $this->_getActionIds('oxarticles.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sArtTable = $this->_getViewName('oxarticles');
            $aChosenArt = $this->_getAll(parent::_addFilter("select $sArtTable.oxid " . $this->_getQuery()));
        }

        if ($oArticle->load($soxId) && $soxId && $soxId != "-1" && is_array($aChosenArt)) {
            foreach ($aChosenArt as $sChosenArt) {
                $oNewGroup = oxNew(BaseModel::class);
                $oNewGroup->init("oxaccessoire2article");
                $oNewGroup->oxaccessoire2article__oxobjectid = new Field($sChosenArt);
                $oNewGroup->oxaccessoire2article__oxarticlenid = new Field($oArticle->oxarticles__oxid->value);
                $oNewGroup->oxaccessoire2article__oxsort = new Field(0);
                $oNewGroup->save();
            }

            $this->onArticleAccessoryRelationChange($oArticle);
        }
    }

    /**
     * Method is used to bind to accessoire addition to article action.
     *
     * @param Article $article
     */
    protected function onArticleAccessoryRelationChange($article)
    {
    }


    /**
     * Applies sorting for Accessoires list
     */
    public function sortAccessoriesList()
    {
        $oxidRelationId = Registry::getConfig()->getRequestEscapedParameter('oxid');
        $selectedIdForSort = Registry::getConfig()->getRequestEscapedParameter('sortoxid');
        $sortDirection = Registry::getConfig()->getRequestEscapedParameter('direction');

        $accessoiresList = oxNew(ListModel::class);
        $accessoiresList->init("oxbase", "oxaccessoire2article");
        $sortQuery = "select * from  oxaccessoire2article where OXARTICLENID = :OXARTICLENID order by oxsort,oxid";
        $accessoiresList->selectString($sortQuery, [
            ':OXARTICLENID' => $oxidRelationId
        ]);


        $rebuildList = $this->rebuildAccessoriesSortIndexes($accessoiresList);

        if (($selectedPosition = array_search($selectedIdForSort, $rebuildList)) !== false) {
            $selectedSortRecord = $accessoiresList->offsetGet($rebuildList[$selectedPosition]);
            $currentPosition = $selectedSortRecord->oxaccessoire2article__oxsort->value;

            // get current selected row sort position

            if (($sortDirection == 'up' && $currentPosition > 0) || ($sortDirection == 'down' && $currentPosition < count($rebuildList) - 1)) {
                $newPosition = ($sortDirection == 'up') ? ($currentPosition - 1) : ($currentPosition + 1);

                // exchanging indexes
                $currentRecord = $accessoiresList->offsetGet($rebuildList[$currentPosition]);
                $newRecord = $accessoiresList->offsetGet($rebuildList[$newPosition]);

                $currentRecord->oxaccessoire2article__oxsort = new Field($newPosition);
                $newRecord->oxaccessoire2article__oxsort = new Field($currentPosition);
                $currentRecord->save();
                $newRecord->save();
            }
        }

        $outputQuery = $this->_getQuery();

        $normalQuery = 'select ' . $this->_getQueryCols() . $outputQuery;
        $countQuery = 'select count( * ) ' . $outputQuery;

        $this->_outputResponse($this->_getData($countQuery, $normalQuery));
    }


    /**
     * rebuild Accessoires sort indexes
     *
     * @param ListModel $inputList
     *
     * @return array
     */
    private function rebuildAccessoriesSortIndexes(ListModel $inputList): array
    {
        $counter = 0;
        $outputList = [];
        foreach ($inputList as $key => $value) {
            if (isset($value->oxaccessoire2article__oxsort)) {
                if ($value->oxaccessoire2article__oxsort->value != $counter) {
                    $value->oxaccessoire2article__oxsort = new Field($counter);
                    $value->save();
                }
            }
            $outputList[$counter] = $key;
            $counter++;
        }

        return $outputList;
    }
}
