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

use Exception;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\DeliveryList;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Model\PaymentList;
use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Application\Model\RssFeed;
use OxidEsales\Eshop\Application\Model\Vendor;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\MailValidator;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article details information page.
 * Collects detailed article information, possible variants, such information
 * as cross-selling, similar-list, picture gallery list, etc.
 * O3-Shop -> (Any chosen product).
 */
class ArticleDetailsController extends FrontendController
{
    /**
     * Current class default template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/details/details.tpl';

    /**
     * Current product parent article object
     *
     * @var Article
     */
    protected $_oParentProd = null;

    /**
     * Parent article name
     *
     * @var string
     */
    protected $_sParentName = null;

    /**
     * Parent article url
     *
     * @var string
     */
    protected $_sParentUrl = null;

    /**
     * Picture gallery
     *
     * @var array
     */
    protected $_aPicGallery = null;

    /**
     * Select lists
     *
     * @var array
     */
    protected $_aSelectLists = null;

    /**
     * Reviews of current article
     *
     * @var array
     */
    protected $_aReviews = null;

    /**
     * CrossSelling article list
     *
     * @var object
     */
    protected $_oCrossSelling = null;

    /**
     * Similar products article list
     *
     * @var object
     */
    protected $_oSimilarProducts = null;

    /**
     * Accessoires of current article
     *
     * @var object
     */
    protected $_oAccessoires = null;

    /**
     * List of customer also bought these products
     *
     * @var object
     */
    protected $_aAlsoBoughtArts = null;

    /**
     * Search title
     *
     * @var string
     */
    protected $_sSearchTitle = null;

    /**
     * Marker if active product was fully initialized before returning it
     * (see details::getProduct())
     *
     * @var bool
     */
    protected $_blIsInitialized = false;

    /**
     * Current view link type
     *
     * @var int
     */
    protected $_iLinkType = null;

    /**
     * Bid price.
     *
     * @var string
     */
    protected $_sBidPrice = null;

    /**
     * Price alarm status.
     *
     * @var integer
     */
    protected $_iPriceAlarmStatus = null;

    /**
     * Search parameter for Html
     *
     * @var string
     */
    protected $_sSearchParamForHtml = null;

    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_aSimilarRecommListIds = null;


    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_blShowSorting = true;

    /**
     * Returns current product parent article object if it is available
     *
     * @param string $parentId parent product id
     *
     * @return Article
     * @deprecated underscore prefix violates PSR12, will be renamed to "getParentProduct" in next major
     */
    protected function _getParentProduct($parentId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($parentId && $this->_oParentProd === null) {
            $this->_oParentProd = false;
            $article = oxNew(Article::class);
            if (($article->load($parentId))) {
                $this->_processProduct($article);
                $this->_oParentProd = $article;
            }
        }

        return $this->_oParentProd;
    }

    /**
     * In case list type is "search" returns search parameters which will be added to product details link
     *
     * @return string|void
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAddDynUrlParams" in next major
     */
    protected function _getAddUrlParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->getListType() == "search") {
            return $this->getDynUrlParams();
        }
    }

    /**
     * Returns array of params => values which are used in hidden forms and as additional url params.
     * NOTICE: this method SHOULD return raw (non encoded into entities) parameters, because values
     * are processed by htmlentities() to avoid security and broken templates problems
     * This exact fix is added for article details to parse variant selection properly for widgets.
     *
     * @return array
     */
    public function getNavigationParams()
    {
        $parameters = parent::getNavigationParams();

        $variantSelectionListId = Registry::getRequest()->getRequestEscapedParameter('varselid');
        $selectListParameters = Registry::getRequest()->getRequestEscapedParameter('sel');
        if (!$variantSelectionListId && !$selectListParameters) {
            return $parameters;
        }

        if ($variantSelectionListId) {
            foreach ($variantSelectionListId as $key => $value) {
                $parameters["varselid[$key]"] = $value;
            }
        }

        if ($selectListParameters) {
            foreach ($selectListParameters as $key => $value) {
                $parameters["sel[$key]"] = $value;
            }
        }

        return $parameters;
    }


    /**
     * Processes product by setting link type and in case list type is search adds search parameters to details link
     *
     * @param Article $article Product to process
     * @deprecated underscore prefix violates PSR12, will be renamed to "processProduct" in next major
     */
    protected function _processProduct($article) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $article->setLinkType($this->getLinkType());
        if ($dynamicParameters = $this->_getAddUrlParams()) {
            $article->appendLink($dynamicParameters);
        }
    }

    /**
     * Generates current view id.
     *
     * @return string
     */
    protected function generateViewId()
    {
        return parent::generateViewId() . '|' . Registry::getRequest()->getRequestEscapedParameter('anid') . '|';
    }

    /**
     * If possible loads additional article info (Article::getCrossSelling(),
     * Article::getAccessoires(), Article::getReviews(), Article::GetSimilarProducts(),
     * Article::GetCustomerAlsoBoughtThisProducts()), forms variants details
     * navigation URLs
     * loads select lists (Article::GetSelectLists()), prepares HTML metadata
     * (details::_convertForMetaTags()). Returns name of template file
     * details::_sThisTemplate
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        $config = Registry::getConfig();

        $article = $this->getProduct();

        // assign template name
        if ($article->oxarticles__oxtemplate->value) {
            $this->_sThisTemplate = $article->oxarticles__oxtemplate->value;
        }

        if (($templateName = Registry::getRequest()->getRequestEscapedParameter('tpl'))) {
            $this->_sThisTemplate = 'custom/' . basename($templateName);
        }

        parent::render();

        $renderPartial = Registry::getRequest()->getRequestEscapedParameter('renderPartial');
        $this->addTplParam('renderPartial', $renderPartial);

        switch ($renderPartial) {
            case "productInfo":
                return 'page/details/ajax/fullproductinfo.tpl';
            case "detailsMain":
                return 'page/details/ajax/productmain.tpl';
            default:
                // can not be removed, as it is used for breadcrumb loading
                $locator = oxNew('oxLocator', $this->getListType());
                $locator->setLocatorData($article, $this);

                // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
                if ($config->getConfigParam('bl_rssRecommLists') && $this->getSimilarRecommListIds()) {
                    $rssFeeds = oxNew(RssFeed::class);
                    $title = $rssFeeds->getRecommListsTitle($article);
                    $url = $rssFeeds->getRecommListsUrl($article);
                    $this->addRssFeed($title, $url, 'recommlists');
                }
                // END deprecated

                return $this->_sThisTemplate;
        }
    }

    /**
     * Returns current view metadata
     * If $meta parameter comes empty, sets to it article title and description.
     * It happens if current view has no metadata defined in oxcontent table
     *
     * @param string $meta           User defined description, description content or empty value
     * @param int    $length         Max length of result, -1 for no truncation
     * @param bool   $removeDuplicatedWords If true - performs additional duplicate cleaning
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaDescription" in next major
     */
    protected function _prepareMetaDescription($meta, $length = 200, $removeDuplicatedWords = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$meta) {
            $article = $this->getProduct();

            if (Registry::getConfig()->getConfigParam('bl_perfParseLongDescinSmarty')) {
                $meta = $article->getLongDesc();
            } else {
                $meta = $article->getLongDescription()->value;
            }
            if ($meta == '') {
                $meta = $article->oxarticles__oxshortdesc->value;
            }
            $meta = $article->oxarticles__oxtitle->value . ' - ' . $meta;
        }

        return parent::_prepareMetaDescription($meta, $length, $removeDuplicatedWords);
    }

    /**
     * Returns current view keywords seperated by comma
     * If $keywords parameter comes empty, sets to it article title and description.
     * It happens if current view has no metadata defined in oxcontent table
     *
     * @param string $keywords              User defined keywords, keywords content or empty value
     * @param bool   $removeDuplicatedWords Remove duplicated words
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($keywords, $removeDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$keywords) {
            $article = $this->getProduct();
            $keywords = trim($this->getTitle());

            if ($categoryTree = $this->getCategoryTree()) {
                foreach ($categoryTree->getPath() as $category) {
                    $keywords .= ", " . trim($category->oxcategories__oxtitle->value);
                }
            }

            // Adding search keys info
            if ($searchKeys = trim($article->oxarticles__oxsearchkeys->value)) {
                $keywords .= ", " . $searchKeys;
            }

            $keywords = parent::_prepareMetaKeyword($keywords, $removeDuplicatedWords);
        }

        return $keywords;
    }

    /**
     * Saves user ratings and review text (oxReview object)
     *
     * @return void
     * @throws Exception
     */
    public function saveReview()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        if (
            $this->canAcceptFormData() &&
            ($user = $this->getUser()) && ($article = $this->getProduct())
        ) {
            $articleRating = Registry::getRequest()->getRequestEscapedParameter('artrating');
            if ($articleRating !== null) {
                $articleRating = (int) $articleRating;
            }

            //save rating
            if ($articleRating !== null && $articleRating >= 1 && $articleRating <= 5) {
                $rating = oxNew(Rating::class);
                if ($rating->allowRating($user->getId(), 'oxarticle', $article->getId())) {
                    $rating->oxratings__oxuserid = new Field($user->getId());
                    $rating->oxratings__oxtype = new Field('oxarticle');
                    $rating->oxratings__oxobjectid = new Field($article->getId());
                    $rating->oxratings__oxrating = new Field($articleRating);
                    $rating->save();
                    $article->addToRatingAverage($articleRating);
                }
            }

            if (($reviewText = trim((string) Registry::getRequest()->getRequestEscapedParameter('rvw_txt', true)))) {
                $review = oxNew(Review::class);
                $review->oxreviews__oxobjectid = new Field($article->getId());
                $review->oxreviews__oxtype = new Field('oxarticle');
                $review->oxreviews__oxtext = new Field($reviewText, Field::T_RAW);
                $review->oxreviews__oxlang = new Field(Registry::getLang()->getBaseLanguage());
                $review->oxreviews__oxuserid = new Field($user->getId());
                $review->oxreviews__oxrating = new Field(($articleRating !== null) ? $articleRating : 0);
                $review->save();
            }
        }
    }

    /**
     * Adds article to selected recommendation list
     *
     * @return void
     * @throws Exception
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     */
    public function addToRecomm()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        if (!$this->getViewConfig()->getShowListmania()) {
            return;
        }

        $recommendationText = trim((string) Registry::getRequest()->getRequestEscapedParameter('recomm_txt'));
        $recommendationListId = Registry::getRequest()->getRequestEscapedParameter('recomm');
        $articleId = $this->getProduct()->getId();

        if ($articleId) {
            $recommendationList = oxNew(RecommendationList::class);
            $recommendationList->load($recommendationListId);
            $recommendationList->addArticle($articleId, $recommendationText);
        }
    }

    /**
     * Returns active product id to load its seo meta info
     *
     * @return string|void
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoObjectId" in next major
     */
    protected function _getSeoObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($article = $this->getProduct()) {
            return $article->getId();
        }
    }

    /**
     * Returns current product
     *
     * @return Article
     */
    public function getProduct()
    {
        $config = Registry::getConfig();
        $utils = Registry::getUtils();

        if ($this->_oProduct === null) {
            // this option is only for lists. We must reset value
            // as blLoadVariants = false affect "ab price" functionality
            $config->setConfigParam('blLoadVariants', true);

            $articleId = Registry::getRequest()->getRequestEscapedParameter('anid');

            // object is not yet loaded
            $this->_oProduct = oxNew(Article::class);

            if (!$this->_oProduct->load($articleId)) {
                $utils->redirect($config->getShopHomeUrl());
                $utils->showMessageAndExit('');
            }

            $variantSelectionId = Registry::getRequest()->getRequestEscapedParameter('varselid');
            $variantSelections = $this->_oProduct->getVariantSelections($variantSelectionId);
            if ($variantSelections && $variantSelections['oActiveVariant'] && $variantSelections['blPerfectFit']) {
                $this->_oProduct = $variantSelections['oActiveVariant'];
            }
        }

        // additional checks
        if (!$this->_blIsInitialized) {
            $this->_additionalChecksForArticle();
        }

        return $this->_oProduct;
    }

    /**
     * Runs additional checks for article.
     * @deprecated underscore prefix violates PSR12, will be renamed to "additionalChecksForArticle" in next major
     */
    protected function _additionalChecksForArticle() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $config = Registry::getConfig();
        $utils = Registry::getUtils();

        $shouldContinue = true;
        if (!$this->_oProduct->isVisible()) {
            $shouldContinue = false;
        } elseif ($this->_oProduct->oxarticles__oxparentid->value) {
            $parentArticle = $this->_getParentProduct($this->_oProduct->oxarticles__oxparentid->value);
            if (!$parentArticle || !$parentArticle->isVisible()) {
                $shouldContinue = false;
            }
        }

        if (!$shouldContinue) {
            $utils->redirect($config->getShopHomeUrl());
            $utils->showMessageAndExit('');
        }

        $this->_processProduct($this->_oProduct);
        $this->_blIsInitialized = true;
    }

    /**
     * Returns current view link type
     *
     * @return int
     */
    public function getLinkType()
    {
        if ($this->_iLinkType === null) {
            $listType = Registry::getRequest()->getRequestEscapedParameter('listtype');
            if ('vendor' == $listType) {
                $this->_iLinkType = OXARTICLE_LINKTYPE_VENDOR;
            } elseif ('manufacturer' == $listType) {
                $this->_iLinkType = OXARTICLE_LINKTYPE_MANUFACTURER;
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            } elseif ('recommlist' == $listType) {
                $this->_iLinkType = OXARTICLE_LINKTYPE_RECOMM;
            // END deprecated
            } else {
                $this->_iLinkType = OXARTICLE_LINKTYPE_CATEGORY;

                // price category has own type ...
                $activeCategory = $this->getActiveCategory();
                if ($activeCategory && $activeCategory->isPriceCategory()) {
                    $this->_iLinkType = OXARTICLE_LINKTYPE_PRICECATEGORY;
                }
            }
        }

        return $this->_iLinkType;
    }

    /**
     * Template variable getter. Returns if draw parent url
     *
     * @return bool
     */
    public function drawParentUrl()
    {
        return $this->getProduct()->isVariant();
    }

    /**
     * Template variable getter. Returns picture gallery of current article
     *
     * @return array
     */
    public function getPictureGallery()
    {
        if ($this->_aPicGallery === null) {
            //get picture gallery
            $this->_aPicGallery = $this->getPicturesProduct()->getPictureGallery();
        }

        return $this->_aPicGallery;
    }

    /**
     * Template variable getter. Returns id of active picture
     *
     * @return string
     */
    public function getActPictureId()
    {
        return $this->getPictureGallery()['ActPicID'];
    }

    /**
     * Template variable getter. Returns active picture
     *
     * @return object
     */
    public function getActPicture()
    {
        return $this->getPictureGallery()['ActPic'];
    }

    /**
     * Template variable getter. Returns pictures of current article
     *
     * @return array
     */
    public function getPictures()
    {
        return $this->getPictureGallery()['Pics'];
    }

    /**
     * Template variable getter. Returns selected picture
     *
     * @param string $pictureNumber
     *
     * @return string
     */
    public function getArtPic($pictureNumber)
    {
        return $this->getPictureGallery()['Pics'][$pictureNumber];
    }

    /**
     * Template variable getter. Returns selectLists of current article
     *
     * @return array
     */
    public function getSelectLists()
    {
        if ($this->_aSelectLists === null) {
            $this->_aSelectLists = false;
            if (Registry::getConfig()->getConfigParam('bl_perfLoadSelectLists')) {
                $this->_aSelectLists = $this->getProduct()->getSelectLists();
            }
        }

        return $this->_aSelectLists;
    }

    /**
     * Template variable getter. Returns reviews of current article
     *
     * @return array
     */
    public function getReviews()
    {
        if ($this->_aReviews === null) {
            $this->_aReviews = false;
            if (Registry::getConfig()->getConfigParam('bl_perfLoadReviews')) {
                $this->_aReviews = $this->getProduct()->getReviews();
            }
        }

        return $this->_aReviews;
    }

    /**
     * Template variable getter. Returns cross selling
     *
     * @return object
     */
    public function getCrossSelling()
    {
        if ($this->_oCrossSelling === null) {
            $this->_oCrossSelling = false;
            if ($article = $this->getProduct()) {
                $this->_oCrossSelling = $article->getCrossSelling();
            }
        }

        return $this->_oCrossSelling;
    }

    /**
     * Template variable getter. Returns similar article list
     *
     * @return object
     */
    public function getSimilarProducts()
    {
        if ($this->_oSimilarProducts === null) {
            $this->_oSimilarProducts = false;
            if ($article = $this->getProduct()) {
                $this->_oSimilarProducts = $article->getSimilarProducts();
            }
        }

        return $this->_oSimilarProducts;
    }

    /**
     * Return array of id to form recommend list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    public function getSimilarRecommListIds()
    {
        if ($this->_aSimilarRecommListIds === null) {
            $this->_aSimilarRecommListIds = false;

            if ($article = $this->getProduct()) {
                $this->_aSimilarRecommListIds = [$article->getId()];
            }
        }

        return $this->_aSimilarRecommListIds;
    }

    /**
     * Template variable getter. Returns accessoires of article
     *
     * @return object
     */
    public function getAccessoires()
    {
        if ($this->_oAccessoires === null) {
            $this->_oAccessoires = false;
            if ($article = $this->getProduct()) {
                $this->_oAccessoires = $article->getAccessoires();
            }
        }

        return $this->_oAccessoires;
    }

    /**
     * Template variable getter. Returns list of customer also bought these products
     *
     * @return object|null
     */
    public function getAlsoBoughtTheseProducts()
    {
        if ($this->_aAlsoBoughtArts === null) {
            $this->_aAlsoBoughtArts = false;
            if ($article = $this->getProduct()) {
                $this->_aAlsoBoughtArts = $article->getCustomerAlsoBoughtThisProducts();
            }
        }

        return $this->_aAlsoBoughtArts;
    }

    /**
     * Template variable getter. Returns if price alarm is enabled
     *
     * @return bool
     */
    public function isPriceAlarm()
    {
        return $this->getProduct()->isPriceAlarm();
    }

    /**
     * returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $languageId language id
     *
     * @return Article
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSubject" in next major
     */
    protected function _getSubject($languageId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getProduct();
    }

    /**
     * Returns search title. It will be set in oxLocator
     *
     * @return string
     */
    public function getSearchTitle()
    {
        return $this->_sSearchTitle;
    }

    /**
     * Returns search title setter
     *
     * @param string $title search title
     */
    public function setSearchTitle($title)
    {
        $this->_sSearchTitle = $title;
    }

    /**
     * Active category path setter.
     *
     * @param string $activeCategoryPath Category tree path
     */
    public function setCatTreePath($activeCategoryPath)
    {
        $this->_sCatTreePath = $activeCategoryPath;
    }

    /**
     * If product details are accessed by vendor url
     * view must not be indexable
     *
     * @return int
     */
    public function noIndex()
    {
        $listType = Registry::getRequest()->getRequestEscapedParameter('listtype');
        if ($listType && ('vendor' == $listType || 'manufacturer' == $listType)) {
            return $this->_iViewIndexState = VIEW_INDEXSTATE_NOINDEXFOLLOW;
        }

        return parent::noIndex();
    }

    /**
     * Returns current view title. Default is null
     *
     * @return string|void
     */
    public function getTitle()
    {
        if ($article = $this->getProduct()) {
            $articleTitle = $article->oxarticles__oxtitle->value;
            $variantSelectionId = $article->oxarticles__oxvarselect->value;

            $variantSelectionValue = $variantSelectionId ? ' ' . $variantSelectionId : '';

            return $articleTitle . $variantSelectionValue;
        }
    }

    /**
     * Returns view canonical url
     *
     * @return string|void
     */
    public function getCanonicalUrl()
    {
        if (($article = $this->getProduct())) {
            if ($article->oxarticles__oxparentid->value) {
                $article = $this->_getParentProduct($article->oxarticles__oxparentid->value);
            }

            $utilsUrl = Registry::getUtilsUrl();
            if (Registry::getUtils()->seoIsActive()) {
                $url = $utilsUrl->prepareCanonicalUrl($article->getBaseSeoLink($article->getLanguage(), true));
            } else {
                $url = $utilsUrl->prepareCanonicalUrl($article->getBaseStdLink($article->getLanguage()));
            }

            return $url;
        }
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        if ('search' == $this->getListType()) {
            $paths = $this->_getSearchBreadCrumb();
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        } elseif ('recommlist' == $this->getListType()) {
            $paths = $this->_getRecommendationListBredCrumb();
        // END deprecated
        } elseif ('vendor' == $this->getListType()) {
            $paths = $this->_getVendorBreadCrumb();
        } else {
            $paths = $this->_getCategoryBreadCrumb();
        }

        return $paths;
    }

    /**
     * Validates email address.
     * If email address is OK - creates price alarm object and saves it (oxPriceAlarm::save()).
     * If email is wrong - returns false.
     * Sends price alarm notification mail to shop owner.
     *
     * @return void
     * @throws Exception
     */
    public function addMe()
    {
        $config = Registry::getConfig();
        $utils = Registry::getUtils();

        $parameters = Registry::getRequest()->getRequestEscapedParameter('pa');

        if (!isset($parameters['email']) || !oxNew(MailValidator::class)->isValidEmail($parameters['email'])) {
            $this->_iPriceAlarmStatus = 0;
            return;
        }

        $parameters['aid'] = $this->getProduct()->getId();
        $activeCurrency = $config->getActShopCurrencyObject();
        // convert currency to default
        $price = $utils->currency2Float($parameters['price']);

        $priceAlarm = oxNew(PriceAlarm::class);
        $priceAlarm->oxpricealarm__oxuserid = new Field(Registry::getSession()->getVariable('usr'));
        $priceAlarm->oxpricealarm__oxemail = new Field($parameters['email']);
        $priceAlarm->oxpricealarm__oxartid = new Field($parameters['aid']);
        $priceAlarm->oxpricealarm__oxprice = new Field($utils->fRound($price, $activeCurrency));
        $priceAlarm->oxpricealarm__oxshopid = new Field($config->getShopId());
        $priceAlarm->oxpricealarm__oxcurrency = new Field($activeCurrency->name);

        $priceAlarm->oxpricealarm__oxlang = new Field(Registry::getLang()->getBaseLanguage());

        $priceAlarm->save();

        // Send Email
        $email = oxNew(Email::class);
        $this->_iPriceAlarmStatus = (int) $email->sendPricealarmNotification($parameters, $priceAlarm);
    }

    /**
     * Return price alarm status (if it was sent)
     *
     * @return integer
     */
    public function getPriceAlarmStatus()
    {
        return $this->_iPriceAlarmStatus;
    }

    /**
     * Template variable getter. Returns bid price
     *
     * @return string
     */
    public function getBidPrice()
    {
        if ($this->_sBidPrice === null) {
            $this->_sBidPrice = false;

            $parameters = Registry::getRequest()->getRequestEscapedParameter('pa');
            $activeCurrency = Registry::getConfig()->getActShopCurrencyObject();
            $price = Registry::getUtils()->currency2Float($parameters['price']);
            $this->_sBidPrice = Registry::getLang()->formatCurrency($price, $activeCurrency);
        }

        return $this->_sBidPrice;
    }

    /**
     * Returns variant selection
     *
     * @return array
     */
    public function getVariantSelections()
    {
        $article = $this->getProduct();
        $variantSelectionListId = Registry::getRequest()->getRequestEscapedParameter('varselid');
        if (($articleParent = $this->_getParentProduct($article->oxarticles__oxparentid->value))) {
            return $articleParent->getVariantSelections($variantSelectionListId, $article->getId());
        }

        return $article->getVariantSelections($variantSelectionListId);
    }

    /**
     * Returns pictures product object
     *
     * @return Article
     */
    public function getPicturesProduct()
    {
        $variantSelections = $this->getVariantSelections();
        if ($variantSelections && $variantSelections['oActiveVariant'] && !$variantSelections['blPerfectFit']) {
            return $variantSelections['oActiveVariant'];
        }

        return $this->getProduct();
    }

    /**
     * Template variable getter. Returns search parameter for Html
     *
     * @return string
     */
    public function getSearchParamForHtml()
    {
        if ($this->_sSearchParamForHtml === null) {
            $this->_sSearchParamForHtml = Registry::getRequest()->getRequestEscapedParameter('searchparam');
        }

        return $this->_sSearchParamForHtml;
    }

    /**
     * Returns if page has rdfa
     *
     * @return bool
     */
    public function showRdfa()
    {
        return Registry::getConfig()->getConfigParam('blRDFaEmbedding');
    }

    /**
     * Sets normalized rating
     *
     * @return bool|array
     */
    public function getRDFaNormalizedRating()
    {
        $config = Registry::getConfig();
        $minRating = $config->getConfigParam("iRDFaMinRating");
        $maxRating = $config->getConfigParam("iRDFaMaxRating");

        $article = $this->getProduct();
        $count = $article->oxarticles__oxratingcnt->value;
        if (isset($minRating) && isset($maxRating) && $maxRating != '' && $minRating != '' && $count > 0) {
            $normalizedRating = [];
            $value = ((4 * ($article->oxarticles__oxrating->value - $minRating) / ($maxRating - $minRating))) + 1;
            $normalizedRating["count"] = $count;
            $normalizedRating["value"] = round($value, 2);

            return $normalizedRating;
        }

        return false;
    }

    /**
     * Sets and returns validity period of given object
     *
     * @param string $configVariableName object name
     *
     * @return array|bool
     */
    public function getRDFaValidityPeriod($configVariableName)
    {
        if ($configVariableName) {
            $validity = [];
            $days = Registry::getConfig()->getConfigParam($configVariableName);
            $from = Registry::getUtilsDate()->getTime();

            $through = $from + ($days * 24 * 60 * 60);
            $validity["from"] = date('Y-m-d\TH:i:s', $from) . "Z";
            $validity["through"] = date('Y-m-d\TH:i:s', $through) . "Z";

            return $validity;
        }

        return false;
    }

    /**
     * Gets business function of the gr:Offering
     *
     * @return string
     */
    public function getRDFaBusinessFnc()
    {
        return Registry::getConfig()->getConfigParam("sRDFaBusinessFnc");
    }

    /**
     * Gets the types of customers for which the given gr:Offering is valid
     *
     * @return array
     */
    public function getRDFaCustomers()
    {
        return Registry::getConfig()->getConfigParam("aRDFaCustomers");
    }

    /**
     * Gets information whether prices include vat
     *
     * @return int
     */
    public function getRDFaVAT()
    {
        return Registry::getConfig()->getConfigParam("iRDFaVAT");
    }

    /**
     * Gets a generic description of product condition
     *
     * @return string
     */
    public function getRDFaGenericCondition()
    {
        return Registry::getConfig()->getConfigParam("iRDFaCondition");
    }

    /**
     * Returns bundle product
     *
     * @return Article|false
     */
    public function getBundleArticle()
    {
        $article = $this->getProduct();
        if ($article && $article->oxarticles__oxbundleid->value) {
            $bundle = oxNew(Article::class);
            $bundle->load($article->oxarticles__oxbundleid->value);

            return $bundle;
        }

        return false;
    }

    /**
     * Gets accepted payment methods
     *
     * @return PaymentList
     */
    public function getRDFaPaymentMethods()
    {
        $price = $this->getProduct()->getPrice()->getBruttoPrice();
        $paymentList = oxNew(PaymentList::class);
        $paymentList->loadRDFaPaymentList($price);

        return $paymentList;
    }

    /**
     * Returns delivery methods with assigned delivery sets.
     *
     * @return DeliverySetList
     */
    public function getRDFaDeliverySetMethods()
    {
        $deliverySetList = oxNew(DeliverySetList::class);
        $deliverySetList->loadRDFaDeliverySetList();

        return $deliverySetList;
    }

    /**
     * Template variable getter. Returns delivery list for current product
     *
     * @return DeliveryList
     */
    public function getProductsDeliveryList()
    {
        $article = $this->getProduct();
        $deliveryList = oxNew(DeliveryList::class);
        $deliveryList->loadDeliveryListForProduct($article);

        return $deliveryList;
    }

    /**
     * Gets content id of delivery information page
     *
     * @return string
     */
    public function getRDFaDeliveryChargeSpecLoc()
    {
        return Registry::getConfig()->getConfigParam("sRDFaDeliveryChargeSpecLoc");
    }

    /**
     * Gets content id of payments
     *
     * @return string
     */
    public function getRDFaPaymentChargeSpecLoc()
    {
        return Registry::getConfig()->getConfigParam("sRDFaPaymentChargeSpecLoc");
    }

    /**
     * Gets content id of company info page (About Us)
     *
     * @return string
     */
    public function getRDFaBusinessEntityLoc()
    {
        return Registry::getConfig()->getConfigParam("sRDFaBusinessEntityLoc");
    }

    /**
     * Returns if to show products left stock
     *
     * @return string
     */
    public function showRDFaProductStock()
    {
        return Registry::getConfig()->getConfigParam("blShowRDFaProductStock");
    }

    /**
     * Template variable getter. Returns if to show zoom pictures
     *
     * @return bool
     */
    public function showZoomPics()
    {
        return $this->getPictureGallery()['ZoomPic'];
    }

    /**
     * Template variable getter. Returns if review module is on
     *
     * @return bool
     */
    public function isReviewActive()
    {
        return Registry::getConfig()->getConfigParam('bl_perfLoadReviews');
    }

    /**
     * Returns default category sorting for selected category
     *
     * @return array
     */
    public function getDefaultSorting()
    {
        $sorting = parent::getDefaultSorting();
        $activeCategory = $this->getActiveCategory();

        if ($this->getListType() != 'search' && $activeCategory && $activeCategory instanceof Category) {
            if ($categorySorting = $activeCategory->getDefaultSorting()) {
                $sortingDirection = ($activeCategory->getDefaultSortingMode()) ? "desc" : "asc";
                $sorting = ['sortby' => $categorySorting, 'sortdir' => $sortingDirection];
            }
        }

        return $sorting;
    }

    /**
     * Returns sorting parameters separated by "|"
     *
     * @return string
     */
    public function getSortingParameters()
    {
        $sorting = $this->getSorting($this->getSortIdent());
        if (!is_array($sorting)) {
            return null;
        }

        return implode('|', $sorting);
    }

    /**
     * Vendor bread crumb
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVendorBreadCrumb" in next major
     */
    protected function _getVendorBreadCrumb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $paths = [];
        $vendorPath = [];

        $vendor = oxNew(Vendor::class);
        $vendor->load('root');

        $vendorPath['link'] = $vendor->getLink();
        $vendorPath['title'] = $vendor->oxvendor__oxtitle->value;
        $paths[] = $vendorPath;

        $vendor = $this->getActVendor();
        if ($vendor instanceof Vendor) {
            $vendorPath['link'] = $vendor->getLink();
            $vendorPath['title'] = $vendor->oxvendor__oxtitle->value;
            $paths[] = $vendorPath;
        }

        return $paths;
    }

    /**
     * Recommendation list bread crumb
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    protected function _getRecommendationListBredCrumb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $paths = [];
        $recommListPath = [];
        $baseLanguageId = Registry::getLang()->getBaseLanguage();
        $recommListPath['title'] = Registry::getLang()->translateString('LISTMANIA', $baseLanguageId, false);
        $paths[] = $recommListPath;

        return $paths;
    }

    /**
     * Search bread crumb
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSearchBreadCrumb" in next major
     */
    protected function _getSearchBreadCrumb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $paths = [];
        $searchPath = [];

        $baseLanguageId = Registry::getLang()->getBaseLanguage();
        $translatedString = Registry::getLang()->translateString('SEARCH_RESULT', $baseLanguageId, false);
        $selfLink = $this->getViewConfig()->getSelfLink();
        $sessionToken = Registry::getSession()->getVariable('sess_stoken');

        $searchPath['title'] = sprintf($translatedString, $this->getSearchParamForHtml());
        $searchPath['link'] = $selfLink . 'stoken=' . $sessionToken . "&amp;cl=search&amp;" .
                            "searchparam=" . $this->getSearchParamForHtml();

        $paths[] = $searchPath;

        return $paths;
    }

    /**
     * Category bread crumb
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategoryBreadCrumb" in next major
     */
    protected function _getCategoryBreadCrumb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $paths = [];

        $categoryTree = $this->getCatTreePath();

        if ($categoryTree) {
            foreach ($categoryTree as $category) {
                /** @var Category $category */
                $categoryPath = [];

                $categoryPath['link'] = $category->getLink();
                $categoryPath['title'] = $category->oxcategories__oxtitle->value;

                $paths[] = $categoryPath;
            }
        }

        return $paths;
    }
}
