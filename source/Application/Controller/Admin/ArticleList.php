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

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\CategoryList;
use OxidEsales\Eshop\Application\Model\ManufacturerList;
use OxidEsales\Eshop\Application\Model\VendorList;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Admin article list manager.
 * Collects base article information (according to filtering rules), performs sorting,
 * deletion of articles, etc.
 * Admin Menu: Manage Products -> Articles.
 */
class ArticleList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxarticle';

    /**
     * Type of list.
     *
     * @var string
     */
    protected $_sListType = 'oxarticlelist';

    /**
     * @return bool|string
     */
    private function getServerDateTime()
    {
        $sDateTimeAsTimestamp = Registry::getUtilsDate()->getTime();
        return Registry::getUtilsDate()->formatDBTimestamp($sDateTimeAsTimestamp);
    }

    /**
     * @param bool|string $sDateTime
     * @param bool        $blUseTimeCheck
     * @param Article     $oArticle
     *
     * @return bool
     */
    private function isArticleActive($sDateTime, $blUseTimeCheck, $oArticle)
    {
        if (!is_bool($sDateTime) && isset($oArticle->oxarticles__oxactive) && $oArticle->oxarticles__oxactive->value === '1') {
            return true;
        } else {
            if (
                !is_bool($sDateTime) && isset($oArticle->oxarticles__oxactivefrom) &&
                isset($oArticle->oxarticles__oxactiveto) && $blUseTimeCheck &&
                $oArticle->oxarticles__oxactivefrom->value <= $sDateTime &&
                $oArticle->oxarticles__oxactiveto->value >= $sDateTime
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collects articles base data and passes them according to filtering rules,
     * returns name of template file "article_list.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        $myConfig = Registry::getConfig();
        $sPwrSearchFld = Registry::getRequest()->getRequestEscapedParameter('pwrsearchfld');
        $sPwrSearchFld = $sPwrSearchFld ? strtolower($sPwrSearchFld) : "oxtitle";

        $sDateTime = $this->getServerDateTime();
        $blUseTimeCheck = Registry::getConfig()->getConfigParam('blUseTimeCheck');
        $oArticle = null;
        $oList = $this->getItemList();
        if ($oList) {
            foreach ($oList as $key => $oArticle) {
                $sFieldName = "oxarticles__{$sPwrSearchFld}";

                // formatting view
                if (!$myConfig->getConfigParam('blSkipFormatConversion')) {
                    if ($oArticle->$sFieldName->fldtype == "datetime") {
                        Registry::getUtilsDate()->convertDBDateTime($oArticle->$sFieldName);
                    } elseif ($oArticle->$sFieldName->fldtype == "timestamp") {
                        Registry::getUtilsDate()->convertDBTimestamp($oArticle->$sFieldName);
                    } elseif ($oArticle->$sFieldName->fldtype == "date") {
                        Registry::getUtilsDate()->convertDBDate($oArticle->$sFieldName);
                    }
                }

                $oArticle->showActiveCheckInAdminPanel = $this->isArticleActive($sDateTime, $blUseTimeCheck, $oArticle);
                $oArticle->pwrsearchval = $oArticle->$sFieldName->value;
                $oList[$key] = $oArticle;
            }
        }

        parent::render();

        // load fields
        if (!$oArticle && $oList) {
            $oArticle = $oList->getBaseObject();
        }
        $this->_aViewData["pwrsearchfields"] = $oArticle ? $this->getSearchFields() : null;
        $this->_aViewData["pwrsearchfld"] = strtoupper($sPwrSearchFld);

        $aFilter = $this->getListFilter();
        if (isset($aFilter["oxarticles"][$sPwrSearchFld])) {
            $this->_aViewData["pwrsearchinput"] = $aFilter["oxarticles"][$sPwrSearchFld];
        }

        $sType = '';
        $sValue = '';

        $sArtCat = Registry::getRequest()->getRequestEscapedParameter('art_category');
        if ($sArtCat && strstr($sArtCat, "@@") !== false) {
            list($sType, $sValue) = explode("@@", $sArtCat);
        }
        $this->_aViewData["art_category"] = $sArtCat;

        // parent category tree
        $this->_aViewData["cattree"] = $this->getCategoryList($sType, $sValue);

        // manufacturer list
        $this->_aViewData["mnftree"] = $this->getManufacturerlist($sType, $sValue);

        // vendor list
        $this->_aViewData["vndtree"] = $this->getVendorList($sType, $sValue);

        return "article_list.tpl";
    }

    /**
     * Returns array of fields which may be used for product data search
     *
     * @return array
     */
    public function getSearchFields()
    {
        $aSkipFields = [
            "oxblfixedprice",
            "oxvarselect",
            "oxamitemid",
            "oxamtaskid",
            "oxpixiexport",
            "oxpixiexported"
        ];
        $oArticle = oxNew(Article::class);

        return array_diff($oArticle->getFieldNames(), $aSkipFields);
    }

    /**
     * Load category list, mark active category;
     *
     * @param string $sType  active list type
     * @param string $sValue active list item id
     *
     * @return CategoryList
     */
    public function getCategoryList($sType, $sValue)
    {
        /** @var CategoryList $oCatTree parent category tree */
        $oCatTree = oxNew(CategoryList::class);
        $oCatTree->loadList();
        if ($sType === 'cat') {
            foreach ($oCatTree as $oCategory) {
                if ($oCategory->oxcategories__oxid->value == $sValue) {
                    $oCategory->selected = 1;
                    break;
                }
            }
        }

        return $oCatTree;
    }

    /**
     * Load manufacturer list, mark active category;
     *
     * @param string $sType  active list type
     * @param string $sValue active list item id
     *
     * @return ManufacturerList
     */
    public function getManufacturerList($sType, $sValue)
    {
        $oMnfTree = oxNew(ManufacturerList::class);
        $oMnfTree->loadManufacturerList();
        if ($sType === 'mnf') {
            foreach ($oMnfTree as $oManufacturer) {
                if ($oManufacturer->oxmanufacturers__oxid->value == $sValue) {
                    $oManufacturer->selected = 1;
                    break;
                }
            }
        }

        return $oMnfTree;
    }

    /**
     * Load vendor list, mark active category;
     *
     * @param string $sType  active list type
     * @param string $sValue active list item id
     *
     * @return VendorList
     */
    public function getVendorList($sType, $sValue)
    {
        $oVndTree = oxNew(VendorList::class);
        $oVndTree->loadVendorList();
        if ($sType === 'vnd') {
            foreach ($oVndTree as $oVendor) {
                if ($oVendor->oxvendor__oxid->value == $sValue) {
                    $oVendor->selected = 1;
                    break;
                }
            }
        }

        return $oVndTree;
    }

    /**
     * Builds and returns SQL query string.
     *
     * @param null $listObject list main object
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
     * Builds and returns SQL query string.
     *
     * @param null $listObject list main object
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function buildSelectString($listObject = null)
    {
        $sQ = parent::buildSelectString($listObject);
        if ($sQ) {
            $sTable = Registry::get(TableViewNameGenerator::class)->getViewName("oxarticles");
            $sQ .= " and $sTable.oxparentid = '' ";

            $sType = false;
            $sValue = '';

            $sArtCat = Registry::getRequest()->getRequestEscapedParameter('art_category');
            if ($sArtCat && strstr($sArtCat, "@@") !== false) {
                list($sType, $sValue) = explode("@@", $sArtCat);
            }

            switch ($sType) {
                // add category
                case 'cat':
                    $oStr = Str::getStr();
                    $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName("oxobject2category");
                    $sInsert = "from $sTable left join {$sViewName} on {$sTable}.oxid = {$sViewName}.oxobjectid " .
                               "where {$sViewName}.oxcatnid = " . DatabaseProvider::getDb()->quote($sValue) . " and ";
                    $sQ = $oStr->preg_replace("/from\s+$sTable\s+where/i", $sInsert, $sQ);
                    break;
                // add category
                case 'mnf':
                    $sQ .= " and $sTable.oxmanufacturerid = " . DatabaseProvider::getDb()->quote($sValue);
                    break;
                // add vendor
                case 'vnd':
                    $sQ .= " and $sTable.oxvendorid = " . DatabaseProvider::getDb()->quote($sValue);
                    break;
            }
        }

        return $sQ;
    }

    /**
     * Builds and returns array of SQL WHERE conditions.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function buildWhere()
    {
        // we override this to select only parent articles
        $this->_aWhere = parent::buildWhere();

        // adding folder check
        $sFolder = Registry::getRequest()->getRequestEscapedParameter('folder');
        if ($sFolder && $sFolder != '-1') {
            $this->_aWhere[Registry::get(TableViewNameGenerator::class)->getViewName("oxarticles") . ".oxfolder"] = $sFolder;
        }

        return $this->_aWhere;
    }

    /**
     * Deletes entry from the database
     */
    public function deleteEntry()
    {
        $sOxId = $this->getEditObjectId();
        $oArticle = oxNew(Article::class);
        if ($sOxId && $oArticle->load($sOxId)) {
            parent::deleteEntry();
        }
    }
}
