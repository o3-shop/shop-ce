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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\EshopCommunity\Application\Model\Contract\ArticleInterface;

/**
 * Order article manager.
 * Performs copying of article.
 */
class OrderArticle extends BaseModel implements ArticleInterface
{
    /**
     * Order cache.
     * @deprecated since v6.4.0 (2019-05-29); This static property will not be used anymore.
     */
    protected static $_aOrderCache = [];

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxorderarticle';

    /**
     * Persistent info
     *
     * @var array
     */
    protected $_aPersParam = null;

    /**
     * ERP status info
     *
     * @var array
     */
    protected $_aStatuses = null;

    /**
     * Order article selection list
     *
     * @var array
     */
    protected $_aOrderArticleSelList = null;

    /**
     * Order article instance
     *
     * @var Article
     */
    protected $_oOrderArticle = null;

    /**
     * Article instance
     *
     * @var Article
     */
    protected $_oArticle = null;

    /**
     * New order article marker
     *
     * @var bool
     */
    protected $_blIsNewOrderItem = false;

    /**
     * Array of fields to skip when saving
     * Overrides oxBase variable
     *
     * @var array
     */
    protected $_aSkipSaveFields = ['oxtimestamp'];

    /**
     * Class constructor, initiates class constructor (parent::oxbase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxorderarticles');
    }

    /**
     * Copies passed to method product into $this.
     *
     * @param object $oProduct product to copy
     */
    public function copyThis($oProduct)
    {
        $aObjectVars = get_object_vars($oProduct);

        foreach ($aObjectVars as $sName => $sValue) {
            if (isset($oProduct->$sName->value)) {
                $sFieldName = preg_replace('/oxarticles__/', 'oxorderarticles__', $sName);
                if ($sFieldName != "oxorderarticles__oxtimestamp") {
                    $this->$sFieldName = $oProduct->$sName;
                }
                // formatting view
                if (!Registry::getConfig()->getConfigParam('blSkipFormatConversion')) {
                    if ($sFieldName == "oxorderarticles__oxinsert") {
                        Registry::getUtilsDate()->convertDBDate($this->$sFieldName, true);
                    }
                }
            }
        }
    }

    /**
     * Assigns DB field values to object fields.
     */
    public function assign($dbRecord)
    {
        parent::assign($dbRecord);
        $this->_setArticleParams();
    }

    /**
     * Performs stock modification for current order article. Additionally,
     * executes changeable article onChange/updateSoldAmount methods to
     * update chained data
     *
     * @param double $dAddAmount amount which will be subtracted from value in db
     * @param bool $blAllowNegativeStock amount allow or not negative stock value
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function updateArticleStock($dAddAmount, $blAllowNegativeStock = false)
    {
        // TODO: use oxarticle reduceStock
        // decrement stock if there is any
        $oArticle = oxNew(Article::class);
        $oArticle->load($this->oxorderarticles__oxartid->value);
        $oArticle->beforeUpdate();

        if (Registry::getConfig()->getConfigParam('blUseStock')) {
            // get real article stock count
            $iStockCount = $this->_getArtStock($dAddAmount, $blAllowNegativeStock);
            $oDb = DatabaseProvider::getDb();

            $oArticle->oxarticles__oxstock = new Field($iStockCount);
            $oDb->execute('update oxarticles set oxarticles.oxstock = :oxstock where oxarticles.oxid = :oxid', [
                ':oxstock' => $iStockCount,
                ':oxid' => $this->oxorderarticles__oxartid->value
            ]);
            $oArticle->onChange(ACTION_UPDATE_STOCK);
        }

        //update article sold amount
        $oArticle->updateSoldAmount($dAddAmount * (-1));
    }

    /**
     * Adds or subtracts defined amount passed by param from article stock
     *
     * @param int $dAddAmount amount which will be added/subtracted from value in db
     * @param bool $blAllowNegativeStock allow/disallow negative stock value
     *
     * @return double
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getArtStock" in next major
     */
    protected function _getArtStock($dAddAmount = 0, $blAllowNegativeStock = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();

        // #1592A. must take real value
        $sQ = 'select oxstock from oxarticles 
            where oxid = :oxid';
        $iStockCount = (float) $masterDb->getOne($sQ, [
            ':oxid' => $this->oxorderarticles__oxartid->value
        ]);

        $iStockCount += $dAddAmount;

        // #1592A. calculating according new stock option
        if (!$blAllowNegativeStock && $iStockCount < 0) {
            $iStockCount = 0;
        }

        return $iStockCount;
    }

    /**
     * Order persistent data getter
     *
     * @return array
     */
    public function getPersParams()
    {
        if ($this->_aPersParam != null) {
            return $this->_aPersParam;
        }

        if ($this->oxorderarticles__oxpersparam->value) {
            $this->_aPersParam = unserialize($this->oxorderarticles__oxpersparam->value);
        }

        return $this->_aPersParam;
    }

    /**
     * Order persistent params setter
     *
     * @param array $aParams array of params
     */
    public function setPersParams($aParams)
    {
        $this->_aPersParam = $aParams;

        // serializing persistent info stored while ordering
        $this->oxorderarticles__oxpersparam = new Field(serialize($aParams), Field::T_RAW);
    }

    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (e.g. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFieldData" in next major
     */
    protected function _setFieldData($sFieldName, $sValue, $iDataType = Field::T_TEXT) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sFieldName = strtolower($sFieldName);
        switch ($sFieldName) {
            case 'oxpersparam':
            case 'oxorderarticles__oxpersparam':
            case 'oxerpstatus':
            case 'oxorderarticles__oxerpstatus':
            case 'oxtitle':
            case 'oxorderarticles__oxtitle':
                $iDataType = Field::T_RAW;
                break;
        }

        return parent::_setFieldData($sFieldName, $sValue, $iDataType);
    }

    /**
     * Executes OrderArticle::load() and returns its result
     *
     * @param int    $iLanguage language id
     * @param string $sOxid     order article id
     *
     * @return bool
     */
    public function loadInLang($iLanguage, $sOxid)
    {
        return $this->load($sOxid);
    }

    /**
     * Returns ordered article id, implements iBaseArticle interface getter method
     *
     * @return string
     */
    public function getProductId()
    {
        return $this->oxorderarticles__oxartid->value;
    }

    /**
     * Returns product parent id
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getParentId()
    {
        // when this field will be introduced there will be no need to load from real article
        if (isset($this->oxorderarticles__oxartparentid) && $this->oxorderarticles__oxartparentid->value !== false) {
            return $this->oxorderarticles__oxartparentid->value;
        }

        $oDb = DatabaseProvider::getDb();
        $oArticle = oxNew(Article::class);
        $sQ = "select oxparentid from " . $oArticle->getViewName() . " 
            where oxid = :oxid";
        $this->oxarticles__oxparentid = new Field($oDb->getOne($sQ, [
            ':oxid' => $this->getProductId()
        ]));

        return $this->oxarticles__oxparentid->value;
    }

    /**
     * Sets article parameters to current object, so this object can be used for basket calculation
     * @deprecated underscore prefix violates PSR12, will be renamed to "setArticleParams" in next major
     */
    protected function _setArticleParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // creating needed fields
        $this->oxarticles__oxstock = $this->oxorderarticles__oxamount;
        $this->oxarticles__oxtitle = $this->oxorderarticles__oxtitle;
        $this->oxarticles__oxwidth = $this->oxorderarticles__oxwidth;
        $this->oxarticles__oxlength = $this->oxorderarticles__oxlength;
        $this->oxarticles__oxheight = $this->oxorderarticles__oxheight;
        $this->oxarticles__oxweight = $this->oxorderarticles__oxweight;
        $this->oxarticles__oxsubclass = $this->oxorderarticles__oxsubclass;
        $this->oxarticles__oxartnum = $this->oxorderarticles__oxartnum;
        $this->oxarticles__oxshortdesc = $this->oxorderarticles__oxshortdesc;

        $this->oxarticles__oxvat = $this->oxorderarticles__oxvat;
        $this->oxarticles__oxprice = $this->oxorderarticles__oxprice;
        $this->oxarticles__oxbprice = $this->oxorderarticles__oxbprice;

        $this->oxarticles__oxthumb = $this->oxorderarticles__oxthumb;
        $this->oxarticles__oxpic1 = $this->oxorderarticles__oxpic1;
        $this->oxarticles__oxpic2 = $this->oxorderarticles__oxpic2;
        $this->oxarticles__oxpic3 = $this->oxorderarticles__oxpic3;
        $this->oxarticles__oxpic4 = $this->oxorderarticles__oxpic4;
        $this->oxarticles__oxpic5 = $this->oxorderarticles__oxpic5;

        $this->oxarticles__oxfile = $this->oxorderarticles__oxfile;
        $this->oxarticles__oxdelivery = $this->oxorderarticles__oxdelivery;
        $this->oxarticles__oxissearch = $this->oxorderarticles__oxissearch;
        $this->oxarticles__oxfolder = $this->oxorderarticles__oxfolder;
        $this->oxarticles__oxtemplate = $this->oxorderarticles__oxtemplate;
        $this->oxarticles__oxexturl = $this->oxorderarticles__oxexturl;
        $this->oxarticles__oxurlimg = $this->oxorderarticles__oxurlimg;
        $this->oxarticles__oxurldesc = $this->oxorderarticles__oxurldesc;
        $this->oxarticles__oxshopid = $this->oxorderarticles__oxordershopid;
        $this->oxarticles__oxquestionemail = $this->oxorderarticles__oxquestionemail;
        $this->oxarticles__oxsearchkeys = $this->oxorderarticles__oxsearchkeys;
    }

    /**
     * Returns true, implements iBaseArticle interface method
     *
     * @param double $dAmount         stock to check
     * @param double $dArtStockAmount stock amount
     *
     * @return bool
     */
    public function checkForStock($dAmount, $dArtStockAmount = 0)
    {
        return true;
    }

    /**
     * Loads, caches and returns real order article instance. If article is not
     * available (deleted from db or so) false is returned
     *
     * @param null $sArticleId article id (optional, is not passed oxorderarticles__oxartid will be used)
     *
     * @return Article|false
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getOrderArticle" in next major
     */
    protected function _getOrderArticle($sArticleId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_oOrderArticle === null) {
            $this->_oOrderArticle = false;

            $sArticleId = $sArticleId ? $sArticleId : $this->getProductId();
            $oArticle = oxNew(Article::class);
            $oArticle->setLoadParentData(true);
            if ($oArticle->load($sArticleId)) {
                $this->_oOrderArticle = $oArticle;
            }
        }

        return $this->_oOrderArticle;
    }

    /**
     * Returns article select lists, implements iBaseArticle interface method
     *
     * @param null $sKeyPrefix prefix (not used)
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getSelectLists($sKeyPrefix = null)
    {
        $aSelLists = [];
        if ($oArticle = $this->_getOrderArticle()) {
            $aSelLists = $oArticle->getSelectLists();
        }

        return $aSelLists;
    }

    /**
     * Returns order article selection list array
     *
     * @param null $sArtId ordered article id [optional]
     * @param null $sOrderArtSelList order article selection list [optional]
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getOrderArticleSelectList($sArtId = null, $sOrderArtSelList = null)
    {
        if ($this->_aOrderArticleSelList === null) {
            $sOrderArtSelList = $sOrderArtSelList ? $sOrderArtSelList : $this->oxorderarticles__oxselvariant->value;

            $sOrderArtSelList = explode(' || ', $sOrderArtSelList)[0];

            $aRet = [];

            if ($oArticle = $this->_getOrderArticle($sArtId)) {
                $aList = explode(", ", $sOrderArtSelList);
                $oStr = Str::getStr();

                $aArticleSelList = $oArticle->getSelectLists();

                //formatting temporary list array from string
                if (count($aArticleSelList) > 0) {
                    foreach ($aList as $sList) {
                        if ($sList) {
                            $aVal = explode(":", $sList);
                            if (isset($aVal[0]) && isset($aVal[1])) {
                                $sOrderArtListTitle = $oStr->strtolower(trim($aVal[0]));
                                $sOrderArtSelValue = $oStr->strtolower(trim($aVal[1]));

                                //checking article list for matches with article list stored in oxOrderItem
                                $iSelListNum = 0;
                                foreach ($aArticleSelList as $aSelect) {
                                    //check if selects titles are equal

                                    if ($oStr->strtolower($aSelect['name']) == $sOrderArtListTitle) {
                                        //try to find matching select items value
                                        $iSelValueNum = 0;
                                        foreach ($aSelect as $oSel) {
                                            if ($oStr->strtolower($oSel->name) == $sOrderArtSelValue) {
                                                // found, adding to return array
                                                $aRet[$iSelListNum] = $iSelValueNum;
                                                break;
                                            }
                                            //next article list item
                                            $iSelValueNum++;
                                        }
                                    }
                                    //next article list
                                    $iSelListNum++;
                                }
                            }
                        }
                    }
                }
            }

            $this->_aOrderArticleSelList = $aRet;
        }

        return $this->_aOrderArticleSelList;
    }

    /**
     * Returns basket order article price
     *
     * @param double $dAmount basket item amount
     * @param array $aSelList chosen selection list
     * @param Basket $oBasket basket
     *
     * @return Price
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getBasketPrice($dAmount, $aSelList, $oBasket)
    {
        $oArticle = $this->_getOrderArticle();

        if ($oArticle) {
            return $oArticle->getBasketPrice($dAmount, $aSelList, $oBasket);
        } else {
            return $this->getPrice();
        }
    }

    /**
     * Returns false, implements iBaseArticle interface method
     *
     * @return bool
     */
    public function skipDiscounts()
    {
        return false;
    }

    /**
     * Returns empty array, implements iBaseArticle interface getter method
     *
     * @param bool $blActCats select categories if all parents are active
     * @param bool $blSkipCache force reload or not (default false - no reload)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCategoryIds($blActCats = false, $blSkipCache = false)
    {
        $aCatIds = [];
        if ($oOrderArticle = $this->_getOrderArticle()) {
            $aCatIds = $oOrderArticle->getCategoryIds($blActCats, $blSkipCache);
        }

        return $aCatIds;
    }

    /**
     * Returns current session language id
     *
     * @return int
     */
    public function getLanguage()
    {
        return Registry::getLang()->getBaseLanguage();
    }

    /**
     * Returns base article price from database
     *
     * @param double $dAmount article amount. Default is 1
     *
     * @return object
     */
    public function getBasePrice($dAmount = 1)
    {
        return $this->getPrice();
    }

    /**
     * Returns order article unit price
     *
     * @return Price
     */
    public function getPrice()
    {
        $oBasePrice = oxNew(Price::class);
        // prices in db are ONLY brutto
        $oBasePrice->setBruttoPriceMode();
        $oBasePrice->setVat($this->oxorderarticles__oxvat->value);
        $oBasePrice->setPrice($this->oxorderarticles__oxbprice->value);

        return $oBasePrice;
    }

    /**
     * Marks object as new order item (this marker useful when recalculating stocks after order recalculation)
     *
     * @param bool $blIsNew marker value - TRUE if this item is newly added to order
     */
    public function setIsNewOrderItem($blIsNew)
    {
        $this->_blIsNewOrderItem = $blIsNew;
    }

    /**
     * Returns TRUE if current order article is newly added to order
     *
     * @return bool
     */
    public function isNewOrderItem()
    {
        return $this->_blIsNewOrderItem;
    }

    /**
     * Ordered article stock setter. Before setting new stock value additionally checks for
     * original article stock value. Is stock values <= preferred, adjusts order stock according
     * to it
     *
     * @param int $iNewAmount new ordered items amount
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setNewAmount($iNewAmount)
    {
        if ($iNewAmount >= 0) {
            // to update stock we must first check if it is possible - article exists?
            $oArticle = oxNew(Article::class);
            if ($oArticle->load($this->oxorderarticles__oxartid->value)) {
                // updating stock info
                $iStockChange = $iNewAmount - $this->oxorderarticles__oxamount->value;
                if ($iStockChange > 0 && ($iOnStock = $oArticle->checkForStock($iStockChange)) !== false) {
                    if ($iOnStock !== true) {
                        $iStockChange = $iOnStock;
                        $iNewAmount = $this->oxorderarticles__oxamount->value + $iStockChange;
                    }
                }

                $this->updateArticleStock($iStockChange * -1, Registry::getConfig()->getConfigParam('blAllowNegativeStock'));

                // updating self
                $this->oxorderarticles__oxamount = new Field($iNewAmount, Field::T_RAW);
                $this->save();
            }
        }
    }

    /**
     * Returns true if object is derived from oxorderarticle class
     *
     * @return bool
     */
    public function isOrderArticle()
    {
        return true;
    }

    /**
     * Sets order article storno value to 1 and if stock control is on -
     * restores previous oxarticle stock state
     */
    public function cancelOrderArticle()
    {
        if ($this->oxorderarticles__oxstorno->value == 0) {
            $myConfig = Registry::getConfig();
            $this->oxorderarticles__oxstorno = new Field(1);
            if ($this->save()) {
                $this->updateArticleStock($this->oxorderarticles__oxamount->value, $myConfig->getConfigParam('blAllowNegativeStock'));
            }
        }
    }

    /**
     * Deletes order article object. If deletion succeeded - updates
     * article stock information. Returns deletion status
     *
     * @param null $sOXID Article id
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($sOXID = null)
    {
        if ($blDelete = parent::delete($sOXID)) {
            $myConfig = Registry::getConfig();
            if ($this->oxorderarticles__oxstorno->value != 1) {
                $this->updateArticleStock($this->oxorderarticles__oxamount->value, $myConfig->getConfigParam('blAllowNegativeStock'));
            }
        }

        return $blDelete;
    }

    /**
     * Saves order article object. If saving succeeded - updates
     * article stock information if OrderArticle::isNewOrderItem()
     * returns TRUE. Returns saving status
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function save()
    {
        // ordered articles
        if (($blSave = parent::save()) && $this->isNewOrderItem()) {
            $myConfig = Registry::getConfig();
            if (
                $myConfig->getConfigParam('blUseStock') &&
                $myConfig->getConfigParam('blPsBasketReservationEnabled')
            ) {
                Registry::getSession()
                    ->getBasketReservations()
                    ->commitArticleReservation(
                        $this->oxorderarticles__oxartid->value,
                        $this->oxorderarticles__oxamount->value
                    );
            } else {
                $this->updateArticleStock($this->oxorderarticles__oxamount->value * (-1), $myConfig->getConfigParam('blAllowNegativeStock'));
            }

            // setting downloadable products article files
            $this->_setOrderFiles();

            // marking object as "non-new" disable further stock changes
            $this->setIsNewOrderItem(false);
        }

        return $blSave;
    }

    /**
     * get used wrapping
     *
     * @return Wrapping
     */
    public function getWrapping()
    {
        if ($this->oxorderarticles__oxwrapid->value) {
            $oWrapping = oxNew(Wrapping::class);
            if ($oWrapping->load($this->oxorderarticles__oxwrapid->value)) {
                return $oWrapping;
            }
        }

        return null;
    }

    /**
     * Returns true if ordered product is bundle
     *
     * @return bool
     */
    public function isBundle()
    {
        return (bool) $this->oxorderarticles__oxisbundle->value;
    }

    /**
     * Get Total brut price formatted
     *
     * @return string
     */
    public function getTotalBrutPriceFormated()
    {
        $oLang = Registry::getLang();
        $oOrder = $this->getOrder();
        $oCurrency = Registry::getConfig()->getCurrencyObject($oOrder->oxorder__oxcurrency->value);

        return $oLang->formatCurrency($this->oxorderarticles__oxbrutprice->value, $oCurrency);
    }

    /**
     * Get  brut price formatted
     *
     * @return string
     */
    public function getBrutPriceFormated()
    {
        $oLang = Registry::getLang();
        $oOrder = $this->getOrder();
        $oCurrency = Registry::getConfig()->getCurrencyObject($oOrder->oxorder__oxcurrency->value);

        return $oLang->formatCurrency($this->oxorderarticles__oxbprice->value, $oCurrency);
    }

    /**
     * Get Net price formatted
     *
     * @return string
     */
    public function getNetPriceFormated()
    {
        $oLang = Registry::getLang();
        $oOrder = $this->getOrder();
        $oCurrency = Registry::getConfig()->getCurrencyObject($oOrder->oxorder__oxcurrency->value);

        return $oLang->formatCurrency($this->oxorderarticles__oxnprice->value, $oCurrency);
    }

    /**
     * Returns oxOrder object that the article belongs to
     *
     * @return mixed - on success returns oxOrder object, else returns null
     */
    public function getOrder()
    {
        if ($this->oxorderarticles__oxorderid->value) {
            // checking if the object already exists in the cache
            if (@isset($this->_aOrderCache[$this->oxorderarticles__oxorderid->value])) {
                // returning the cached object
                return $this->_aOrderCache[$this->oxorderarticles__oxorderid->value];
            }
            // creating new order object and trying to load it
            $oOrder = oxNew(Order::class);
            if ($oOrder->load($this->oxorderarticles__oxorderid->value)) {
                return $this->_aOrderCache[$this->oxorderarticles__oxorderid->value] = $oOrder;
            }
        }

        return null;
    }

    /**
     * Sets article creation date
     * (OrderArticle::oxorderarticles__oxtimestamp). Then executes parent method
     * parent::_insert() and returns insertion status.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iInsertTime = time();
        $now = date('Y-m-d H:i:s', $iInsertTime);
        $this->oxorderarticles__oxtimestamp = new Field($now);

        return parent::_insert();
    }


    /**
     * Set article
     *
     * @param object $oArticle - article object
     */
    public function setArticle($oArticle)
    {
        $this->_oArticle = $oArticle;
    }

    /**
     * Get article
     *
     * @return Article
     * @throws DatabaseConnectionException
     */
    public function getArticle()
    {
        if ($this->_oArticle === null) {
            $oArticle = oxNew(Article::class);
            $oArticle->load($this->oxorderarticles__oxartid->value);
            $this->_oArticle = $oArticle;
        }

        return $this->_oArticle;
    }


    /**
     * Set order files
     * @deprecated will be renamed to "setOrderFiles" in next major
     */
    public function _setOrderFiles() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oArticle = $this->getArticle();

        if ($oArticle->oxarticles__oxisdownloadable->value) {
            $oConfig = Registry::getConfig();
            $sOrderId = $this->oxorderarticles__oxorderid->value;
            $sOrderArticleId = $this->getId();
            $sShopId = $oConfig->getShopId();

            $oUser = $oConfig->getUser();

            $oFiles = $oArticle->getArticleFiles(true);

            if ($oFiles) {
                foreach ($oFiles as $oFile) {
                    $oOrderFile = oxNew(OrderFile::class);
                    $oOrderFile->setOrderId($sOrderId);
                    $oOrderFile->setOrderArticleId($sOrderArticleId);
                    $oOrderFile->setShopId($sShopId);
                    $iMaxDownloadCount = (!empty($oUser) && !$oUser->hasAccount()) ? $oFile->getMaxUnregisteredDownloadsCount() : $oFile->getMaxDownloadsCount();
                    $oOrderFile->setFile(
                        $oFile->oxfiles__oxfilename->value,
                        $oFile->getId(),
                        $iMaxDownloadCount * $this->oxorderarticles__oxamount->value,
                        $oFile->getLinkExpirationTime(),
                        $oFile->getDownloadExpirationTime()
                    );

                    $oOrderFile->save();
                }
            }
        }
    }

    /**
     * Get Total brut price formatted
     *
     * @return string
     */
    public function getTotalNetPriceFormated()
    {
        $oLang = Registry::getLang();
        $oOrder = $this->getOrder();
        $oCurrency = Registry::getConfig()->getCurrencyObject($oOrder->oxorder__oxcurrency->value);

        return $oLang->formatCurrency($this->oxorderarticles__oxnetprice->value, $oCurrency);
    }
}
