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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Controller\ArticleListController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Core\Registry;

/**
 * List of articles for a selected Manufacturer.
 * Collects list of articles, according to it generates links for list gallery,
 * meta-tags (for search engines). Result - "manufacturerlist.tpl" template.
 * O3-Shop -> (Any selected shop product category).
 */
class ManufacturerListController extends ArticleListController
{
    /**
     * List type
     *
     * @var string
     */
    protected $_sListType = 'manufacturer';

    /**
     * List type
     *
     * @var string
     */
    protected $_blVisibleSubCats = null;

    /**
     * List type
     *
     * @var string
     */
    protected $_oSubCatList = null;

    /**
     * Recommlist
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_oRecommList = null;

    /**
     * Template location
     *
     * @var string
     */
    protected $_sTplLocation = null;

    /**
     * Template location
     *
     * @var string
     */
    protected $_sCatTitle = null;

    /**
     * Page navigation
     *
     * @var object
     */
    protected $_oPageNavigation = null;

    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_blShowSorting = true;

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_INDEX;

    /**
     * Executes parent::render(), loads active Manufacturer, prepares article
     * list sorting rules. Loads list of articles which belong to this Manufacturer
     * Generates page navigation data
     * such as previous/next window URL, number of available pages, generates
     * meta-tags info (FrontendController::_convertForMetaTags()) and returns
     * name of template to render.
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        FrontendController::render();

        // load Manufacturer
        if ($this->getManufacturerTree()) {
            if (($oManufacturer = $this->getActManufacturer())) {
                if ($oManufacturer->getId() != 'root') {
                    // load the articles
                    $this->getArticleList();

                    // checking if requested page is correct
                    $this->_checkRequestedPage();

                    // processing list articles
                    $this->_processListArticles();
                }
            }
        }

        return $this->_sThisTemplate;
    }

    /**
     * Returns product link type (OXARTICLE_LINKTYPE_MANUFACTURER)
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductLinkType" in next major
     */
    protected function _getProductLinkType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return OXARTICLE_LINKTYPE_MANUFACTURER;
    }

    /**
     * Loads and returns article list of active Manufacturer.
     *
     * @param Manufacturer $category Manufacturer object
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadArticles" in next major
     */
    protected function _loadArticles($category) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sManufacturerId = $category->getId();

        // load only articles which we show on screen
        $iNrofCatArticles = (int) Registry::getConfig()->getConfigParam('iNrofCatArticles');
        $iNrofCatArticles = $iNrofCatArticles ? $iNrofCatArticles : 1;

        $oArtList = oxNew(ArticleList::class);
        $oArtList->setSqlLimit($iNrofCatArticles * $this->_getRequestPageNr(), $iNrofCatArticles);
        $oArtList->setCustomSorting($this->getSortingSql($this->getSortIdent()));

        // load the articles
        $this->_iAllArtCnt = $oArtList->loadManufacturerArticles($sManufacturerId, $category);

        // counting pages
        $this->_iCntPages = ceil($this->_iAllArtCnt / $iNrofCatArticles);

        return [$oArtList, $this->_iAllArtCnt];
    }

    /**
     * Returns active product id to load its seo meta info
     *
     * @return string|void
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoObjectId" in next major
     */
    protected function _getSeoObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($oManufacturer = $this->getActManufacturer())) {
            return $oManufacturer->getId();
        }
    }

    /**
     * Modifies url by adding page parameters. When seo is on, url is additionally
     * formatted by SEO engine
     *
     * @param string $url  current url
     * @param int    $currentPage page number
     * @param int    $languageId active language id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addPageNrParam" in next major
     */
    protected function _addPageNrParam($url, $currentPage, $languageId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (Registry::getUtils()->seoIsActive() && ($oManufacturer = $this->getActManufacturer())) {
            if ($currentPage) {
                // only if page number > 0
                return $oManufacturer->getBaseSeoLink($languageId, $currentPage);
            }
        }

        return parent::_addPageNrParam($url, $currentPage, $languageId);
    }

    /**
     * Returns current view Url
     *
     * @return string
     */
    public function generatePageNavigationUrl()
    {
        if ((Registry::getUtils()->seoIsActive() && ($oManufacturer = $this->getActManufacturer()))) {
            return $oManufacturer->getLink();
        } else {
            return parent::generatePageNavigationUrl();
        }
    }

    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return bool
     */
    public function hasVisibleSubCats()
    {
        if ($this->_blVisibleSubCats === null) {
            $this->_blVisibleSubCats = false;
            if (($oManufacturerTree = $this->getManufacturerTree())) {
                if (($oManufacturer = $this->getActManufacturer())) {
                    if ($oManufacturer->getId() == 'root') {
                        $this->_blVisibleSubCats = $oManufacturerTree->count();
                        $this->_oSubCatList = $oManufacturerTree;
                    }
                }
            }
        }

        return $this->_blVisibleSubCats;
    }

    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function getSubCatList()
    {
        if ($this->_oSubCatList === null) {
            $this->_oSubCatList = $this->hasVisibleSubCats() ? $this->_oSubCatList : [];
        }

        return $this->_oSubCatList;
    }

    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function getArticleList()
    {
        if ($this->_aArticleList === null) {
            $this->_aArticleList = [];
            if ($this->getManufacturerTree()) {
                $oManufacturer = $this->getActManufacturer();
                if ($oManufacturer && ($oManufacturer->getId() != 'root') && $oManufacturer->getIsVisible()) {
                    list($aArticleList, $iAllArtCnt) = $this->_loadArticles($oManufacturer);
                    if ($iAllArtCnt) {
                        $this->_aArticleList = $aArticleList;
                    }
                }
            }
        }

        return $this->_aArticleList;
    }

    /**
     * Template variable getter. Returns template location
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->_sCatTitle === null) {
            $this->_sCatTitle = '';
            if ($this->getManufacturerTree()) {
                if ($oManufacturer = $this->getActManufacturer()) {
                    $this->_sCatTitle = $oManufacturer->oxmanufacturers__oxtitle->value;
                }
            }
        }

        return $this->_sCatTitle;
    }

    /**
     * Template variable getter. Returns category path array
     *
     * @return array|void
     */
    public function getTreePath()
    {
        if ($oManufacturerTree = $this->getManufacturerTree()) {
            return $oManufacturerTree->getPath();
        }
    }

    /**
     * Template variable getter. Returns active Manufacturer
     *
     * @return object
     */
    public function getActiveCategory()
    {
        if ($this->_oActCategory === null) {
            $this->_oActCategory = false;
            if ($this->getManufacturerTree()) {
                if ($oManufacturer = $this->getActManufacturer()) {
                    $this->_oActCategory = $oManufacturer;
                }
            }
        }

        return $this->_oActCategory;
    }

    /**
     * Template variable getter. Returns template location
     *
     * @return string
     */
    public function getCatTreePath()
    {
        if ($this->_sCatTreePath === null) {
            $this->_sCatTreePath = false;
            if (($oManufacturerTree = $this->getManufacturerTree())) {
                $this->_sCatTreePath = $oManufacturerTree->getPath();
            }
        }

        return $this->_sCatTreePath;
    }

    /**
     * Returns title suffix used in template
     *
     * @return string|void
     */
    public function getTitleSuffix()
    {
        if ($this->getActManufacturer()->oxmanufacturers__oxshowsuffix->value) {
            return Registry::getConfig()->getActiveShop()->oxshops__oxtitlesuffix->value;
        }
    }

    /**
     * Calls and returns result of parent:: _collectMetaKeyword();
     *
     * @param mixed $keywords                category path
     * @param bool  $removeDuplicatedWords remove duplicated words
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($keywords, $removeDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return parent::_collectMetaKeyword($keywords);
    }

    /**
     * Meta tags - description and keywords - generator for search
     * engines. Uses string passed by parameters, cleans HTML tags,
     * string duplicates, special chars. Also removes strings defined
     * in $myConfig->aSkipTags (Admin area).
     *
     * @param mixed $meta  category path
     * @param int   $length   max length of result, -1 for no truncation
     * @param bool  $descriptionTag if true - performs additional duplicate cleaning
     *
     * @return  string  $sString    converted string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaDescription" in next major
     */
    protected function _prepareMetaDescription($meta, $length = 1024, $descriptionTag = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return parent::_collectMetaDescription($meta, $length, $descriptionTag);
    }

    /**
     * returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $languageId language id
     *
     * @return object
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSubject" in next major
     */
    protected function _getSubject($languageId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getActManufacturer();
    }

    /**
     * Returns additional URL parameters which must be added to list products dynamic urls
     *
     * @return string
     */
    public function getAddUrlParams()
    {
        $sAddParams = parent::getAddUrlParams();
        $sAddParams .= ($sAddParams ? '&amp;' : '') . "listtype={$this->_sListType}";
        if ($oManufacturer = $this->getActManufacturer()) {
            $sAddParams .= "&amp;mnid=" . $oManufacturer->getId();
        }

        return $sAddParams;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];

        $oCatTree = $this->getManufacturerTree();

        if ($oCatTree) {
            foreach ($oCatTree->getPath() as $oCat) {
                $aCatPath = [];
                $aCatPath['link'] = $oCat->getLink();
                $aCatPath['title'] = $oCat->oxmanufacturers__oxtitle->value;

                $aPaths[] = $aCatPath;
            }
        }

        return $aPaths;
    }

    /**
     * Template variable getter. Returns array of attribute values
     * we do have here in this category
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return null;
    }
}
