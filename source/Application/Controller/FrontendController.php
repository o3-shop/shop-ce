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

use OxidEsales\Eshop\Application\Controller\CompareController;
use OxidEsales\Eshop\Application\Model\ActionList;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\CategoryList;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Application\Model\ManufacturerList;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Vendor;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\UserReviewAndRatingBridgeInterface;
use OxidEsales\EshopCommunity\Core\SortingValidator;
use stdClass;

// view indexing state for search engines:
define('VIEW_INDEXSTATE_INDEX', 0); //  index without limitations
define('VIEW_INDEXSTATE_NOINDEXNOFOLLOW', 1); //  no index / no follow
define('VIEW_INDEXSTATE_NOINDEXFOLLOW', 2); //  no index / follow

/**
 * Base view class.
 * Class is responsible for managing of components that must be
 * loaded and executed before any regular operation.
 */
class FrontendController extends BaseController
{
    /**
     * Characters which should be removed while preparing meta keywords
     *
     * @var string
     */
    protected $_sRemoveMetaChars = '.\+*?[^]$(){}=!<>|:&';

    /**
     * Array of component objects.
     *
     * @var array of object
     */
    protected $_oaComponents = [];

    /**
     * Flag if current view is an order view
     *
     * @var bool
     */
    protected $_blIsOrderStep = false;

    /**
     * List type
     *
     * @var string
     */
    protected $_sListType = null;

    /**
     * Possible list display types
     *
     * @var array
     */
    protected $_aListDisplayTypes = ['grid', 'line', 'infogrid'];

    /**
     * List display type
     *
     * @var string
     */
    protected $_sListDisplayType = null;

    /**
     * List display type
     *
     * @var string
     */
    protected $_sCustomListDisplayType = null;

    /**
     * Active articles category object.
     *
     * @var Category
     */
    protected $_oActCategory = null;

    /**
     * Active Manufacturer object.
     *
     * @var Manufacturer
     */
    protected $_oActManufacturer = null;

    /**
     * Active vendor object.
     *
     * @var Vendor
     */
    protected $_oActVendor = null;

    /**
     * Active recommendation's list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var RecommendationList
     */
    protected $_oActiveRecommList = null;

    /**
     * Active search object - stdClass object which keeps navigation info
     *
     * @var stdClass
     */
    protected $_oActSearch = null;

    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_blShowSorting = false;

    /**
     * Load currency option
     *
     * @var bool
     */
    protected $_blLoadCurrency = null;

    /**
     * Load Manufacturers option
     *
     * @var bool
     */
    protected $_blLoadManufacturerTree = null;

    /**
     * Don't show empty cats
     *
     * @var bool
     */
    protected $_blDontShowEmptyCats = null;

    /**
     * Load language option
     *
     * @var bool
     */
    protected $_blLoadLanguage = null;

    /**
     * Item count in category top navigation
     *
     * @var integer
     */
    protected $_iTopCatNavItmCnt = null;

    /**
     * Rss links
     *
     * @var array
     */
    protected $_aRssLinks = null;

    /**
     * List's "order by"
     *
     * @var string
     */
    protected $_sListOrderBy = null;

    /**
     * Order direction of list
     *
     * @var string
     */
    protected $_sListOrderDir = null;

    /**
     * Meta description
     *
     * @var string
     */
    protected $_sMetaDescription = null;

    /**
     * Meta keywords
     *
     * @var string
     */
    protected $_sMetaKeywords = null;

    /**
     * Start page meta description CMS ident
     *
     * @var string
     */
    protected $_sMetaDescriptionIdent = null;

    /**
     * Start page meta keywords CMS ident
     *
     * @var string
     */
    protected $_sMetaKeywordsIdent = null;

    /**
     * Additional params for url.
     *
     * @var string
     */
    protected $_sAdditionalParams = null;

    /**
     * Active currency object.
     *
     * @var object
     */
    protected $_oActCurrency = null;

    /**
     * Private sales on/off state
     *
     * @var bool
     */
    protected $_blEnabledPrivateSales = null;

    /**
     * Sign if any new component is added. On this case will be
     * executed components stored in oxBaseView::_aComponentNames
     * plus oxBaseView::_aComponentNames.
     *
     * @var bool
     */
    protected $_blCommonAdded = false;

    /**
     * Current view search engine indexing state:
     *     VIEW_INDEXSTATE_INDEX - index without limitations
     *     VIEW_INDEXSTATE_NOINDEXNOFOLLOW - no index / no follow
     *     VIEW_INDEXSTATE_NOINDEXFOLLOW - no index / follow
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_INDEX;

    /**
     * If true, forces FrontendController::noIndex returns VIEW_INDEXSTATE_NOINDEXFOLLOW
     * (FrontendController::$_iViewIndexState = VIEW_INDEXSTATE_NOINDEXFOLLOW; index / follow)
     *
     * @var bool
     */
    protected $_blForceNoIndex = false;

    /**
     * Number of products in compare list.
     *
     * @var integer
     */
    protected $_iCompItemsCnt = null;

    /**
     * Default content id
     *
     * @return string
     */
    protected $_sContentId = null;

    /** @return Content Default content. */
    protected $_oContent = null;

    /** @var string View id. */
    protected $_sViewResetID = null;

    /** @var array Menu list. */
    protected $_aMenueList = null;

    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     *
     * @var array
     */
    protected $_aComponentNames = [
        'oxcmp_user'       => 1, // 0 means don't init if cached
        'oxcmp_lang'       => 0,
        'oxcmp_cur'        => 1,
        'oxcmp_shop'       => 1,
        'oxcmp_categories' => 0,
        'oxcmp_utils'      => 1,
        // @deprecated 6.5.6 "News" feature will be removed completely
        'oxcmp_news' => 0,
        // END deprecated
        'oxcmp_basket'     => 1
    ];

    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation. User may modify this himself.
     *
     * @var array
     */
    protected $_aUserComponentNames = [];

    /** @var Article Current view product object. */
    protected $_oProduct = null;

    /** @var int Number of current list page. */
    protected $_iActPage = null;

    /** @var array A list of articles. */
    protected $_aArticleList = null;

    /** @var ManufacturerList Manufacturer list object. */
    protected $_oManufacturerTree = null;

    /** @var CategoryList Category tree object. */
    protected $_oCategoryTree = null;

    /** @var array Top 5 article list. */
    protected $_aTop5ArticleList = null;

    /** @var array Bargain article list. */
    protected $_aBargainArticleList = null;

    /** @var integer If order price to low. */
    protected $_blLowOrderPrice = null;

    /** @var string Min order price. */
    protected $_sMinOrderPrice = null;

    /** @var int Real newsletter status. */
    protected $_iNewsRealStatus = null;

    /** @return array Url parameters which block redirection. */
    protected $_aBlockRedirectParams = ['fnc', 'stoken', 'force_sid', 'force_admin_sid'];

    /** @var Vendor Root vendor object. */
    protected $_oRootVendor = null;

    /** @var string Vendor id. */
    protected $_sVendorId = null;

    /** @var array Manufacturer list for search. */
    protected $_aManufacturerlist = null;

    /** @var Manufacturer Root manufacturer object. */
    protected $_oRootManufacturer = null;

    /** @var string Manufacturer id. */
    protected $_sManufacturerId = null;

    /** @var bool Has user newsletter subscribed. */
    protected $_blNewsSubscribed = null;

    /** @var Address Delivery address. */
    protected $_oDelAddress = null;

    /** @var string Category tree path. */
    protected $_sCatTreePath = null;

    /** @var array Loaded contents array (cache). */
    protected $_aContents = [];

    /** @var bool Sign if to load and show top5articles action. */
    protected $_blTop5Action = false;

    /** @var bool Sign if to load and show bargain action. */
    protected $_blBargainAction = false;

    /** @var array check all "must-be-fields" if they are completely. */
    protected $_aMustFillFields = null;

    /** @var bool If active root category was changed. */
    protected $_blRootCatChanged = false;

    /** @var array User address. */
    protected $_aInvoiceAddress = null;

    /** @var array User delivery address. */
    protected $_aDeliveryAddress = null;

    /** @var string Logged in user name. */
    protected $_sActiveUsername = null;

    /** @var boolean is VAT included in prices */
    protected $_blIsVatIncluded = null;

    /** @var array Components which needs to be initialized/rendered (depending on cache and its cache status). */
    protected static $_aCollectedComponentNames = null;

    /** @var array If active load components. By default, active. */
    protected $_blLoadComponents = true;

    /** @var array Sorting columns list. */
    protected $_aSortColumns = null;

    /** @var StdClass Page navigation. */
    protected $_oPageNavigation = null;

    /** @var integer Number of possible pages. */
    protected $_iCntPages = null;

    /** @var string Form id. */
    protected $_sFormId = null;

    /** @var bool Whether session form id matches with request form id. */
    protected $_blCanAcceptFormData = null;

    /**
     * Return true, if the review manager should be shown.
     *
     * @return bool
     */
    public function isUserAllowedToManageOwnReviews()
    {
        return (bool) Registry::getConfig()->getConfigParam('blAllowUsersToManageTheirReviews');
    }

    /**
     * Get the total number of reviews for the active user.
     *
     * @return integer Number of reviews
     */
    public function getReviewAndRatingItemsCount()
    {
        $user = $this->getUser();
        $count = 0;
        if ($user) {
            $count = $this
                ->getContainer()
                ->get(UserReviewAndRatingBridgeInterface::class)
                ->getReviewAndRatingListCount($user->getId());
        }

        return $count;
    }

    /**
     * Returns component names.
     *
     * At the moment it is not possible to override $_aCollectedComponentNames in oxUBase,
     * so aUserComponentNames was added to config.inc.php file.
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getComponentNames" in next major
     */
    protected function _getComponentNames() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (self::$_aCollectedComponentNames === null) {
            self::$_aCollectedComponentNames = array_merge($this->_aComponentNames, $this->_aUserComponentNames);

            if (($userComponentNames = Registry::getConfig()->getConfigParam('aUserComponentNames'))) {
                self::$_aCollectedComponentNames = array_merge(self::$_aCollectedComponentNames, $userComponentNames);
            }

            if (Registry::getRequest()->getRequestEscapedParameter('_force_no_basket_cmp')) {
                unset(self::$_aCollectedComponentNames['oxcmp_basket']);
            }
        }

        // resetting array pointer
        reset(self::$_aCollectedComponentNames);

        return self::$_aCollectedComponentNames;
    }

    /**
     * In non admin mode checks if request was NOT processed by seo handler.
     * If NOT, then tries to load alternative SEO url and if url is available -
     * redirects to it. If no alternative path was found - 404 header is emitted
     * and page is rendered
     * @deprecated underscore prefix violates PSR12, will be renamed to "processRequest" in next major
     */
    protected function _processRequest() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $utils = Registry::getUtils();

        // non admin, request is not empty and was not processed by seo engine
        if (!isSearchEngineUrl() && $utils->seoIsActive() && ($requestUrl = getRequestUrl())) {
            // fetching standard url and looking for it in seo table
            if ($this->_canRedirect() && ($redirectUrl = Registry::getSeoEncoder()->fetchSeoUrl($requestUrl))) {
                $utils->redirect(Registry::getConfig()->getCurrentShopUrl() . $redirectUrl, false, 301);
            } elseif (VIEW_INDEXSTATE_INDEX == $this->noIndex()) {
                // forcing to set no index/follow meta
                $this->_forceNoIndex();

                if (Registry::getConfig()->getConfigParam('blSeoLogging')) {
                    $shopId = Registry::getConfig()->getShopId();
                    $languageId = Registry::getLang()->getBaseLanguage();
                    $id = md5(strtolower($requestUrl) . $shopId . $languageId);

                    // logging "not found" url
                    $database = DatabaseProvider::getDb();
                    $database->execute(
                        "replace oxseologs ( oxstdurl, oxident, oxshopid, oxlang ) values ( ?, ?, ?, ? ) ",
                        [$requestUrl, $id, $shopId, $languageId]
                    );
                }
            }
        }
    }

    /**
     * Calls self::_processRequest(), initializes components which needs to
     * be loaded, sets current list type, calls parent::init()
     */
    public function init()
    {
        $this->_processRequest();

        // storing current view
        $shouldInitialize = $this->shouldInitializeComponents();

        // init all components if there are any
        if ($this->_blLoadComponents) {
            foreach ($this->_getComponentNames() as $componentName => $isNotCacheable) {
                // do not override initiated components
                if (!isset($this->_oaComponents[$componentName])) {
                    // component objects MUST be created to support user called functions
                    $component = oxNew($componentName);
                    $component->setParent($this);
                    $component->setThisAction($componentName);
                    $this->_oaComponents[$componentName] = $component;
                }

                // do we really need to initiate them ?
                if ($shouldInitialize) {
                    $this->_oaComponents[$componentName]->init();

                    // executing only is view does not have action method
                    if (!method_exists($this, $this->getFncName())) {
                        $this->_oaComponents[$componentName]->executeFunction($this->getFncName());
                    }
                }
            }
        }

        parent::init();
    }

    /**
     * Returns whether init() should initialize created components.
     *
     * @return bool
     */
    protected function shouldInitializeComponents()
    {
        return true;
    }

    /**
     * If current view ID is not set - forms and returns view ID
     * according to language and currency.
     *
     * @return string $this->_sViewId
     */
    public function getViewId()
    {
        if (isset($this->_sViewId)) {
            return $this->_sViewId;
        }

        return $this->_sViewId = $this->generateViewId();
    }

    /**
     * Generates current view id.
     *
     * @return string
     */
    protected function generateViewId()
    {
        $config = Registry::getConfig();
        $viewId = $this->generateViewIdBase();

        $viewId .= "|" . ((int) $this->_blForceNoIndex) . '|' . ((int) $this->isRootCatChanged());

        // #0004798: SSL should be included in viewId
        if ($config->isSsl()) {
            $viewId .= "|ssl";
        }

        // #0002866: external global viewID addition
        if (function_exists('customGetViewId')) {
            $externalViewId = customGetViewId();

            if ($externalViewId !== null) {
                $viewId .= '|' . md5(serialize($externalViewId));
            }
        }

        return $viewId;
    }

    /**
     * Generates base for view id.
     *
     * @return string
     */
    protected function generateViewIdBase()
    {
        $languageId = Registry::getLang()->getBaseLanguage();
        $currencyId = (int) Registry::getConfig()->getShopCurrency();

        return "ox|$languageId|$currencyId";
    }

    /**
     * Template variable getter. Returns true if sorting is on
     *
     * @return bool
     */
    public function showSorting()
    {
        return $this->_blShowSorting && Registry::getConfig()->getConfigParam('blShowSorting');
    }

    /**
     * Set array of component objects
     *
     * @param array $components array of components objects
     */
    public function setComponents($components = null)
    {
        $this->_oaComponents = $components;
    }

    /**
     * Get array of component objects
     *
     * @return array
     */
    public function getComponents()
    {
        return $this->_oaComponents;
    }

    /**
     * Get component object
     *
     * @param string $name name of component object
     *
     * @return object|void
     */
    public function getComponent($name)
    {
        if (isset($name) && isset($this->_oaComponents[$name])) {
            return $this->_oaComponents[$name];
        }
    }

    /**
     * Set flag if current view is an order view
     *
     * @param bool $isOrderStep flag if current view is an order view
     */
    public function setIsOrderStep($isOrderStep = null)
    {
        $this->_blIsOrderStep = $isOrderStep;
    }

    /**
     * Get flag if current view is an order view
     *
     * @return bool
     */
    public function getIsOrderStep()
    {
        return $this->_blIsOrderStep;
    }


    /**
     * Active category setter
     *
     * @param object $category active category
     */
    public function setActiveCategory($category)
    {
        $this->_oActCategory = $category;
    }

    /**
     * Returns active category
     *
     * @return Category|null
     */
    public function getActiveCategory()
    {
        return $this->_oActCategory;
    }

    /**
     * Get list type
     *
     * @return string list type
     */
    public function getListType()
    {
        if ($this->_sListType == null) {
            if ($listType = Registry::getRequest()->getRequestEscapedParameter('listtype')) {
                $this->_sListType = $listType;
            } elseif ($listType = Registry::getConfig()->getGlobalParameter('listtype')) {
                $this->_sListType = $listType;
            }
        }

        return $this->_sListType;
    }

    /**
     * Returns list type
     *
     * @return string
     */
    public function getListDisplayType()
    {
        if ($this->_sListDisplayType == null) {
            $this->_sListDisplayType = $this->getCustomListDisplayType();

            if (!$this->_sListDisplayType) {
                $this->_sListDisplayType = Registry::getConfig()->getConfigParam('sDefaultListDisplayType');
            }

            $this->_sListDisplayType = in_array((string) $this->_sListDisplayType, $this->_aListDisplayTypes) ?
                $this->_sListDisplayType : 'infogrid';

            // writing to session
            if (Registry::getRequest()->getRequestEscapedParameter('ldtype')) {
                Registry::getSession()->setVariable('ldtype', $this->_sListDisplayType);
            }
        }

        return $this->_sListDisplayType;
    }

    /**
     * Returns changed default list type
     *
     * @return string
     */
    public function getCustomListDisplayType()
    {
        if ($this->_sCustomListDisplayType == null) {
            $this->_sCustomListDisplayType = Registry::getRequest()->getRequestEscapedParameter('ldtype');

            if (!$this->_sCustomListDisplayType) {
                $this->_sCustomListDisplayType = Registry::getSession()->getVariable('ldtype');
            }
        }

        return $this->_sCustomListDisplayType;
    }

    /**
     * List type setter
     *
     * @param string $type type of list
     */
    public function setListType($type)
    {
        $this->_sListType = $type;
        Registry::getConfig()->setGlobalParameter('listtype', $type);
    }

    /**
     * Returns currency switching option
     *
     * @return bool
     */
    public function loadCurrency()
    {
        if ($this->_blLoadCurrency == null) {
            $this->_blLoadCurrency = false;
            if ($loadCurrency = Registry::getConfig()->getConfigParam('bl_perfLoadCurrency')) {
                $this->_blLoadCurrency = $loadCurrency;
            }
        }

        return $this->_blLoadCurrency;
    }

    /**
     * Returns true if empty categories are not loaded
     *
     * @return bool
     */
    public function dontShowEmptyCategories()
    {
        if ($this->_blDontShowEmptyCats == null) {
            $this->_blDontShowEmptyCats = false;
            if ($dontShowEmptyCats = Registry::getConfig()->getConfigParam('blDontShowEmptyCategories')) {
                $this->_blDontShowEmptyCats = $dontShowEmptyCats;
            }
        }

        return $this->_blDontShowEmptyCats;
    }

    /**
     * Returns true if empty categories are not loaded
     *
     * @return bool
     */
    public function showCategoryArticlesCount()
    {
        return Registry::getConfig()->getConfigParam('bl_perfShowActionCatArticleCnt');
    }

    /**
     * Returns if language should be loaded
     *
     * @return bool
     */
    public function isLanguageLoaded()
    {
        if ($this->_blLoadLanguage == null) {
            $this->_blLoadLanguage = false;
            if ($loadLanguage = Registry::getConfig()->getConfigParam('bl_perfLoadLanguages')) {
                $this->_blLoadLanguage = $loadLanguage;
            }
        }

        return $this->_blLoadLanguage;
    }

    /**
     * Returns item count in top navigation of categories
     *
     * @return integer
     */
    public function getTopNavigationCatCnt()
    {
        if ($this->_iTopCatNavItmCnt == null) {
            $topCategoryNavigationItemsCount = Registry::getConfig()->getConfigParam('iTopNaviCatCount');
            $this->_iTopCatNavItmCnt = $topCategoryNavigationItemsCount ? $topCategoryNavigationItemsCount : 5;
        }

        return $this->_iTopCatNavItmCnt;
    }

    /**
     * addRssFeed adds link to rss
     *
     * @param string $title feed page title
     * @param string $url   feed url
     * @param int    $key   feed number
     */
    public function addRssFeed($title, $url, $key = null)
    {
        if (!is_array($this->_aRssLinks)) {
            $this->_aRssLinks = [];
        }

        $url = Registry::getUtilsUrl()->prepareUrlForNoSession($url);

        if ($key === null) {
            $this->_aRssLinks[] = ['title' => $title, 'link' => $url];
        } else {
            $this->_aRssLinks[$key] = ['title' => $title, 'link' => $url];
        }
    }

    /**
     * Returns sorted column parameter name
     *
     * @return string
     */
    public function getSortOrderByParameterName()
    {
        return 'listorderby';
    }

    /**
     * Returns sorted column direction parameter name
     *
     * @return string
     */
    public function getSortOrderParameterName()
    {
        return 'listorder';
    }


    /**
     * Returns page sort ident. It is used as ident in session variable aSorting[ident]
     *
     * @return string
     */
    public function getSortIdent()
    {
        return 'alist';
    }

    /**
     * Returns default category sorting for selected category
     *
     * @return null
     */
    public function getDefaultSorting()
    {
        return null;
    }

    /**
     * Returns default category sorting for selected category
     *
     * @return array|void
     */
    public function getUserSelectedSorting()
    {
        $request = Registry::get(Request::class);
        $sortBy = $request->getRequestEscapedParameter($this->getSortOrderByParameterName());
        $sortOrder = $request->getRequestEscapedParameter($this->getSortOrderParameterName());

        if ((new SortingValidator())->isValid($sortBy, $sortOrder)) {
            return ['sortby' => $sortBy, 'sortdir' => $sortOrder];
        }
    }

    /**
     * Returns sorting variable from session
     *
     * @param string $sortIdent sorting indent
     *
     * @return array|void
     */
    public function getSavedSorting($sortIdent)
    {
        $sorting = Registry::getSession()->getVariable('aSorting');
        if (isset($sorting[$sortIdent])) {
            return $sorting[$sortIdent];
        }
    }

    /**
     * Set sorting column name
     *
     * @param string $column - column name
     */
    public function setListOrderBy($column)
    {
        $this->_sListOrderBy = $column;
    }

    /**
     * Set sorting directions
     *
     * @param string $direction - direction desc / asc
     */
    public function setListOrderDirection($direction)
    {
        $this->_sListOrderDir = $direction;
    }

    /**
     * Template variable getter. Returns string after the list is ordered by
     *
     * @return string
     */
    public function getListOrderBy()
    {
        //if column is with table name split it
        $columns = explode('.', $this->_sListOrderBy);

        if (is_array($columns) && count($columns) > 1) {
            return $columns[1];
        }

        return $this->_sListOrderBy;
    }

    /**
     * Template variable getter. Returns list order direction
     *
     * @return string
     */
    public function getListOrderDirection()
    {
        return $this->_sListOrderDir;
    }

    /**
     * Sets the view parameter "meta_description"
     *
     * @param string $description prepared string for description
     *
     * @return null
     */
    public function setMetaDescription($description)
    {
        return $this->_sMetaDescription = $description;
    }

    /**
     * Sets the view parameter 'meta_keywords'
     *
     * @param string $keywords prepared string for meta keywords
     *
     * @return null
     */
    public function setMetaKeywords($keywords)
    {
        return $this->_sMetaKeywords = $keywords;
    }

    /**
     * Fetches metadata (description or keywords) from seo table
     *
     * @param string $dataType data type "oxkeywords" or "oxdescription"
     *
     * @return string|void
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMetaFromSeo" in next major
     */
    protected function _getMetaFromSeo($dataType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $seoObjectId = $this->_getSeoObjectId();
        $baseLanguageId = Registry::getLang()->getBaseLanguage();
        $shopId = Registry::getConfig()->getShopId();

        if (
            $seoObjectId && Registry::getUtils()->seoIsActive() &&
            ($keywords = Registry::getSeoEncoder()->getMetaData($seoObjectId, $dataType, $shopId, $baseLanguageId))
        ) {
            return $keywords;
        }
    }

    /**
     * Fetches metadata (description or keywords) from content table
     *
     * @param string $metaIdent meta content ident
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMetaFromContent" in next major
     */
    protected function _getMetaFromContent($metaIdent) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($metaIdent) {
            $content = oxNew(Content::class);
            if (
                $content->loadByIdent($metaIdent) &&
                $content->oxcontents__oxactive->value
            ) {
                return Str::getStr()->strip_tags($content->oxcontents__oxcontent->value);
            }
        }
    }

    /**
     * Template variable getter. Returns meta keywords
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getMetaKeywords()
    {
        if ($this->_sMetaKeywords === null) {
            $this->_sMetaKeywords = false;

            // set special meta keywords ?
            if (($keywords = $this->_getMetaFromSeo('oxkeywords'))) {
                $this->_sMetaKeywords = $keywords;
            } elseif (($keywords = $this->_getMetaFromContent($this->_sMetaKeywordsIdent))) {
                $this->_sMetaKeywords = $this->_prepareMetaKeyword($keywords, false);
            } else {
                $this->_sMetaKeywords = $this->_prepareMetaKeyword(false, true);
            }
        }

        return $this->_sMetaKeywords;
    }

    /**
     * Template variable getter. Returns meta description
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getMetaDescription()
    {
        if ($this->_sMetaDescription === null) {
            $this->_sMetaDescription = false;

            // set special meta description ?
            if (($description = $this->_getMetaFromSeo('oxdescription'))) {
                $this->_sMetaDescription = $description;
            } elseif (($description = $this->_getMetaFromContent($this->_sMetaDescriptionIdent))) {
                $this->_sMetaDescription = $this->_prepareMetaDescription($description);
            } else {
                $this->_sMetaDescription = $this->_prepareMetaDescription(false);
            }
        }

        return $this->_sMetaDescription;
    }

    /**
     * Get active currency
     *
     * @return object
     */
    public function getActCurrency()
    {
        return $this->_oActCurrency;
    }

    /**
     * Active currency setter
     *
     * @param object $currency Currency object
     */
    public function setActCurrency($currency)
    {
        $this->_oActCurrency = $currency;
    }

    /**
     * Template variable getter. Returns comparison article list count.
     *
     * @return integer
     */
    public function getCompareItemCount()
    {
        if ($this->_iCompItemsCnt === null) {
            $items = Registry::getSession()->getVariable('aFiltcompproducts');
            $this->_iCompItemsCnt = is_array($items) ? count($items) : 0;
        }

        return $this->_iCompItemsCnt;
    }

    /**
     * Forces output no index metadata for current view
     * @deprecated underscore prefix violates PSR12, will be renamed to "forceNoIndex" in next major
     */
    protected function _forceNoIndex() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_blForceNoIndex = true;
    }

    /**
     * Marks that current view is marked as no index, no follow and
     * article details links must contain no follow tags
     *
     * @return int
     */
    public function noIndex()
    {
        if ($this->_blForceNoIndex) {
            $this->_iViewIndexState = VIEW_INDEXSTATE_NOINDEXFOLLOW;
        } elseif (Registry::getRequest()->getRequestEscapedParameter('cur')) {
            $this->_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
        } elseif (0 < Registry::get(Request::class)->getRequestEscapedParameter('pgNr')) {
            $this->_iViewIndexState = VIEW_INDEXSTATE_NOINDEXFOLLOW;
        } else {
            switch (Registry::getRequest()->getRequestEscapedParameter('fnc')) {
                case 'tocomparelist':
                case 'tobasket':
                    $this->_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
                    break;
            }
        }

        return $this->_iViewIndexState;
    }

    /**
     * Template variable getter. Returns header menu list
     *
     * @return array
     */
    public function getMenueList()
    {
        return $this->_aMenueList;
    }

    /**
     * Header menu list setter
     *
     * @param array $menu menu list
     */
    public function setMenueList($menu)
    {
        $this->_aMenueList = $menu;
    }

    /**
     * Sets number of articles per page to config value
     * @deprecated underscore prefix violates PSR12, will be renamed to "setNrOfArtPerPage" in next major
     */
    protected function _setNrOfArtPerPage() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $config = Registry::getConfig();

        //setting default values to avoid possible errors showing article list
        $numberOfCategoryArticles = $config->getConfigParam('iNrofCatArticles');

        $numberOfCategoryArticles = ($numberOfCategoryArticles) ? $numberOfCategoryArticles : 10;

        // checking if all needed data is set
        switch ($this->getListDisplayType()) {
            case 'grid':
                $numbersOfCategoryArticles = $config->getConfigParam('aNrofCatArticlesInGrid');
                break;
            case 'line':
            case 'infogrid':
            default:
                $numbersOfCategoryArticles = $config->getConfigParam('aNrofCatArticles');
        }

        if (!is_array($numbersOfCategoryArticles) || !isset($numbersOfCategoryArticles[0])) {
            $numbersOfCategoryArticles = [$numberOfCategoryArticles];
            $config->setConfigParam('aNrofCatArticles', $numbersOfCategoryArticles);
        } else {
            $numberOfCategoryArticles = $numbersOfCategoryArticles[0];
        }

        $viewConfig = $this->getViewConfig();
        //value from user input
        $session = Registry::getSession();
        if (($articlesPerPage = (int) Registry::getRequest()->getRequestEscapedParameter('_artperpage'))) {
            // M45 Possibility to push any "Show articles per page" number parameter
            $numberOfCategoryArticles = (in_array($articlesPerPage, $numbersOfCategoryArticles))
                ? $articlesPerPage
                : $numberOfCategoryArticles;
            $viewConfig->setViewConfigParam('iartPerPage', $numberOfCategoryArticles);
            $session->setVariable('_artperpage', $numberOfCategoryArticles);
        } elseif (($sessArtPerPage = $session->getVariable('_artperpage')) && is_numeric($sessArtPerPage)) {
            // M45 Possibility to push any "Show articles per page" number parameter
            $numberOfCategoryArticles = (in_array($sessArtPerPage, $numbersOfCategoryArticles))
                ? $sessArtPerPage
                : $numberOfCategoryArticles;
            $viewConfig->setViewConfigParam('iartPerPage', $numberOfCategoryArticles);
            $session->setVariable('_artperpage', $numberOfCategoryArticles);
        } else {
            $viewConfig->setViewConfigParam('iartPerPage', $numberOfCategoryArticles);
        }

        //setting number of articles per page to config value
        $config->setConfigParam('iNrofCatArticles', $numberOfCategoryArticles);
    }

    /**
     * Override this function to return object it which is used to identify its seo meta info
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoObjectId" in next major
     */
    protected function _getSeoObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Returns current view meta description data
     *
     * @param string $meta                  Category path
     * @param int    $length                Max length of result, -1 for no truncation
     * @param bool   $removeDuplicatedWords If true - performs additional duplicate cleaning
     *
     * @return  string|void  $string    converted string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaDescription" in next major
     */
    protected function _prepareMetaDescription($meta, $length = 1024, $removeDuplicatedWords = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($meta) {
            $stringModifier = Str::getStr();
            if ($length != -1) {
                /* *
                 * performance - we do not need a huge amount of initial text.
                 * assume that effective text may be double longer than $length
                 * and simple truncate it
                 */
                $doubleLength = ($length * 2);
                $meta = $stringModifier->substr($meta, 0, $doubleLength);
            }

            // decoding html entities
            $meta = $stringModifier->html_entity_decode($meta);
            // stripping HTML tags
            $meta = $stringModifier->strip_tags($meta);

            // removing some special chars
            $meta = $stringModifier->cleanStr($meta);

            // removing duplicate words
            if ($removeDuplicatedWords) {
                $meta = $this->_removeDuplicatedWords($meta, Registry::getConfig()->getConfigParam('aSkipTags'));
            }

            // some special cases
            $meta = str_replace(' ,', ',', $meta);
            $pattern = ["/,[\s\+\-\*]*,/", "/\s+,/"];
            $meta = $stringModifier->preg_replace($pattern, ',', $meta);
            $meta = Registry::getUtilsString()->minimizeTruncateString($meta, $length);
            $meta = $stringModifier->htmlspecialchars($meta);

            return trim($meta);
        }
    }

    /**
     * Returns current view keywords separated by comma
     *
     * @param string $keywords              Data to use as keywords
     * @param bool   $removeDuplicatedWords If true - performs additional duplicate cleaning
     *
     * @return string of keywords separated by comma
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($keywords, $removeDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $string = $this->_prepareMetaDescription($keywords, -1, false);

        if ($removeDuplicatedWords) {
            $string = $this->_removeDuplicatedWords($string, Registry::getConfig()->getConfigParam('aSkipTags'));
        }

        return trim($string);
    }

    /**
     * Removes duplicated words (not case-sensitive)
     *
     * @param mixed $input    array of string or string
     * @param array $skipTags in admin defined strings
     *
     * @return string of words separated by comma
     * @deprecated underscore prefix violates PSR12, will be renamed to "removeDuplicatedWords" in next major
     */
    protected function _removeDuplicatedWords($input, $skipTags = []) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $stringModifier = Str::getStr();
        if (is_array($input)) {
            $input = implode(" ", $input);
        }

        // removing some usually met characters ...
        $input = $stringModifier->preg_replace("/[" . preg_quote($this->_sRemoveMetaChars, "/") . "]/", " ", $input);

        // splitting by word
        $strings = $stringModifier->preg_split("/[\s,]+/", $input);

        if ($count = count($skipTags)) {
            for ($num = 0; $num < $count; $num++) {
                $skipTags[$num] = $stringModifier->strtolower($skipTags[$num]);
            }
        }
        $count = count($strings);
        for ($num = 0; $num < $count; $num++) {
            $strings[$num] = $stringModifier->strtolower($strings[$num]);
            // removing in admin defined strings
            if (!$strings[$num] || in_array($strings[$num], $skipTags)) {
                unset($strings[$num]);
            }
        }

        // duplicates
        return implode(', ', array_unique($strings));
    }

    /**
     * Returns array of params => values which are used in hidden forms and as additional url params.
     * NOTICE: this method SHOULD return raw (non encoded into entities) parameters, because values
     * are processed by htmlentities() to avoid security and broken templates problems
     *
     * @return array
     */
    public function getNavigationParams()
    {
        $oRequest = Registry::getRequest();
        $params['cnid'] = $this->getCategoryId();
        $params['mnid'] = $oRequest->getRequestEscapedParameter('mnid');

        $params['listtype'] = $this->getListType();
        $params['ldtype'] = $this->getCustomListDisplayType();
        $params['actcontrol'] = $this->getClassName();

        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        $params['recommid'] = $oRequest->getRequestEscapedParameter('recommid');

        $params['searchrecomm'] = $oRequest->getRequestEscapedParameter('searchrecomm', true);
        // END deprecated
        $params['searchparam'] = $oRequest->getRequestEscapedParameter('searchparam', true);

        $params['searchvendor'] = $oRequest->getRequestEscapedParameter('searchvendor');
        $params['searchcnid'] = $oRequest->getRequestEscapedParameter('searchcnid');
        $params['searchmanufacturer'] = $oRequest->getRequestEscapedParameter('searchmanufacturer');

        $params = array_merge($params, $this->getViewConfig()->getAdditionalNavigationParameters());

        return $params;
    }

    /**
     * Sets sorting item config
     *
     * @param string $sortIdent sortable item id
     * @param string $sortBy    sort field
     * @param string $sortDir   sort direction (optional)
     */
    public function setItemSorting($sortIdent, $sortBy, $sortDir = null)
    {
        $sorting = Registry::getSession()->getVariable('aSorting');
        $sorting[$sortIdent]['sortby'] = $sortBy;
        $sorting[$sortIdent]['sortdir'] = $sortDir ? $sortDir : null;

        Registry::getSession()->setVariable('aSorting', $sorting);
    }

    /**
     * Returns sorting config for current item
     *
     * @param string $sortIdent sortable item id
     *
     * @return array
     */
    public function getSorting($sortIdent)
    {
        if ($sorting = $this->getUserSelectedSorting()) {
            $this->setItemSorting($sortIdent, $sorting['sortby'], $sorting['sortdir']);
        } elseif (!$sorting = $this->getSavedSorting($sortIdent)) {
            $sorting = $this->getDefaultSorting();
        }

        if ($sorting) {
            $this->setListOrderBy($sorting['sortby']);
            $this->setListOrderDirection($sorting['sortdir']);
        }

        return $sorting;
    }

    /**
     * Returns part of SQL query with sorting params
     *
     * @param string $ident sortable item id
     *
     * @return string|void
     * @throws DatabaseConnectionException
     */
    public function getSortingSql($ident)
    {
        $sorting = $this->getSorting($ident);
        if (is_array($sorting)) {
            $sortDir = isset($sorting['sortdir']) ? $sorting['sortdir'] : '';
            if ($this->isAllowedSortingOrder($sortDir)) {
                $sortBy = DatabaseProvider::getDb()->quoteIdentifier($sorting['sortby']);
                return trim($sortBy . ' ' . $sortDir);
            }
        }
    }

    /**
     * Returns title suffix used in template
     *
     * @return string
     */
    public function getTitleSuffix()
    {
        return Registry::getConfig()->getActiveShop()->oxshops__oxtitlesuffix->value;
    }

    /**
     * Returns title page suffix used in template in lists
     */
    public function getTitlePageSuffix()
    {
    }

    /**
     * Returns title prefix used in template
     *
     * @return string
     */
    public function getTitlePrefix()
    {
        return Registry::getConfig()->getActiveShop()->oxshops__oxtitleprefix->value;
    }


    /**
     * Returns full page title
     *
     * @return string
     */
    public function getPageTitle()
    {
        $titleParts = [];
        $titleParts[] = $this->getTitlePrefix();
        $titleParts[] = $this->getTitle();
        $titleParts[] = $this->getTitleSuffix();
        $titleParts[] = $this->getTitlePageSuffix();

        $titleParts = array_filter($titleParts);
        $title = implode(' | ', $titleParts);

        return $this->replaceDoubleQuotesWithHTMLCharacters($title);
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
        return null;
    }

    /**
     * returns additional url params for dynamic url building
     *
     * @return string
     */
    public function getDynUrlParams()
    {
        $result = '';
        $listType = $this->getListType();
        $oRequest = Registry::getRequest();

        switch ($listType) {
            default:
                $result .= $this->getViewConfig()->getDynUrlParameters($listType);
                break;
            case 'search':
                $result .= "&amp;listtype={$listType}";
                if ($searchParamForLink = rawurlencode($oRequest->getRequestEscapedParameter('searchparam', true))) {
                    $result .= "&amp;searchparam={$searchParamForLink}";
                }

                if (($var = $oRequest->getRequestEscapedParameter('searchcnid', true))) {
                    $result .= '&amp;searchcnid=' . rawurlencode(rawurldecode($var));
                }
                if (($var = $oRequest->getRequestEscapedParameter('searchvendor', true))) {
                    $result .= '&amp;searchvendor=' . rawurlencode(rawurldecode($var));
                }
                if (($var = $oRequest->getRequestEscapedParameter('searchmanufacturer', true))) {
                    $result .= '&amp;searchmanufacturer=' . rawurlencode(rawurldecode($var));
                }
                break;
        }

        return $result;
    }

    /**
     * Get base link of current view
     *
     * @param int $languageId requested language
     *
     * @return string
     */
    public function getBaseLink($languageId = null)
    {
        if (!isset($languageId)) {
            $languageId = Registry::getLang()->getBaseLanguage();
        }

        $config = Registry::getConfig();

        if (Registry::getUtils()->seoIsActive()) {
            if ($displayObj = $this->_getSubject($languageId)) {
                $url = $displayObj->getLink($languageId);
            } else {
                $encoder = Registry::getSeoEncoder();
                $constructedUrl = $config->getShopHomeUrl($languageId) . $this->_getSeoRequestParams();
                $url = $encoder->getStaticUrl($constructedUrl, $languageId);
            }
        }

        if (!$url) {
            $constructedUrl = $config->getShopCurrentURL($languageId) . $this->_getRequestParams();
            $url = Registry::getUtilsUrl()->processUrl($constructedUrl, true, null, $languageId);
        }

        return $url;
    }


    /**
     * Get link of current view. In url its include also page number if it is list page
     *
     * @param int $languageId requested language
     *
     * @return string
     */
    public function getLink($languageId = null)
    {
        return $this->_addPageNrParam($this->getBaseLink($languageId), $this->getActPage(), $languageId);
    }

    /**
     * Returns view object canonical url
     */
    public function getCanonicalUrl()
    {
    }

    /**
     * Return array of id to form recommend list.
     * Should be overridden if needed.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return bool
     */
    public function getSimilarRecommListIds()
    {
        return false;
    }

    /**
     * Template variable getter. Returns search parameter for Html
     * So far this method is implemented in search (search.php) view.
     */
    public function getSearchParamForHtml()
    {
    }

    /**
     * collects _GET parameters used by Shop and returns uri
     *
     * @param bool $addPageNumber if TRUE - page number will be added
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getRequestParams" in next major
     */
    protected function _getRequestParams($addPageNumber = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $class = $this->getClassName();
        $function = $this->getFncName();

        $forbiddenFunctions = [
            'tobasket',
            'login_noredirect',
            'addVoucher',
            'moveleft',
            'moveright',
            'deleteReviewAndRating',
        ];

        if (in_array($function, $forbiddenFunctions)) {
            $function = '';
        }

        // #680
        $url = "cl={$class}";
        if ($function) {
            $url .= "&amp;fnc={$function}";
        }
        if ($value = Registry::getRequest()->getRequestEscapedParameter('cnid')) {
            $url .= "&amp;cnid={$value}";
        }
        if ($value = Registry::getRequest()->getRequestEscapedParameter('mnid')) {
            $url .= "&amp;mnid={$value}";
        }
        if ($value = Registry::getRequest()->getRequestEscapedParameter('anid')) {
            $url .= "&amp;anid={$value}";
        }

        if ($value = basename(Registry::getRequest()->getRequestEscapedParameter('page'))) {
            $url .= "&amp;page={$value}";
        }

        if ($value = basename(Registry::getRequest()->getRequestEscapedParameter('tpl'))) {
            $url .= "&amp;tpl={$value}";
        }

        if ($value = Registry::getRequest()->getRequestEscapedParameter('oxloadid')) {
            $url .= "&amp;oxloadid={$value}";
        }

        $pageNumber = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
        // don't include page number for navigation
        // it will be done in FrontendController::generatePageNavigation
        if ($addPageNumber && $pageNumber > 0) {
            $url .= "&amp;pgNr={$pageNumber}";
        }

        // #1184M - specialchar search
        if ($value = rawurlencode(Registry::getRequest()->getRequestEscapedParameter('searchparam', true))) {
            $url .= "&amp;searchparam={$value}";
        }

        if ($value = Registry::getRequest()->getRequestEscapedParameter('searchcnid')) {
            $url .= "&amp;searchcnid={$value}";
        }

        if ($value = Registry::getRequest()->getRequestEscapedParameter('searchvendor')) {
            $url .= "&amp;searchvendor={$value}";
        }

        if ($value = Registry::getRequest()->getRequestEscapedParameter('searchmanufacturer')) {
            $url .= "&amp;searchmanufacturer={$value}";
        }

        if ($value = Registry::getRequest()->getRequestEscapedParameter('searchrecomm')) {
            $url .= "&amp;searchrecomm={$value}";
        }

        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($value = Registry::getRequest()->getRequestEscapedParameter('recommid')) {
            $url .= "&amp;recommid={$value}";
        }
        // END deprecated

        $url .= $this->getViewConfig()->addRequestParameters();

        return $url;
    }

    /**
     * collects _GET parameters used by Shop SEO and returns uri
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoRequestParams" in next major
     */
    protected function _getSeoRequestParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $class = $this->getClassName();
        $function = $this->getFncName();

        // #921 S
        $forbiddenFunctions = ['tobasket', 'login_noredirect', 'addVoucher'];
        if (in_array($function, $forbiddenFunctions)) {
            $function = '';
        }

        // #680
        $url = "cl={$class}";
        if ($function) {
            $url .= "&amp;fnc={$function}";
        }
        if ($value = basename(Registry::getRequest()->getRequestEscapedParameter('page'))) {
            $url .= "&amp;page={$value}";
        }

        if ($value = basename(Registry::getRequest()->getRequestEscapedParameter('tpl'))) {
            $url .= "&amp;tpl={$value}";
        }

        if ($value = Registry::getRequest()->getRequestEscapedParameter('oxloadid')) {
            $url .= "&amp;oxloadid={$value}";
        }

        $pageNumber = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
        if ($pageNumber > 0) {
            $url .= "&amp;pgNr={$pageNumber}";
        }

        return $url;
    }

    /**
     * Returns show category search
     *
     * @return bool
     */
    public function showSearch()
    {
        return !(Registry::getConfig()->getConfigParam('blDisableNavBars') && $this->getIsOrderStep());
    }

    /**
     * Returns RSS links
     *
     * @return array
     */
    public function getRssLinks()
    {
        return $this->_aRssLinks;
    }

    /**
     * Template variable getter. Returns sorting columns
     *
     * @return array
     */
    public function getSortColumns()
    {
        if ($this->_aSortColumns === null) {
            $this->setSortColumns(Registry::getConfig()->getConfigParam('aSortCols'));
        }

        return $this->_aSortColumns;
    }


    /**
     * Set sorting columns
     *
     * @param array $sortColumns array of column names array('name1', 'name2',...)
     */
    public function setSortColumns($sortColumns)
    {
        $this->_aSortColumns = $sortColumns;
    }

    /**
     * Template variable getter. Returns search string
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     */
    public function getRecommSearch()
    {
    }

    /**
     * Template variable getter. Returns payment id
     */
    public function getPaymentList()
    {
    }

    /**
     * Template variable getter. Returns active recommendation lists
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return RecommendationList
     */
    public function getActiveRecommList()
    {
        if ($this->_oActiveRecommList === null) {
            $this->_oActiveRecommList = false;
            if ($recommendationListId = Registry::getRequest()->getRequestEscapedParameter('recommid')) {
                $this->_oActiveRecommList = oxNew(RecommendationList::class);
                $this->_oActiveRecommList->load($recommendationListId);
            }
        }

        return $this->_oActiveRecommList;
    }

    /**
     * Template variable getter. Returns accessoires of article
     */
    public function getAccessoires()
    {
    }

    /**
     * Template variable getter. Returns cross-selling
     */
    public function getCrossSelling()
    {
    }

    /**
     * Template variable getter. Returns similar article list
     */
    public function getSimilarProducts()
    {
    }

    /**
     * Template variable getter. Returns list of customer also bought thies products
     */
    public function getAlsoBoughtTheseProducts()
    {
    }

    /**
     * Return the active article id
     */
    public function getArticleId()
    {
    }

    /**
     * Returns current view title. Default is search for translation of PAGE_TITLE_{view_class_name}
     *
     * @return string
     */
    public function getTitle()
    {
        $language = Registry::getLang();
        $translationName = 'PAGE_TITLE_' . strtoupper(Registry::getConfig()->getActiveView()->getClassName());
        $translated = $language->translateString($translationName, Registry::getLang()->getBaseLanguage(), false);

        return $translationName == $translated ? null : $translated;
    }

    /**
     * Returns active lang suffix
     * usually it used in html lang attr to allow the browser to interpret the page in the right language
     * e.g. to support hyphens
     * @return string
     */
    public function getActiveLangAbbr()
    {
        if (!isset($this->_sActiveLangAbbr)) {
            $languageService = Registry::getLang();
            if (Registry::getConfig()->getConfigParam('bl_perfLoadLanguages')) {
                $languages = $languageService->getLanguageArray();
                foreach ($languages as $language) {
                    if ($language->selected) {
                        $this->_sActiveLangAbbr = $language->abbr;
                        break;
                    }
                }
            } else {
                // Performance
                // use oxid shop internal languageAbbr, this might be correct in the most cases but not guaranteed to
                // be that configured in the admin backend for that language
                $this->_sActiveLangAbbr = $languageService->getLanguageAbbr();
            }
        }

        return $this->_sActiveLangAbbr;
    }

    /**
     * Sets and caches default parameters for shop object and returns it.
     *
     * @param Shop $oShop current shop object
     *
     * @return ViewConfig Current shop object
     */
    public function addGlobalParams($oShop = null)
    {
        $viewConfig = parent::addGlobalParams($oShop);

        $this->_setNrOfArtPerPage();

        return $viewConfig;
    }

    /**
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function getAdditionalParams()
    {
        if ($this->_sAdditionalParams === null) {
            // #1018A
            $this->_sAdditionalParams = parent::getAdditionalParams();
            $this->_sAdditionalParams .= 'cl=' . Registry::getConfig()->getTopActiveView()->getClassName();

            // #1834M - special char search
            $searchParamForLink = rawurlencode(Registry::getRequest()->getRequestEscapedParameter('searchparam', true));
            if (isset($searchParamForLink)) {
                $this->_sAdditionalParams .= "&amp;searchparam={$searchParamForLink}";
            }
            if (($value = Registry::getRequest()->getRequestEscapedParameter('searchcnid'))) {
                $this->_sAdditionalParams .= '&amp;searchcnid=' . rawurlencode(rawurldecode($value));
            }
            if (($value = Registry::getRequest()->getRequestEscapedParameter('searchvendor'))) {
                $this->_sAdditionalParams .= '&amp;searchvendor=' . rawurlencode(rawurldecode($value));
            }
            if (($value = Registry::getRequest()->getRequestEscapedParameter('searchmanufacturer'))) {
                $this->_sAdditionalParams .= '&amp;searchmanufacturer=' . rawurlencode(rawurldecode($value));
            }
            if (($value = Registry::getRequest()->getRequestEscapedParameter('cnid'))) {
                $this->_sAdditionalParams .= '&amp;cnid=' . rawurlencode(rawurldecode($value));
            }
            if (($value = Registry::getRequest()->getRequestEscapedParameter('mnid'))) {
                $this->_sAdditionalParams .= '&amp;mnid=' . rawurlencode(rawurldecode($value));
            }

            $this->_sAdditionalParams .= $this->getViewConfig()->getAdditionalParameters();
        }

        return $this->_sAdditionalParams;
    }

    /**
     * Generates URL for page navigation
     *
     * @return string $url String with working page url.
     */
    public function generatePageNavigationUrl()
    {
        return Registry::getConfig()->getShopHomeUrl() . $this->_getRequestParams(false);
    }

    /**
     * Adds page number parameter to url and returns modified url, if page number 0 drops from url
     *
     * @param string $url        Url to add page number
     * @param int    $page       Active page number
     * @param int    $languageId Language id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addPageNrParam" in next major
     */
    protected function _addPageNrParam($url, $page, $languageId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($page) {
            if ((strpos($url, 'pgNr='))) {
                $url = preg_replace('/pgNr=[0-9]*/', 'pgNr=' . $page, $url);
            } else {
                $url .= ((strpos($url, '?') === false) ? '?' : '&amp;') . 'pgNr=' . $page;
            }
        } else {
            $url = preg_replace('/pgNr=[0-9]*/', '', $url);
            $url = preg_replace('/\&amp\;\&amp\;/', '&amp;', $url);
            $url = preg_replace('/\?\&amp\;/', '?', $url);
            $url = preg_replace('/\&amp\;$/', '', $url);
        }

        return $url;
    }

    /**
     * Template variable getter. Returns page navigation
     */
    public function getPageNavigation()
    {
    }

    /**
     * Template variable getter. Returns page navigation with default 7 positions
     *
     * @param int $positionCount Paging positions count ( 0 - unlimited )
     *
     * @return StdClass
     */
    public function getPageNavigationLimitedTop($positionCount = 7)
    {
        return $this->_oPageNavigation = $this->generatePageNavigation($positionCount);
    }

    /**
     * Template variable getter. Returns page navigation with default 11 positions
     *
     * @param int $positionCount Paging positions count ( 0 - unlimited )
     *
     * @return StdClass
     */
    public function getPageNavigationLimitedBottom($positionCount = 11)
    {
        return $this->_oPageNavigation = $this->generatePageNavigation($positionCount);
    }

    /**
     * Generates variables for page navigation
     *
     * @param int $positionCount Paging positions count ( 0 - unlimited )
     *
     * @return StdClass Object with page navigation data
     */
    public function generatePageNavigation($positionCount = 0)
    {
        startProfile('generatePageNavigation');

        $pageNavigation = new StdClass();

        $pageNavigation->NrOfPages = $this->_iCntPages;
        $activePage = $this->getActPage();
        $pageNavigation->actPage = $activePage + 1;
        $url = $this->generatePageNavigationUrl();

        if ($positionCount == 0 || ($positionCount >= $pageNavigation->NrOfPages)) {
            $startNo = 2;
            $finishNo = $pageNavigation->NrOfPages;
        } else {
            $tmpVal = $positionCount - 3;
            $tmpVal2 = floor(($positionCount - 4) / 2);

            // actual page is at the start
            if ($pageNavigation->actPage <= $tmpVal) {
                $startNo = 2;
                $finishNo = $tmpVal + 1;
            // actual page is at the end
            } elseif ($pageNavigation->actPage >= $pageNavigation->NrOfPages - $tmpVal + 1) {
                $startNo = $pageNavigation->NrOfPages - $tmpVal;
                $finishNo = $pageNavigation->NrOfPages - 1;
            // actual page is in the middle
            } else {
                $startNo = $pageNavigation->actPage - $tmpVal2;
                $finishNo = $pageNavigation->actPage + $tmpVal2;
            }
        }

        $pageNavigation->previousPage = null;
        if ($activePage > 0) {
            $pageNavigation->previousPage = $this->_addPageNrParam($url, $activePage - 1);
        }

        $pageNavigation->nextPage = null;
        if ($activePage < $pageNavigation->NrOfPages - 1) {
            $pageNavigation->nextPage = $this->_addPageNrParam($url, $activePage + 1);
        }

        if ($pageNavigation->NrOfPages > 1) {
            for ($i = 1; $i < $pageNavigation->NrOfPages + 1; $i++) {
                if ($i == 1 || $i == $pageNavigation->NrOfPages || ($i >= $startNo && $i <= $finishNo)) {
                    $page = new stdClass();
                    $page->url = $this->_addPageNrParam($url, $i - 1);
                    $page->selected = ($i == $pageNavigation->actPage) ? 1 : 0;
                    $pageNavigation->changePage[$i] = $page;
                }
            }

            // first/last one
            $pageNavigation->firstpage = $this->_addPageNrParam($url, 0);
            $pageNavigation->lastpage = $this->_addPageNrParam($url, $pageNavigation->NrOfPages - 1);
        }

        stopProfile('generatePageNavigation');

        return $pageNavigation;
    }

    /**
     * While ordering disables navigation controls if Config::blDisableNavBars
     * is on and executes parent::render()
     *
     * @return string
     */
    public function render()
    {
        foreach (array_keys($this->_oaComponents) as $componentName) {
            $this->_aViewData[$componentName] = $this->_oaComponents[$componentName]->render();
        }

        parent::render();

        if ($this->getIsOrderStep()) {
            // disabling navigation during order ...
            if (Registry::getConfig()->getConfigParam('blDisableNavBars')) {
                $this->_iNewsRealStatus = 1;
                $this->setShowNewsletter(0);
            }
        }

        return $this->_sThisTemplate;
    }

    /**
     * Returns current view product object (if it is loaded)
     *
     * @return Article|null
     */
    public function getViewProduct()
    {
        return $this->getProduct();
    }

    /**
     * Sets view product
     *
     * @param Article $product view product object
     */
    public function setViewProduct($product)
    {
        $this->_oProduct = $product;
    }

    /**
     * Returns view product list
     *
     * @return array
     */
    public function getViewProductList()
    {
        return $this->_aArticleList;
    }

    /**
     * Active page getter
     *
     * @return int
     */
    public function getActPage()
    {
        if ($this->_iActPage === null) {
            $this->_iActPage = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
            $this->_iActPage = ($this->_iActPage < 0) ? 0 : $this->_iActPage;
        }

        return $this->_iActPage;
    }

    /**
     * Returns active vendor set by categories component; if vendor is
     * not set by component - will create vendor object and will try to
     * load by id passed by request
     *
     * @return Vendor
     */
    public function getActVendor()
    {
        // if active vendor is not set yet - trying to load it from request params
        // this may be useful when category component was unable to load active vendor.
        // We still need some object to mount navigation info
        if ($this->_oActVendor === null) {
            $this->_oActVendor = false;
            $vendorId = Registry::getRequest()->getRequestEscapedParameter('cnid');
            $vendorId = $vendorId ? str_replace('v_', '', $vendorId) : $vendorId;
            $vendor = oxNew(Vendor::class);
            if ($vendor->load($vendorId)) {
                $this->_oActVendor = $vendor;
            }
        }

        return $this->_oActVendor;
    }

    /**
     * Returns active Manufacturer set by categories component; if Manufacturer is
     * not set by component - will create Manufacturer object and will try to
     * load by id passed by request
     *
     * @return Manufacturer
     */
    public function getActManufacturer()
    {
        // if active Manufacturer is not set yet - trying to load it from request params
        // this may be useful when category component was unable to load active Manufacturer.
        // We still need some object to mount navigation info
        if ($this->_oActManufacturer === null) {
            $this->_oActManufacturer = false;
            $manufacturerId = Registry::getRequest()->getRequestEscapedParameter('mnid');
            $manufacturer = oxNew(Manufacturer::class);
            if ($manufacturer->load($manufacturerId)) {
                $this->_oActManufacturer = $manufacturer;
            }
        }

        return $this->_oActManufacturer;
    }

    /**
     * Active vendor setter
     *
     * @param Vendor $vendor active vendor
     */
    public function setActVendor($vendor)
    {
        $this->_oActVendor = $vendor;
    }

    /**
     * Active Manufacturer setter
     *
     * @param Manufacturer $manufacturer active Manufacturer
     */
    public function setActManufacturer($manufacturer)
    {
        $this->_oActManufacturer = $manufacturer;
    }

    /**
     * Returns fake object which is used to mount navigation info
     *
     * @return stdClass
     */
    public function getActSearch()
    {
        if ($this->_oActSearch === null) {
            $this->_oActSearch = new stdClass();
            $url = Registry::getConfig()->getShopHomeUrl();
            $this->_oActSearch->link = "{$url}cl=search";
        }

        return $this->_oActSearch;
    }

    /**
     * Returns category tree (if it is loaded)
     *
     * @return CategoryList
     */
    public function getCategoryTree()
    {
        return $this->_oCategoryTree;
    }

    /**
     * Category list setter
     *
     * @param CategoryList $categoryTree category tree
     */
    public function setCategoryTree($categoryTree)
    {
        $this->_oCategoryTree = $categoryTree;
    }

    /**
     * Returns Manufacturer tree (if it is loaded0
     *
     * @return ManufacturerList
     */
    public function getManufacturerTree()
    {
        return $this->_oManufacturerTree;
    }

    /**
     * Manufacturer tree setter
     *
     * @param ManufacturerList $manufacturerTree Manufacturer tree
     */
    public function setManufacturerTree($manufacturerTree)
    {
        $this->_oManufacturerTree = $manufacturerTree;
    }

    /**
     * Returns additional URL parameters which must be added to list products urls
     */
    public function getAddUrlParams()
    {
    }

    /**
     * Template variable getter. Returns Top 5 article list.
     * Parameter FrontendController::$_blTop5Action must be set to true.
     *
     * @param integer $count Product count in list
     *
     * @return array
     */
    public function getTop5ArticleList($count = null)
    {
        if ($this->_blTop5Action) {
            if ($this->_aTop5ArticleList === null) {
                $this->_aTop5ArticleList = false;
                $config = Registry::getConfig();
                if ($config->getConfigParam('bl_perfLoadAktion')) {
                    // top 5 articles
                    $artList = oxNew(ArticleList::class);
                    $artList->loadTop5Articles($count);
                    if ($artList->count()) {
                        $this->_aTop5ArticleList = $artList;
                    }
                }
            }
        }

        return $this->_aTop5ArticleList;
    }

    /**
     * Template variable getter. Returns bargain article list
     * Parameter FrontendController::$_blBargainAction must be set to true.
     *
     * @return array
     */
    public function getBargainArticleList()
    {
        if ($this->_blBargainAction) {
            if ($this->_aBargainArticleList === null) {
                $this->_aBargainArticleList = [];
                if (Registry::getConfig()->getConfigParam('bl_perfLoadAktion')) {
                    $articleList = oxNew(ArticleList::class);
                    $articleList->loadActionArticles('OXBARGAIN');
                    if ($articleList->count()) {
                        $this->_aBargainArticleList = $articleList;
                    }
                }
            }
        }

        return $this->_aBargainArticleList;
    }

    /**
     * Template variable getter. Returns if order price is lower than
     * minimum order price setup (config param "iMinOrderPrice")
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; use Basket method
     *
     * @return bool
     */
    public function isLowOrderPrice()
    {
        if ($this->_blLowOrderPrice === null && ($basket = Registry::getSession()->getBasket())) {
            $this->_blLowOrderPrice = $basket->isBelowMinOrderPrice();
        }

        return $this->_blLowOrderPrice;
    }

    /**
     * Template variable getter. Returns formatted min order price value
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; use Basket method
     *
     * @return string
     */
    public function getMinOrderPrice()
    {
        if ($this->_sMinOrderPrice === null && $this->isLowOrderPrice()) {
            $minOrderPrice = Price::getPriceInActCurrency(Registry::getConfig()->getConfigParam('iMinOrderPrice'));
            $this->_sMinOrderPrice = Registry::getLang()->formatCurrency($minOrderPrice);
        }

        return $this->_sMinOrderPrice;
    }

    /**
     * Template variable getter. Returns if newsletter is really active (for user.tpl)
     *
     * @return int|null
     */
    public function getNewsRealStatus()
    {
        return $this->_iNewsRealStatus;
    }

    /**
     * Checks if current request parameters does not block SEO redirection process
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "canRedirect" in next major
     */
    protected function _canRedirect() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        foreach ($this->_aBlockRedirectParams as $param) {
            if (Registry::getRequest()->getRequestEscapedParameter($param) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Empty active product getter
     */
    public function getProduct()
    {
    }

    /**
     * Template variable getter. Returns Manufacturer list for search
     *
     * @return array
     */
    public function getManufacturerList()
    {
        return $this->_aManufacturerlist;
    }

    /**
     * Sets Manufacturer list for search
     *
     * @param object $list manufacturer list
     */
    public function setManufacturerList($list)
    {
        $this->_aManufacturerlist = $list;
    }

    /**
     * Sets root vendor
     *
     * @param Vendor $vendor vendor object
     */
    public function setRootVendor($vendor)
    {
        $this->_oRootVendor = $vendor;
    }

    /**
     * Template variable getter. Returns root vendor
     *
     * @return Vendor
     */
    public function getRootVendor()
    {
        return $this->_oRootVendor;
    }

    /**
     * Sets root Manufacturer
     *
     * @param Manufacturer $manufacturer manufacturer object
     */
    public function setRootManufacturer($manufacturer)
    {
        $this->_oRootManufacturer = $manufacturer;
    }

    /**
     * Template variable getter. Returns root Manufacturer
     *
     * @return Manufacturer
     */
    public function getRootManufacturer()
    {
        return $this->_oRootManufacturer;
    }

    /**
     * Template variable getter. Returns vendor id
     *
     * @return string
     */
    public function getVendorId()
    {
        if ($this->_sVendorId === null) {
            $this->_sVendorId = false;
            if (($vendor = $this->getActVendor())) {
                $this->_sVendorId = $vendor->getId();
            }
        }

        return $this->_sVendorId;
    }

    /**
     * Template variable getter. Returns Manufacturer id
     *
     * @return string
     */
    public function getManufacturerId()
    {
        if ($this->_sManufacturerId === null) {
            $this->_sManufacturerId = false;
            if (($manufacturer = $this->getActManufacturer())) {
                $this->_sManufacturerId = $manufacturer->getId();
            }
        }

        return $this->_sManufacturerId;
    }

    /**
     * Template variable getter. Returns more category
     *
     * @return string
     */
    public function getCatMoreUrl()
    {
        return Registry::getConfig()->getShopHomeUrl() . 'cnid=oxmore';
    }

    /**
     * Template variable getter. Returns category path
     *
     * @return array
     */
    public function getCatTreePath()
    {
        return $this->_sCatTreePath;
    }

    /**
     * Loads and returns oxContent object requested by its ident
     *
     * @param string $ident content identifier
     *
     * @return Content
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getContentByIdent($ident)
    {
        if (!isset($this->_aContents[$ident])) {
            $this->_aContents[$ident] = oxNew(Content::class);
            $this->_aContents[$ident]->loadByIdent($ident);
        }

        return $this->_aContents[$ident];
    }

    /**
     * Default content category getter, returns FALSE by default
     *
     * @return bool
     */
    public function getContentCategory()
    {
        return false;
    }

    /**
     * Returns array of fields which must be filled during registration
     *
     * @return array | bool
     */
    public function getMustFillFields()
    {
        if ($this->_aMustFillFields === null) {
            $this->_aMustFillFields = false;

            // passing must-be-filled-fields info
            $mustFillFields = Registry::getConfig()->getConfigParam('aMustFillFields');
            if (is_array($mustFillFields)) {
                $this->_aMustFillFields = array_flip($mustFillFields);
            }
        }

        return $this->_aMustFillFields;
    }

    /**
     * Returns if field is required.
     *
     * @param string $field required field to check
     *
     * @return bool
     */
    public function isFieldRequired($field)
    {
        return isset($this->getMustFillFields()[$field]);
    }

    /**
     * Form id getter. This id used to prevent double review entry submit
     *
     * @return string
     */
    public function getFormId()
    {
        if ($this->_sFormId === null) {
            $this->_sFormId = Registry::getUtilsObject()->generateUId();
            Registry::getSession()->setVariable('sessionuformid', $this->_sFormId);
        }

        return $this->_sFormId;
    }

    /**
     * Checks if session form id matches with request form id
     *
     * @return bool
     */
    public function canAcceptFormData()
    {
        if ($this->_blCanAcceptFormData === null) {
            $this->_blCanAcceptFormData = false;

            $formId = Registry::getRequest()->getRequestEscapedParameter('uformid');
            $sessionFormId = Registry::getSession()->getVariable("sessionuformid");

            // testing if form and session ids matches
            if ($formId && $formId === $sessionFormId) {
                $this->_blCanAcceptFormData = true;
            }

            // regenerating form data
            $this->getFormId();
        }

        return $this->_blCanAcceptFormData;
    }

    /**
     * return last finished promotion list
     *
     * @return ActionList
     * @throws DatabaseConnectionException
     */
    public function getPromoFinishedList()
    {
        if (isset($this->_oPromoFinishedList)) {
            return $this->_oPromoFinishedList;
        }
        $this->_oPromoFinishedList = oxNew(ActionList::class);
        $this->_oPromoFinishedList->loadFinishedByCount(2);

        return $this->_oPromoFinishedList;
    }

    /**
     * return current promotion list
     *
     * @return ActionList
     */
    public function getPromoCurrentList()
    {
        if (isset($this->_oPromoCurrentList)) {
            return $this->_oPromoCurrentList;
        }
        $this->_oPromoCurrentList = oxNew(ActionList::class);
        $this->_oPromoCurrentList->loadCurrent();

        return $this->_oPromoCurrentList;
    }

    /**
     * return future promotion list
     *
     * @return ActionList
     * @throws DatabaseConnectionException
     */
    public function getPromoFutureList()
    {
        if (isset($this->_oPromoFutureList)) {
            return $this->_oPromoFutureList;
        }
        $this->_oPromoFutureList = oxNew(ActionList::class);
        $this->_oPromoFutureList->loadFutureByCount(2);

        return $this->_oPromoFutureList;
    }

    /**
     * should promotions list be shown?
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function getShowPromotionList()
    {
        if (isset($this->_blShowPromotions)) {
            return $this->_blShowPromotions;
        }
        $this->_blShowPromotions = false;
        if (oxNew(ActionList::class)->areAnyActivePromotions()) {
            $this->_blShowPromotions = (count($this->getPromoFinishedList()) + count($this->getPromoCurrentList()) +
                                        count($this->getPromoFutureList())) > 0;
        }

        return $this->_blShowPromotions;
    }

    /**
     * Checks if private sales is on
     *
     * @return bool
     */
    public function isEnabledPrivateSales()
    {
        if ($this->_blEnabledPrivateSales === null) {
            $this->_blEnabledPrivateSales = (bool) Registry::getConfig()->getConfigParam('blPsLoginEnabled');
            if ($this->_blEnabledPrivateSales && ($canPreview = Registry::getUtils()->canPreview()) !== null) {
                $this->_blEnabledPrivateSales = !$canPreview;
            }
        }

        return $this->_blEnabledPrivateSales;
    }

    /**
     * Returns input field validation error array (if available)
     *
     * @return array
     */
    public function getFieldValidationErrors()
    {
        return Registry::getInputValidator()->getFieldValidationErrors();
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return null
     */
    public function getBreadCrumb()
    {
        return null;
    }

    /**
     * Sets if active root category was changed
     *
     * @param bool $rootCategoryChanged root category changed
     */
    public function setRootCatChanged($rootCategoryChanged)
    {
        $this->_blRootCatChanged = $rootCategoryChanged;
    }

    /**
     * Template variable getter. Returns true if active root category was changed
     *
     * @return bool
     */
    public function isRootCatChanged()
    {
        return $this->_blRootCatChanged;
    }

    /**
     * Template variable getter. Returns user address
     *
     * @return array
     */
    public function getInvoiceAddress()
    {
        if ($this->_aInvoiceAddress == null) {
            $invoiceAddress = Registry::getRequest()->getRequestEscapedParameter('invadr');
            if ($invoiceAddress) {
                $this->_aInvoiceAddress = $invoiceAddress;
            }
        }

        return $this->_aInvoiceAddress;
    }

    /**
     * Template variable getter. Returns user delivery address
     *
     * @return array
     */
    public function getDeliveryAddress()
    {
        if ($this->_aDeliveryAddress == null) {
            $oRequest = Registry::getRequest();
            //do not show deladr if address was reloaded
            if (!$oRequest->getRequestEscapedParameter('reloadaddress')) {
                $this->_aDeliveryAddress = $oRequest->getRequestEscapedParameter('deladr');
            }
        }

        return $this->_aDeliveryAddress;
    }

    /**
     * Template variable setter. Sets user delivery address
     *
     * @param array $deliveryAddress delivery address
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->_aDeliveryAddress = $deliveryAddress;
    }

    /**
     * Template variable setter. Sets user address
     *
     * @param array $address user address
     */
    public function setInvoiceAddress($address)
    {
        $this->_aInvoiceAddress = $address;
    }

    /**
     * Template variable getter. Returns logged in user name
     *
     * @return string
     */
    public function getActiveUsername()
    {
        if ($this->_sActiveUsername == null) {
            $this->_sActiveUsername = false;
            $username = Registry::getRequest()->getRequestEscapedParameter('lgn_usr');
            if ($username) {
                $this->_sActiveUsername = $username;
            } elseif ($user = $this->getUser()) {
                $this->_sActiveUsername = $user->oxuser__oxusername->value;
            }
        }

        return $this->_sActiveUsername;
    }

    /**
     * Template variable getter. Returns user id from wish list
     *
     * @return string
     */
    public function getWishlistUserId()
    {
        return Registry::getRequest()->getRequestEscapedParameter('wishid');
    }

    /**
     * Template variable getter. Returns searched category id
     */
    public function getSearchCatId()
    {
    }

    /**
     * Template variable getter. Returns searched vendor id
     */
    public function getSearchVendor()
    {
    }

    /**
     * Template variable getter. Returns searched Manufacturer id
     */
    public function getSearchManufacturer()
    {
    }

    /**
     * Template variable getter. Returns last seen products
     */
    public function getLastProducts()
    {
    }

    /**
     * Returns added basket item notification message type
     *
     * @return int
     */
    public function getNewBasketItemMsgType()
    {
        return (int) Registry::getConfig()->getConfigParam("iNewBasketItemMessage");
    }

    /**
     * Checks if feature is enabled
     *
     * @param string $name feature name
     *
     * @return bool
     */
    public function isActive($name)
    {
        return Registry::getConfig()->getConfigParam("bl" . $name . "Enabled");
    }

    /**
     * Checks if downloadable files are turned on
     *
     * @return bool
     */
    public function isEnabledDownloadableFiles()
    {
        return (bool) Registry::getConfig()->getConfigParam("blEnableDownloads");
    }

    /**
     * Returns true if "Remember me" are ON
     *
     * @return boolean
     */
    public function showRememberMe()
    {
        return (bool) Registry::getConfig()->getConfigParam('blShowRememberMe');
    }

    /**
     * Returns true if articles shown in shop with VAT.
     * Checks country VAT and options (show vat only in basket and check if b2b mode is activated).
     *
     * @return boolean
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function isVatIncluded()
    {
        if ($this->_blIsVatIncluded !== null) {
            return $this->_blIsVatIncluded;
        }

        $config = Registry::getConfig();
        /*
         * Do not show "inclusive VAT" when:
         *
         *   B2B mode is activated
         * OR
         *   the VAT will only be calculated in the basket
         * OR
         *   the country does not bill VAT
         *
         * oxcountry__oxvatstatus: Vat status: 0 - Do not bill VAT, 1 - Do not bill VAT only if provided valid VAT ID
         * if country is not available (no session) oxvatstatus->value will return null
         */
        if ($config->getConfigParam('blShowNetPrice') || $config->getConfigParam('bl_perfCalcVatOnlyForBasketOrder')) {
            return $this->_blIsVatIncluded = false;
        }

        $user = $this->getUser();
        if ($user !== false) {
            if ($user->getFieldData('oxustid') && $user->getFieldData('oxustidstatus') == 1) {
                return $this->_blIsVatIncluded = false;
            }
        } else {
            $user = oxNew(User::class);
        }

        $activeCountry = $user->getActiveCountry();
        if ($activeCountry !== '') {
            $country = oxNew(Country::class);
            if (
                $country->load($activeCountry) &&
                $country->oxcountry__oxvatstatus->value !== null &&
                $country->oxcountry__oxvatstatus->value == 0
            ) {
                return $this->_blIsVatIncluded = false;
            }
        }

        return $this->_blIsVatIncluded = true;
    }

    /**
     * Returns true if price calculation is activated
     *
     * @return boolean
     */
    public function isPriceCalculated()
    {
        return (bool) Registry::getConfig()->getConfigParam('bl_perfLoadPrice');
    }

    /**
     * Template variable getter. Returns username of searched wishlist
     *
     * @return string
     */
    public function getWishlistName()
    {
        if ($this->getUser()) {
            $wishId = Registry::getRequest()->getRequestEscapedParameter('wishid');
            $userId = ($wishId) ? $wishId : Registry::getSession()->getVariable('wishid');
            if ($userId) {
                $wishUser = oxNew(User::class);
                if ($wishUser->load($userId)) {
                    return $wishUser;
                }
            }
        }

        return false;
    }

    /**
     * Get widget link for Ajax calls
     *
     * @return string
     */
    public function getWidgetLink()
    {
        return Registry::getConfig()->getWidgetUrl();
    }

    /**
     * Template variable getter. Returns article list count in comparison.
     *
     * @return integer
     */
    public function getCompareItemsCnt()
    {
        $compareController = oxNew(CompareController::class);

        return $compareController->getCompareItemsCnt();
    }

    /**
     * @param string $sortOrder
     *
     * @return bool
     */
    private function isAllowedSortingOrder($sortOrder)
    {
        $allowedSortOrders = array_merge((new SortingValidator())->getSortingOrders(), ['']);
        return in_array(strtolower($sortOrder), $allowedSortOrders);
    }

    /**
     * @param string $title
     * @return string
     */
    private function replaceDoubleQuotesWithHTMLCharacters(string $title): string
    {
        return str_replace('"', '&quot;', $title);
    }
}
