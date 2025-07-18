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
use OxidEsales\Eshop\Application\Model\VendorList;
use OxidEsales\Eshop\Core\Registry;

/**
 * List of articles for a selected vendor.
 * Collects list of articles, according to it generates links for list gallery,
 * meta tags (for search engines). Result - "vendorlist.tpl" template.
 * O3-Shop -> (Any selected shop product category).
 */
class VendorListController extends ArticleListController
{
    /**
     * List type
     *
     * @var string
     */
    protected $_sListType = 'vendor';

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
     * Vendor list object.
     *
     * @var object
     */
    protected $_oVendorTree = null;

    /**
     * Executes parent::render(), loads active vendor, prepares article
     * list sorting rules. Loads list of articles which belong to this vendor
     * Generates page navigation data
     * such as previous/next window URL, number of available pages, generates
     * meta tags info (FrontendController::_convertForMetaTags()) and returns
     * name of template to render.
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        FrontendController::render();

        // load vendor
        if (($this->_getVendorId() && $this->getVendorTree())) {
            if (($oVendor = $this->getActVendor())) {
                if ($oVendor->getId() != 'root') {
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
     * Returns product link type (OXARTICLE_LINKTYPE_VENDOR)
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductLinkType" in next major
     */
    protected function _getProductLinkType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return OXARTICLE_LINKTYPE_VENDOR;
    }

    /**
     * Loads and returns article list of active vendor.
     *
     * @param object $oVendor vendor object
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadArticles" in next major
     */
    protected function _loadArticles($oVendor) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sVendorId = $oVendor->getId();

        // load only articles which we show on screen
        $iNrOfCatArticles = (int) Registry::getConfig()->getConfigParam('iNrofCatArticles');
        $iNrOfCatArticles = $iNrOfCatArticles ? $iNrOfCatArticles : 1;

        $oArtList = oxNew(ArticleList::class);
        $oArtList->setSqlLimit($iNrOfCatArticles * $this->_getRequestPageNr(), $iNrOfCatArticles);
        $oArtList->setCustomSorting($this->getSortingSql($this->getSortIdent()));

        // load the articles
        $this->_iAllArtCnt = $oArtList->loadVendorArticles($sVendorId, $oVendor);

        // counting pages
        $this->_iCntPages = ceil($this->_iAllArtCnt / $iNrOfCatArticles);

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
        if (($oVendor = $this->getActVendor())) {
            return $oVendor->getId();
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
        if (Registry::getUtils()->seoIsActive() && ($oVendor = $this->getActVendor())) {
            if ($currentPage) {
                // only if page number > 0
                $url = $oVendor->getBaseSeoLink($languageId, $currentPage);
            }
        } else {
            $url = FrontendController::_addPageNrParam($url, $currentPage, $languageId);
        }

        return $url;
    }

    /**
     * Returns current view Url
     *
     * @return string
     */
    public function generatePageNavigationUrl()
    {
        if ((Registry::getUtils()->seoIsActive() && ($oVendor = $this->getActVendor()))) {
            return $oVendor->getLink();
        } else {
            return parent::generatePageNavigationUrl();
        }
    }

    /**
     * Returns if vendor has visible sub-cats and load them.
     *
     * @return bool
     */
    public function hasVisibleSubCats()
    {
        if ($this->_blVisibleSubCats === null) {
            $this->_blVisibleSubCats = false;
            if (($this->_getVendorId() && $oVendorTree = $this->getVendorTree())) {
                if (($oVendor = $this->getActVendor())) {
                    if ($oVendor->getId() == 'root') {
                        $this->_blVisibleSubCats = $oVendorTree->count();
                        $this->_oSubCatList = $oVendorTree;
                    }
                }
            }
        }

        return $this->_blVisibleSubCats;
    }

    /**
     * Returns vendor subcategories
     *
     * @return array
     */
    public function getSubCatList()
    {
        if ($this->_oSubCatList === null) {
            $this->_oSubCatList = [];
            if ($this->hasVisibleSubCats()) {
                return $this->_oSubCatList;
            }
        }

        return $this->_oSubCatList;
    }

    /**
     * Get vendor article list
     *
     * @return array
     */
    public function getArticleList()
    {
        if ($this->_aArticleList === null) {
            $this->_aArticleList = [];
            if (($oVendor = $this->getActVendor()) && ($oVendor->getId() != 'root')) {
                list($aArticleList, $iAllArtCnt) = $this->_loadArticles($oVendor);
                if ($iAllArtCnt) {
                    $this->_aArticleList = $aArticleList;
                }
            }
        }

        return $this->_aArticleList;
    }

    /**
     * Return vendor title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->_sCatTitle === null) {
            $this->_sCatTitle = '';
            if ($oVendor = $this->getActVendor()) {
                $this->_sCatTitle = $oVendor->oxvendor__oxtitle->value;
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
        if ($this->_getVendorId() && $oVendorTree = $this->getVendorTree()) {
            return $oVendorTree->getPath();
        }
    }

    /**
     * Returns request parameter of vendor id.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVendorIdFromRequest" in next major
     */
    protected function _getVendorId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::getRequest()->getRequestEscapedParameter('cnid');
    }

    /**
     * Template variable getter. Returns active vendor
     *
     * @return object
     */
    public function getActiveCategory()
    {
        if ($this->_oActCategory === null) {
            $this->_oActCategory = false;
            if (($this->_getVendorId() && $this->getVendorTree())) {
                if ($oVendor = $this->getActVendor()) {
                    $this->_oActCategory = $oVendor;
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
            if (($oVendorTree = $this->getVendorTree())) {
                $this->_sCatTreePath = $oVendorTree->getPath();
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
        if ($this->getActVendor()->oxvendor__oxshowsuffix->value) {
            return Registry::getConfig()->getActiveShop()->oxshops__oxtitlesuffix->value;
        }
    }

    /**
     * Returns current view keywords separated by comma
     * (calls parent::_collectMetaKeyword())
     *
     * @param string $keywords               data to use as keywords
     * @param bool   $removeDuplicatedWords remove duplicated words
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($keywords, $removeDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return parent::_collectMetaKeyword($keywords);
    }

    /**
     * Returns current view meta description data
     * (calls parent::_collectMetaDescription())
     *
     * @param string $meta     category path
     * @param int    $length   max length of result, -1 for no truncation
     * @param bool   $descriptionTag if true - performs additional duplicate cleaning
     *
     * @return string
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
        return $this->getActVendor();
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
        if ($oVendor = $this->getActVendor()) {
            $sAddParams .= "&amp;cnid=v_" . $oVendor->getId();
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
        $oCatTree = $this->getVendorTree();

        if ($oCatTree) {
            foreach ($oCatTree->getPath() as $oCat) {
                $aCatPath = [];

                $aCatPath['link'] = $oCat->getLink();
                $aCatPath['title'] = $oCat->oxcategories__oxtitle->value;

                $aPaths[] = $aCatPath;
            }
        }

        return $aPaths;
    }


    /**
     * Returns vendor tree
     *
     * @return VendorList
     */
    public function getVendorTree()
    {
        if ($this->_getVendorId() && $this->_oVendorTree === null) {
            /** @var VendorList $oVendorTree */
            $oVendorTree = oxNew(VendorList::class);
            $oVendorTree->buildVendorTree(
                'vendorlist',
                $this->getActVendor()->getId(),
                Registry::getConfig()->getShopHomeUrl()
            );
            $this->_oVendorTree = $oVendorTree;
        }

        return $this->_oVendorTree;
    }

    /**
     * Vendor tree setter
     *
     * @param VendorList $oVendorTree vendor tree
     */
    public function setVendorTree($oVendorTree)
    {
        $this->_oVendorTree = $oVendorTree;
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
