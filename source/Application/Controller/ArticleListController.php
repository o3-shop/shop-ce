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

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\RssFeed;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * List of articles for a selected product group.
 * Collects list of articles, according to it generates links for list gallery,
 * meta tags (for search engines). Result - "list.tpl" template.
 * O3-Shop -> (Any selected shop product category).
 */
class ArticleListController extends FrontendController
{
    /**
     * Count of all articles in list.
     *
     * @var integer
     */
    protected $_iAllArtCnt = 0;

    /**
     * Number of possible pages.
     *
     * @var integer
     */
    protected $_iCntPages = 0;

    /**
     * Current class default template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/list/list.tpl';

    /**
     * New layout list template
     *
     * @var string
     */
    protected $_sThisMoreTemplate = 'page/list/morecategories.tpl';

    /**
     * Category path string
     *
     * @var string
     */
    protected $_sCatPathString = null;

    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_blShowSorting = true;

    /**
     * Category attributes.
     *
     * @var array
     */
    protected $_aAttributes = null;

    /**
     * Category article list
     *
     * @var array
     */
    protected $_aCatArtList = null;

    /**
     * If category has subcategories
     *
     * @var bool
     */
    protected $_blHasVisibleSubCats = null;

    /**
     * List of category's subcategories
     *
     * @var array
     */
    protected $_aSubCatList = null;

    /**
     * Page navigation
     *
     * @var object
     */
    protected $_oPageNavigation = null;

    /**
     * Active object is category.
     *
     * @var bool
     */
    protected $_blIsCat = null;

    /**
     * Recomendation list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_oRecommList = null;

    /**
     * Category title
     *
     * @var string
     */
    protected $_sCatTitle = null;

    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_blBargainAction = false;

    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_aSimilarRecommListIds = null;

    /**
     * Generates (if not generated yet) and returns view ID (for
     * template engine caching).
     *
     * @return string   $this->_sViewId view id
     */
    protected function generateViewId()
    {
        $categoryId = Registry::getRequest()->getRequestEscapedParameter('cnid');
        $activePage = $this->getActPage();
        $articlesPerPage = Registry::getSession()->getVariable('_artperpage');
        $listDisplayType = $this->_getListDisplayType();
        $parentViewId = parent::generateViewId();

        return md5(
            $parentViewId . '|' . $categoryId . '|' . $activePage . '|' . $articlesPerPage . '|' . $listDisplayType
        );
    }

    /**
     * Executes parent::render(), loads active category, prepares article
     * list sorting rules. According to category type loads list of
     * articles - regular (ArticleList::LoadCategoryArticles()) or price
     * dependent (ArticleList::LoadPriceArticles()). Generates page navigation data
     * such as previous/next window URL, number of available pages, generates
     * meta tags info (FrontendController::_convertForMetaTags()) and returns
     * name of template to render. Also checks if actual pages count does not exceed real
     * articles page count. If yes - calls error_404_handler().
     *
     * @return  string  $this->_sThisTemplate   current template file name
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        $config = Registry::getConfig();

        $category = $this->getCategoryToRender();

        $isCategoryActive = $category && $category->oxcategories__oxactive->value;
        if (!$isCategoryActive) {
            Registry::getUtils()->redirect($config->getShopURL() . 'index.php', true, 302);
        }

        $activeCategory = $this->getActiveCategory();
        if ($activeCategory && $config->getConfigParam('bl_rssCategories')) {
            $rss = oxNew(RssFeed::class);
            $this->addRssFeed(
                $rss->getCategoryArticlesTitle($activeCategory),
                $rss->getCategoryArticlesUrl($activeCategory),
                'activeCategory'
            );
        }

        //checking if actual pages count does not exceed real articles page count
        $this->getArticleList();

        if ($this->_blIsCat) {
            $this->_checkRequestedPage();
        }

        parent::render();

        // processing list articles
        $this->_processListArticles();

        return $this->getTemplateName();
    }

    /**
     * Returns category, which should be rendered.
     * In case of 'more categories' page is viewed, sets 'more categories' template,
     * sets empty category as active category and returns it.
     *
     * @return Category
     * @throws DatabaseConnectionException
     */
    protected function getCategoryToRender()
    {
        $this->_blIsCat = false;

        // A. checking for fake "more" category
        if ('oxmore' == Registry::getRequest()->getRequestEscapedParameter('cnid')) {
            // overriding some standard value and parameters
            $this->_sThisTemplate = $this->_sThisMoreTemplate;
            $category = oxNew(Category::class);
            $category->oxcategories__oxactive = new Field(1, Field::T_RAW);
            $this->setActiveCategory($category);
        } elseif (($category = $this->getActiveCategory())) {
            $this->_blIsCat = true;
            $this->_blBargainAction = true;
        }

        return $category;
    }

    /**
     * Checks if requested page is valid and:
     * - redirecting to first page in case requested page does not exist
     * or
     * - displays 404 error if category has no products
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkRequestedPage" in next major
     */
    protected function _checkRequestedPage() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $pageCount = $this->getPageCount();
        $currentPageNumber = $this->getActPage();
        // redirecting to first page in case requested page does not exist
        if ($pageCount && (($pageCount - 1) < $currentPageNumber)) {
            Registry::getUtils()->redirect($this->getActiveCategory()->getLink(), false);
        }
        if (!$pageCount && $currentPageNumber) {
            // display error if category has no products, but page number is entered
            $this->_iActPage = 0;
            error_404_handler($this->getActiveCategory()->getLink());
        }
    }

    /**
     * Iterates through list articles and performs list view specific tasks:
     *  - sets type of link which needs to be generated (Manufacturer link)
     * @deprecated underscore prefix violates PSR12, will be renamed to "processListArticles" in next major
     */
    protected function _processListArticles() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($articleList = $this->getArticleList()) {
            $linkType = $this->_getProductLinkType();
            $dynamicParameters = $this->getAddUrlParams();
            $seoParameters = $this->getAddSeoUrlParams();

            foreach ($articleList as $article) {
                /** @var Article $article */
                $article->setLinkType($linkType);

                if ($dynamicParameters) {
                    $article->appendStdLink($dynamicParameters);
                }

                if ($seoParameters) {
                    $article->appendLink($seoParameters);
                }
            }
        }
    }

    /**
     * Returns additional URL parameters which must be added to list products dynamic urls
     *
     * @return string
     */
    public function getAddUrlParams()
    {
        $dynamicParameters = parent::getAddUrlParams();
        if (!Registry::getUtils()->seoIsActive()) {
            $pageNumber = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
            if ($pageNumber > 0) {
                $dynamicParameters .= ($dynamicParameters ? '&amp;' : '') . "pgNr={$pageNumber}";
            }
        }

        return $dynamicParameters;
    }

    /**
     * Returns additional URL parameters which must be added to list products seo urls
     *
     * @return string
     */
    public function getAddSeoUrlParams()
    {
        return '';
    }

    /**
     * Returns product link type:
     *  - OXARTICLE_LINKTYPE_PRICECATEGORY - when active category is price category
     *  - OXARTICLE_LINKTYPE_CATEGORY - when active category is regular category
     *
     * @return int
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductLinkType" in next major
     */
    protected function _getProductLinkType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $categoryType = OXARTICLE_LINKTYPE_CATEGORY;
        if (($category = $this->getActiveCategory()) && $category->isPriceCategory()) {
            $categoryType = OXARTICLE_LINKTYPE_PRICECATEGORY;
        }

        return $categoryType;
    }

    /**
     * Stores chosen category filter into session.
     *
     * Session variables:
     * <b>session_attrfilter</b>
     */
    public function executefilter()
    {
        $baseLanguageId = Registry::getLang()->getBaseLanguage();
        // store this into session
        $attributeFilter = Registry::getRequest()->getRequestEscapedParameter('attrfilter', true);
        $activeCategory = Registry::getRequest()->getRequestEscapedParameter('cnid');

        if (!empty($attributeFilter)) {
            $sessionFilter = Registry::getSession()->getVariable('session_attrfilter');
            //fix for #2904 - if language will be changed attributes of this category will be deleted from session
            //and new filters for active language set.
            $sessionFilter[$activeCategory] = null;
            $sessionFilter[$activeCategory][$baseLanguageId] = $attributeFilter;
            Registry::getSession()->setVariable('session_attrfilter', $sessionFilter);
        }
    }

    /**
     * Reset filter.
     */
    public function resetFilter()
    {
        $activeCategory = Registry::getRequest()->getRequestEscapedParameter('cnid');
        $sessionFilter = Registry::getSession()->getVariable('session_attrfilter');

        unset($sessionFilter[$activeCategory]);
        Registry::getSession()->setVariable('session_attrfilter', $sessionFilter);
    }

    /**
     * Loads and returns article list of active category.
     *
     * @param Category $category category object
     *
     * @return ArticleList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadArticles" in next major
     */
    protected function _loadArticles($category) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $config = Registry::getConfig();

        $numberOfCategoryArticles = (int) $config->getConfigParam('iNrofCatArticles');
        $numberOfCategoryArticles = $numberOfCategoryArticles ? $numberOfCategoryArticles : 1;

        // load only articles which we show on screen
        $articleList = oxNew(ArticleList::class);
        $articleList->setSqlLimit($numberOfCategoryArticles * $this->_getRequestPageNr(), $numberOfCategoryArticles);
        $articleList->setCustomSorting($this->getSortingSql($this->getSortIdent()));

        if ($category->isPriceCategory()) {
            $priceFrom = $category->oxcategories__oxpricefrom->value;
            $priceTo = $category->oxcategories__oxpriceto->value;

            $this->_iAllArtCnt = $articleList->loadPriceArticles($priceFrom, $priceTo, $category);
        } else {
            $sessionFilter = Registry::getSession()->getVariable('session_attrfilter');

            $activeCategoryId = $category->getId();
            $this->_iAllArtCnt = $articleList->loadCategoryArticles($activeCategoryId, $sessionFilter);
        }

        $this->_iCntPages = ceil($this->_iAllArtCnt / $numberOfCategoryArticles);

        return $articleList;
    }

    /**
     * Get actual page number.
     *
     * @return int
     */
    public function getActPage()
    {
        // Fake oxmore category has no subpages, so we can set the page number to zero
        if ('oxmore' == Registry::getRequest()->getRequestEscapedParameter('cnid')) {
            return 0;
        }

        return $this->_getRequestPageNr();
    }

    /**
     * Calls parent::getActPage();
     *
     * @todo this function is a temporary solution and should be removed as
     * soon product list loading is refactored
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getRequestPageNr" in next major
     */
    protected function _getRequestPageNr() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return parent::getActPage();
    }

    /**
     * Get list display type
     *
     * @return null|string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getArticleListDisplayType" in next major
     */
    protected function _getListDisplayType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $listDisplayType = Registry::getSession()->getVariable('ldtype');

        if (is_null($listDisplayType)) {
            $listDisplayType = Registry::getConfig()->getConfigParam('sDefaultListDisplayType');
        }

        return $listDisplayType;
    }

    /**
     * Returns active product id to load its seo meta info
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoObjectId" in next major
     */
    protected function _getSeoObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($category = $this->getActiveCategory())) {
            return $category->getId();
        }
    }

    /**
     * Returns string built from category titles
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCatPathString" in next major
     */
    protected function _getCatPathString() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_sCatPathString === null) {
            // marking as already set
            $this->_sCatPathString = false;

            //fetching category path
            if (is_array($categoryTreePath = $this->getCatTreePath())) {
                $stringModifier = Str::getStr();
                $this->_sCatPathString = '';
                foreach ($categoryTreePath as $category) {
                    if ($this->_sCatPathString) {
                        $this->_sCatPathString .= ', ';
                    }
                    $this->_sCatPathString .= $stringModifier->strtolower($category->oxcategories__oxtitle->value);
                }
            }
        }

        return $this->_sCatPathString;
    }

    /**
     * Returns current view meta description data.
     *
     * @param string $meta Category path.
     * @param int $length Max length of result, -1 for no truncation.
     * @param bool $removeDuplicatedWords If true - performs additional duplicate cleaning.
     *
     * @return  string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaDescription" in next major
     */
    protected function _prepareMetaDescription($meta, $length = 1024, $removeDuplicatedWords = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $description = '';
        // appending parent title
        if ($activeCategory = $this->getActiveCategory()) {
            if (($parentCategory = $activeCategory->getParentCategory())) {
                $description .= " {$parentCategory->oxcategories__oxtitle->value} -";
            }

            // adding category title
            $description .= " {$activeCategory->oxcategories__oxtitle->value}.";
        }

        // and final component ...
        //changed for #2776
        if (($suffix = Registry::getConfig()->getActiveShop()->oxshops__oxtitleprefix->value)) {
            $description .= " {$suffix}";
        }

        // making safe for output
        $description = Str::getStr()->html_entity_decode($description);
        $description = Str::getStr()->strip_tags($description);
        $description = Str::getStr()->cleanStr($description);
        $description = Str::getStr()->htmlspecialchars($description);

        return trim($description);
    }

    /**
     * Template variable getter. Returns meta description
     *
     * @return string
     */
    public function getMetaDescription()
    {
        $meta = parent::getMetaDescription();

        if ($titlePageSuffix = $this->getTitlePageSuffix()) {
            if ($meta) {
                $meta .= ", ";
            }
            $meta .= $titlePageSuffix;
        }

        return $meta;
    }

    /**
     * Meta tags - description and keywords - generator for search
     * engines. Uses string passed by parameters, cleans HTML tags,
     * string duplicates, special chars. Also removes strings defined
     * in $config->aSkipTags (Admin area).
     *
     * @param string $meta Category path
     * @param int $length Max length of result, -1 for no truncation
     * @param bool $descriptionTag If true - performs additional duplicate cleaning
     *
     * @return  string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "collectMetaDescription" in next major
     */
    protected function _collectMetaDescription($meta, $length = 1024, $descriptionTag = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        //formatting description tag
        $category = $this->getActiveCategory();

        $additionalText = (($category instanceof Category)) ? trim($category->getLongDesc()) : '';

        $articleList = $this->getArticleList();
        if (!$additionalText && count($articleList)) {
            foreach ($articleList as $article) {
                if ($additionalText) {
                    $additionalText .= ', ';
                }
                $additionalText .= $article->oxarticles__oxtitle->value;
            }
        }

        if (!$meta) {
            $meta = trim($this->_getCatPathString());
        }

        if ($meta) {
            $meta = "{$meta} - {$additionalText}";
        } else {
            $meta = $additionalText;
        }

        return parent::_prepareMetaDescription($meta, $length, $descriptionTag);
    }

    /**
     * Returns current view keywords separated by comma
     *
     * @param string $keywords Data to use as keywords
     * @param bool $removeDuplicatedWords Remove duplicated words
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($keywords, $removeDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $keywords = '';
        if (($activeCategory = $this->getActiveCategory())) {
            $keywordsList = [];

            if ($categoryTree = $this->getCategoryTree()) {
                foreach ($categoryTree->getPath() as $category) {
                    $keywordsList[] = trim($category->oxcategories__oxtitle->value);
                }
            }

            $subCategories = $activeCategory->getSubCats();
            if (is_array($subCategories)) {
                foreach ($subCategories as $subCategory) {
                    $keywordsList[] = $subCategory->oxcategories__oxtitle->value;
                }
            }

            if (count($keywordsList) > 0) {
                $keywords = implode(", ", $keywordsList);
            }
        }

        $keywords = parent::_prepareMetaDescription($keywords, -1, $removeDuplicatedWords);

        return trim($keywords);
    }

    /**
     * Creates a string of keyword filtered by the function prepareMetaDescription and without any duplicates
     * additional the admin defined strings are removed
     *
     * @param string $keywords category path
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "collectMetaKeyword" in next major
     */
    protected function _collectMetaKeyword($keywords) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $maxTextLength = 60;
        $text = '';

        if (count($articleList = $this->getArticleList())) {
            $stringModifier = Str::getStr();
            foreach ($articleList as $article) {
                /** @var Article $article */
                $description = $stringModifier->strip_tags(
                    trim($stringModifier->strtolower($article->getLongDescription()->value))
                );

                //removing dots from string (they are not cleaned up during general string cleanup)
                $description = $stringModifier->preg_replace("/\./", " ", $description);

                if ($stringModifier->strlen($description) > $maxTextLength) {
                    $midText = $stringModifier->substr($description, 0, $maxTextLength);
                    $description = $stringModifier->substr(
                        $midText,
                        0,
                        ($stringModifier->strlen($midText) - $stringModifier->strpos(strrev($midText), ' '))
                    );
                }
                if ($text) {
                    $text .= ', ';
                }
                $text .= $description;
            }
        }

        if (!$keywords) {
            $keywords = $this->_getCatPathString();
        }

        if ($keywords) {
            $text = "{$keywords}, {$text}";
        }

        return parent::_prepareMetaKeyword($text);
    }

    /**
     * Assigns Template name ($this->_sThisTemplate) for article list
     * preview. Name of template can be defined in admin or passed by
     * URL ("tpl" variable).
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getTemplateName()
    {
        // assign template name
        if (($templateName = basename(Registry::getRequest()->getRequestEscapedParameter('tpl')))) {
            $this->_sThisTemplate = 'custom/' . $templateName;
        } elseif (($category = $this->getActiveCategory()) && $category->oxcategories__oxtemplate->value) {
            $this->_sThisTemplate = $category->oxcategories__oxtemplate->value;
        }

        return $this->_sThisTemplate;
    }

    /**
     * Adds page number parameter to current Url and returns formatted url
     *
     * @param string $url Url to append page numbers
     * @param int $page Current page number
     * @param int $languageId Requested language
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "addPageNrParam" in next major
     */
    protected function _addPageNrParam($url, $page, $languageId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (Registry::getUtils()->seoIsActive() && ($category = $this->getActiveCategory())) {
            if ($page) {
                // only if page number > 0
                $url = $category->getBaseSeoLink($languageId, $page);
            }
        } else {
            $url = parent::_addPageNrParam($url, $page, $languageId);
        }

        return $url;
    }

    /**
     * Returns true if we have category
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isActCategory" in next major
     */
    protected function _isActCategory() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_blIsCat;
    }

    /**
     * Generates Url for page navigation
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function generatePageNavigationUrl()
    {
        if ((Registry::getUtils()->seoIsActive() && ($category = $this->getActiveCategory()))) {
            return $category->getLink();
        }

        return parent::generatePageNavigationUrl();
    }

    /**
     * Returns default category sorting for selected category
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getDefaultSorting()
    {
        $sorting = parent::getDefaultSorting();

        $category = $this->getActiveCategory();
        if ($category instanceof Category) {
            if ($defaultSorting = $category->getDefaultSorting()) {
                $articleViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxarticles');
                $sortBy = $articleViewName . '.' . $defaultSorting;
                $sortDirection = ($category->getDefaultSortingMode()) ? "desc" : "asc";
                $sorting = ['sortby' => $sortBy, 'sortdir' => $sortDirection];
            }
        }

        return $sorting;
    }


    /**
     * Returns title suffix used in template
     *
     * @return string|void
     * @throws DatabaseConnectionException
     */
    public function getTitleSuffix()
    {
        if ($this->getActiveCategory()->oxcategories__oxshowsuffix->value) {
            return Registry::getConfig()->getActiveShop()->oxshops__oxtitlesuffix->value;
        }
    }

    /**
     * Returns title page suffix used in template
     *
     * @return string|void
     */
    public function getTitlePageSuffix()
    {
        if (($activePage = $this->getActPage())) {
            return Registry::getLang()->translateString('PAGE') . " " . ($activePage + 1);
        }
    }

    /**
     * Returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $languageId Language id
     *
     * @return object
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSubject" in next major
     */
    protected function _getSubject($languageId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getActiveCategory();
    }

    /**
     * Template variable getter. Returns array of attribute values
     * we do have here in this category
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getAttributes()
    {
        $this->_aAttributes = false;

        if (($category = $this->getActiveCategory())) {
            $attributes = $category->getAttributes();
            if (count($attributes)) {
                $this->_aAttributes = $attributes;
            }
        }

        return $this->_aAttributes;
    }

    /**
     * Template variable getter. Returns category's article list
     *
     * @return ArticleList|null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getArticleList()
    {
        if ($this->_aArticleList === null) {
            if ($category = $this->getActiveCategory()) {
                $articleList = $this->_loadArticles($category);
                if (count($articleList)) {
                    $this->_aArticleList = $articleList;
                }
            }
        }

        return $this->_aArticleList;
    }

    /**
     * Article count getter
     *
     * @return int
     */
    public function getArticleCount()
    {
        return $this->_iAllArtCnt;
    }

    /**
     * Return array of id to form recommend list.
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     */
    public function getSimilarRecommListIds()
    {
        if ($this->_aSimilarRecommListIds === null) {
            $this->_aSimilarRecommListIds = false;

            if ($categoryArticlesList = $this->getArticleList()) {
                $this->_aSimilarRecommListIds = $categoryArticlesList->arrayKeys();
            }
        }

        return $this->_aSimilarRecommListIds;
    }

    /**
     * Template variable getter. Returns category path
     *
     * @return array
     */
    public function getCatTreePath()
    {
        if ($this->_sCatTreePath === null) {
            $this->_sCatTreePath = false;
            // category path
            if ($categoryTree = $this->getCategoryTree()) {
                $this->_sCatTreePath = $categoryTree->getPath();
            }
        }

        return $this->_sCatTreePath;
    }

    /**
     * Template variable getter. Returns category path array
     *
     * @return array|void
     */
    public function getTreePath()
    {
        if ($categoryTree = $this->getCategoryTree()) {
            return $categoryTree->getPath();
        }
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $paths = [];

        if ('oxmore' == Registry::getRequest()->getRequestEscapedParameter('cnid')) {
            $path = [];
            $path['title'] = Registry::getLang()->translateString(
                'CATEGORY_OVERVIEW',
                Registry::getLang()->getBaseLanguage(),
                false
            );
            $path['link'] = $this->getLink();

            $paths[] = $path;

            return $paths;
        }

        if (($categoryTree = $this->getCategoryTree()) && ($categoryPaths = $categoryTree->getPath())) {
            foreach ($categoryPaths as $category) {
                /** @var Category $category */
                $categoryPath = [];

                $categoryPath['link'] = $category->getLink();
                $categoryPath['title'] = $category->oxcategories__oxtitle->value;

                $paths[] = $categoryPath;
            }
        }

        return $paths;
    }

    /**
     * Template variable getter. Returns true if category has active
     * subcategories.
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function hasVisibleSubCats()
    {
        if ($this->_blHasVisibleSubCats === null) {
            $this->_blHasVisibleSubCats = false;
            if ($activeCategory = $this->getActiveCategory()) {
                $this->_blHasVisibleSubCats = $activeCategory->getHasVisibleSubCats();
            }
        }

        return $this->_blHasVisibleSubCats;
    }

    /**
     * Template variable getter. Returns list of subcategories.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getSubCatList()
    {
        if ($this->_aSubCatList === null) {
            $this->_aSubCatList = [];
            if ($activeCategory = $this->getActiveCategory()) {
                $this->_aSubCatList = $activeCategory->getSubCats();
            }
        }

        return $this->_aSubCatList;
    }

    /**
     * Template variable getter. Returns page navigation
     *
     * @return object
     */
    public function getPageNavigation()
    {
        if ($this->_oPageNavigation === null) {
            $this->_oPageNavigation = $this->generatePageNavigation();
        }

        return $this->_oPageNavigation;
    }

    /**
     * Template variable getter. Returns category title.
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getTitle()
    {
        if ($this->_sCatTitle === null) {
            $this->_sCatTitle = false;
            if ($this->getCategoryId() == 'oxmore') {
                $language = Registry::getLang();
                $baseLanguageId = $language->getBaseLanguage();

                $this->_sCatTitle = $language->translateString('CATEGORY_OVERVIEW', $baseLanguageId, false);
            } elseif (($category = $this->getActiveCategory())) {
                $this->_sCatTitle = $category->oxcategories__oxtitle->value;
            }
        }

        return $this->_sCatTitle;
    }

    /**
     * Template variable getter. Returns bargain article list
     *
     * @return array
     */
    public function getBargainArticleList()
    {
        if ($this->_aBargainArticleList === null) {
            $this->_aBargainArticleList = [];
            if (Registry::getConfig()->getConfigParam('bl_perfLoadAktion') && $this->_isActCategory()) {
                $articleList = oxNew(ArticleList::class);
                $articleList->loadActionArticles('OXBARGAIN');
                if ($articleList->count()) {
                    $this->_aBargainArticleList = $articleList;
                }
            }
        }

        return $this->_aBargainArticleList;
    }

    /**
     * Template variable getter. Returns active search
     *
     * @return Category
     * @throws DatabaseConnectionException
     */
    public function getActiveCategory()
    {
        if ($this->_oActCategory === null) {
            $this->_oActCategory = false;
            $category = oxNew(Category::class);
            if ($category->load($this->getCategoryId())) {
                $this->_oActCategory = $category;
            }
        }

        return $this->_oActCategory;
    }

    /**
     * Returns view canonical url
     *
     * @return string|void
     * @throws DatabaseConnectionException
     */
    public function getCanonicalUrl()
    {
        if (($category = $this->getActiveCategory())) {
            $utilsUrl = Registry::getUtilsUrl();
            if (Registry::getUtils()->seoIsActive()) {
                $url = $utilsUrl->prepareCanonicalUrl(
                    $category->getBaseSeoLink($category->getLanguage(), $this->getActPage())
                );
            } else {
                $url = $utilsUrl->prepareCanonicalUrl(
                    $category->getBaseStdLink($category->getLanguage(), $this->getActPage())
                );
            }

            return $url;
        }
    }

    /**
     * Returns config parameters blShowListDisplayType value
     *
     * @return boolean
     */
    public function canSelectDisplayType()
    {
        return Registry::getConfig()->getConfigParam('blShowListDisplayType');
    }

    /**
     * Get list articles pages count
     *
     * @return int
     */
    public function getPageCount()
    {
        return $this->_iCntPages;
    }
}
