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

use Exception;
use OxidEsales\Eshop\Application\Model\Contract\ArticleInterface;
use OxidEsales\Eshop\Core\Contract\IUrl;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;

// defining supported link types
define('OXARTICLE_LINKTYPE_CATEGORY', 0);
define('OXARTICLE_LINKTYPE_VENDOR', 1);
define('OXARTICLE_LINKTYPE_MANUFACTURER', 2);
define('OXARTICLE_LINKTYPE_PRICECATEGORY', 3);
// @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
define('OXARTICLE_LINKTYPE_RECOMM', 5);
// END deprecated

/**
 * Article manager.
 * Creates fully detailed article object, with such information as VAT,
 * discounts, etc.
 *
 */
class Article extends MultiLanguageModel implements ArticleInterface, IUrl
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxarticle';

    /**
     * Set $_blUseLazyLoading to true if you want to load only actually used fields not full object, depending on views.
     *
     * @var bool
     */
    protected $_blUseLazyLoading = true;

    /**
     * item key the usage with oxuserbasketitems
     *
     * @var string (md5 hash)
     */
    protected $_sItemKey;

    /**
     * Variable controls price calculation type (set true, to calculate price
     * with taxes etc., or false to return base article price).
     *
     * @var bool
     */
    protected $_blCalcPrice = true;

    /**
     * Article oxPrice object.
     *
     * @var Price
     */
    protected $_oPrice = null;


    /**
     * cached article variant min price
     *
     * @var double | null
     */
    protected $_dVarMinPrice = null;

    /**
     * cached article variant max price
     *
     * @var double | null
     */
    protected $_dVarMaxPrice = null;

    /**
     * caches article vat
     *
     * @var double | null
     */
    protected $_dArticleVat = null;

    /**
     * Persistent Parameter.
     *
     * @var array
     */
    protected $_aPersistParam = null;

    /**
     * Status of article - buyable/not buyable.
     *
     * @var bool
     */
    protected $_blNotBuyable = false;

    /**
     * Indicates if we should load variants for current article. When $_blLoadVariants is set to false then
     * neither simple nor full variants for this article are loaded.
     *
     * @var bool
     */
    protected $_blLoadVariants = true;

    /**
     * Article variants without empty stock, not order-able flagged variants
     *
     * @var array
     */
    protected $_aVariants = null;

    /**
     * Article variants with empty stock, not order-able flagged variants
     *
     * @var array
     */
    protected $_aVariantsWithNotOrderables = null;

    /**
     * $_blNotBuyableParent is set to true, when article has variants and is not buyable due to:
     *      a) config option
     *      b) it is not active
     *      c) all variants are not active
     *
     * @var bool
     */
    protected $_blNotBuyableParent = false;


    /**
     * $_blHasVariants is set to true if article has any variants.
     */
    protected $_blHasVariants = false;

    /**
     * $_blHasVariants is set to true if article has multidimensional variants.
     */
    protected $_blHasMdVariants = false;

    /**
     * If set true, then this object is on comparison list
     *
     * @var bool
     */
    protected $_blIsOnComparisonList = false;

    /**
     * user object
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Performance issue. Sometimes you want to load articles without calculating
     * correct discounts and prices etc.
     *
     * @var bool
     */
    protected $_blLoadPrice = true;

    /**
     * $_fPricePerUnit holds price per unit value in active shop currency.
     * $_fPricePerUnit is calculated from Article::oxarticles__oxunitquantity->value
     * and from Article::oxarticles__oxunitname->value. If either one of these values is empty then $_fPricePerUnit is not calculated.
     * Example: In case when product price is 10 EUR and product quantity is 0.5 (liters) then $_fPricePerUnit would be 20,00
     */
    protected $_fPricePerUnit = null;

    /**
     * Variable used to force load parent data in export
     */
    protected $_blLoadParentData = false;

    /**
     * Variable used to determine if setting parentId to empty value is allowed
     */
    protected $_blAllowEmptyParentId = false;

    /**
     * Variable used to force load parent data in export
     */
    protected $_blSkipAssign = false;

    /**
     * Set $_blSkipDiscounts to true if you want to skip the discount.
     *
     * @var bool
     */
    protected $_blSkipDiscounts = null;

    /**
     * Object holding the list of attributes and attribute values associated with this article
     * @var AttributeList
     */
    protected $_oAttributeList = null;

    /**
     * Object holding the list of attributes and attribute values associated with this article and displayable in basket
     * @var AttributeList
     */
    protected $basketAttributeList = null;

    /**
     * Indicates whether the price is "From" price
     *
     * @var bool
     */
    protected $_blIsRangePrice = null;

    /**
     * The list of article media URLs
     *
     * @var string
     */
    protected $_aMediaUrls = null;

    /**
     * Array containing references to already loaded parent articles, in order for variant to skip parent data loading
     *
     * @var array
     */
    protected static $_aLoadedParents;

    /**
     * Cached select lists array
     *
     * @var array
     */
    protected static $_aSelList;

    /**
     * Select lists for tpl
     *
     * @var array
     */
    protected $_aDispSelList;

    /**
     * Marks that current object is managed by SEO
     *
     * @var bool
     */
    protected $_blIsSeoObject = true;

    /**
     * loaded amount prices
     *
     * @var AmountPriceList
     */
    protected $_oAmountPriceList = null;

    /**
     * Article details link type (default is 0):
     *     0 - category link
     *     1 - vendor link
     *     2 - manufacturer link
     *
     * @var int
     */
    protected $_iLinkType = 0;

    /**
     * Standard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_aStdUrls = [];

    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_aSeoUrls = [];

    /**
     * Additional parameters to seo urls
     *
     * @var array
     */
    protected $_aSeoAddParams = [];

    /**
     * Additional parameters to std urls
     *
     * @var array
     */
    protected $_aStdAddParams = [];

    /**
     * Image url
     *
     * @var string
     */
    protected $_sDynImageDir = null;

    /**
     * More details link
     *
     * @var string
     */
    protected $_sMoreDetailLink = null;

    /**
     * To basket link
     *
     * @var string
     */
    protected $_sToBasketLink = null;

    /**
     * Article stock status when article is initially loaded.
     *
     * @var int
     */
    protected $_iStockStatusOnLoad = null;

    /**
     * Article original parameters when loaded.
     *
     * @var array
     */
    protected $_aSortingFieldsOnLoad = [];

    /**
     * Stock status
     *
     * @var integer
     */
    protected $_iStockStatus = null;

    /**
     * T price
     *
     * @var Price
     */
    protected $_oTPrice = null;

    /**
     * Amount price list info
     *
     * @var object
     */
    protected $_oAmountPriceInfo = null;

    /**
     * Amount price
     *
     * @var double
     */
    protected $_dAmountPrice = null;

    /**
     * Articles manufacturer ids cache
     *
     * @var array
     */
    protected static $_aArticleManufacturers = [];

    /**
     * Articles vendor ids cache
     *
     * @var array
     */
    protected static $_aArticleVendors = [];

    /**
     * Articles category ids cache
     *
     * @var array
     */
    protected static $_aArticleCats = [];

    /**
     * Do not copy certain parent fields to variant
     *
     * @var array
     */
    protected $_aNonCopyParentFields = [
        'oxarticles__oxinsert',
        'oxarticles__oxtimestamp',
        'oxarticles__oxnid',
        'oxarticles__oxid',
        'oxarticles__oxparentid'
    ];

    /**
     * Override certain parent fields to variant
     *
     * @var array
     */
    protected $_aCopyParentField = [
        'oxarticles__oxnonmaterial',
        'oxarticles__oxfreeshipping',
        'oxarticles__oxisdownloadable',
        'oxarticles__oxshowcustomagreement'
    ];

    /**
     * Multidimensional variant tree structure
     *
     * @var MdVariant
     */
    protected $_oMdVariants = null;

    /**
     * Product long description field
     *
     * @var Field
     */
    protected $_oLongDesc = null;

    /**
     * Variant selections array
     *
     * @see getVariantSelections()
     *
     * @var array
     */
    protected $_aVariantSelections = [];

    /**
     * Array of product selections
     *
     * @var array
     */
    protected static $_aSelections = [];

    /**
     * Category instance cache
     *
     * @var array
     */
    protected static $_aCategoryCache = null;

    /**
     * stores if are stored any amount price
     *
     * @var bool
     */
    protected static $_blHasAmountPrice = null;

    /**
     * stores downloadable file list
     *
     * @var array|ListModel of oxArticleFile
     */
    protected $_aArticleFiles = null;

    /**
     * If admin can edit any field.
     *
     * @var bool
     */
    protected $_blCanUpdateAnyField = null;

    /**
     * Triggered action type
     *
     * @var integer
     */
    protected $actionType = ACTION_NA;

    /**
     * Constructor, sets shop ID for article (\OxidEsales\Eshop\Core\Config::getShopId()),
     * initiates parent constructor (parent::oxI18n()).
     *
     * @param array $aParams The array of names and values of oxArticle instance properties to be set on object instantiation
     */
    public function __construct($aParams = null)
    {
        if ($aParams && is_array($aParams)) {
            foreach ($aParams as $sParam => $mValue) {
                $this->$sParam = $mValue;
            }
        }
        parent::__construct();
        $this->init('oxarticles');
    }

    /**
     * Magic getter, deals with values which are loaded on demand.
     * Additionally, it sets default value for unknown picture fields
     *
     * @param string $sName Variable name
     *
     * @return mixed
     */
    public function __get($sName)
    {
        $this->$sName = parent::__get($sName);
        if ($this->$sName) {
            // since the field could have been loaded via lazy loading
            $this->_assignParentFieldValue($sName);
        }

        return $this->$sName;
    }

    /**
     * Sets article parameter
     *
     * @param string $sName  name of parameter to set
     * @param mixed  $sValue parameter value
     */
    public function __set($sName, $sValue)
    {
        parent::__set($sName, $sValue);
    }

    /**
     * @param AmountPriceList $amountPriceList
     */
    public function setAmountPriceList($amountPriceList)
    {
        $this->_oAmountPriceList = $amountPriceList;
    }

    /**
     * @return AmountPriceList
     */
    protected function getAmountPriceList()
    {
        return $this->_oAmountPriceList;
    }

    /**
     * Checks whether object is in list or not
     * It's needed for oxArticle so that it can pass this to widgets
     *
     * @return bool
     */
    public function isInList()
    {
        return $this->_isInList();
    }

    /**
     * Sets object ID, additionally sets $this->oxarticles__oxnid field value
     *
     * @param string $sId New ID
     *
     * @return string|null
     */
    public function setId($sId = null)
    {
        $sId = parent::setId($sId);

        // TODO: in \OxidEsales\Eshop\Core\Model\BaseModel::setId make it to check if exists and update, not recreate, then delete this overload
        $this->oxarticles__oxnid = $this->oxarticles__oxid;

        return $sId;
    }

    /**
     * Returns part of sql query used in active snippet. Query checks
     * if product "oxactive = 1". If config option "blUseTimeCheck" is TRUE
     * additionally checks if "oxactivefrom < current data < oxactiveto"
     *
     * @param bool $blForceCoreTable force core table usage
     *
     * @return string
     */
    public function getActiveCheckQuery($blForceCoreTable = null)
    {
        $sTable = $this->getViewName($blForceCoreTable);

        // check if article is still active
        $sQ = " $sTable.oxactive = 1 ";

        $sQ .= " and $sTable.oxhidden = 0 ";

        // enabled time range check ?
        if (Registry::getConfig()->getConfigParam('blUseTimeCheck')) {
            $sQ = $this->addSqlActiveRangeSnippet($sQ, $sTable);
        }

        return $sQ;
    }

    /**
     * Returns part of sql query used in active snippet. If config
     * option "blUseStock" is TRUE checks if "oxstockflag != 2 or
     * ( oxstock + oxvarstock ) > 0". If config option "blVariantParentBuyable"
     * is TRUE checks if product has variants, and if it has - checks is
     * there at least one variant which is buyable. If config option
     * "blUseTimeCheck" is TRUE additionally checks if variants
     * "oxactivefrom < current data < oxactiveto"
     *
     * @param bool $blForceCoreTable force core table usage
     *
     * @return string
     */
    public function getStockCheckQuery($blForceCoreTable = null)
    {
        $myConfig = Registry::getConfig();
        $sTable = $this->getViewName($blForceCoreTable);

        $sQ = "";

        //do not check for variants
        if ($myConfig->getConfigParam('blUseStock')) {
            $sQ = " and ( $sTable.oxstockflag != 2 or ( $sTable.oxstock + $sTable.oxvarstock ) > 0  ) ";
            //V #M513: When Parent article is not purchasable, it's visibility should be displayed in shop only if any of Variants is available.
            if (!$myConfig->getConfigParam('blVariantParentBuyable')) {
                $activeCheck = 'art.oxactive = 1';
                if ($myConfig->getConfigParam('blUseTimeCheck')) {
                    $activeCheck = $this->addSqlActiveRangeSnippet($activeCheck, 'art');
                }
                $sQ = " $sQ and IF( $sTable.oxvarcount = 0, 1, ( select 1 from $sTable as art where art.oxparentid=$sTable.oxid and $activeCheck and ( art.oxstockflag != 2 or art.oxstock > 0 ) limit 1 ) ) ";
            }
        }

        return $sQ;
    }

    /**
     * Returns part of query which checks if product is variant of current
     * object. Additionally, if config option "blUseStock" is TRUE checks
     * stock state "( oxstock > 0 or ( oxstock <= 0 and ( oxstockflag = 1
     * or oxstockflag = 4 ) ) )"
     *
     * @param bool $blRemoveNotOrderables remove or leave non order-able products
     * @param bool $blForceCoreTable      force core table usage
     *
     * @return string
     */
    public function getVariantsQuery($blRemoveNotOrderables, $blForceCoreTable = null)
    {
        $sTable = $this->getViewName($blForceCoreTable);
        $sQ = " and $sTable.oxparentid = '" . $this->getId() . "' ";

        //checking if variant is active and stock status
        if (Registry::getConfig()->getConfigParam('blUseStock')) {
            $sQ .= " and ( $sTable.oxstock > 0 or ( $sTable.oxstock <= 0 and $sTable.oxstockflag != 2 ";
            if ($blRemoveNotOrderables) {
                $sQ .= " and $sTable.oxstockflag != 3 ";
            }
            $sQ .= " ) ) ";
        }

        return $sQ;
    }

    /**
     * Return unit quantity
     *
     * @return string
     */
    public function getUnitQuantity()
    {
        return $this->oxarticles__oxunitquantity->value;
    }

    /**
     * Return Size of product: length*width*height
     *
     * @return double
     */
    public function getSize()
    {
        return $this->oxarticles__oxlength->value *
                 $this->oxarticles__oxwidth->value *
                 $this->oxarticles__oxheight->value;
    }

    /**
     * Return product weight
     *
     * @return double
     */
    public function getWeight()
    {
        return $this->oxarticles__oxweight->value;
    }

    /**
     * Returns SQL select string with checks if items are available
     *
     * @param bool $blForceCoreTable forces core table usage (optional)
     *
     * @return string
     */
    public function getSqlActiveSnippet($blForceCoreTable = null)
    {
        return "( {$this->_createSqlActiveSnippet($blForceCoreTable)} ) ";
    }

    /**
     *
     * Getter for action type.
     *
     * @return int
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Returns SQL select string with checks if items are available
     *
     * @param bool $forceCoreTable forces core table usage (optional)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "createSqlActiveSnippet" in next major
     */
    protected function _createSqlActiveSnippet($forceCoreTable) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // check if article is still active
        $sQ = $this->getActiveCheckQuery($forceCoreTable);

        // stock and variants check
        $sQ .= $this->getStockCheckQuery($forceCoreTable);

        return $sQ;
    }

    /**
     * Assign condition setter. In case article assignment is skipped ($_blSkipAssign = true), it does not perform additional
     *
     * @param bool $blSkipAssign Whether to skip assign process for the article
     */
    public function setSkipAssign($blSkipAssign)
    {
        $this->_blSkipAssign = $blSkipAssign;
    }

    /**
     * Disables article price loading. Should be called before assign(), or load()
     */
    public function disablePriceLoad()
    {
        $this->_blLoadPrice = false;
    }

    /**
     * Enable article price loading, if disabled.
     */
    public function enablePriceLoad()
    {
        $this->_blLoadPrice = true;
    }

    /**
     * Returns item key used with oxuserbasket
     *
     * @return string
     */
    public function getItemKey()
    {
        return $this->_sItemKey;
    }

    /**
     * Sets item key used with oxuserbasket
     *
     * @param string $sItemKey Item key
     */
    public function setItemKey($sItemKey)
    {
        $this->_sItemKey = $sItemKey;
    }

    /**
     * Disables/enables variant loading
     *
     * @param bool $blLoadVariants skip variant loading or not
     */
    public function setNoVariantLoading($blLoadVariants)
    {
        $this->_blLoadVariants = !$blLoadVariants;
    }

    /**
     * Checks if article is buyable.
     *
     * @return bool
     */
    public function isBuyable()
    {
        return !($this->_blNotBuyableParent || $this->_blNotBuyable);
    }

    /**
     * Checks if price alarm is enabled.
     *
     * @return bool
     */
    public function isPriceAlarm()
    {
        // #419 disabling price alarm if article has fixed price
        return !(($this->__isset('oxarticles__oxblfixedprice') || $this->__get('oxarticles__oxblfixedprice')) && $this->__get('oxarticles__oxblfixedprice')->value);
    }

    /**
     * Get persistent parameters
     *
     * @deprecated on b-dev (2015-11-30); Not used anymore. Setting pers params to session was removed since 2.7.
     *
     * @return array
     */
    public function getPersParams()
    {
        return $this->_aPersistParam;
    }

    /**
     * Checks whether article is included in comparison list
     *
     * @return bool
     */
    public function isOnComparisonList()
    {
        return $this->_blIsOnComparisonList;
    }

    /**
     * Set if article is included in comparison list
     *
     * @param bool $blOnList Whether is article on the list
     */
    public function setOnComparisonList($blOnList)
    {
        $this->_blIsOnComparisonList = $blOnList;
    }

    /**
     * A setter for $_blLoadParentData (whether article parent info should be loaded fully) class variable
     *
     * @param bool $blLoadParentData Whether to load parent data
     */
    public function setLoadParentData($blLoadParentData)
    {
        $this->_blLoadParentData = $blLoadParentData;
    }

    /**
     * Getter for do we load parent data
     *
     * @return bool
     */
    public function getLoadParentData()
    {
        return $this->_blLoadParentData;
    }

    /**
     * Returns true if the field is multilanguage
     *
     * @param string $sFieldName Field name
     *
     * @return bool
     */
    public function isMultilingualField($sFieldName)
    {
        if ('oxlongdesc' == $sFieldName) {
            return true;
        }

        return parent::isMultilingualField($sFieldName);
    }

    /**
     * Returns formatted price per unit
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated since v5.1 (2013-09-25); use oxPrice smarty plugin for formatting in templates
     */
    public function getFUnitPrice()
    {
        if ($this->_fPricePerUnit == null) {
            if ($oPrice = $this->getUnitPrice()) {
                if ($dPrice = $this->_getPriceForView($oPrice)) {
                    $this->_fPricePerUnit = Registry::getLang()->formatCurrency($dPrice);
                }
            }
        }

        return $this->_fPricePerUnit;
    }

    /**
     * Returns price per unit
     *
     * @return Price|null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getUnitPrice()
    {
        // Performance
        if (!Registry::getConfig()->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice) {
            return null;
        }

        $oPrice = null;
        if ((double) $this->getUnitQuantity() && $this->oxarticles__oxunitname->value) {
            $oPrice = clone $this->getPrice();
            $oPrice->divide((double) $this->getUnitQuantity());
        }

        return $oPrice;
    }

    /**
     * Returns formatted article min price
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws DatabaseErrorException
     * @deprecated since v5.1 (2013-10-04); use oxPrice smarty plugin for formatting in templates
     *
     */
    public function getFMinPrice()
    {
        $sPrice = '';
        if ($oPrice = $this->getMinPrice()) {
            $dPrice = $this->_getPriceForView($oPrice);
            $sPrice = Registry::getLang()->formatCurrency($dPrice);
        }

        return $sPrice;
    }

    /**
     * Returns formatted min article variant price
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws DatabaseErrorException
     * @deprecated since v5.1 (2013-10-04); use oxPrice smarty plugin for formatting in templates
     *
     */
    public function getFVarMinPrice()
    {
        $sPrice = '';
        if ($oPrice = $this->getVarMinPrice()) {
            $dPrice = $this->_getPriceForView($oPrice);
            $sPrice = Registry::getLang()->formatCurrency($dPrice);
        }

        return $sPrice;
    }

    /**
     * Returns article min price of variants
     *
     * @return Price|void
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws DatabaseErrorException
     */
    public function getVarMinPrice()
    {
        if (!Registry::getConfig()->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice) {
            return;
        }

        $dPrice = $this->_calculateVarMinPrice();

        $oPrice = $this->_getPriceObject();
        $oPrice->setPrice($dPrice);

        $this->_calculatePrice($oPrice);

        return $oPrice;
    }

    /**
     * Calculates lowest price of available article variants.
     *
     * @return double
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "calculateVarMinPrice" in next major
     */
    protected function _calculateVarMinPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $dPrice = $this->_getVarMinPrice();

        return $this->_preparePrice($dPrice, $this->getArticleVat());
    }

    /**
     * Returns article min price in calculation included variants
     *
     * @return Price|void
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws DatabaseErrorException
     */
    public function getMinPrice()
    {
        if (!Registry::getConfig()->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice) {
            return;
        }

        $dPrice = $this->_getPrice();
        if ($this->_getVarMinPrice() !== null && $dPrice > $this->_getVarMinPrice()) {
            $dPrice = $this->_getVarMinPrice();
        }

        $dPrice = $this->_prepareModifiedPrice($dPrice);

        $oPrice = $this->_getPriceObject();
        $oPrice->setPrice($dPrice);
        $this->_calculatePrice($oPrice);

        return $oPrice;
    }

    /**
     * @param double $dPrice
     *
     * @return double
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareModifiedPrice" in next major
     */
    protected function _prepareModifiedPrice($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $dPrice = $this->_preparePrice($dPrice, $this->getArticleVat());

        return $dPrice;
    }

    /**
     * Returns true if article has variant with different price
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function isRangePrice()
    {
        if ($this->_blIsRangePrice === null) {
            $this->setRangePrice(false);

            if ($this->_hasAnyVariant()) {
                $dPrice = $this->_getPrice();
                $dMinPrice = $this->_getVarMinPrice();
                $dMaxPrice = $this->_getVarMaxPrice();

                if ($dMinPrice != $dMaxPrice) {
                    $this->setRangePrice();
                } elseif (!$this->isParentNotBuyable() && $dMinPrice != $dPrice) {
                    $this->setRangePrice();
                }
            }
        }

        return $this->_blIsRangePrice;
    }


    /**
     * Setter to set if article has range price
     *
     * @param bool $blIsRangePrice - true if ranged, else false
     *
     * @return null
     */
    public function setRangePrice($blIsRangePrice = true)
    {
        return $this->_blIsRangePrice = $blIsRangePrice;
    }

    /**
     * Checks if article has visible status. Returns TRUE if its visible
     *
     * @return bool
     */
    public function isVisible()
    {
        // admin preview mode
        if (($blCanPreview = Registry::getUtils()->canPreview()) !== null) {
            return $blCanPreview;
        }

        // active ?
        $sNow = date('Y-m-d H:i:s');
        if (
            !$this->oxarticles__oxactive->value &&
            (
                $this->oxarticles__oxactivefrom->value > $sNow ||
             $this->oxarticles__oxactiveto->value < $sNow
            )
        ) {
            return false;
        }

        // stock flags
        if (Registry::getConfig()->getConfigParam('blUseStock') && $this->oxarticles__oxstockflag->value == 2) {
            $iOnStock = $this->oxarticles__oxstock->value + $this->oxarticles__oxvarstock->value;
            if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
                $iOnStock += Registry::getSession()->getBasketReservations()->getReservedAmount($this->getId());
            }
            if ($iOnStock <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assigns to oxarticle object some base parameters/values (such as
     * detail-link, more-detail-link, etc).
     *
     * @param array $aRecord Array representing current field values
     *
     * @return void
     */
    public function assign($aRecord)
    {
        startProfile('articleAssign');

        // load object from database
        parent::assign($aRecord);

        //clear seo urls
        $this->_aSeoUrls = [];

        $this->oxarticles__oxnid = $this->oxarticles__oxid;

        // check for simple article.
        if ($this->_blSkipAssign) {
            return;
        }

        $this->_assignParentFieldValues();
        $this->_assignNotBuyableParent();

        // assign only for a first load time
        if (!$this->isLoaded()) {
            $this->_setShopValues($this);
        }

        $this->_assignStock();
        $this->_assignPersistentParam();
        $this->_assignDynImageDir();
        $this->_assignComparisonListFlag();

        stopProfile('articleAssign');
    }

    /**
     * @param Article $article
     * @deprecated underscore prefix violates PSR12, will be renamed to "setShopValues" in next major
     */
    protected function _setShopValues($article) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Loads object data from DB (object data ID must be passed to method).
     * Converts dates (Article::oxarticles__oxinsert)
     * to international format (oxUtils.php \OxidEsales\Eshop\Core\Registry::getUtilsDate()->formatDBDate(...)).
     * Returns true if article was loaded successfully.
     *
     * @param string $sOXID Article object ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function load($sOXID)
    {
        // A. #1325 resetting to avoid problems when reloading (details etc.)
        $this->_blNotBuyableParent = false;

        $aData = $this->_loadData($sOXID);

        if ($aData) {
            $this->assign($aData);

            $this->_saveSortingFieldValuesOnLoad();

            $this->_iStockStatusOnLoad = $this->_iStockStatus;

            $this->_isLoaded = true;

            return true;
        }

        return false;
    }

    /**
     * Loads data from database and returns it.
     *
     * @param string $articleId
     *
     * @return array
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadData" in next major
     */
    protected function _loadData($articleId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_loadFromDb($articleId);
    }

    /**
     * Checks whether sorting fields changed from last article loading.
     *
     * @return bool
     */
    public function hasSortingFieldsChanged()
    {
        $aSortingFields = Registry::getConfig()->getConfigParam('aSortCols');
        $aSortingFields = !empty($aSortingFields) ? (array) $aSortingFields : [];
        $blChanged = false;
        foreach ($aSortingFields as $sField) {
            $sParameterName = 'oxarticles__' . $sField;
            $currentValueOfField = $this->$sParameterName instanceof Field ? $this->$sParameterName->value : '';
            $valueOfFieldOnLoad = $this->_aSortingFieldsOnLoad[$sParameterName] ?? null;
            if ($valueOfFieldOnLoad !== $currentValueOfField) {
                $blChanged = true;
                break;
            }
        }

        return $blChanged;
    }

    /**
     * Calculates and saves product rating average
     *
     * @param integer $rating new rating value
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addToRatingAverage($rating)
    {
        $dOldRating = $this->oxarticles__oxrating->value;
        $dOldCnt = $this->oxarticles__oxratingcnt->value;
        $this->oxarticles__oxrating->setValue(($dOldRating * $dOldCnt + $rating) / ($dOldCnt + 1));
        $this->oxarticles__oxratingcnt->setValue($dOldCnt + 1);
        $dRating = ($dOldRating * $dOldCnt + $rating) / ($dOldCnt + 1);
        $dRatingCnt = (int) ($dOldCnt + 1);
        // oxarticles.oxtimestamp = oxarticles.oxtimestamp to keep old timestamp value
        $oDb = DatabaseProvider::getDb();
        $query = "update oxarticles
                  set oxarticles.oxrating = :oxrating,
                      oxarticles.oxratingcnt = :oxratingcnt,
                      oxarticles.oxtimestamp = oxarticles.oxtimestamp
                  where oxarticles.oxid = :oxid";
        $oDb->execute($query, [
            ':oxrating' => $dRating,
            ':oxratingcnt' => $dRatingCnt,
            ':oxid' => $this->getId()
        ]);
    }

    /**
     * Set product rating average
     *
     * @param integer $iRating new rating value
     */
    public function setRatingAverage($iRating)
    {
        $this->oxarticles__oxrating = new Field($iRating);
    }

    /**
     * Set product rating count
     *
     * @param integer $iRatingCnt new rating count
     */
    public function setRatingCount($iRatingCnt)
    {
        $this->oxarticles__oxratingcnt = new Field($iRatingCnt);
    }

    /**
     * Returns product rating average
     *
     * @param bool $blIncludeVariants - include variant ratings
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getArticleRatingAverage($blIncludeVariants = false)
    {
        if (!$blIncludeVariants) {
            return round($this->oxarticles__oxrating->value, 1);
        } else {
            $oRating = oxNew(Rating::class);

            return $oRating->getRatingAverage($this->getId(), 'oxarticle', $this->getVariantIds());
        }
    }

    /**
     * Returns product rating count
     *
     * @param bool $blIncludeVariants - include variant ratings
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getArticleRatingCount($blIncludeVariants = false)
    {
        if (!$blIncludeVariants) {
            return $this->oxarticles__oxratingcnt->value;
        } else {
            $oRating = oxNew(Rating::class);

            return $oRating->getRatingCount($this->getId(), 'oxarticle', $this->getVariantIds());
        }
    }


    /**
     * Collects user written reviews about an article.
     *
     * @return ListModel
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getReviews()
    {
        $aIds = [$this->getId()];

        if ($this->oxarticles__oxparentid->value) {
            $aIds[] = $this->oxarticles__oxparentid->value;
        }

        // showing variant reviews ...
        if (Registry::getConfig()->getConfigParam('blShowVariantReviews')) {
            $aAdd = $this->getVariantIds();
            if (is_array($aAdd)) {
                $aIds = array_merge($aIds, $aAdd);
            }
        }

        $oReview = oxNew(Review::class);
        $oRevs = $oReview->loadList('oxarticle', $aIds);

        //if no review found, return null
        if ($oRevs->count() < 1) {
            return null;
        }

        return $oRevs;
    }

    /**
     * Loads and returns array with cross-selling information.
     *
     * @return Articlelist|void
     */
    public function getCrossSelling()
    {
        $oCrossList = oxNew(ArticleList::class);
        $oCrossList->loadArticleCrossSell($this->oxarticles__oxid->value);
        if ($oCrossList->count()) {
            return $oCrossList;
        }
    }

    /**
     * Loads and returns array with accessories information.
     *
     * @return array|void
     */
    public function getAccessoires()
    {
        $myConfig = Registry::getConfig();

        // Performance
        if (!$myConfig->getConfigParam('bl_perfLoadAccessoires')) {
            return;
        }

        $oAccList = oxNew(ArticleList::class);
        $oAccList->setSqlLimit(0, $myConfig->getConfigParam('iNrofCrossellArticles'));
        $oAccList->loadArticleAccessoires($this->oxarticles__oxid->value);

        if ($oAccList->count()) {
            return $oAccList;
        }
    }

    /**
     * Returns a list of similar products.
     *
     * @return array|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getSimilarProducts()
    {
        // Performance
        $myConfig = Registry::getConfig();
        if (!$myConfig->getConfigParam('bl_perfLoadSimilar')) {
            return;
        }

        // Check configured number of similar products (bug #6062)
        if ($myConfig->getConfigParam('iNrofSimilarArticles') < 1) {
            return;
        }

        $sArticleTable = $this->getViewName();

        $sAttribs = '';
        $iCnt = 0;
        $this->_getAttribsString($sAttribs, $iCnt);

        if (!$sAttribs) {
            return null;
        }

        $aList = $this->_getSimList($sAttribs, $iCnt);

        if (count($aList)) {
            uasort($aList, function ($a, $b) {
                if ($a->cnt == $b->cnt) {
                    return 0;
                }
                return ($a->cnt < $b->cnt) ? -1 : 1;
            });

            $sSearch = $this->_generateSimListSearchStr($sArticleTable, $aList);

            $oSimilarList = oxNew(ArticleList::class);
            $oSimilarList->setSqlLimit(0, $myConfig->getConfigParam('iNrofSimilarArticles'));
            $oSimilarList->selectString($sSearch);

            return $oSimilarList;
        }
    }

    /**
     * Loads and returns articles list, bought by same customer.
     *
     * @return ArticleList|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCustomerAlsoBoughtThisProducts()
    {
        // Performance
        $myConfig = Registry::getConfig();
        if (!$myConfig->getConfigParam('bl_perfLoadCustomerWhoBoughtThis')) {
            return;
        }

        // selecting products that fits
        $sQ = $this->_generateSearchStrForCustomerBought();

        $oArticles = oxNew(ArticleList::class);
        $oArticles->setSqlLimit(0, $myConfig->getConfigParam('iNrofCustomerWhoArticles'));
        $oArticles->selectString($sQ);
        if ($oArticles->count()) {
            return $oArticles;
        }
    }

    /**
     * Returns list object with info about article price that depends on amount in basket.
     * Takes data from oxprice2article table. Returns false if such info is not set.
     *
     * @return array|object|null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function loadAmountPriceInfo()
    {
        $myConfig = Registry::getConfig();
        if (!$myConfig->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice || !$this->_blCalcPrice || !$this->hasAmountPrice()) {
            return [];
        }

        if ($this->_oAmountPriceInfo === null) {
            $this->_oAmountPriceInfo = [];
            if (count(($aAmPriceList = $this->_getAmountPriceList()->getArray()))) {
                $this->_oAmountPriceInfo = $this->_fillAmountPriceList($aAmPriceList);
            }
        }

        return $this->_oAmountPriceInfo;
    }

    /**
     * Returns all selectlists this article has (used in Basket)
     *
     * @param null $sKeyPrefix Optional key prefix
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getSelectLists($sKeyPrefix = null)
    {
        //#1468C - more than one article in basket with different selectlist...
        //optionally function parameter $sKeyPrefix added, used only in basket.php
        $sKey = $this->getId();
        if (isset($sKeyPrefix)) {
            $sKey = $sKeyPrefix . '__' . $sKey;
        }

        if (!isset(self::$_aSelList[$sKey])) {
            $sSLViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxselectlist');

            $sQ = "select {$sSLViewName}.* from oxobject2selectlist join {$sSLViewName} on $sSLViewName.oxid=oxobject2selectlist.oxselnid
                   where oxobject2selectlist.oxobjectid = :oxobjectid order by oxobject2selectlist.oxsort";

            // all selectlists this article has
            $oLists = oxNew(ListModel::class);
            $oLists->init('oxselectlist');
            $oLists->selectString($sQ, [':oxobjectid' => $this->getId()]);

            //#1104S if this is variant ant it has no selectlists, trying with parent
            if ($oLists->count() == 0 && $this->oxarticles__oxparentid->value) {
                $oLists->selectString($sQ, [':oxobjectid' => $this->oxarticles__oxparentid->value]);
            }

            // We do not need to calculate price here as there are method to get current article vat
            /*if ( $this->getPrice() != null ) {
                $dVat = $this->getPrice()->getVat();
            }*/
            $dVat = $this->getArticleVat();

            $iCnt = 0;
            self::$_aSelList[$sKey] = [];
            foreach ($oLists as $oSelectlist) {
                self::$_aSelList[$sKey][$iCnt] = $oSelectlist->getFieldList($dVat);
                self::$_aSelList[$sKey][$iCnt]['name'] = $oSelectlist->oxselectlist__oxtitle->value;
                $iCnt++;
            }
        }

        return self::$_aSelList[$sKey];
    }

    /**
     * Returns amount of variants article has
     *
     * @return mixed
     */
    public function getVariantsCount()
    {
        return $this->oxarticles__oxvarcount->value;
    }

    /**
     * Checks if article has multidimensional variants
     *
     * @return bool
     */
    public function hasMdVariants()
    {
        return $this->_blHasMdVariants;
    }

    /**
     * Returns if article has intangible agreement with which customer will have to agree.
     *
     * @return bool
     */
    public function hasIntangibleAgreement()
    {
        return $this->oxarticles__oxshowcustomagreement->value && $this->oxarticles__oxnonmaterial->value && !$this->hasDownloadableAgreement();
    }

    /**
     * Returns if article has downloadable agreement with which customer will have to agree.
     *
     * @return bool
     */
    public function hasDownloadableAgreement()
    {
        return $this->oxarticles__oxshowcustomagreement->value && $this->oxarticles__oxisdownloadable->value;
    }

    /**
     * Returns variants selections lists array
     *
     * @param array|null $aFilterIds ids of active selections [optional]
     * @param string|null $sActVariantId active variant id [optional]
     * @param int $iLimit limit variant lists count (if non-zero, return limited number of multidimensional variant selections)
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getVariantSelections($aFilterIds = null, $sActVariantId = null, $iLimit = 0)
    {
        $iLimit = (int) $iLimit;
        if (!isset($this->_aVariantSelections[$iLimit])) {
            $aVariantSelections = false;
            if ($this->oxarticles__oxvarcount->value) {
                $oVariants = $this->getVariants(false);
                $aVariantSelections = oxNew(VariantHandler::class)->buildVariantSelections(
                    $this->oxarticles__oxvarname->getRawValue(),
                    $oVariants,
                    $aFilterIds,
                    $sActVariantId,
                    $iLimit
                );

                if (!empty($oVariants) && empty($aVariantSelections['rawselections'])) {
                    $aVariantSelections = false;
                }
            }
            $this->_aVariantSelections[$iLimit] = $aVariantSelections;
        }

        return $this->_aVariantSelections[$iLimit];
    }

    /**
     * Returns product selections lists array (used in azure theme)
     *
     * @param null $iLimit if given - will load limited count of selections [optional]
     * @param null $aFilter selection filter [optional]
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getSelections($iLimit = null, $aFilter = null)
    {
        $sId = $this->getId() . ((int) $iLimit);
        if (!array_key_exists($sId, self::$_aSelections)) {
            $sSLViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxselectlist');

            $sQ = "select {$sSLViewName}.* from oxobject2selectlist join {$sSLViewName} on $sSLViewName.oxid=oxobject2selectlist.oxselnid
                   where oxobject2selectlist.oxobjectid = :oxobjectid order by oxobject2selectlist.oxsort";

            if (($iLimit = (int) $iLimit)) {
                $sQ .= " limit $iLimit ";
            }

            // vat value for price
            $dVat = 0;
            if (($oPrice = $this->getPrice()) != null) {
                $dVat = $oPrice->getVat();
            }

            // all selectlists this article has
            $oList = oxNew(ListModel::class);
            $oList->init('oxselectlist');
            $oList->getBaseObject()->setVat($dVat);
            $oList->selectString($sQ, [':oxobjectid' => $this->getId()]);

            //#1104S if this is variant and has no selectlists, trying with parent
            if ($oList->count() == 0 && $this->oxarticles__oxparentid->value) {
                $oList->selectString($sQ, [':oxobjectid' => $this->oxarticles__oxparentid->value]);
            }

            self::$_aSelections[$sId] = $oList->count() ? $oList : false;
        }

        if (self::$_aSelections[$sId]) {
            // marking active from filter
            $aFilter = ($aFilter === null) ? Registry::getRequest()->getRequestEscapedParameter("sel") : $aFilter;
            if ($aFilter) {
                $iSelIdx = 0;
                foreach (self::$_aSelections[$sId] as $oSelection) {
                    if (isset($aFilter[$iSelIdx])) {
                        $oSelection->setActiveSelectionByIndex($aFilter[$iSelIdx]);
                    }
                    $iSelIdx++;
                }
            }
        }

        return self::$_aSelections[$sId];
    }

    /**
     * Returns variant list (list contains oxArticle objects)
     *
     * @param bool $blRemoveNotOrderables if true, removes from list not order-able articles, which are out of stock [optional]
     * @param null $blForceCoreTable if true forces core table use, default is false [optional]
     *
     * @return ArticleList
     * @throws DatabaseConnectionException
     */
    public function getFullVariants($blRemoveNotOrderables = true, $blForceCoreTable = null)
    {
        return $this->_loadVariantList(false, $blRemoveNotOrderables, $blForceCoreTable);
    }

    /**
     * Collects and returns article variants.
     * Note: Only active variants are returned by this method. If you need full variant list use Article::getAdminVariants()
     *
     * @param bool $blRemoveNotOrderables if true, removes from list not order-able articles, which are out of stock
     * @param null $blForceCoreTable if true forces core table use, default is false [optional]
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getVariants($blRemoveNotOrderables = true, $blForceCoreTable = null)
    {
        return $this->_loadVariantList($this->_isInList(), $blRemoveNotOrderables, $blForceCoreTable);
    }

    /**
     * Simple way to get variants without querying oxArticle table first. This is basically used for lists.
     *
     * @return array|Simplevariantlist|Articlelist|void
     * @throws DatabaseConnectionException
     */
    public function getSimpleVariants()
    {
        if ($this->oxarticles__oxvarcount->value) {
            return $this->getVariants();
        }
    }

    /**
     * Loads article variants and returns variants list object. Article language may
     * be set by passing with parameter, or GET/POST/Session variable.
     *
     * @param string $sLanguage shop language.
     *
     * @return object
     */
    public function getAdminVariants($sLanguage = null)
    {
        $oVariants = oxNew(ArticleList::class);
        if (($sId = $this->getId())) {
            $oBaseObj = $oVariants->getBaseObject();

            if (is_null($sLanguage)) {
                $oBaseObj->setLanguage(Registry::getLang()->getBaseLanguage());
            } else {
                $oBaseObj->setLanguage($sLanguage);
            }

            $sSql = "select * from " . $oBaseObj->getViewName() . " 
                where oxparentid = :oxparentid 
                order by oxsort ";
            $oVariants->selectString($sSql, [':oxparentid' => $sId]);

            //if we have variants then depending on config option the parent may be non-buyable
            if (!Registry::getConfig()->getConfigParam('blVariantParentBuyable') && ($oVariants->count() > 0)) {
                //$this->blNotBuyable = true;
                $this->_blNotBuyableParent = true;
            }
        }

        return $oVariants;
    }

    /**
     * Loads and returns article category object. First tries to load
     * assigned category and is such category does not exist, tries to
     * load category by price
     *
     * @return Category
     * @throws DatabaseConnectionException
     */
    public function getCategory()
    {
        $oCategory = oxNew(Category::class);
        $oCategory->setLanguage($this->getLanguage());

        // variant handling
        $sOXID = $this->getId();
        if (isset($this->oxarticles__oxparentid->value) && $this->oxarticles__oxparentid->value) {
            $sOXID = $this->oxarticles__oxparentid->value;
        }

        if ($sOXID) {
            // if the oxcategory instance of this article is not cached
            if (!isset($this->_aCategoryCache[$sOXID])) {
                startProfile('getCategory');
                $oStr = Str::getStr();
                $sWhere = $oCategory->getSqlActiveSnippet();
                $sSelect = $this->_generateSearchStr($sOXID);
                $sSelect .= ($oStr->strstr(
                    $sSelect,
                    'where'
                ) ? ' and ' : ' where ') . $sWhere . " order by oxobject2category.oxtime limit 1";

                // category not found ?
                if (!$oCategory->assignRecord($sSelect)) {
                    $sSelect = $this->_generateSearchStr($sOXID, true);
                    $sSelect .= ($oStr->strstr($sSelect, 'where') ? ' and ' : ' where ') . $sWhere . " limit 1";

                    // looking for price category
                    if (!$oCategory->assignRecord($sSelect)) {
                        $oCategory = null;
                    }
                }
                // add the category instance to cache
                $this->_aCategoryCache[$sOXID] = $oCategory;
                stopPRofile('getCategory');
            } else {
                // if the oxcategory instance is cached
                $oCategory = $this->_aCategoryCache[$sOXID];
            }
        }

        return $oCategory;
    }

    /**
     * Returns ID's of categories where this article is assigned
     *
     * @param bool $blActCats select categories if all parents are active
     * @param bool $blSkipCache Whether to skip cache
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCategoryIds($blActCats = false, $blSkipCache = false)
    {
        $sArticleId = $this->getId();

        if (!isset(self::$_aArticleCats[$sArticleId]) || $blSkipCache) {
            $sSql = $this->_getCategoryIdsSelect($blActCats);
            $aCategoryIds = $this->_selectCategoryIds($sSql, 'oxcatnid');

            $sSql = $this->getSqlForPriceCategories();
            $aPriceCategoryIds = $this->_selectCategoryIds($sSql, 'oxid');

            self::$_aArticleCats[$sArticleId] = array_unique(array_merge($aCategoryIds, $aPriceCategoryIds));
        }

        return self::$_aArticleCats[$sArticleId];
    }

    /**
     * Returns current article vendor object. If $blShopCheck = false, then
     * vendor loading will fall back to oxI18n object and blReadOnly parameter
     * will be set to true if vendor is not assigned to current shop
     *
     * @param bool $blShopCheck Set false if shop check is not required (default is true)
     *
     * @return object
     */
    public function getVendor($blShopCheck = true)
    {
        $sVendorId = $this->getVendorId();
        if ($sVendorId) {
            $oVendor = oxNew(Vendor::class);
        } elseif (!$blShopCheck && $this->oxarticles__oxvendorid->value) {
            $oVendor = $this->_createMultilanguageVendorObject();
            $sVendorId = $this->oxarticles__oxvendorid->value;
        }
        if ($sVendorId && $oVendor && $oVendor->load($sVendorId) && $oVendor->oxvendor__oxactive->value) {
            return $oVendor;
        }

        return null;
    }

    /**
     * @return MultiLanguageModel
     * @deprecated underscore prefix violates PSR12, will be renamed to "createMultilanguageVendorObject" in next major
     */
    protected function _createMultilanguageVendorObject() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oVendor = oxNew(MultiLanguageModel::class);
        $oVendor->init('oxvendor');
        $oVendor->setReadOnly(true);

        return $oVendor;
    }

    /**
     * Returns article object vendor ID. Result is cached into self::$_aArticleVendors
     *
     * @return string
     */
    public function getVendorId()
    {
        $sVendorId = false;
        if ($this->oxarticles__oxvendorid->value) {
            $sVendorId = $this->oxarticles__oxvendorid->value;
        }

        return $sVendorId;
    }

    /**
     * Returns article object Manufacturer ID. Result is cached into self::$_aArticleManufacturers
     *
     * @return string
     */
    public function getManufacturerId()
    {
        return $this->oxarticles__oxmanufacturerid->value ?: false;
    }

    /**
     * Returns current article Manufacturer object. If $blShopCheck = false, then
     * Manufacturer blReadOnly parameter will be set to true. If Manufacturer is
     * not assigned to current shop
     *
     * @param bool $blShopCheck Set false if shop check is not required (default is true)
     *
     * @return Manufacturer|null
     */
    public function getManufacturer($blShopCheck = true)
    {
        $oManufacturer = oxNew(Manufacturer::class);
        if (
            !($sManufacturerId = $this->getManufacturerId()) &&
            !$blShopCheck && $this->oxarticles__oxmanufacturerid->value
        ) {
            $this->updateManufacturerBeforeLoading($oManufacturer);
            $sManufacturerId = $this->oxarticles__oxmanufacturerid->value;
        }

        if ($sManufacturerId && $oManufacturer->load($sManufacturerId)) {
            if (!Registry::getConfig()->getConfigParam('bl_perfLoadManufacturerTree')) {
                $oManufacturer->setReadOnly(true);
            }
            $oManufacturer = $oManufacturer->oxmanufacturers__oxactive->value ? $oManufacturer : null;
        } else {
            $oManufacturer = null;
        }

        return $oManufacturer;
    }

    /**
     * Checks if article is assigned to category $sCatNID.
     *
     * @param string $sCatNid category ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function inCategory($sCatNid)
    {
        return in_array($sCatNid, $this->getCategoryIds());
    }

    /**
     * Checks if article is assigned to passed category (even checks
     * if this category is "price category"). Returns true on success.
     *
     * @param string $sCatId category ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function isAssignedToCategory($sCatId)
    {
        // variant handling
        $sOXID = $this->getId();
        if (isset($this->oxarticles__oxparentid->value) && $this->oxarticles__oxparentid->value) {
            $sOXID = $this->oxarticles__oxparentid->value;
        }

        $oDb = DatabaseProvider::getDb();
        $sSelect = $this->_generateSelectCatStr($sOXID, $sCatId);
        $sOXID = $oDb->getOne($sSelect);
        // article is assigned to passed category!
        if (isset($sOXID) && $sOXID) {
            return true;
        }

        // maybe this category is price category ?
        if (Registry::getConfig()->getConfigParam('bl_perfLoadPrice') && $this->_blLoadPrice) {
            $dPriceFromTo = $this->getPrice()->getBruttoPrice();
            if ($dPriceFromTo > 0) {
                $sSelect = $this->_generateSelectCatStr($sOXID, $sCatId, $dPriceFromTo);
                $sOXID = $oDb->getOne($sSelect);
                // article is assigned to passed category!
                if (isset($sOXID) && $sOXID) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns T price
     *
     * @return Price|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getTPrice()
    {
        if (!Registry::getConfig()->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice) {
            return;
        }

        // return cached result, since oPrice is created ONLY in this function [or function of EQUAL level]
        if ($this->_oTPrice !== null) {
            return $this->_oTPrice;
        }

        $oPrice = $this->_getPriceObject();

        $dBasePrice = $this->oxarticles__oxtprice->value;
        $dBasePrice = $this->_preparePrice($dBasePrice, $this->getArticleVat());

        $oPrice->setPrice($dBasePrice);

        $this->_applyVat($oPrice, $this->getArticleVat());
        $this->_applyCurrency($oPrice);

        if ($this->isParentNotBuyable()) {
            // if parent article is not buyable then compare against min article variant price
            $oPrice2 = $this->getVarMinPrice();
        } else {
            // else compare against article price
            $oPrice2 = $this->getPrice();
        }

        if ($oPrice->getPrice() <= $oPrice2->getPrice()) {
            // if RRP price is less or equal to comparable price then return
            return;
        }

        $this->_oTPrice = $oPrice;

        return $this->_oTPrice;
    }

    /**
     * Checks if discount should be skipped for this article in basket. Returns true if yes.
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function skipDiscounts()
    {
        // already loaded skip discounts config
        if ($this->_blSkipDiscounts !== null) {
            return $this->_blSkipDiscounts;
        }

        if ($this->oxarticles__oxskipdiscounts->value) {
            return true;
        }


        $this->_blSkipDiscounts = false;
        if (Registry::get(DiscountList::class)->hasSkipDiscountCategories()) {
            $oDb = DatabaseProvider::getDb();
            $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category', $this->getLanguage());
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories', $this->getLanguage());
            $sSelect = "select 1 from $sO2CView as $sO2CView 
                left join {$sViewName} on {$sViewName}.oxid = $sO2CView.oxcatnid
                where $sO2CView.oxobjectid = :oxobjectid 
                    and {$sViewName}.oxactive = :oxactive 
                    and {$sViewName}.oxskipdiscounts = :oxskipdiscounts ";
            $params = [
                ':oxobjectid' => $this->getId(),
                ':oxactive' => 1,
                ':oxskipdiscounts' => 1
            ];
            $this->_blSkipDiscounts = ($oDb->getOne($sSelect, $params) == 1);
        }

        return $this->_blSkipDiscounts;
    }

    /**
     * Sets the current oxPrice object
     *
     * @param Price $oPrice the new price object
     */
    public function setPrice(Price $oPrice)
    {
        $this->_oPrice = $oPrice;
    }

    /**
     * Returns base article price from database. Price may differ according to users group
     * Override this function if you want e.g. different prices for diff. user-groups.
     *
     * @param int $dAmount article amount. Default is 1
     *
     * @return double|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasePrice($dAmount = 1)
    {
        // override this function if you want e.g. different prices
        // for diff. user groups.

        // Performance
        $myConfig = Registry::getConfig();
        if (!$myConfig->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice) {
            return;
        }

        // GroupPrice or DB price adjusted by AmountPrice
        $dPrice = $this->_getModifiedAmountPrice($dAmount);

        return $dPrice;
    }

    /**
     * Modifies given amount price.
     *
     * @param int $amount
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getModifiedAmountPrice" in next major
     */
    protected function _getModifiedAmountPrice($amount) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getAmountPrice($amount);
    }

    /**
     * Calculates and returns price of article (adds taxes and discounts).
     *
     * @param float|int $dAmount article amount.
     *
     * @return Price|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getPrice($dAmount = 1)
    {
        $myConfig = Registry::getConfig();
        // Performance
        if (!$myConfig->getConfigParam('bl_perfLoadPrice') || !$this->_blLoadPrice) {
            return;
        }

        // return cached result, since oPrice is created ONLY in this function [or function of EQUAL level]
        if ($dAmount != 1 || $this->_oPrice === null) {
            // module
            $dBasePrice = $this->getBasePrice($dAmount);
            $dBasePrice = $this->_preparePrice($dBasePrice, $this->getArticleVat());

            $oPrice = $this->_getPriceObject();

            $oPrice->setPrice($dBasePrice);

            // price handling
            if (!$this->_blCalcPrice && $dAmount == 1) {
                return $this->_oPrice = $oPrice;
            }

            $this->_calculatePrice($oPrice);
            if ($dAmount != 1) {
                return $oPrice;
            }

            $this->_oPrice = $oPrice;
        }

        return $this->_oPrice;
    }

    /**
     * sets article user
     *
     * @param User $oUser user to set
     */
    public function setArticleUser($oUser)
    {
        $this->_oUser = $oUser;
    }

    /**
     * @return User article user.
     */
    public function getArticleUser()
    {
        if ($this->_oUser) {
            return $this->_oUser;
        }

        return $this->getUser();
    }

    /**
     * Creates, calculates and returns oxPrice object for basket product.
     *
     * @param float $dAmount Amount
     * @param array $aSelList Selection list
     * @param Basket $oBasket User shopping basket object
     *
     * @return Price
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getBasketPrice($dAmount, $aSelList, $oBasket)
    {
        $oUser = $oBasket->getBasketUser();
        $this->setArticleUser($oUser);

        $oBasketPrice = $this->_getPriceObject($oBasket->isCalculationModeNetto());

        // get base price
        $dBasePrice = $this->getBasePrice($dAmount);

        $dBasePrice = $this->_modifySelectListPrice($dBasePrice, $aSelList);
        $dBasePrice = $this->_preparePrice($dBasePrice, $this->getArticleVat(), $oBasket->isCalculationModeNetto());

        // applying select list price

        // setting price
        $oBasketPrice->setPrice($dBasePrice);

        $dVat = Registry::get(VatSelector::class)->getBasketItemVat($this, $oBasket);
        $this->_calculatePrice($oBasketPrice, $dVat);

        // returning final price object
        return $oBasketPrice;
    }

    /**
     * Deletes record and other information related to this article such as images from DB,
     * also removes variants. Returns true if entry was deleted.
     *
     * @param string $sOXID Article id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete($sOXID = null)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }
        if (!$sOXID) {
            return false;
        }

        $database = DatabaseProvider::getDb();
        $database->startTransaction();
        try {
            // #2339 delete first variants before deleting parent product
            $this->_deleteVariantRecords($sOXID);
            $this->load($sOXID);
            $this->_deletePics();
            $this->_onChangeResetCounts($sOXID, $this->oxarticles__oxvendorid->value, $this->oxarticles__oxmanufacturerid->value);

            // delete self
            $deleted = parent::delete($sOXID);

            $this->_deleteRecords($sOXID);

            Registry::get(SeoEncoderArticle::class)->onDeleteArticle($this);

            $this->onChange(ACTION_DELETE, $sOXID, $this->oxarticles__oxparentid->value);

            $database->commitTransaction();
        } catch (Exception $exception) {
            $database->rollbackTransaction();

            throw $exception;
        }

        return $deleted;
    }

    /**
     * Reduce article stock. return the affected amount
     *
     * @param float $dAmount amount to reduce
     * @param bool $blAllowNegativeStock are negative stocks allowed?
     *
     * @return float
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function reduceStock($dAmount, $blAllowNegativeStock = false)
    {
        $this->actionType = ACTION_UPDATE_STOCK;
        $this->beforeUpdate();

        $database = DatabaseProvider::getDb();
        $query = 'select oxstock 
            from oxarticles 
            where oxid = :oxid FOR UPDATE ';
        $actualStock = $database->getOne($query, [
            ':oxid' => $this->getId()
        ]);

        $iStockCount = $actualStock - $dAmount;
        if (!$blAllowNegativeStock && ($iStockCount < 0)) {
            $dAmount += $iStockCount;
            $iStockCount = 0;
        }
        $this->oxarticles__oxstock = new Field($iStockCount);

        $query = 'update oxarticles set oxarticles.oxstock = :oxstock where oxarticles.oxid = :oxid';
        $database->execute($query, [
            ':oxstock' => $iStockCount,
            ':oxid' => $this->getId()
        ]);
        $this->onChange(ACTION_UPDATE_STOCK);

        return $dAmount;
    }

    /**
     * Recursive function. Updates quantity of sold articles.
     * Return true if amount was changed in database.
     *
     * @param int $dAmount Number of articles sold
     *
     * @return bool|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function updateSoldAmount($dAmount = 0)
    {
        if (!$dAmount) {
            return;
        }

        // article is not variant - should be updated current amount
        if (!$this->oxarticles__oxparentid->value) {
            //updating by SQL query, due to wrong behaviour if saving article using not admin mode
            $dAmount = (double) $dAmount;
            $oDb = DatabaseProvider::getDb();
            $query = "update oxarticles
                      set oxarticles.oxsoldamount = (oxarticles.oxsoldamount + :amount)
                      where oxarticles.oxid = :oxid";
            $rs = $oDb->execute($query, [
                ':oxid' => $this->oxarticles__oxid->value,
                ':amount' => $dAmount
            ]);

            return (bool) $rs;
        } elseif ($this->oxarticles__oxparentid->value) {
            // article is variant - should be updated this article parent amount
            $oUpdateArticle = $this->getParentArticle();
            if ($oUpdateArticle) {
                $oUpdateArticle->updateSoldAmount($dAmount);
            }
        }

        return false;
    }

    /**
     * Disables reminder functionality for article
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function disableReminder()
    {
        $oDb = DatabaseProvider::getDb();
        $query = "update oxarticles set oxarticles.oxremindactive = 2 where oxarticles.oxid = :oxid";

        return (bool) $oDb->execute($query, [':oxid' => $this->oxarticles__oxid->value]);
    }

    /**
     * (Article::_saveArtLongDesc()) save the object using parent::save() method.
     *
     * @return bool
     * @throws Exception
     */
    public function save()
    {
        $this->_assignParentDependFields();
        $blRet = parent::save();
        // saving long description
        $this->_saveArtLongDesc();

        return $blRet;
    }

    /**
     * Changes article variant to parent article
     */
    public function resetParent()
    {
        $sParentId = $this->oxarticles__oxparentid->value;
        $this->oxarticles__oxparentid = new Field('', Field::T_RAW);
        $this->_blAllowEmptyParentId = true;
        $this->save();
        $this->_blAllowEmptyParentId = false;

        if ($sParentId !== '') {
            $this->onChange(ACTION_UPDATE, null, $sParentId);
        }
    }

    /**
     * collect article pics, icons, zoom-pic and puts it all in an array
     * structure of array (ActPicID, ActPic, MorePics, Pics, Icons, ZoomPic)
     *
     * @return array
     */
    public function getPictureGallery()
    {
        $myConfig = Registry::getConfig();

        //initialize
        $blMorePic = false;
        $aArtPics = [];
        $aArtIcons = [];
        $iActPicId = 1;
        $sActPic = $this->getPictureUrl($iActPicId);

        if (Registry::getRequest()->getRequestEscapedParameter('actpicid')) {
            $iActPicId = Registry::getRequest()->getRequestEscapedParameter('actpicid');
        }

        $oStr = Str::getStr();
        $iCntr = 0;
        $iPicCount = $myConfig->getConfigParam('iPicCount');
        $blCheckActivePicId = true;

        for ($i = 1; $i <= $iPicCount; $i++) {
            $sPicVal = $this->getPictureUrl($i);
            $sIcoVal = $this->getIconUrl($i);
            if (
                !$oStr->strstr($sIcoVal, 'nopic_ico.jpg') && !$oStr->strstr($sIcoVal, 'nopic.jpg') &&
                !$oStr->strstr($sPicVal, 'nopic_ico.jpg') && !$oStr->strstr($sPicVal, 'nopic.jpg') &&
                $sPicVal !== null
            ) {
                if ($iCntr) {
                    $blMorePic = true;
                }
                $aArtIcons[$i] = $sIcoVal;
                $aArtPics[$i] = $sPicVal;
                $iCntr++;

                if ($iActPicId == $i) {
                    $sActPic = $sPicVal;
                    $blCheckActivePicId = false;
                }
            } elseif ($blCheckActivePicId && $iActPicId <= $i) {
                // if picture is empty, setting active pic id to next
                // picture
                $iActPicId++;
            }
        }

        $blZoomPic = false;
        $aZoomPics = [];
        $iZoomPicCount = $myConfig->getConfigParam('iPicCount');

        for ($j = 1, $c = 1; $j <= $iZoomPicCount; $j++) {
            $sVal = $this->getZoomPictureUrl($j);

            if ($sVal && !$oStr->strstr($sVal, 'nopic.jpg')) {
                $blZoomPic = true;
                $aZoomPics[$c]['id'] = $c;
                $aZoomPics[$c]['file'] = $sVal;
                //anything is better than empty name, because <img src=""> calls shop once more = x2 SLOW.
                if (!$sVal) {
                    $aZoomPics[$c]['file'] = "nopic.jpg";
                }
                $c++;
            }
        }

        $aPicGallery = [
            'ActPicID' => $iActPicId,
            'ActPic'   => $sActPic,
            'MorePics' => $blMorePic,
            'Pics'     => $aArtPics,
            'Icons'    => $aArtIcons,
            'ZoomPic'  => $blZoomPic,
            'ZoomPics' => $aZoomPics
        ];

        return $aPicGallery;
    }

    /**
     * This function is triggered whenever article is saved or deleted or after the stock is changed.
     * Originally we need to update the oxstock for possible article parent in case parent is not buyable
     * Plus you may want to extend this function to update some extended information.
     * Call Article::onChange($sAction, $sOXID) with ID parameter when changes are executed over SQL.
     * (or use module class instead of oxArticle if such exists)
     *
     * @param null $action Action constant
     * @param null $articleId Article ID
     * @param null $parentArticleId Parent ID
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function onChange($action = null, $articleId = null, $parentArticleId = null)
    {
        $this->actionType = !is_null($action) ? $action : $this->actionType;
        $myConfig = Registry::getConfig();

        if (!isset($articleId)) {
            if ($this->getId()) {
                $articleId = $this->getId();
            }
            if (!isset($articleId)) {
                $articleId = $this->oxarticles__oxid->value;
            }
            if ($this->oxarticles__oxparentid && $this->oxarticles__oxparentid->value) {
                $parentArticleId = $this->oxarticles__oxparentid->value;
            }
        }
        if (!isset($articleId)) {
            return;
        }

        //if (isset($sOXID) && !$myConfig->blVariantParentBuyable && $myConfig->blUseStock)
        if ($myConfig->getConfigParam('blUseStock')) {
            //if article has variants then updating oxvarstock field
            //getting parent id
            if (!isset($parentArticleId)) {
                $oDb = DatabaseProvider::getDb();
                $sQ = 'select oxparentid from oxarticles where oxid = :oxid';
                $parentArticleId = $oDb->getOne($sQ, [
                    ':oxid' => $articleId
                ]);
            }
            //if we have parent id then update stock
            if ($parentArticleId) {
                $this->_onChangeUpdateStock($parentArticleId);
            }
        }
        // if we have parent id then update count
        // even if blUseStock is not active
        if ($parentArticleId) {
            $this->_onChangeUpdateVarCount($parentArticleId);
        }

        $sId = ($parentArticleId) ? $parentArticleId : $articleId;
        $this->_setVarMinMaxPrice($sId);

        $this->_updateParentDependFields();

        // resetting articles count cache if stock has changed and some
        // articles goes offline (M:1448)
        if ($action === ACTION_UPDATE_STOCK) {
            $this->_assignStock();
            $this->_onChangeStockResetCount($articleId);
        }

        $this->dispatchEvent(new AfterModelUpdateEvent($this));
    }

    /**
     * Returns custom article VAT value if possible
     * By default value is taken from oxarticle__oxvat field
     *
     * @return double|void
     */
    public function getCustomVAT()
    {
        if ($this->__isset('oxarticles__oxvat') || $this->__get('oxarticles__oxvat')) {
            return $this->oxarticles__oxvat->value;
        }
    }

    /**
     * Checks if stock configuration allows to buy user chosen amount $dAmount
     *
     * @param double $dAmount buyable amount
     * @param double|int $dArtStockAmount stock amount
     * @param bool $selectForUpdate Set true to select for update
     *
     * @return mixed
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function checkForStock($dAmount, $dArtStockAmount = 0, $selectForUpdate = false)
    {
        $myConfig = Registry::getConfig();
        if (!$myConfig->getConfigParam('blUseStock')) {
            return true;
        }

        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        // fetching DB info as its up-to-date
        $sQ = 'select oxstock, oxstockflag from oxarticles 
            where oxid = :oxid';
        $sQ .= $selectForUpdate ? ' FOR UPDATE ' : '';
        $rs = $oDb->select($sQ, [
            ':oxid' => $this->getId()
        ]);

        $iOnStock = 0;
        if ($rs !== false && $rs->count() > 0) {
            $iOnStock = $rs->fields['oxstock'] - $dArtStockAmount;
            $iStockFlag = $rs->fields['oxstockflag'];

            //When using oxstockflag 1 and 4 with basket reservations enabled but disallowing
            //negative stock values we would allow to reserve more items than are initially available
            //by keeping the stock level not lower than zero. When discarding reservations
            //stock level might differ from original value.
            if (
                !$myConfig->getConfigParam('blPsBasketReservationEnabled')
                 || ($myConfig->getConfigParam('blPsBasketReservationEnabled')
                     && $myConfig->getConfigParam('blAllowNegativeStock'))
            ) {
                // foreign stock is also always considered as on stock
                if ($iStockFlag == 1 || $iStockFlag == 4) {
                    return true;
                }
            }
            if (!$myConfig->getConfigParam('blAllowUnevenAmounts')) {
                $iOnStock = floor($iOnStock);
            }
        }
        if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
            $iOnStock += Registry::getSession()->getBasketReservations()->getReservedAmount($this->getId());
        }
        if ($iOnStock >= $dAmount) {
            return true;
        } else {
            if ($iOnStock > 0) {
                return $iOnStock;
            } else {
                $oEx = oxNew(ArticleInputException::class);
                $oEx->setMessage('ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE');
                Registry::getUtilsView()->addErrorToDisplay($oEx);

                return false;
            }
        }
    }

    /**
     * Get article long description
     *
     * @return object $oField field object
     * @throws DatabaseConnectionException
     */
    public function getLongDescription()
    {
        if ($this->_oLongDesc === null) {
            // initializing
            $this->_oLongDesc = new Field();

            // choosing which to get...
            $sOxid = $this->getId();
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxartextends', $this->getLanguage());

            $oDb = DatabaseProvider::getDb();
            $sDbValue = $oDb->getOne("select oxlongdesc from {$sViewName} where oxid = :oxid", [
                ':oxid' => $sOxid
            ]);

            if ($sDbValue) {
                $this->_oLongDesc->setValue($sDbValue, Field::T_RAW);
            } elseif ($this->oxarticles__oxparentid && $this->oxarticles__oxparentid->value) {
                if (!$this->isAdmin() || $this->_blLoadParentData) {
                    $oParent = $this->getParentArticle();
                    if ($oParent) {
                        $this->_oLongDesc->setValue($oParent->getLongDescription()->getRawValue(), Field::T_RAW);
                    }
                }
            }
        }

        return $this->_oLongDesc;
    }

    /**
     * get long description, parsed through smarty. should only be used by exports or so.
     * In templates use [{oxeval var=$oProduct->getLongDescription()->getRawValue()}]
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getLongDesc()
    {
        return Registry::getUtilsView()->parseThroughSmarty($this->getLongDescription()->getRawValue(), $this->getId() . $this->getLanguage(), null, true);
    }

    /**
     * Save article long description to oxartextends table
     *
     * @param string $longDescription description to set
     */
    public function setArticleLongDesc($longDescription)
    {
        // setting current value
        $this->_oLongDesc = new Field($longDescription, Field::T_RAW);
        $this->oxarticles__oxlongdesc = new Field($longDescription, Field::T_RAW);
    }

    /**
     * the uninitialised list of attributes
     * use getAttributes
     * @return AttributeList
     */
    protected function newAttributeList()
    {
        return oxNew(AttributeList::class);
    }

    /**
     * Loads and returns attribute list associated with this article
     *
     * @return AttributeList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getAttributes()
    {
        if ($this->_oAttributeList === null) {
            $this->_oAttributeList = $this->newAttributelist();
            $this->_oAttributeList->loadAttributes($this->getId(), $this->getParentId());
        }

        return $this->_oAttributeList;
    }

    /**
     * Loads and returns attribute list for display in basket
     *
     * @return AttributeList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getAttributesDisplayableInBasket()
    {
        if ($this->basketAttributeList === null) {
            $this->basketAttributeList = $this->newAttributelist();
            $this->basketAttributeList->loadAttributesDisplayableInBasket($this->getId(), $this->getParentId());
        }

        return $this->basketAttributeList;
    }


    /**
     * Appends article seo url with additional request parameters
     *
     * @param string $sAddParams additional parameters which needs to be added to product url
     * @param int    $iLang      language id
     */
    public function appendLink($sAddParams, $iLang = null)
    {
        if ($sAddParams) {
            if ($iLang === null) {
                $iLang = $this->getLanguage();
            }

            $this->_aSeoAddParams[$iLang] = isset($this->_aSeoAddParams[$iLang]) ? $this->_aSeoAddParams[$iLang] . "&amp;" : "";
            $this->_aSeoAddParams[$iLang] .= $sAddParams;
        }
    }

    /**
     * Returns raw article seo url
     *
     * @param int $iLang language id
     * @param bool $blMain force to return main url [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBaseSeoLink($iLang, $blMain = false)
    {
        /** @var SeoEncoderArticle $oEncoder */
        $oEncoder = Registry::get(SeoEncoderArticle::class);
        if (!$blMain) {
            return $oEncoder->getArticleUrl($this, $iLang, $this->getLinkType());
        }

        return $oEncoder->getArticleMainUrl($this, $iLang);
    }

    /**
     * Gets article link
     *
     * @param null $iLang language id [optional]
     * @param bool $blMain force to return main url [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getLink($iLang = null, $blMain = false)
    {
        if (!Registry::getUtils()->seoIsActive()) {
            return $this->getStdLink($iLang);
        }

        if ($iLang === null) {
            $iLang = $this->getLanguage();
        }

        $iLinkType = $this->getLinkType();
        if (!isset($this->_aSeoUrls[$iLang][$iLinkType])) {
            $this->_aSeoUrls[$iLang][$iLinkType] = $this->getBaseSeoLink($iLang, $blMain);
        }

        $sUrl = $this->_aSeoUrls[$iLang][$iLinkType];
        if (isset($this->_aSeoAddParams[$iLang])) {
            $sUrl .= ((strpos($sUrl . $this->_aSeoAddParams[$iLang], '?') === false) ? '?' : '&amp;') . $this->_aSeoAddParams[$iLang];
        }

        return $sUrl;
    }

    /**
     * Returns main object URL. If SEO is ON returned link will be in SEO form,
     * else URL will have dynamic form
     *
     * @param null $iLang language id [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getMainLink($iLang = null)
    {
        return $this->getLink($iLang, true);
    }

    /**
     * Resets details link
     *
     * @param int $iType type of link to load
     */
    public function setLinkType($iType)
    {
        // resetting details link, to force new
        $this->_sDetailLink = null;

        // setting link type
        $this->_iLinkType = (int) $iType;
    }

    /**
     * Get link type
     *
     * @return int
     */
    public function getLinkType()
    {
        return $this->_iLinkType;
    }

    /**
     * Appends article dynamic url with additional request parameters
     *
     * @param string $sAddParams additional parameters which needs to be added to product url
     * @param int    $iLang      language id
     */
    public function appendStdLink($sAddParams, $iLang = null)
    {
        if ($sAddParams) {
            if ($iLang === null) {
                $iLang = $this->getLanguage();
            }

            $this->_aStdAddParams[$iLang] = isset($this->_aStdAddParams[$iLang]) ? $this->_aStdAddParams[$iLang] . "&amp;" : "";
            $this->_aStdAddParams[$iLang] .= $sAddParams;
        }
    }

    /**
     * Returns base dynamic url: shopurl/index.php?cl=details
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not [optional]
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function getBaseStdLink($iLang, $blAddId = true, $blFull = true)
    {
        $sUrl = '';
        if ($blFull) {
            //always returns shop url, not admin
            $sUrl = Registry::getConfig()->getShopUrl($iLang, false);
        }

        $sUrl .= "index.php?cl=details" . ($blAddId ? "&amp;anid=" . $this->getId() : "");

        return $sUrl . (isset($this->_aStdAddParams[$iLang]) ? "&amp;" . $this->_aStdAddParams[$iLang] : "");
    }

    /**
     * Returns standard URL to product
     *
     * @param int   $iLang   required language. optional
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function getStdLink($iLang = null, $aParams = [])
    {
        if ($iLang === null) {
            $iLang = $this->getLanguage();
        }

        if (!isset($this->_aStdUrls[$iLang])) {
            $this->_aStdUrls[$iLang] = $this->getBaseStdLink($iLang);
        }

        return Registry::getUtilsUrl()->processUrl($this->_aStdUrls[$iLang], true, $aParams, $iLang);
    }

    /**
     * Return article media URL
     *
     * @return array
     */
    public function getMediaUrls()
    {
        if ($this->_aMediaUrls === null) {
            $this->_aMediaUrls = oxNew(ListModel::class);
            $this->_aMediaUrls->init("oxmediaurl");
            $this->_aMediaUrls->getBaseObject()->setLanguage($this->getLanguage());

            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName("oxmediaurls", $this->getLanguage());
            $sQ = "select * from {$sViewName} where oxobjectid = :oxobjectid";
            $this->_aMediaUrls->selectString($sQ, [
                ':oxobjectid' => $this->getId()
            ]);
        }

        return $this->_aMediaUrls;
    }

    /**
     * Get image url
     *
     * @return string|null
     */
    public function getDynImageDir()
    {
        return $this->_sDynImageDir;
    }

    /**
     * Returns select lists to display
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getDispSelList()
    {
        if ($this->_aDispSelList === null) {
            if (Registry::getConfig()->getConfigParam('bl_perfLoadSelectLists') && Registry::getConfig()->getConfigParam('bl_perfLoadSelectListsInAList')) {
                $this->_aDispSelList = $this->getSelectLists();
            }
        }

        return $this->_aDispSelList;
    }

    /**
     * Get more details link
     *
     * @return string
     */
    public function getMoreDetailLink()
    {
        if ($this->_sMoreDetailLink == null) {
            // and assign special article values
            $this->_sMoreDetailLink = Registry::getConfig()->getShopHomeUrl() . 'cl=moredetails';

            // not always it is ok, as not all the time active category is the same as primary article cat.
            if ($sActCat = Registry::getRequest()->getRequestEscapedParameter('cnid')) {
                $this->_sMoreDetailLink .= '&amp;cnid=' . $sActCat;
            }
            $this->_sMoreDetailLink .= '&amp;anid=' . $this->getId();
        }

        return $this->_sMoreDetailLink;
    }

    /**
     * Get to basket link
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getToBasketLink()
    {
        if ($this->_sToBasketLink == null) {
            $myConfig = Registry::getConfig();

            if (Registry::getUtils()->isSearchEngine()) {
                $this->_sToBasketLink = $this->getLink();
            } else {
                // and assign special article values
                $this->_sToBasketLink = $myConfig->getShopHomeUrl();

                // override some classes as these should never show up
                $actControllerId = Registry::getConfig()->getRequestControllerId();
                if ($actControllerId == 'thankyou') {
                    $actControllerId = 'basket';
                }
                $this->_sToBasketLink .= 'cl=' . $actControllerId;

                // this is not very correct
                if ($sActCat = Registry::getRequest()->getRequestEscapedParameter('cnid')) {
                    $this->_sToBasketLink .= '&amp;cnid=' . $sActCat;
                }

                $this->_sToBasketLink .= '&amp;fnc=tobasket&amp;aid=' . $this->getId() . '&amp;anid=' . $this->getId();

                if ($sTpl = basename(Registry::getRequest()->getRequestEscapedParameter('tpl'))) {
                    $this->_sToBasketLink .= '&amp;tpl=' . $sTpl;
                }
            }
        }

        return $this->_sToBasketLink;
    }

    /**
     * Get stock status
     *
     * @return integer
     */
    public function getStockStatus()
    {
        return $this->_iStockStatus;
    }

    /**
     * Get stock status as it was on loading this object.
     *
     * @return integer
     */
    public function getStockStatusOnLoad()
    {
        return $this->_iStockStatusOnLoad;
    }

    /**
     * Get stock
     *
     * @return float
     */
    public function getStock()
    {
        return $this->oxarticles__oxstock->value;
    }

    /**
     * Returns formatted delivery date. If the date is past or not set ('0000-00-00') returns false.
     *
     * @deprecated since v6.2 (2020-02-26); use getRestockDate();
     * @return false|string
     */
    public function getDeliveryDate()
    {
        return $this->getRestockDate();
    }

    /**
     * Returns formatted delivery date. If the date is past or not set ('0000-00-00') returns false.
     *
     * @return false|string
     */
    public function getRestockDate()
    {
        $restockDate = $this->getFieldData('oxdelivery');
        if ($restockDate >= date('Y-m-d')) {
            return Registry::getUtilsDate()->formatDBDate($restockDate);
        }

        return false;
    }

    /**
     * Returns rounded T price.
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated since v5.1 (2013-10-03); use getTPrice() and oxPrice modifier;
     */
    public function getFTPrice()
    {
        // module
        if ($oPrice = $this->getTPrice()) {
            if ($dPrice = $this->_getPriceForView($oPrice)) {
                return Registry::getLang()->formatCurrency($dPrice);
            }
        }
    }

    /**
     * Returns formatted product's price.
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated since v5.1 (2013-10-04); use oxPrice smarty plugin for formatting in templates
     */
    public function getFPrice()
    {
        if ($oPrice = $this->getPrice()) {
            $dPrice = $this->_getPriceForView($oPrice);

            return Registry::getLang()->formatCurrency($dPrice);
        }
    }

    /**
     * Resets oxremindactive status.
     * If remindActive status is 2, reminder is already sent.
     */
    public function resetRemindStatus()
    {
        if (
            $this->oxarticles__oxremindactive->value == 2 &&
            $this->oxarticles__oxremindamount->value <= $this->oxarticles__oxstock->value
        ) {
            $this->oxarticles__oxremindactive->value = 1;
        }
    }

    /**
     * Returns formatted product's NETTO price.
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated since v5.1 (2013-10-03); use getPrice() and oxPrice modifier;
     */
    public function getFNetPrice()
    {
        if ($oPrice = $this->getPrice()) {
            return Registry::getLang()->formatCurrency($oPrice->getNettoPrice());
        }
    }

    /**
     * Returns true if parent is not buyable
     *
     * @return bool
     */
    public function isParentNotBuyable()
    {
        return $this->_blNotBuyableParent;
    }

    /**
     * Returns true if article is not buyable
     *
     * @return bool
     */
    public function isNotBuyable()
    {
        return $this->_blNotBuyable;
    }

    /**
     * Sets product state - buyable or not
     *
     * @param bool $blBuyable state - buyable or not (default false)
     */
    public function setBuyableState($blBuyable = false)
    {
        $this->_blNotBuyable = !$blBuyable;
    }

    /**
     * Sets selectlists of current product
     *
     * @param array $aSelList selectlist
     */
    public function setSelectlist($aSelList)
    {
        $this->_aDispSelList = $aSelList;
    }

    /**
     * Returns article picture
     *
     * @param int $iIndex picture index
     *
     * @return string|void
     */
    public function getPictureUrl($iIndex = 1)
    {
        if ($iIndex) {
            $sImgName = false;
            if (!$this->_isFieldEmpty("oxarticles__oxpic" . $iIndex)) {
                $sImgName = basename($this->{"oxarticles__oxpic$iIndex"}->value);
            }

            $sSize = Registry::getConfig()->getConfigParam('aDetailImageSizes');

            return Registry::getPictureHandler()
                ->getProductPicUrl("product/{$iIndex}/", $sImgName, $sSize, 'oxpic' . $iIndex);
        }
    }

    /**
     * Returns article icon picture url. If no index specified, will
     * return main icon url.
     *
     * @param int $iIndex picture index
     *
     * @return string
     */
    public function getIconUrl($iIndex = 0)
    {
        $sImgName = false;
        $sDirname = "product/1/";
        if ($iIndex && !$this->_isFieldEmpty("oxarticles__oxpic{$iIndex}")) {
            $sImgName = basename($this->{"oxarticles__oxpic$iIndex"}->value);
            $sDirname = "product/{$iIndex}/";
        } elseif (!$this->_isFieldEmpty("oxarticles__oxicon")) {
            $sImgName = basename($this->oxarticles__oxicon->value);
            $sDirname = "product/icon/";
        } elseif (!$this->_isFieldEmpty("oxarticles__oxpic1")) {
            $sImgName = basename($this->oxarticles__oxpic1->value);
        }

        $sSize = Registry::getConfig()->getConfigParam('sIconsize');

        $sIconUrl = Registry::getPictureHandler()->getProductPicUrl($sDirname, $sImgName, $sSize, $iIndex);

        return $sIconUrl;
    }

    /**
     * Returns article thumbnail picture url
     *
     * @param bool $bSsl to force SSL
     *
     * @return string
     */
    public function getThumbnailUrl($bSsl = null)
    {
        $sImgName = false;
        $sDirname = "product/1/";
        if (!$this->_isFieldEmpty("oxarticles__oxthumb")) {
            $sImgName = basename($this->oxarticles__oxthumb->value);
            $sDirname = "product/thumb/";
        } elseif (!$this->_isFieldEmpty("oxarticles__oxpic1")) {
            $sImgName = basename($this->oxarticles__oxpic1->value);
        }

        $sSize = Registry::getConfig()->getConfigParam('sThumbnailsize');

        return Registry::getPictureHandler()->getProductPicUrl($sDirname, $sImgName, $sSize, 0, $bSsl);
    }

    /**
     * Returns article zoom picture url
     *
     * @param int $iIndex picture index
     *
     * @return string|void
     */
    public function getZoomPictureUrl($iIndex = '')
    {
        $iIndex = (int) $iIndex;
        if ($iIndex > 0 && !$this->_isFieldEmpty("oxarticles__oxpic" . $iIndex)) {
            $sImgName = basename($this->{"oxarticles__oxpic" . $iIndex}->value);
            $sSize = Registry::getConfig()->getConfigParam("sZoomImageSize");

            return Registry::getPictureHandler()->getProductPicUrl(
                "product/{$iIndex}/",
                $sImgName,
                $sSize,
                'oxpic' . $iIndex
            );
        }
    }

    /**
     * apply article and article use
     *
     * @param Price $oPrice target price
     * @throws DatabaseConnectionException
     * @throws ObjectException
     */
    public function applyVats(Price $oPrice)
    {
        $this->_applyVAT($oPrice, $this->getArticleVat());
    }

    /**
     * Applies discounts which should be applied in general case (for 0 amount)
     *
     * @param Price $oPrice Price object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function applyDiscountsForVariant($oPrice)
    {
        // apply discounts
        if (!$this->skipDiscounts()) {
            $oDiscountList = Registry::get(DiscountList::class);
            $aDiscounts = $oDiscountList->getArticleDiscounts($this, $this->getArticleUser());

            reset($aDiscounts);
            foreach ($aDiscounts as $oDiscount) {
                $oPrice->setDiscount($oDiscount->getAddSum(), $oDiscount->getAddSumType());
            }
            $oPrice->calculateDiscount();
        }
    }

    /**
     * Get parent article
     *
     * @return Article|void
     */
    public function getParentArticle()
    {
        if ($this->oxarticles__oxparentid && ($sParentId = $this->oxarticles__oxparentid->value)) {
            $sIndex = $sParentId . "_" . $this->getLanguage();
            if (!isset(self::$_aLoadedParents[$sIndex])) {
                self::$_aLoadedParents[$sIndex] = oxNew(Article::class);
                self::$_aLoadedParents[$sIndex]->_blLoadPrice = false;
                self::$_aLoadedParents[$sIndex]->_blLoadVariants = false;

                if (!self::$_aLoadedParents[$sIndex]->loadInLang($this->getLanguage(), $sParentId)) {
                    //return false in case parent product failed to load
                    self::$_aLoadedParents[$sIndex] = false;
                }
            }

            return self::$_aLoadedParents[$sIndex];
        }
    }

    /**
     * Updates article variants oxremindactive field, as variants inherit this setting from parent
     */
    public function updateVariantsRemind()
    {
        // check if it is parent article
        if (!$this->isVariant() && $this->_hasAnyVariant()) {
            $oDb = DatabaseProvider::getDb();
            $sUpdate = "update oxarticles
                        set oxremindactive = :oxremindactive
                        where oxparentid = :oxparentid and
                              oxshopid = :oxshopid";
            $oDb->execute($sUpdate, [
                ':oxremindactive' => $this->oxarticles__oxremindactive->value,
                ':oxparentid' => $this->getId(),
                ':oxshopid' => $this->getShopId()
            ]);
        }
    }

    /**
     * Returns product id (oxid)
     * (required for interface oxIArticle)
     *
     * @return string
     */
    public function getProductId()
    {
        return $this->getId();
    }

    /**
     * Returns product parent id (oxparentid)
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->oxarticles__oxparentid instanceof Field ? $this->oxarticles__oxparentid->value : '';
    }

    /**
     * Returns false if object is not derived from OrderArticle class
     *
     * @return bool
     */
    public function isOrderArticle()
    {
        return false;
    }

    /**
     * Returns TRUE if product is variant, and false if not
     *
     * @return bool
     */
    public function isVariant(): bool
    {
        $isVariant = false;
        if (isset($this->oxarticles__oxparentid) && false !== $this->oxarticles__oxparentid) {
            $isVariant = (bool) $this->oxarticles__oxparentid->value;
        }

        return $isVariant;
    }

    /**
     * Returns TRUE if product is multidimensional variant, and false if not
     *
     * @return bool
     */
    public function isMdVariant()
    {
        $oMdVariant = oxNew(VariantHandler::class);

        return $oMdVariant->isMdVariant($this);
    }

    /**
     * get Sql for loading price categories which include this article
     *
     * @param string $sFields fields to load from oxCategories
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getSqlForPriceCategories($sFields = '')
    {
        if (!$sFields) {
            $sFields = 'oxid';
        }
        $sSelectWhere = "select $sFields from " . $this->_getObjectViewName('oxcategories') . " where";
        $sQuotedPrice = DatabaseProvider::getDb()->quote($this->oxarticles__oxprice->value);

        return "$sSelectWhere oxpricefrom != 0 and oxpriceto != 0 and oxpricefrom <= $sQuotedPrice and oxpriceto >= $sQuotedPrice"
               . " union $sSelectWhere oxpricefrom != 0 and oxpriceto = 0 and oxpricefrom <= $sQuotedPrice"
               . " union $sSelectWhere oxpricefrom = 0 and oxpriceto != 0 and oxpriceto >= $sQuotedPrice";
    }

    /**
     * Checks if article is assigned to price category $sCatNID.
     *
     * @param string $categoryPriceId Price category ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function inPriceCategory($categoryPriceId)
    {
        return (bool) $this->fetchFirstInPriceCategory($categoryPriceId);
    }

    /**
     * Fetch the article corresponding to this object in the price category with the given id.
     *
     * @param string $categoryPriceId The id of the category we want to check, if this article is in.
     *
     * @return string One, if the given article is in the given price category, else empty string.
     * @throws DatabaseConnectionException
     */
    protected function fetchFirstInPriceCategory($categoryPriceId)
    {
        $database = $this->getDatabase();

        $query = $this->createFetchFirstInPriceCategorySql($categoryPriceId);

        $result = $database->getOne($query);

        return $result;
    }

    /**
     * Create the sql for the fetchFirstInPriceCategory method.
     *
     * @param string $categoryPriceId The price category id.
     *
     * @return string The wished sql.
     * @throws DatabaseConnectionException
     */
    protected function createFetchFirstInPriceCategorySql($categoryPriceId)
    {
        $database = $this->getDatabase();

        $quotedPrice = $database->quote($this->oxarticles__oxprice->value);
        $quotedCategoryId = $database->quote($categoryPriceId);

        $query = "select 1 from " . $this->_getObjectViewName('oxcategories') . " where oxid=$quotedCategoryId and"
                 . "(   (oxpricefrom != 0 and oxpriceto != 0 and oxpricefrom <= $quotedPrice and oxpriceto >= $quotedPrice)"
                 . " or (oxpricefrom != 0 and oxpriceto = 0 and oxpricefrom <= $quotedPrice)"
                 . " or (oxpricefrom = 0 and oxpriceto != 0 and oxpriceto >= $quotedPrice)"
                 . ")";

        return $query;
    }

    /**
     * Get the database object.
     *
     * @return DatabaseInterface
     * @throws DatabaseConnectionException
     */
    protected function getDatabase()
    {
        return DatabaseProvider::getDb();
    }

    /**
     * Returns multidimensional variant structure
     *
     * @return MdVariant
     * @throws DatabaseConnectionException
     */
    public function getMdVariants()
    {
        if ($this->_oMdVariants) {
            return $this->_oMdVariants;
        }

        $oParentArticle = $this->getParentArticle();
        if ($oParentArticle) {
            $oVariants = $oParentArticle->getVariants();
        } else {
            $oVariants = $this->getVariants();
        }

        /** @var VariantHandler $oVariantHandler */
        $oVariantHandler = oxNew(VariantHandler::class);
        $this->_oMdVariants = $oVariantHandler->buildMdVariants($oVariants, $this->getId());

        return $this->_oMdVariants;
    }

    /**
     * Returns first level variants from multidimensional variants list
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getMdSubvariants()
    {
        return $this->getMdVariants()->getMdSubvariants();
    }

    /**
     * Return article picture file name
     *
     * @param string $sFieldName article picture field name
     * @param int    $iIndex     article picture index
     *
     * @return string|void
     */
    public function getPictureFieldValue($sFieldName, $iIndex = null)
    {
        if ($sFieldName) {
            $sFieldName = "oxarticles__" . $sFieldName . $iIndex;

            return $this->$sFieldName->value;
        }
    }

    /**
     * Get master zoom picture url
     *
     * @param int $iIndex picture index
     *
     * @return string
     */
    public function getMasterZoomPictureUrl($iIndex)
    {
        $sPicUrl = false;
        $sPicName = basename($this->{"oxarticles__oxpic" . $iIndex}->value);

        if ($sPicName && $sPicName != "nopic.jpg") {
            $sPicUrl = Registry::getConfig()->getPictureUrl("master/product/" . $iIndex . "/" . $sPicName);
            if (!$sPicUrl || basename($sPicUrl) == "nopic.jpg") {
                $sPicUrl = false;
            }
        }

        return $sPicUrl;
    }

    /**
     * Returns oxarticles__oxunitname value processed by \OxidEsales\Eshop\Core\Language::translateString()
     *
     * @return string|void
     */
    public function getUnitName()
    {
        if ($this->oxarticles__oxunitname->value) {
            return Registry::getLang()->translateString($this->oxarticles__oxunitname->value);
        }
    }

    /**
     * Return article downloadable file list (list of oxfile)
     *
     * @param bool $blAddFromParent - return with parent files if not buyable
     *
     * @return null|ListModel of oxFile
     */
    public function getArticleFiles($blAddFromParent = false)
    {
        if ($this->_aArticleFiles === null) {
            $this->_aArticleFiles = false;

            $sQ = "SELECT * FROM `oxfiles` WHERE `oxartid` = :oxartid";

            if (!Registry::getConfig()->getConfigParam('blVariantParentBuyable') && $blAddFromParent) {
                $sQ .= " OR `oxartId` = :oxparentid";
            }

            $oArticleFiles = oxNew(ListModel::class);
            $oArticleFiles->init("oxfile");
            $oArticleFiles->selectString($sQ, [
                ':oxartid' => $this->getId(),
                ':oxparentid' => $this->oxarticles__oxparentid->value
            ]);
            $this->_aArticleFiles = $oArticleFiles;
        }

        return $this->_aArticleFiles;
    }

    /**
     * Returns oxarticles__oxisdownloadable value
     *
     * @return bool
     */
    public function isDownloadable()
    {
        return $this->oxarticles__oxisdownloadable->value;
    }

    /**
     * Checks if articles has amount price
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function hasAmountPrice()
    {
        if (self::$_blHasAmountPrice === null) {
            self::$_blHasAmountPrice = false;

            $oDb = DatabaseProvider::getDb();
            $sQ = "SELECT 1 FROM `oxprice2article` LIMIT 1";

            if ($oDb->getOne($sQ)) {
                self::$_blHasAmountPrice = true;
            }
        }

        return self::$_blHasAmountPrice;
    }

    /**
     * Loads and returns variants list.
     *
     * @param bool $loadSimpleVariants if parameter $blSimple - list will be filled with oxSimpleVariant objects, else - oxArticle
     * @param bool $blRemoveNotOrderables if true, removes from list not order-able articles, which are out of stock [optional]
     * @param bool|null $forceCoreTableUsage if true forces core table use, default is false [optional]
     *
     * @return array|Simplevariantlist|Articlelist
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadVariantList" in next major
     */
    protected function _loadVariantList($loadSimpleVariants, $blRemoveNotOrderables = true, $forceCoreTableUsage = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $variants = [];
        if (($articleId = $this->getId())) {
            //do not load me as a parent later
            self::$_aLoadedParents[$articleId . "_" . $this->getLanguage()] = $this;

            $config = Registry::getConfig();

            if (
                !$this->_blLoadVariants ||
                (!$this->isAdmin() && !$config->getConfigParam('blLoadVariants')) ||
                (!$this->isAdmin() && !$this->oxarticles__oxvarcount->value)
            ) {
                return $variants;
            }

            // cache
            $cacheKey = $loadSimpleVariants ? "simple" : "full";
            if ($blRemoveNotOrderables) {
                if (isset($this->_aVariants[$cacheKey])) {
                    return $this->_aVariants[$cacheKey];
                }
                $this->_aVariants[$cacheKey] = &$variants;
            } elseif (!$blRemoveNotOrderables) {
                if (isset($this->_aVariantsWithNotOrderables[$cacheKey])) {
                    return $this->_aVariantsWithNotOrderables[$cacheKey];
                }
                $this->_aVariantsWithNotOrderables[$cacheKey] = &$variants;
            }

            if (($this->_blHasVariants = $this->_hasAnyVariant($forceCoreTableUsage))) {
                //load simple variants for lists
                if ($loadSimpleVariants) {
                    $variants = oxNew(SimpleVariantList::class);
                    $variants->setParent($this);
                } else {
                    //loading variants
                    $variants = oxNew(ArticleList::class);
                    $variants->getBaseObject()->modifyCacheKey('_variants');
                }

                startProfile("selectVariants");
                $forceCoreTableUsage = (bool) $forceCoreTableUsage;

                $baseObject = $variants->getBaseObject();
                $this->updateVariantsBaseObject($baseObject, $forceCoreTableUsage);

                $sArticleTable = $this->getViewName($forceCoreTableUsage);

                $query = $this->getLoadVariantsQuery($blRemoveNotOrderables, $forceCoreTableUsage, $baseObject, $sArticleTable);
                $variants->selectString($query);

                //if this is multidimensional variants, make additional processing
                if ($config->getConfigParam('blUseMultidimensionVariants')) {
                    $oMdVariants = oxNew(VariantHandler::class);
                    $this->_blHasMdVariants = $oMdVariants->isMdVariant($variants->current());
                }
                stopProfile("selectVariants");
            }

            //if we have variants then depending on config option the parent may be non-buyable
            if (!$config->getConfigParam('blVariantParentBuyable') && $this->_blHasVariants) {
                $this->_blNotBuyableParent = true;
            }

            //if we have variants, but all variants are inactive means article may be non-buyable (depends on config option)
            if (!$config->getConfigParam('blVariantParentBuyable') && count($variants) == 0 && $this->_blHasVariants) {
                $this->_blNotBuyable = true;
            }
        }

        return $variants;
    }

    /**
     * Selects category IDs from given SQL statement and ID field name
     *
     * @param string $query sql statement
     * @param string $field category ID field name
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "selectCategoryIds" in next major
     */
    protected function _selectCategoryIds($query, $field) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $aResult = $oDb->getAll($query);
        $aReturn = [];

        foreach ($aResult as $aValue) {
            $aValue = array_change_key_case($aValue, CASE_LOWER);

            $aReturn[] = $aValue[$field];
        }

        return $aReturn;
    }

    /**
     * Returns query for article categories select
     *
     * @param bool $blActCats select categories if all parents are active
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategoryIdsSelect" in next major
     */
    protected function _getCategoryIdsSelect($blActCats = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sO2CView = $this->_getObjectViewName('oxobject2category');
        $sCatView = $this->_getObjectViewName('oxcategories');

        $sArticleIdSql = 'oxobject2category.oxobjectid=' . DatabaseProvider::getDb()->quote($this->getId());
        if ($this->getParentId()) {
            $sArticleIdSql = '(' . $sArticleIdSql . ' or oxobject2category.oxobjectid=' . DatabaseProvider::getDb()->quote($this->getParentId()) . ')';
        }
        $sActiveCategorySql = $blActCats ? $this->_getActiveCategorySelectSnippet() : '';

        $sSelect = "select
                        oxobject2category.oxcatnid as oxcatnid
                     from $sO2CView as oxobject2category
                        left join $sCatView as oxcategories on oxcategories.oxid = oxobject2category.oxcatnid
                    where $sArticleIdSql and oxcategories.oxid is not null and oxcategories.oxactive = 1 $sActiveCategorySql
                    order by oxobject2category.oxtime";

        return $sSelect;
    }

    /**
     * Returns active category select snippet
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getActiveCategorySelectSnippet" in next major
     */
    protected function _getActiveCategorySelectSnippet() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCatView = $this->_getObjectViewName('oxcategories');

        return "and oxcategories.oxhidden = 0 and (select count(cats.oxid) from $sCatView as cats where cats.oxrootid = oxcategories.oxrootid and cats.oxleft < oxcategories.oxleft and cats.oxright > oxcategories.oxright and ( cats.oxhidden = 1 or cats.oxactive = 0 ) ) = 0 ";
    }

    /**
     * Calculates price of article (adds taxes, currency and discounts).
     *
     * @param Price $oPrice price object
     * @param null $dVat vat value, optional, if passed, bypasses "bl_perfCalcVatOnlyForBasketOrder" config value
     *
     * @return Price
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "calculatePrice" in next major
     */
    protected function _calculatePrice($oPrice, $dVat = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // apply VAT only if configuration requires it
        if (isset($dVat) || !Registry::getConfig()->getConfigParam('bl_perfCalcVatOnlyForBasketOrder')) {
            $this->_applyVAT($oPrice, isset($dVat) ? $dVat : $this->getArticleVat());
        }

        // apply currency
        $this->_applyCurrency($oPrice);
        // apply discounts
        if (!$this->skipDiscounts()) {
            $oDiscountList = Registry::get(DiscountList::class);
            $aDiscounts = $oDiscountList->getArticleDiscounts($this, $this->getArticleUser());

            reset($aDiscounts);
            foreach ($aDiscounts as $oDiscount) {
                $oPrice->setDiscount($oDiscount->getAddSum(), $oDiscount->getAddSumType());
            }
            $oPrice->calculateDiscount();
        }

        return $oPrice;
    }

    /**
     * Checks if parent has ANY variant assigned
     *
     * @param null $blForceCoreTable force core table usage
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "hasAnyVariant" in next major
     */
    protected function _hasAnyVariant($blForceCoreTable = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($sId = $this->getId())) {
            if ($this->oxarticles__oxshopid->value == Registry::getConfig()->getShopId()) {
                return (bool) $this->oxarticles__oxvarcount->value;
            }
            $sArticleTable = $this->getViewName($blForceCoreTable);

            $db = DatabaseProvider::getDb();
            return (bool)$db->getOne("select 1 from $sArticleTable where oxparentid = :oxparentid", [
                ':oxparentid' => $sId
            ]);
        }

        return false;
    }

    /**
     * Check if stock status has changed since loading the article
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isStockStatusChanged" in next major
     */
    protected function _isStockStatusChanged() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_iStockStatus != $this->_iStockStatusOnLoad;
    }

    /**
     * Check if visibility has changed since loading the article
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isVisibilityChanged" in next major
     */
    protected function _isVisibilityChanged() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_isStockStatusChanged() && ($this->_iStockStatus == -1 || $this->_iStockStatusOnLoad == -1);
    }

    /**
     * inserts article long description to oxartextends table
     *
     * @return void
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "saveArtLongDesc" in next major
     */
    protected function _saveArtLongDesc() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (in_array("oxlongdesc", $this->_aSkipSaveFields)) {
            return;
        }

        if ($this->_blEmployMultilanguage) {
            $sValue = $this->getLongDescription()->getRawValue();
            if ($sValue !== null) {
                $oArtExt = oxNew(MultiLanguageModel::class);
                $oArtExt->init('oxartextends');
                $oArtExt->setLanguage((int) $this->getLanguage());
                if (!$oArtExt->load($this->getId())) {
                    $oArtExt->setId($this->getId());
                }
                $oArtExt->oxartextends__oxlongdesc = new Field($sValue, Field::T_RAW);
                $oArtExt->save();
            }
        } else {
            $oArtExt = oxNew(MultiLanguageModel::class);
            $oArtExt->setEnableMultilang(false);
            $oArtExt->init('oxartextends');
            $aObjFields = $oArtExt->_getAllFields(true);
            if (!$oArtExt->load($this->getId())) {
                $oArtExt->setId($this->getId());
            }

            foreach ($aObjFields as $sKey => $sValue) {
                if (preg_match('/^oxlongdesc(_(\d{1,2}))?$/', $sKey)) {
                    $sField = $this->_getFieldLongName($sKey);

                    if (isset($this->$sField)) {
                        $sLongDesc = null;
                        if ($this->$sField instanceof Field) {
                            $sLongDesc = $this->$sField->getRawValue();
                        } elseif (is_object($this->$sField)) {
                            $sLongDesc = $this->$sField->value;
                        }
                        if (isset($sLongDesc)) {
                            $sAEField = $oArtExt->_getFieldLongName($sKey);
                            $oArtExt->$sAEField = new Field($sLongDesc, Field::T_RAW);
                        }
                    }
                }
            }
            $oArtExt->save();
        }
    }

    /**
     * Removes object data fields (oxarticles__oxtimestamp, oxarticles__oxparentid, oxarticles__oxinsert).
     * @deprecated underscore prefix violates PSR12, will be renamed to "skipSaveFields" in next major
     */
    protected function _skipSaveFields() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_aSkipSaveFields = [];

        $this->_aSkipSaveFields[] = 'oxtimestamp';
        // $this->_aSkipSaveFields[] = 'oxlongdesc';
        $this->_aSkipSaveFields[] = 'oxinsert';
        $this->_addSkippedSaveFieldsForMapping();

        if (!$this->_blAllowEmptyParentId && (!isset($this->oxarticles__oxparentid->value) || $this->oxarticles__oxparentid->value == '')) {
            $this->_aSkipSaveFields[] = 'oxparentid';
        }
    }

    /**
     * Merges two discount arrays. If there are two the same
     * discounts, discount values will be added.
     *
     * @param array $aDiscounts     Discount array
     * @param array $aItemDiscounts Discount array
     *
     * @return array $aDiscounts
     * @deprecated underscore prefix violates PSR12, will be renamed to "mergeDiscounts" in next major
     */
    protected function _mergeDiscounts($aDiscounts, $aItemDiscounts) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        foreach ($aItemDiscounts as $sKey => $oDiscount) {
            // add prices of the same discounts
            if (array_key_exists($sKey, $aDiscounts)) {
                $aDiscounts[$sKey]->dDiscount += $oDiscount->dDiscount;
            } else {
                $aDiscounts[$sKey] = $oDiscount;
            }
        }

        return $aDiscounts;
    }

    /**
     * get user Group A, B or C price, returns db price if user is not in groups
     *
     * @return double
     * @deprecated underscore prefix violates PSR12, will be renamed to "getGroupPrice" in next major
     */
    protected function _getGroupPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPriceSuffix = $this->_getUserPriceSufix();
        $sVarName = "oxarticles__oxprice{$sPriceSuffix}";
        $dPrice = $this->$sVarName->value;

        // #1437/1436C - added config option, and check for zero A,B,C price values
        if (Registry::getConfig()->getConfigParam('blOverrideZeroABCPrices') && (double) $dPrice == 0) {
            $dPrice = $this->oxarticles__oxprice->value;
        }

        return $dPrice;
    }

    /**
     * Modifies article price depending on given amount.
     * Takes data from oxprice2article table.
     *
     * @param int $amount Basket amount
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAmountPrice" in next major
     */
    protected function _getAmountPrice($amount = 1) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        startProfile("_getAmountPrice");

        $dPrice = $this->_getGroupPrice();
        $oAmtPrices = $this->_getAmountPriceList();
        foreach ($oAmtPrices as $oAmPrice) {
            if (
                $oAmPrice->oxprice2article__oxamount->value <= $amount
                && $amount <= $oAmPrice->oxprice2article__oxamountto->value
                && $dPrice > $oAmPrice->oxprice2article__oxaddabs->value
            ) {
                $dPrice = $oAmPrice->oxprice2article__oxaddabs->value;
            }
        }

        stopProfile("_getAmountPrice");

        return $dPrice;
    }

    /**
     * Modifies article price according to selected select list value
     *
     * @param double $dPrice Modifiable price
     * @param null $aChosenList Selection list array
     *
     * @return double
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "modifySelectListPrice" in next major
     */
    protected function _modifySelectListPrice($dPrice, $aChosenList = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        // #690
        if ($myConfig->getConfigParam('bl_perfLoadSelectLists') && $myConfig->getConfigParam('bl_perfUseSelectlistPrice')) {
            $aSelLists = $this->getSelectLists();

            foreach ($aSelLists as $key => $aSel) {
                if (isset($aChosenList[$key]) && isset($aSel[$aChosenList[$key]])) {
                    $oSel = $aSel[$aChosenList[$key]];
                    if ($oSel->priceUnit == 'abs') {
                        $dPrice += $oSel->price;
                    } elseif ($oSel->priceUnit == '%') {
                        $dPrice += Price::percent($dPrice, $oSel->price);
                    }
                }
            }
        }

        return $dPrice;
    }

    /**
     * Fills amount price list object and sets amount price for article object
     *
     * @param array $aAmPriceList Amount price list
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "fillAmountPriceList" in next major
     */
    protected function _fillAmountPriceList($aAmPriceList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oLang = Registry::getLang();

        // trying to find the lowest price value
        foreach ($aAmPriceList as $sId => $oItem) {
            $oItemPrice = $this->_getPriceObject();
            if ($oItem->oxprice2article__oxaddabs->value) {
                $dBasePrice = $oItem->oxprice2article__oxaddabs->value;
                $dBasePrice = $this->_prepareModifiedPrice($dBasePrice);

                $oItemPrice->setPrice($dBasePrice);
                $this->_calculatePrice($oItemPrice);
            } else {
                $dBasePrice = $this->_getGroupPrice();

                $dBasePrice = $this->_prepareModifiedPrice($dBasePrice);

                $oItemPrice->setPrice($dBasePrice);
                $oItemPrice->subtractPercent($oItem->oxprice2article__oxaddperc->value);
            }

            $aAmPriceList[$sId]->fbrutprice = $oLang->formatCurrency($oItemPrice->getBruttoPrice());
            $aAmPriceList[$sId]->fnetprice = $oLang->formatCurrency($oItemPrice->getNettoPrice());

            if ($quantity = $this->getUnitQuantity()) {
                $aAmPriceList[$sId]->fbrutamountprice = $oLang->formatCurrency($oItemPrice->getBruttoPrice() / $quantity);
                $aAmPriceList[$sId]->fnetamountprice = $oLang->formatCurrency($oItemPrice->getNettoPrice() / $quantity);
            }
        }

        return $aAmPriceList;
    }

    /**
     * Collects and returns active/all variant ids of article.
     *
     * @param bool $blActiveVariants Parameter to load only active variants.
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getVariantIds($blActiveVariants = true)
    {
        $aSelect = [];
        $sId = $this->getId();
        if ($sId) {
            $sActiveSqlSnippet = "";
            if ($blActiveVariants) {
                $sActiveSqlSnippet = " and " . $this->getSqlActiveSnippet(true);
            }
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            $sQ = "select oxid from " . $this->getViewName(true) . " 
                where oxparentid = :oxparentid" . $sActiveSqlSnippet . " order by oxsort";
            $oRs = $oDb->select($sQ, [
                ':oxparentid' => $sId
            ]);
            if ($oRs && $oRs->count() > 0) {
                while (!$oRs->EOF) {
                    $aSelect[] = reset($oRs->fields);
                    $oRs->fetchRow();
                }
            }
        }

        return $aSelect;
    }

    /**
     * retrieve article VAT (cached)
     *
     * @return double
     * @throws DatabaseConnectionException
     */
    public function getArticleVat()
    {
        if (!isset($this->_dArticleVat)) {
            $this->_dArticleVat = Registry::get(VatSelector::class)->getArticleVat($this);
        }

        return $this->_dArticleVat;
    }

    /**
     * Applies VAT to article
     *
     * @param Price $oPrice Price object
     * @param double $dVat VAT percent
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyVAT" in next major
     */
    protected function _applyVAT(Price $oPrice, $dVat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        startProfile(__FUNCTION__);
        $oPrice->setVAT($dVat);
        /** @var VatSelector $oVatSelector */
        $oVatSelector = Registry::get(VatSelector::class);
        if (($dVat = $oVatSelector->getArticleUserVat($this)) !== false) {
            $oPrice->setUserVat($dVat);
        }
        stopProfile(__FUNCTION__);
    }

    /**
     * Applies currency factor
     *
     * @param Price $oPrice Price object
     * @param object                       $oCur   Currency object
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyCurrency" in next major
     */
    protected function _applyCurrency(Price $oPrice, $oCur = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$oCur) {
            $oCur = Registry::getConfig()->getActShopCurrencyObject();
        }

        $oPrice->multiply($oCur->rate);
    }

    /**
     * gets attribs string
     *
     * @param string $sAttributeSql Attribute selection snippet
     * @param int $iCnt The number of selected attributes
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAttribsString" in next major
     */
    protected function _getAttribsString(&$sAttributeSql, &$iCnt) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // we do not use lists here as we don't need this overhead right now
        $oDb = DatabaseProvider::getDb();
        $sSelect = 'select oxattrid from oxobject2attribute 
            where oxobject2attribute.oxobjectid = :oxobjectid';
        if ($this->getParentId()) {
            $sSelect .= ' OR oxobject2attribute.oxobjectid = :oxparentid';
        }
        $sAttributeSql = '';
        $aAttributeIds = $oDb->getCol($sSelect, [
            ':oxobjectid' => $this->getId(),
            ':oxparentid' => $this->getParentId()
        ]);
        if (is_array($aAttributeIds) && count($aAttributeIds)) {
            $aAttributeIds = array_unique($aAttributeIds);
            $iCnt = count($aAttributeIds);
            $sAttributeSql .= 't1.oxattrid IN ( ' . implode(',', $oDb->quoteArray($aAttributeIds)) . ') ';
        }
    }

    /**
     * Gets similar list.
     *
     * @param string $sAttributeSql Attribute selection snippet
     * @param int $iCnt Similar list article count
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSimList" in next major
     */
    protected function _getSimList($sAttributeSql, $iCnt) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // #523A
        $iAttrPercent = Registry::getConfig()->getConfigParam('iAttributesPercent') / 100;
        // 70% same attributes
        if (!$iAttrPercent || $iAttrPercent < 0 || $iAttrPercent > 1) {
            $iAttrPercent = 0.70;
        }
        // #1137V iAttributesPercent = 100 doesn't work
        $iHitMin = ceil($iCnt * $iAttrPercent);

        $aExcludeIds = [];
        $aExcludeIds[] = $this->getId();
        if ($this->getParentId()) {
            $aExcludeIds[] = $this->getParentId();
        }

        // we do not use lists here as we don't need this overhead right now
        $sSelect = "select oxobjectid from oxobject2attribute as t1 where
                    ( $sAttributeSql )
                    and t1.oxobjectid NOT IN (" . implode(', ', DatabaseProvider::getDb()->quoteArray($aExcludeIds)) . ")
                    group by t1.oxobjectid having count(*) >= :minhit LIMIT 0, 20";

        return DatabaseProvider::getDb()->getCol($sSelect, [
            ':minhit' => $iHitMin
        ]);
    }

    /**
     * Generates search string for similar list.
     *
     * @param string $sArticleTable Article table name
     * @param array $aList A list of original articles
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "generateSimListSearchStr" in next major
     */
    protected function _generateSimListSearchStr($sArticleTable, $aList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sFieldList = $this->getSelectFields();
        $aList = array_slice($aList, 0, Registry::getConfig()->getConfigParam('iNrofSimilarArticles'));

        $sSearch = "select $sFieldList from $sArticleTable where " . $this->getSqlActiveSnippet() . "  and $sArticleTable.oxissearch = 1 and $sArticleTable.oxid in ( ";

        $sSearch .= implode(',', DatabaseProvider::getDb()->quoteArray($aList)) . ')';

        // #524A -- randomizing articles in attribute list
        $sSearch .= ' order by rand() ';

        return $sSearch;
    }

    /**
     * Generates SearchString for getCategory()
     *
     * @param string $sOXID Article ID
     * @param bool $blSearchPriceCat Whether to perform the search within price categories
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "generateSearchStr" in next major
     */
    protected function _generateSearchStr($sOXID, $blSearchPriceCat = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCatView = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories', $this->getLanguage());
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');

        // we do not use lists here as we don't need this overhead right now
        if (!$blSearchPriceCat) {
            return "select {$sCatView}.* from {$sO2CView} as oxobject2category left join {$sCatView} on
                         {$sCatView}.oxid = oxobject2category.oxcatnid
                         where oxobject2category.oxobjectid=" . DatabaseProvider::getDb()->quote($sOXID) . " and {$sCatView}.oxid is not null ";
        }
        return "select {$sCatView}.* from {$sCatView} where
                      '{$this->oxarticles__oxprice->value}' >= {$sCatView}.oxpricefrom and
                      '{$this->oxarticles__oxprice->value}' <= {$sCatView}.oxpriceto ";
    }

    /**
     * Generates SQL select string for getCustomerAlsoBoughtThisProduct
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "generateSearchStrForCustomerBought" in next major
     */
    protected function _generateSearchStrForCustomerBought() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArtTable = $this->getViewName();
        $sOrderArtTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxorderarticles');

        // fetching filter params
        $sIn = " '{$this->oxarticles__oxid->value}' ";
        if ($this->oxarticles__oxparentid->value) {
            // adding article parent
            $sIn .= ", '{$this->oxarticles__oxparentid->value}' ";
            $sParentIdForVariants = $this->oxarticles__oxparentid->value;
        } else {
            $sParentIdForVariants = $this->getId();
        }

        // adding variants
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $params = [
            ':oxparentid' => $sParentIdForVariants,
            ':oxid' => $this->oxarticles__oxid->value
        ];
        $oRs = $oDb->select("select oxid from {$sArtTable} 
            where oxparentid = :oxparentid 
            and oxid != :oxid ", $params);
        if ($oRs && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $sIn .= ", " . $oDb->quote(current($oRs->fields)) . " ";
                $oRs->fetchRow();
            }
        }

        $iLimit = (int) Registry::getConfig()->getConfigParam('iNrofCustomerWhoArticles');
        $iLimit = $iLimit ? ($iLimit * 10) : 50;

        // building sql (optimized)
        return "select distinct {$sArtTable}.* from (
                   select d.oxorderid as suborderid from {$sOrderArtTable} as d use index ( oxartid ) where d.oxartid in ( {$sIn} ) limit {$iLimit}
               ) as suborder
               left join {$sOrderArtTable} force index ( oxorderid ) on suborder.suborderid = {$sOrderArtTable}.oxorderid
               left join {$sArtTable} on {$sArtTable}.oxid = {$sOrderArtTable}.oxartid
               where {$sArtTable}.oxid not in ( {$sIn} )
               and ( {$sArtTable}.oxissearch = 1 or {$sArtTable}.oxparentid <> '' ) and " . $this->getSqlActiveSnippet();

        /* non optimized, but could be used if index forcing is not supported
        // building sql
        $sQ = "select distinct {$sArtTable}.* from {$sOrderArtTable}, {$sArtTable} where {$sOrderArtTable}.oxorderid in (
                   select {$sOrderArtTable}.oxorderid from {$sOrderArtTable} where {$sOrderArtTable}.oxartid in ( {$sIn} )
               ) and {$sArtTable}.oxid = {$sOrderArtTable}.oxartid and {$sArtTable}.oxid not in ( {$sIn} )
               and ( {$sArtTable}.oxissearch = 1 or {$sArtTable}.oxparentid <> '' )
               and ".$this->getSqlActiveSnippet();
        */
    }

    /**
     * Generates select string for isAssignedToCategory()
     *
     * @param string $sOXID Article ID
     * @param string $sCatId Category ID
     * @param bool $dPriceFromTo Article price for price categories
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "generateSelectCatStr" in next major
     */
    protected function _generateSelectCatStr($sOXID, $sCatId, $dPriceFromTo = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCategoryView = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories');
        $sO2CView = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');

        $oDb = DatabaseProvider::getDb();
        $sOXID = $oDb->quote($sOXID);
        $sCatId = $oDb->quote($sCatId);

        if (!$dPriceFromTo) {
            $sSelect = "select oxobject2category.oxcatnid from $sO2CView as oxobject2category ";
            $sSelect .= "left join $sCategoryView as oxcategories on oxcategories.oxid = oxobject2category.oxcatnid ";
            $sSelect .= "where oxobject2category.oxcatnid=$sCatId and oxobject2category.oxobjectid=$sOXID ";
            $sSelect .= "and oxcategories.oxactive = 1 order by oxobject2category.oxtime ";
        } else {
            $dPriceFromTo = $oDb->quote($dPriceFromTo);
            $sSelect = "select oxcategories.oxid from $sCategoryView as oxcategories where ";
            $sSelect .= "oxcategories.oxid=$sCatId and $dPriceFromTo >= oxcategories.oxpricefrom and ";
            $sSelect .= "$dPriceFromTo <= oxcategories.oxpriceto ";
        }

        return $sSelect;
    }

    /**
     * Collecting assigned to article amount-price list
     *
     * @return AmountPriceList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated on b-dev (2015-04-02); use buildAmountPriceList().
     *
     */
    protected function _getAmountPriceList() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->buildAmountPriceList();
    }

    /**
     * Collecting assigned to article amount-price list.
     *
     * @return AmountPriceList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function buildAmountPriceList()
    {
        if ($this->getAmountPriceList() === null) {
            /** @var AmountPriceList $oAmPriceList */
            $oAmPriceList = oxNew(AmountPriceList::class);
            $this->setAmountPriceList($oAmPriceList);

            if (!$this->skipDiscounts()) {
                //collecting assigned to article amount-price list
                $oAmPriceList->load($this);

                // prepare abs prices if currently having percentages
                $oBasePrice = $this->_getGroupPrice();
                foreach ($oAmPriceList as $oAmPrice) {
                    if ($oAmPrice->oxprice2article__oxaddperc->value) {
                        $oAmPrice->oxprice2article__oxaddabs = new Field(
                            Price::percent($oBasePrice, 100 - $oAmPrice->oxprice2article__oxaddperc->value),
                            Field::T_RAW
                        );
                    }
                }
            }

            $this->setAmountPriceList($oAmPriceList);
        }

        return $this->_oAmountPriceList;
    }

    /**
     * Detects if field is empty.
     *
     * @param string $sFieldName Field name
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isFieldEmpty" in next major
     */
    protected function _isFieldEmpty($sFieldName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $mValue = $this->$sFieldName->value;

        if (is_null($mValue)) {
            return true;
        }

        if ($mValue === '') {
            return true;
        }

        // certain fields with zero value treat as empty
        $aZeroValueFields = ['oxarticles__oxprice', 'oxarticles__oxvat', 'oxarticles__oxunitquantity'];

        if (!$mValue && in_array($sFieldName, $aZeroValueFields)) {
            return true;
        }


        if (!strcmp($mValue, '0000-00-00 00:00:00') || !strcmp($mValue, '0000-00-00')) {
            return true;
        }

        $sFieldName = strtolower($sFieldName);

        if (
            $sFieldName == 'oxarticles__oxicon' && (strpos($mValue, "nopic_ico.jpg") !== false || strpos(
                $mValue,
                "nopic.jpg"
            ) !== false)
        ) {
            return true;
        }

        if (
            strpos($mValue, "nopic.jpg") !== false && ($sFieldName == 'oxarticles__oxthumb' || substr(
                $sFieldName,
                0,
                17
            ) == 'oxarticles__oxpic' || substr($sFieldName, 0, 18) == 'oxarticles__oxzoom')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Assigns parent field values to article
     *
     * @param string $sFieldName field name
     *
     * @return void
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignParentFieldValue" in next major
     */
    protected function _assignParentFieldValue($sFieldName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!($oParentArticle = $this->getParentArticle())) {
            return;
        }

        $sCopyFieldName = $this->_getFieldLongName($sFieldName);

        // assigning only these which parent article has
        if ($oParentArticle->$sCopyFieldName != null) {
            // only overwrite database values
            if (substr($sCopyFieldName, 0, 12) != 'oxarticles__') {
                return;
            }

            //do not copy certain fields
            if (in_array($sCopyFieldName, $this->_aNonCopyParentFields)) {
                return;
            }

            //skip picture parent value assignment in case master image is set for variant
            if ($this->_isFieldEmpty($sCopyFieldName) && $this->_isImageField($sCopyFieldName) && $this->_hasMasterImage(1)) {
                return;
            }

            //COPY THE VALUE
            if ($this->_isFieldEmpty($sCopyFieldName)) {
                $this->$sCopyFieldName = clone $oParentArticle->$sCopyFieldName;
            }
        }
    }

    /**
     * Detects if field is an image field by field name
     *
     * @param string $sFieldName Field name
     *
     * @return bool .
     * @deprecated underscore prefix violates PSR12, will be renamed to "isImageField" in next major
     */
    protected function _isImageField($sFieldName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return (stristr($sFieldName, '_oxthumb') || stristr($sFieldName, '_oxicon') || stristr(
            $sFieldName,
            '_oxzoom'
        ) || stristr($sFieldName, '_oxpic'));
    }

    /**
     * Assigns parent field values to article
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignParentFieldValues" in next major
     */
    protected function _assignParentFieldValues() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        startProfile('articleAssignParentInternal');
        if ($this->oxarticles__oxparentid->value) {
            // yes, we are in fact a variant
            if (!$this->isAdmin() || ($this->_blLoadParentData && $this->isAdmin())) {
                foreach ($this->_aFieldNames as $sFieldName => $sVal) {
                    $this->_assignParentFieldValue($sFieldName);
                }
            }
        }
        stopProfile('articleAssignParentInternal');
    }

    /**
     * if we have variants then depending on config option the parent may be non-buyable
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignNotBuyableParent" in next major
     */
    protected function _assignNotBuyableParent() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            !Registry::getConfig()->getConfigParam('blVariantParentBuyable') &&
            ($this->_blHasVariants || $this->oxarticles__oxvarstock->value || $this->oxarticles__oxvarcount->value)
        ) {
            $this->_blNotBuyableParent = true;
        }
    }

    /**
     * Assigns stock status to article
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignStock" in next major
     */
    protected function _assignStock() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        // -----------------------------------
        // stock
        // -----------------------------------

        // #1125 A. must round (using floor()) value taken from database and cast to int
        if (!$myConfig->getConfigParam('blAllowUnevenAmounts') && !$this->isAdmin()) {
            $this->oxarticles__oxstock = new Field((int) floor($this->oxarticles__oxstock->value));
        }
        //GREEN light
        $this->_iStockStatus = 0;

        // if we have flag /*1 or*/ 4 - we show always green light
        if (
            $myConfig->getConfigParam('blUseStock') && /*$this->oxarticles__oxstockflag->value != 1 && */
            $this->oxarticles__oxstockflag->value != 4
        ) {
            //ORANGE light
            $iStock = $this->oxarticles__oxstock->value;

            if ($this->_blNotBuyableParent) {
                $iStock = $this->oxarticles__oxvarstock->value;
            }

            if ($iStock <= $myConfig->getConfigParam('sStockWarningLimit') && $iStock > 0) {
                $this->_iStockStatus = 1;
            }

            //RED light
            if ($iStock <= 0) {
                $this->_iStockStatus = -1;
            }
        }


        // stock
        if ($myConfig->getConfigParam('blUseStock') && ($this->oxarticles__oxstockflag->value == 3 || $this->oxarticles__oxstockflag->value == 2)) {
            $iOnStock = $this->oxarticles__oxstock->value;
            if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
                $iOnStock += Registry::getSession()->getBasketReservations()->getReservedAmount($this->getId());
            }
            if ($iOnStock <= 0) {
                $this->setBuyableState(false);
            }
        }

        //exceptional handling for variant parent stock:
        if ($this->_blNotBuyable && $this->oxarticles__oxvarstock->value) {
            $this->setBuyableState(true);
            //but then at least setting notBuyableParent to true
            $this->_blNotBuyableParent = true;
        }

        //special treatment for lists when blVariantParentBuyable config option is set to false
        //then we just hide "to basket" button.
        //if variants are not loaded in the list and this article has variants and parent is not buyable then this article is not buyable
        if (!$myConfig->getConfigParam('blVariantParentBuyable') && !$myConfig->getConfigParam('blLoadVariants') && $this->oxarticles__oxvarstock->value) {
            $this->setBuyableState(false);
        }

        //setting to non-buyable when variant list is empty (for example not loaded or inactive) and $this is non-buyable parent
        if (!$this->_blNotBuyable && $this->_blNotBuyableParent && $this->oxarticles__oxvarcount->value == 0) {
            $this->setBuyableState(false);
        }
    }

    /**
     * Assigns persistent param to article.
     *
     * @deprecated on b-dev (2015-11-30); Not used anymore. Setting pers params to session was removed since 2.7.
     */
    protected function _assignPersistentParam() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // Persistent Parameter Handling
        $aPersParam = Registry::getSession()->getVariable('persparam');
        if (isset($aPersParam[$this->getId()])) {
            $this->_aPersistParam = $aPersParam[$this->getId()];
        }
    }

    /**
     * assigns DynImageDir to article
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignDynImageDir" in next major
     */
    protected function _assignDynImageDir() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();

        $sThisShop = $this->oxarticles__oxshopid->value;

        $this->_sDynImageDir = $myConfig->getPictureUrl(null, false);
        $this->dabsimagedir = $myConfig->getPictureDir(false); //$sThisShop
        $this->nossl_dimagedir = $myConfig->getPictureUrl(null, false, false, null, $sThisShop); //$sThisShop
        $this->ssl_dimagedir = $myConfig->getPictureUrl(null, false, true, null, $sThisShop); //$sThisShop
    }

    /**
     * Adds a flag if article is on comparison-list.
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignComparisonListFlag" in next major
     */
    protected function _assignComparisonListFlag() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // #657 add a flag if article is on comparison-list

        $aItems = Registry::getSession()->getVariable('aFiltcompproducts');
        if (isset($aItems[$this->getId()])) {
            $this->_blIsOnComparisonList = true;
        }
    }

    /**
     * Sets article creation date
     * (Article::oxarticles__oxinsert). Then executes parent method
     * parent::_insert() and returns insertion status.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // set oxinsert
        $sNow = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
        $this->oxarticles__oxinsert = new Field($sNow);
        if (!is_object($this->oxarticles__oxsubclass) || $this->oxarticles__oxsubclass->value == '') {
            $this->oxarticles__oxsubclass = new Field('oxarticle');
        }

        return parent::_insert();
    }

    /**
     * Executes Article::_skipSaveFields() and updates article information
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "update" in next major
     */
    protected function _update() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setUpdateSeo(true);
        $this->_setUpdateSeoOnFieldChange('oxtitle');

        $this->_skipSaveFields();

        return parent::_update();
    }

    /**
     * Deletes records in database
     *
     * @param string $articleId Article ID
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteRecords" in next major
     */
    protected function _deleteRecords($articleId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();

        //remove other records
        $sDelete = 'delete from oxobject2article where oxarticlenid = :articleId or oxobjectid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxobject2attribute where oxobjectid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxobject2category where oxobjectid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxobject2selectlist where oxobjectid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxprice2article where oxartid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxreviews where oxtype="oxarticle" and oxobjectid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxratings where oxobjectid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxaccessoire2article where oxobjectid = :articleId or oxarticlenid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        //#1508C - deleting oxobject2delivery entries added
        $sDelete = 'delete from oxobject2delivery where oxobjectid = :articleId and oxtype=\'oxarticles\' ';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxartextends where oxid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        //delete the record
        foreach ($this->_getLanguageSetTables("oxartextends") as $sSetTbl) {
            $oDb->execute("delete from $sSetTbl where oxid = :articleId", [
                ':articleId' => $articleId
            ]);
        }

        $sDelete = 'delete from oxactions2article where oxartid = :articleId';
        $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);

        $sDelete = 'delete from oxobject2list where oxobjectid = :articleId';

        return $oDb->execute($sDelete, [
            ':articleId' => $articleId
        ]);
    }

    /**
     * Deletes variant records
     *
     * @param string $sOXID Article ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteVariantRecords" in next major
     */
    protected function _deleteVariantRecords($sOXID) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($sOXID) {
            $database = DatabaseProvider::getDb();
            //collect variants to remove recursively
            $query = 'select oxid from ' . $this->getViewName() . ' where oxparentid = :oxparentid';
            $rs = $database->select($query, [
                ':oxparentid' => $sOXID
            ]);
            $oArticle = oxNew(Article::class);
            if ($rs && $rs->count() > 0) {
                while (!$rs->EOF) {
                    $oArticle->setId($rs->fields[0]);
                    $oArticle->delete();
                    $rs->fetchRow();
                }
            }
        }
    }

    /**
     * Delete pics
     * @deprecated underscore prefix violates PSR12, will be renamed to "deletePics" in next major
     */
    protected function _deletePics() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $oPictureHandler = Registry::getPictureHandler();

        //deleting custom main icon
        $oPictureHandler->deleteMainIcon($this);

        //deleting custom thumbnail
        $oPictureHandler->deleteThumbnail($this);

        // deleting master image and all generated images
        $iPicCount = $myConfig->getConfigParam('iPicCount');
        for ($i = 1; $i <= $iPicCount; $i++) {
            $oPictureHandler->deleteArticleMasterPicture($this, $i);
        }
    }

    /**
     * Resets category and vendor counts. This method is supposed to be called on article change trigger.
     *
     * @param string $sOxid object to reset ID
     * @param null $sVendorId Vendor ID
     * @param null $sManufacturerId Manufacturer ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "onChangeResetCounts" in next major
     */
    protected function _onChangeResetCounts($sOxid, $sVendorId = null, $sManufacturerId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsCount = Registry::getUtilsCount();

        if ($sVendorId) {
            $myUtilsCount->resetVendorArticleCount($sVendorId);
        }

        if ($sManufacturerId) {
            $myUtilsCount->resetManufacturerArticleCount($sManufacturerId);
        }

        $aCategoryIds = $this->getCategoryIds();
        //also reseting category counts
        foreach ($aCategoryIds as $sCatId) {
            $myUtilsCount->resetCatArticleCount($sCatId);
        }
    }

    /**
     * Updates article stock. This method is supposed to be called on article change trigger.
     *
     * @param string $parentId product parent id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "onChangeUpdateStock" in next major
     */
    protected function _onChangeUpdateStock($parentId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($parentId) {
            $database = DatabaseProvider::getDb();
            $query = 'SELECT oxstock, oxvendorid, oxmanufacturerid FROM oxarticles WHERE oxid = :oxid';
            $rs = $database->select($query, [
                ':oxid' => $parentId
            ]);
            $oldStock = $rs->fields[0];
            $vendorId = $rs->fields[1];
            $manufacturerId = $rs->fields[2];

            $query = 'SELECT SUM(oxstock) FROM ' . $this->getViewName(true) . ' 
                WHERE oxparentid = :oxparentid 
                AND ' . $this->getSqlActiveSnippet(true) . ' 
                AND oxstock > 0 ';
            $stock = (float) $database->getOne($query, [
                ':oxparentid' => $parentId
            ]);

            $query = 'UPDATE oxarticles SET oxvarstock = :oxvarstock WHERE oxid = :oxid';
            $database->execute($query, [
                ':oxvarstock' => $stock,
                ':oxid' => $parentId
            ]);

            //now lets update category counts
            //first detect stock status change for this article (to or from 0)
            if ($stock < 0) {
                $stock = 0;
            }
            if ($oldStock < 0) {
                $oldStock = 0;
            }
            if ($this->oxarticles__oxstockflag->value == 2 && $oldStock xor $stock) {
                //means the stock status could be changed (oxstock turns from 0 to 1 or from 1 to 0)
                // so far we leave it like this, but later we could move all count resets to one or two functions
                $this->_onChangeResetCounts($parentId, $vendorId, $manufacturerId);
            }
        }
    }

    /**
     * Resets article count cache when stock value is zero and article goes offline.
     *
     * @param string $sOxid product id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "onChangeStockResetCount" in next major
     */
    protected function _onChangeStockResetCount($sOxid) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();

        if (
            $myConfig->getConfigParam('blUseStock') && $this->oxarticles__oxstockflag->value == 2 &&
            ($this->oxarticles__oxstock->value + $this->oxarticles__oxvarstock->value) <= 0
        ) {
            $this->_onChangeResetCounts(
                $sOxid,
                $this->oxarticles__oxvendorid->value,
                $this->oxarticles__oxmanufacturerid->value
            );
        }
    }

    /**
     * Updates variant count. This method is supposed to be called on article change trigger.
     *
     * @param string $parentId Parent ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "onChangeUpdateVarCount" in next major
     */
    protected function _onChangeUpdateVarCount($parentId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($parentId) {
            $database = DatabaseProvider::getDb();

            $query = "SELECT COUNT(*) AS varcount FROM oxarticles WHERE oxparentid = :oxparentid";
            $varCount = (int) $database->getOne($query, [
                ':oxparentid' => $parentId
            ]);

            $query = "UPDATE oxarticles SET oxvarcount = :oxvarcount WHERE oxid = :oxid";
            $database->execute($query, [
                ':oxvarcount' => $varCount,
                ':oxid' => $parentId
            ]);
        }
    }

    /**
     * Updates variant min price. This method is supposed to be called on article change trigger.
     *
     * @param string $sParentId Parent ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setVarMinMaxPrice" in next major
     */
    protected function _setVarMinMaxPrice($sParentId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($sParentId) {
            $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            $sQ = '
                SELECT
                    MIN( IF( `oxarticles`.`oxprice` > 0, `oxarticles`.`oxprice`, `p`.`oxprice` ) ) AS `varminprice`,
                    MAX( IF( `oxarticles`.`oxprice` > 0, `oxarticles`.`oxprice`, `p`.`oxprice` ) ) AS `varmaxprice`
                FROM ' . $this->getViewName(true) . ' AS `oxarticles`
                    LEFT JOIN ' . $this->getViewName(true) . ' AS `p` ON ( `p`.`oxid` = `oxarticles`.`oxparentid` AND `p`.`oxprice` > 0 )
                WHERE ' . $this->getSqlActiveSnippet(true) . '
                    AND ( `oxarticles`.`oxparentid` = :oxparentid )';
            $aPrices = $database->getRow($sQ, [
                ':oxparentid' => $sParentId
            ]);
            if (isset($aPrices['varminprice'], $aPrices['varmaxprice'])) {
                $sQ = '
                    UPDATE `oxarticles`
                    SET
                        `oxvarminprice` = :oxvarminprice,
                        `oxvarmaxprice` = :oxvarmaxprice
                    WHERE
                        `oxid` = :oxid';
                $params = [
                    ':oxvarminprice' => $aPrices['varminprice'],
                    ':oxvarmaxprice' => $aPrices['varmaxprice'],
                    ':oxid' => $sParentId
                ];
            } else {
                $sQ = '
                    UPDATE `oxarticles`
                    SET
                        `oxvarminprice` = `oxprice`,
                        `oxvarmaxprice` = `oxprice`
                    WHERE
                        `oxid` = :oxid';
                $params = [':oxid' => $sParentId];
            }
            $database->execute($sQ, $params);
        }
    }

    /**
     * Checks if article has uploaded master image for selected picture
     *
     * @param int $iIndex master picture index
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "hasMasterImage" in next major
     */
    protected function _hasMasterImage($iIndex) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPicName = basename($this->{"oxarticles__oxpic" . $iIndex}->value);

        if ($sPicName == "nopic.jpg" || $sPicName == "") {
            return false;
        }
        if (
            $this->isVariant() &&
            $this->getParentArticle() &&
            $this->getParentArticle()->{"oxarticles__oxpic" . $iIndex}->value == $this->{"oxarticles__oxpic" . $iIndex}->value
        ) {
            return false;
        }

        $sMasterPic = 'product/' . $iIndex . "/" . $sPicName;

        if (Registry::getConfig()->getMasterPicturePath($sMasterPic)) {
            return true;
        }

        return false;
    }


    /**
     * Checks and return true if price view mode is netto
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isPriceViewModeNetto" in next major
     */
    protected function _isPriceViewModeNetto() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blResult = (bool) Registry::getConfig()->getConfigParam('blShowNetPrice');
        $oUser = $this->getArticleUser();
        if ($oUser) {
            $blResult = $oUser->isPriceViewModeNetto();
        }

        return $blResult;
    }


    /**
     * Depending on view mode prepare oxPrice object
     *
     * @param bool $blCalculationModeNetto - if calculation mode netto - true
     *
     * @return Price
     * @deprecated underscore prefix violates PSR12, will be renamed to "getPriceObject" in next major
     */
    protected function _getPriceObject($blCalculationModeNetto = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        /** @var Price $oPrice */
        $oPrice = oxNew(Price::class);

        if ($blCalculationModeNetto === null) {
            $blCalculationModeNetto = $this->_isPriceViewModeNetto();
        }

        if ($blCalculationModeNetto) {
            $oPrice->setNettoPriceMode();
        } else {
            $oPrice->setBruttoPriceMode();
        }

        return $oPrice;
    }

    /**
     * Depending on view mode prepare price for viewing
     *
     * @param Price $oPrice price object
     *
     * @return double
     * @deprecated underscore prefix violates PSR12, will be renamed to "getPriceForView" in next major
     */
    protected function _getPriceForView($oPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_isPriceViewModeNetto()) {
            $dPrice = $oPrice->getNettoPrice();
        } else {
            $dPrice = $oPrice->getBruttoPrice();
        }

        return $dPrice;
    }


    /**
     * Depending on view mode prepare price before calculation
     *
     * @param double $dPrice                 - price
     * @param double $dVat                   - VAT
     * @param bool   $blCalculationModeNetto - if calculation mode netto - true
     *
     * @return double
     * @deprecated underscore prefix violates PSR12, will be renamed to "preparePrice" in next major
     */
    protected function _preparePrice($dPrice, $dVat, $blCalculationModeNetto = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($blCalculationModeNetto === null) {
            $blCalculationModeNetto = $this->_isPriceViewModeNetto();
        }

        $oCurrency = Registry::getConfig()->getActShopCurrencyObject();

        $blEnterNetPrice = Registry::getConfig()->getConfigParam('blEnterNetPrice');
        if ($blCalculationModeNetto && !$blEnterNetPrice) {
            $dPrice = round(Price::brutto2Netto($dPrice, $dVat), $oCurrency->decimal);
        } elseif (!$blCalculationModeNetto && $blEnterNetPrice) {
            $dPrice = round(Price::netto2Brutto($dPrice, $dVat), $oCurrency->decimal);
        }

        return $dPrice;
    }


    /**
     * Return price suffix
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getUserPriceSufix" in next major
     */
    protected function _getUserPriceSufix() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPriceSuffix = '';
        $oUser = $this->getArticleUser();

        if ($oUser) {
            if ($oUser->inGroup('oxidpricea')) {
                $sPriceSuffix = 'a';
            } elseif ($oUser->inGroup('oxidpriceb')) {
                $sPriceSuffix = 'b';
            } elseif ($oUser->inGroup('oxidpricec')) {
                $sPriceSuffix = 'c';
            }
        }

        return $sPriceSuffix;
    }

    /**
     * Return prepared price
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getRawPrice" in next major
     */
    protected function _getPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPriceSuffix = $this->_getUserPriceSufix();
        if ($sPriceSuffix === '') {
            $dPrice = $this->oxarticles__oxprice->value;
        } else {
            if (Registry::getConfig()->getConfigParam('blOverrideZeroABCPrices')) {
                $dPrice = ($this->{'oxarticles__oxprice' . $sPriceSuffix}->value != 0) ? $this->{'oxarticles__oxprice' . $sPriceSuffix}->value : $this->oxarticles__oxprice->value;
            } else {
                $dPrice = $this->{'oxarticles__oxprice' . $sPriceSuffix}->value;
            }
        }

        return $dPrice;
    }

    /**
     * Return variant min price
     *
     * @return null
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVarMinRawPrice" in next major
     */
    protected function _getVarMinPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_dVarMinPrice === null) {
            $dPrice = $this->_getShopVarMinPrice();

            if (is_null($dPrice)) {
                $sPriceSuffix = $this->_getUserPriceSufix();
                if ($sPriceSuffix === '') {
                    $dPrice = $this->oxarticles__oxvarminprice->value;
                } else {
                    $sSql = 'SELECT ';
                    if (Registry::getConfig()->getConfigParam('blOverrideZeroABCPrices')) {
                        $sSql .= 'MIN( IF(`oxprice' . $sPriceSuffix . '` = 0, `oxprice`, `oxprice' . $sPriceSuffix . '`) ) AS `varminprice` ';
                    } else {
                        $sSql .= 'MIN(`oxprice' . $sPriceSuffix . '`) AS `varminprice` ';
                    }

                    $sSql .= ' FROM ' . $this->getViewName(true) . '
                    WHERE ' . $this->getSqlActiveSnippet(true) . '
                        AND ( `oxparentid` = :oxparentid )';

                    $dPrice = DatabaseProvider::getDb()->getOne($sSql, [
                        ':oxparentid' => $this->getId()
                    ]);
                }
            }

            $this->_dVarMinPrice = $dPrice;
        }

        return $this->_dVarMinPrice;
    }

    /**
     * Return variant max price
     *
     * @return null
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVarMaxPrice" in next major
     */
    protected function _getVarMaxPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_dVarMaxPrice === null) {
            $dPrice = $this->_getShopVarMaxPrice();

            if (is_null($dPrice)) {
                $sPriceSuffix = $this->_getUserPriceSufix();
                if ($sPriceSuffix === '') {
                    $dPrice = $this->oxarticles__oxvarmaxprice->value;
                } else {
                    $sSql = 'SELECT ';
                    if (Registry::getConfig()->getConfigParam('blOverrideZeroABCPrices')) {
                        $sSql .= 'MAX( IF(`oxprice' . $sPriceSuffix . '` = 0, `oxprice`, `oxprice' . $sPriceSuffix . '`) ) AS `varmaxprice` ';
                    } else {
                        $sSql .= 'MAX(`oxprice' . $sPriceSuffix . '`) AS `varmaxprice` ';
                    }

                    $sSql .= ' FROM ' . $this->getViewName(true) . '
                        WHERE ' . $this->getSqlActiveSnippet(true) . '
                            AND ( `oxparentid` = :oxparentid )';

                    $dPrice = DatabaseProvider::getDb()->getOne($sSql, [
                        ':oxparentid' => $this->getId()
                    ]);
                }
            }

            $this->_dVarMaxPrice = $dPrice;
        }

        return $this->_dVarMaxPrice;
    }

    /**
     * Place to hook to return variant min price if it might be different,
     * for example for subshops.
     *
     * @return double|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getShopVarMinPrice" in next major
     */
    protected function _getShopVarMinPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return null;
    }

    /**
     * Place to hook to return variant max price if it might be different,
     * for example for subshops.
     *
     * @return double|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getShopVarMaxPrice" in next major
     */
    protected function _getShopVarMaxPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return null;
    }

    /**
     * Get data from db
     *
     * @param string $articleId id
     *
     * @return array
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromDb" in next major
     */
    protected function _loadFromDb($articleId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSelect = $this->buildSelectString([$this->getViewName() . ".oxid" => $articleId]);

        return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow($sSelect);
    }


    /**
     * Place to hook and change amount if it should be calculated by different logic,
     * for example VPE.
     *
     * @param double $amount Amount
     */
    public function checkForVpe($amount)
    {
    }

    /**
     * Set parent field value to child - variants in DB
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateParentDependFields" in next major
     */
    protected function _updateParentDependFields() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();

        foreach ($this->_getCopyParentFields() as $sField) {
            $sValue = isset($this->$sField->value) ? $this->$sField->value : 0;
            $sSqlSets[] = '`' . str_replace('oxarticles__', '', $sField) . '` = ' . $oDb->quote($sValue);
        }

        $sSql = "UPDATE `oxarticles` SET ";
        $sSql .= implode(', ', $sSqlSets);
        $sSql .= " WHERE `oxparentid` = :oxparentid";

        return $oDb->execute($sSql, [':oxparentid' => $this->getId()]);
    }

    /**
     * Returns array of fields which should not changed in variants
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCopyParentFields" in next major
     */
    protected function _getCopyParentFields() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_aCopyParentField;
    }

    /**
     * Set parent field value to child - variants
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignParentDependFields" in next major
     */
    protected function _assignParentDependFields() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sParent = $this->getParentArticle();
        if ($sParent) {
            foreach ($this->_getCopyParentFields() as $sField) {
                $this->$sField = new Field($sParent->$sField->value);
            }
        }
    }

    /**
     * Saves values of sorting fields on article load.
     * @deprecated underscore prefix violates PSR12, will be renamed to "saveSortingFieldValuesOnLoad" in next major
     */
    protected function _saveSortingFieldValuesOnLoad() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aSortingFields = Registry::getConfig()->getConfigParam('aSortCols');
        $aSortingFields = !empty($aSortingFields) ? (array) $aSortingFields : [];

        foreach ($aSortingFields as $sField) {
            $sFullField = $this->_getFieldLongName($sField);
            $this->_aSortingFieldsOnLoad[$sFullField] = $this->$sFullField->value;
        }
    }

    /**
     * Forms query to load variants.
     *
     * @param bool                  $blRemoveNotOrderables
     * @param bool                  $forceCoreTableUsage
     * @param SimpleVariant|Article $baseObject
     * @param string                $sArticleTable
     *
     * @return string
     */
    protected function getLoadVariantsQuery($blRemoveNotOrderables, $forceCoreTableUsage, $baseObject, $sArticleTable)
    {
        return "select " . $baseObject->getSelectFields($forceCoreTableUsage) . " from $sArticleTable where " .
                 $this->getActiveCheckQuery($forceCoreTableUsage) .
                 $this->getVariantsQuery($blRemoveNotOrderables, $forceCoreTableUsage) .
                 " order by $sArticleTable.oxsort";
    }

    /**
     * Set needed parameters to article list object like language.
     *
     * @param BaseModel $baseObject          article list template object.
     * @param bool|null                              $forceCoreTableUsage if true forces core table use, default is false [optional]
     */
    protected function updateVariantsBaseObject($baseObject, $forceCoreTableUsage = null)
    {
        $baseObject->setLanguage($this->getLanguage());
    }

    /**
     * @param Manufacturer $oManufacturer
     */
    protected function updateManufacturerBeforeLoading($oManufacturer)
    {
        $oManufacturer->setReadOnly(true);
    }
}
