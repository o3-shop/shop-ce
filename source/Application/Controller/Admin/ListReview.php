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

use OxidEsales\Eshop\Application\Controller\Admin\ArticleList;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * user list "view" class.
 */
class ListReview extends ArticleList
{
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = 'oxlist';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxreview';

    /**
     * Viewable list size getter
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getViewListSize" in next version
     */
    protected function _getViewListSize() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getViewListSize()();
    }

    /**
     * Executes parent method parent::render(), passes data to Smarty engine
     * and returns name of template file "list_review.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        AdminListController::render();

        $this->_aViewData["menustructure"] = $this->getNavigation()->getDomXml()->documentElement->childNodes;
        $this->_aViewData["articleListTable"] = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');

        return "list_review.tpl";
    }

    /**
     * Returns select query string
     *
     * @param object $listObject list item object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildSelectString" in next major
     */
    protected function _buildSelectString($listObject = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->buildSelectString($listObject);
    }

    /**
     * Returns select query string
     *
     * @param object $listObject list item object
     *
     * @return string
     */
    protected function buildSelectString($listObject = null)
    {
        $sArtTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles', $this->_iEditLang);

        $sQ = "select oxreviews.oxid, oxreviews.oxcreate, oxreviews.oxtext, oxreviews.oxobjectid, {$sArtTable}.oxparentid, {$sArtTable}.oxtitle as oxtitle, {$sArtTable}.oxvarselect as oxvarselect, oxparentarticles.oxtitle as parenttitle, ";
        $sQ .= "concat( {$sArtTable}.oxtitle, if(isnull(oxparentarticles.oxtitle), '', oxparentarticles.oxtitle), {$sArtTable}.oxvarselect) as arttitle from oxreviews ";
        $sQ .= "left join $sArtTable as {$sArtTable} on {$sArtTable}.oxid=oxreviews.oxobjectid and 'oxarticle' = oxreviews.oxtype ";
        $sQ .= "left join $sArtTable as oxparentarticles on oxparentarticles.oxid = {$sArtTable}.oxparentid ";
        $sQ .= "where 1 and oxreviews.oxlang = '{$this->_iEditLang}' ";


        //removing parent id checking from sql
        $sStr = "/\s+and\s+" . $sArtTable . "\.oxparentid\s*=\s*''/";
        $sQ = Str::getStr()->preg_replace($sStr, " ", $sQ);

        return " $sQ and {$sArtTable}.oxid is not null ";
    }

    /**
     * Adds filtering conditions to query string
     *
     * @param array $whereQuery filter conditions
     * @param string $fullQuery query string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareWhereQuery" in next major
     */
    protected function _prepareWhereQuery($whereQuery, $fullQuery) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->prepareWhereQuery($whereQuery, $fullQuery);
    }

    /**
     * Adds filtering conditions to query string
     *
     * @param array $whereQuery filter conditions
     * @param string $fullQuery query string
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareWhereQuery($whereQuery, $fullQuery)
    {
        $sSql = parent::prepareWhereQuery($whereQuery, $fullQuery);

        $sArtTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles', $this->_iEditLang);
        $sArtTitleField = "{$sArtTable}.oxtitle";

        // if searching in article title field, updating sql for this case
        if ($this->_aWhere[$sArtTitleField]) {
            $sSqlForTitle = " (CONCAT( {$sArtTable}.oxtitle, if(isnull(oxparentarticles.oxtitle), '', oxparentarticles.oxtitle), {$sArtTable}.oxvarselect)) ";
            $sSql = Str::getStr()->preg_replace("/{$sArtTable}\.oxtitle\s+like/", "$sSqlForTitle like", $sSql);
        }

        return $sSql;
    }
}
