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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Implements search
 *
 */
class Search extends Base
{
    /**
     * Active language id
     *
     * @var int
     */
    protected $_iLanguage = 0;

    /**
     * Class constructor. Executes search language setter
     */
    public function __construct()
    {
        $this->setLanguage();
    }

    /**
     * Search language setter. If no param is passed, will be taken default shop language
     *
     * @param string $iLanguage string (default null)
     */
    public function setLanguage($iLanguage = null)
    {
        if (!isset($iLanguage)) {
            $this->_iLanguage = Registry::getLang()->getBaseLanguage();
        } else {
            $this->_iLanguage = $iLanguage;
        }
    }

    /**
     * Returns a list of articles according to search parameters. Returns matched
     *
     * @param bool $sSearchParamForQuery query parameter
     * @param bool $sInitialSearchCat initial category to search in
     * @param bool $sInitialSearchVendor initial vendor to search for
     * @param bool $sInitialSearchManufacturer initial Manufacturer to search for
     * @param bool $sSortBy sort by
     *
     * @return ArticleList
     * @throws DatabaseConnectionException
     */
    public function getSearchArticles($sSearchParamForQuery = false, $sInitialSearchCat = false, $sInitialSearchVendor = false, $sInitialSearchManufacturer = false, $sSortBy = false)
    {
        // sets active page
        $this->iActPage = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
        $this->iActPage = ($this->iActPage < 0) ? 0 : $this->iActPage;

        // load only articles which we show on screen
        //setting default values to avoid possible errors showing article list
        $iNrofCatArticles = Registry::getConfig()->getConfigParam('iNrofCatArticles');
        $iNrofCatArticles = $iNrofCatArticles ? $iNrofCatArticles : 10;

        $oArtList = oxNew(ArticleList::class);
        $oArtList->setSqlLimit($iNrofCatArticles * $this->iActPage, $iNrofCatArticles);

        $sSelect = $this->_getSearchSelect($sSearchParamForQuery, $sInitialSearchCat, $sInitialSearchVendor, $sInitialSearchManufacturer, $sSortBy);
        if ($sSelect) {
            $oArtList->selectString($sSelect);
        }

        return $oArtList;
    }

    /**
     * Returns the amount of articles according to search parameters.
     *
     * @param bool $sSearchParamForQuery query parameter
     * @param bool $sInitialSearchCat initial category to search in
     * @param bool $sInitialSearchVendor initial vendor to search for
     * @param bool $sInitialSearchManufacturer initial Manufacturer to search for
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    public function getSearchArticleCount($sSearchParamForQuery = false, $sInitialSearchCat = false, $sInitialSearchVendor = false, $sInitialSearchManufacturer = false)
    {
        $iCnt = 0;
        $sSelect = $this->_getSearchSelect($sSearchParamForQuery, $sInitialSearchCat, $sInitialSearchVendor, $sInitialSearchManufacturer, false);
        if ($sSelect) {
            $sPartial = substr($sSelect, strpos($sSelect, ' from '));
            $sSelect = "select count( " . Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles', $this->_iLanguage) . ".oxid ) $sPartial ";

            $iCnt = DatabaseProvider::getDb()->getOne($sSelect);
        }

        return $iCnt;
    }

    /**
     * Returns the appropriate SQL select for a search according to search parameters
     *
     * @param bool $sSearchParamForQuery query parameter
     * @param bool $sInitialSearchCat initial category to search in
     * @param bool $sInitialSearchVendor initial vendor to search for
     * @param bool $sInitialSearchManufacturer initial Manufacturer to search for
     * @param bool $sSortBy sort by
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSearchSelect" in next major
     */
    protected function _getSearchSelect($sSearchParamForQuery = false, $sInitialSearchCat = false, $sInitialSearchVendor = false, $sInitialSearchManufacturer = false, $sSortBy = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$sSearchParamForQuery && !$sInitialSearchCat && !$sInitialSearchVendor && !$sInitialSearchManufacturer) {
            //no search string
            return null;
        }

        $oDb = DatabaseProvider::getDb();

        // performance
        if ($sInitialSearchCat) {
            // let's search this category - is no such category - skip all other code
            $oCategory = oxNew(Category::class);
            $sCatTable = $oCategory->getViewName();

            $sQ = "select 1 from $sCatTable 
                where $sCatTable.oxid = :oxid ";
            $sQ .= "and " . $oCategory->getSqlActiveSnippet();

            $params = [
                ':oxid' => $sInitialSearchCat
            ];

            if (!$oDb->getOne($sQ, $params)) {
                return;
            }
        }

        // performance:
        if ($sInitialSearchVendor) {
            // let's search this vendor - if no such vendor - skip all other code
            $oVendor = oxNew(Vendor::class);
            $sVndTable = $oVendor->getViewName();

            $sQ = "select 1 from $sVndTable 
                where $sVndTable.oxid = :oxid ";
            $sQ .= "and " . $oVendor->getSqlActiveSnippet();

            $params = [
                ':oxid' => $sInitialSearchVendor
            ];

            if (!$oDb->getOne($sQ, $params)) {
                return;
            }
        }

        // performance:
        if ($sInitialSearchManufacturer) {
            // let's search this Manufacturer - if no such Manufacturer - skip all other code
            $oManufacturer = oxNew(Manufacturer::class);
            $sManTable = $oManufacturer->getViewName();

            $sQ = "select 1 from $sManTable 
                where $sManTable.oxid = :oxid ";
            $sQ .= "and " . $oManufacturer->getSqlActiveSnippet();

            $params = [
                ':oxid' => $sInitialSearchManufacturer
            ];

            if (!$oDb->getOne($sQ, $params)) {
                return;
            }
        }

        $sWhere = null;
        if ($sSearchParamForQuery) {
            $sWhere = $this->_getWhere($sSearchParamForQuery);
        }

        $oArticle = oxNew(Article::class);
        $sArticleTable = $oArticle->getViewName();
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');

        $sSelectFields = $oArticle->getSelectFields();

        // longdesc field now is kept on different table
        $sDescJoin = $this->getDescriptionJoin($sArticleTable);

        //select articles
        $sSelect = "select {$sSelectFields}, {$sArticleTable}.oxtimestamp from {$sArticleTable} {$sDescJoin} where ";

        // must be additional conditions in select if searching in category
        if ($sInitialSearchCat) {
            $sCatView = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories', $this->_iLanguage);
            $sInitialSearchCatQuoted = $oDb->quote($sInitialSearchCat);
            $sSelectCat = "select oxid from {$sCatView} where oxid = $sInitialSearchCatQuoted and (oxpricefrom != '0' or oxpriceto != 0)";
            if ($oDb->getOne($sSelectCat)) {
                $sSelect = "select {$sSelectFields}, {$sArticleTable}.oxtimestamp from {$sArticleTable} $sDescJoin " .
                           "where {$sArticleTable}.oxid in ( select {$sArticleTable}.oxid as id from {$sArticleTable}, {$sO2CView} as oxobject2category, {$sCatView} as oxcategories " .
                           "where (oxobject2category.oxcatnid=$sInitialSearchCatQuoted and oxobject2category.oxobjectid={$sArticleTable}.oxid) or (oxcategories.oxid=$sInitialSearchCatQuoted and {$sArticleTable}.oxprice >= oxcategories.oxpricefrom and
                            {$sArticleTable}.oxprice <= oxcategories.oxpriceto )) and ";
            } else {
                $sSelect = "select {$sSelectFields} from {$sO2CView} as
                            oxobject2category, {$sArticleTable} {$sDescJoin} where oxobject2category.oxcatnid=$sInitialSearchCatQuoted and
                            oxobject2category.oxobjectid={$sArticleTable}.oxid and ";
            }
        }

        $sSelect .= $oArticle->getSqlActiveSnippet();
        $sSelect .= " and {$sArticleTable}.oxparentid = '' and {$sArticleTable}.oxissearch = 1 ";

        if ($sInitialSearchVendor) {
            $sSelect .= " and {$sArticleTable}.oxvendorid = " . $oDb->quote($sInitialSearchVendor) . " ";
        }

        if ($sInitialSearchManufacturer) {
            $sSelect .= " and {$sArticleTable}.oxmanufacturerid = " . $oDb->quote($sInitialSearchManufacturer) . " ";
        }

        $sSelect .= $sWhere;

        if ($sSortBy) {
            $sSelect .= " order by {$sSortBy} ";
        }

        return $sSelect;
    }

    /**
     * Forms and returns SQL query string for search in DB.
     *
     * @param string $sSearchString searching string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getWhere" in next major
     */
    protected function _getWhere($sSearchString) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $myConfig = Registry::getConfig();
        $blSep = false;
        $sArticleTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles', $this->_iLanguage);

        $aSearchCols = $myConfig->getConfigParam('aSearchCols');
        if (!(is_array($aSearchCols) && count($aSearchCols))) {
            return '';
        }

        $sSearchSep = $myConfig->getConfigParam('blSearchUseAND') ? 'and ' : 'or ';
        $aSearch = explode(' ', $sSearchString);
        $sSearch = ' and ( ';
        $myUtilsString = Registry::getUtilsString();

        foreach ($aSearch as $sSearchString) {
            if (!strlen($sSearchString)) {
                continue;
            }

            if ($blSep) {
                $sSearch .= $sSearchSep;
            }

            $blSep2 = false;
            $sSearch .= '( ';

            foreach ($aSearchCols as $sField) {
                if ($blSep2) {
                    $sSearch .= ' or ';
                }

                // as long description now is on different table, table must differ
                $sSearchField = $this->getSearchField($sArticleTable, $sField);

                $sSearch .= " {$sSearchField} like " . $oDb->quote("%$sSearchString%");

                // special chars ?
                if (($sUml = $myUtilsString->prepareStrForSearch($sSearchString))) {
                    $sSearch .= " or {$sSearchField} like " . $oDb->quote("%$sUml%");
                }

                $blSep2 = true;
            }
            $sSearch .= ' ) ';

            $blSep = true;
        }

        $sSearch .= ' ) ';

        return $sSearch;
    }

    /**
     * Get description join. Needed in case of searching for data in table oxartextends or its views.
     *
     * @param string $table
     *
     * @return string
     */
    protected function getDescriptionJoin($table)
    {
        $descriptionJoin = '';
        $searchColumns = Registry::getConfig()->getConfigParam('aSearchCols');

        if (is_array($searchColumns) && in_array('oxlongdesc', $searchColumns)) {
            $viewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxartextends', $this->_iLanguage);
            $descriptionJoin = " LEFT JOIN {$viewName } ON {$table}.oxid={$viewName }.oxid ";
        }
        return $descriptionJoin;
    }

    /**
     * Get search field name.
     * Needed in case of searching for data in table oxartextends or its views.
     *
     * @param string $table
     * @param string $field Chose table depending on field.
     *
     * @return string
     */
    protected function getSearchField($table, $field)
    {
        if ($field == 'oxlongdesc') {
            $searchField = Registry::get(TableViewNameGenerator::class)->getViewName('oxartextends', $this->_iLanguage) . ".{$field}";
        } else {
            $searchField = "{$table}.{$field}";
        }
        return $searchField;
    }
}
