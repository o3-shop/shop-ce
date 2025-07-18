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
use OxidEsales\Eshop\Core\Counter;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Price as ShopPrice;
use OxidEsales\Eshop\Application\Model\Payment as EshopPayment;
use OxidEsales\Eshop\Application\Model\Voucher as EshopVoucherModel;
use OxidEsales\EshopCommunity\Core\Exception\DatabaseException;
use stdClass;

/**
 * Order manager.
 * Performs creation assigning, updating, deleting and other order functions.
 *
 */
class Order extends BaseModel
{
    // defining order state constants
    /**
     * Error while sending order notification mail to customer
     *
     * @var int
     */
    const ORDER_STATE_MAILINGERROR = 0;

    /**
     * Order finalization was completed without errors
     *
     * @var int
     */
    const ORDER_STATE_OK = 1;

    /**
     * Error during payment execution
     *
     * @var int
     */
    const ORDER_STATE_PAYMENTERROR = 2;

    /**
     * Order with such id already exist
     *
     * @var int
     */
    const ORDER_STATE_ORDEREXISTS = 3;

    /**
     * Delivery parameters used for order are invalid
     *
     * @var int
     */
    const ORDER_STATE_INVALIDDELIVERY = 4;

    /**
     * Payment parameters used for order are invalid
     *
     * @var int
     */
    const ORDER_STATE_INVALIDPAYMENT = 5;

    /**
     * Protection parameters used for some data in order are invalid
     *
     * @deprecated since v6.0.0 (2018-01-05); Use ORDER_STATE_INVALIDDELADDRESSCHANGED instead.
     *
     * @var int
     */
    const ORDER_STATE_INVALIDDElADDRESSCHANGED = 7; // phpcs:ignore

    /**
     * Protection parameters used for some data in order are invalid
     *
     * @var int
     */
    const ORDER_STATE_INVALIDDELADDRESSCHANGED = 7;

    /**
     * Basket price < minimum order price
     *
     * @var int
     */
    const ORDER_STATE_BELOWMINPRICE = 8;

    /**
     * Voucher cannot be applied
     *
     * @var int
     */
    const ORDER_STATE_VOUCHERERROR = 9;

    /**
     * Skip update fields
     *
     * @var array
     */
    protected $_aSkipSaveFields = ['oxtimestamp'];

    /**
     * oxList of oxarticle objects
     *
     * @var ListModel
     */
    protected $_oArticles = null;

    /**
     * Deliveryset object
     *
     * @var Deliveryset
     */
    protected $_oDelSet = null;

    /**
     * Gift card
     *
     * @var Wrapping
     */
    protected $_oGiftCard = null;

    /**
     * Payment type
     *
     * @var EshopPayment
     */
    protected $_oPaymentType = null;

    /**
     * User payment
     *
     * @var UserPayment
     */
    protected $_oPayment = null;

    /**
     * Order vouchers marked as used
     *
     * @var array
     */
    protected $_aVoucherList = null;

    /**
     * Order delivery costs price object
     *
     * @var ShopPrice
     */
    protected $_oDelPrice = null;

    /**
     * Order user
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Order basket
     *
     * @var Basket
     */
    protected $_oBasket = null;

    /**
     * Order wrapping costs price object
     *
     * @var ShopPrice
     */
    protected $_oWrappingPrice = null;

    /**
     * Order gift card price object
     *
     * @var ShopPrice
     */
    protected $_oGiftCardPrice = null;

    /**
     * Order payment costs price object
     *
     * @var ShopPrice
     */
    protected $_oPaymentPrice = null;

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxorder';

    /**
     * Usage of separate orders numbering for different shops
     *
     * @var bool
     */
    protected $_blSeparateNumbering = null;

    /**
     * Order language id
     *
     * @var int
     */
    protected $_iOrderLang = null;

    /**
     * If true delivery will be recalculated while recalculating order
     *
     * @var bool
     */
    protected $_blReloadDelivery = true;

    /**
     * If true discount will be recalculated while recalculating order
     *
     * @var bool
     */
    protected $_blReloadDiscount = true;

    /**
     * Current order currency object
     *
     * @var stdClass
     */
    protected $_oOrderCurrency = null;

    /**
     * Current order files object
     *
     * @var object
     */
    protected $_oOrderFiles = null;

    /**
     * Shipment tracking url
     *
     * @var string
     */
    protected $_sShipTrackUrl = null;

    /**
     * @var Basket
     */
    protected $_oOrderBasket = null;

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxorder');

        // set usage of separate orders numbering for different shops
        $this->setSeparateNumbering(Registry::getConfig()->getConfigParam('blSeparateNumbering'));
    }

    /**
     * Getter made for order delivery set object access
     *
     * @param string $sName parameter name
     *
     * @return DeliverySet|Field|void
     */
    public function __get($sName)
    {
        if ($sName == 'oDelSet') {
            return $this->getDelSet();
        }

        if ($sName == 'oxorder__oxbillcountry') {
            return $this->getBillCountry();
        }

        if ($sName == 'oxorder__oxdelcountry') {
            return $this->getDelCountry();
        }
    }

    /**
     * Assigns data, stored in DB to oxorder object
     *
     * @param mixed $dbRecord DB record
     */
    public function assign($dbRecord)
    {
        parent::assign($dbRecord);

        $oUtilsDate = Registry::getUtilsDate();

        // convert date's to international format
        $this->oxorder__oxorderdate = new Field($oUtilsDate->formatDBDate($this->oxorder__oxorderdate->value));
        $this->oxorder__oxsenddate = new Field($oUtilsDate->formatDBDate($this->oxorder__oxsenddate->value));
    }

    /**
     * Gets country title by country id.
     *
     * @param string $sCountryId country ID
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCountryTitle" in next major
     */
    protected function _getCountryTitle($sCountryId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sTitle = null;
        if ($sCountryId && $sCountryId != '-1') {
            $oCountry = oxNew(Country::class);
            $oCountry->loadInLang($this->getOrderLanguage(), $sCountryId);
            $sTitle = $oCountry->oxcountry__oxtitle->value;
        }

        return $sTitle;
    }

    /**
     * returned assigned order-articles from order
     *
     * @param bool $blExcludeCanceled excludes canceled items from list
     *
     * @return ListModel
     * @deprecated underscore prefix violates PSR12, will be renamed to "getArticles" in next major
     */
    protected function _getArticles($blExcludeCanceled = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSelect = "SELECT `oxorderarticles`.* FROM `oxorderarticles`
                        WHERE `oxorderarticles`.`oxorderid` = :oxorderid" .
                   ($blExcludeCanceled ? " AND `oxorderarticles`.`oxstorno` != 1 " : " ") . "
                        ORDER BY `oxorderarticles`.`oxartid`, `oxorderarticles`.`oxselvariant`, `oxorderarticles`.`oxpersparam` ";

        // order articles
        $oArticles = oxNew(ListModel::class);
        $oArticles->init('oxorderarticle');
        $oArticles->selectString($sSelect, [
            ':oxorderid' => (string) $this->getId()
        ]);

        return $oArticles;
    }

    /**
     * Assigns data, stored in oxorderarticles to oxorder object .
     *
     * @param bool $blExcludeCanceled excludes canceled items from list
     *
     * @return ListModel
     */
    public function getOrderArticles($blExcludeCanceled = false)
    {
        // checking set value
        if ($blExcludeCanceled) {
            return $this->_getArticles(true);
        } elseif ($this->_oArticles === null) {
            $this->_oArticles = $this->_getArticles();
        }

        return $this->_oArticles;
    }

    /**
     * Order article list setter
     *
     * @param OrderArticleList $oOrderArticleList
     */
    public function setOrderArticleList($oOrderArticleList)
    {
        $this->_oArticles = $oOrderArticleList;
    }

    /**
     * Returns order delivery expenses price object
     *
     * @return ShopPrice
     */
    public function getOrderDeliveryPrice()
    {
        if ($this->_oDelPrice != null) {
            return $this->_oDelPrice;
        }

        $this->_oDelPrice = oxNew(ShopPrice::class);
        $this->_oDelPrice->setBruttoPriceMode();
        $this->_oDelPrice->setPrice($this->oxorder__oxdelcost->value, $this->oxorder__oxdelvat->value);

        return $this->_oDelPrice;
    }

    /**
     * Returns order wrapping expenses price object
     *
     * @return ShopPrice
     */
    public function getOrderWrappingPrice()
    {
        if ($this->_oWrappingPrice != null) {
            return $this->_oWrappingPrice;
        }

        $this->_oWrappingPrice = oxNew(ShopPrice::class);
        $this->_oWrappingPrice->setBruttoPriceMode();
        $this->_oWrappingPrice->setPrice($this->oxorder__oxwrapcost->value, $this->oxorder__oxwrapvat->value);

        return $this->_oWrappingPrice;
    }

    /**
     * Returns order wrapping expenses price object
     *
     * @return ShopPrice
     */
    public function getOrderGiftCardPrice()
    {
        if ($this->_oGidtCardPrice != null) {
            return $this->_oGidtCardPrice;
        }

        $this->_oGidtCardPrice = oxNew(ShopPrice::class);
        $this->_oGidtCardPrice->setBruttoPriceMode();
        $this->_oGidtCardPrice->setPrice($this->oxorder__oxgiftcardcost->value, $this->oxorder__oxgiftcardvat->value);

        return $this->_oGidtCardPrice;
    }


    /**
     * Returns order payment expenses price object
     *
     * @return ShopPrice
     */
    public function getOrderPaymentPrice()
    {
        if ($this->_oPaymentPrice != null) {
            return $this->_oPaymentPrice;
        }

        $this->_oPaymentPrice = oxNew(ShopPrice::class);
        $this->_oPaymentPrice->setBruttoPriceMode();
        $this->_oPaymentPrice->setPrice($this->oxorder__oxpaycost->value, $this->oxorder__oxpayvat->value);

        return $this->_oPaymentPrice;
    }

    /**
     * Returns order netto sum (total order price - VAT)
     *
     * @return double
     */
    public function getOrderNetSum()
    {
        $dTotalNetSum = 0;

        $dTotalNetSum += $this->oxorder__oxtotalnetsum->value;
        $dTotalNetSum += $this->getOrderDeliveryPrice()->getNettoPrice();
        $dTotalNetSum += $this->getOrderWrappingPrice()->getNettoPrice();
        $dTotalNetSum += $this->getOrderPaymentPrice()->getNettoPrice();

        return $dTotalNetSum;
    }

    /**
     * Order checking, processing and saving method.
     * Before saving performed checking if order is still not executed (checks in
     * database oxorder table for order with know ID), if yes - returns error code 3,
     * if not - loads payment data, assigns all info from basket to new Order object
     * and saves full order with error status. Then executes payment. On failure -
     * deletes order and returns error code 2. On success - saves order (Order::save()),
     * removes article from wishlist (Order::_updateWishlist()), updates voucher data
     * (Order::_markVouchers()). Finally, sends order confirmation email to customer
     * (Email::SendOrderEMailToUser()) and shop owner (Email::SendOrderEMailToOwner()).
     * If this is order recalculation, skipping payment execution, marking vouchers as used
     * and sending order by email to shop owner and user
     * Mailing status (1 if OK, 0 on error) is returned.
     *
     * @param Basket $oBasket Basket object
     * @param User $oUser Current User object
     * @param bool $blRecalculatingOrder Order recalculation
     *
     * @return integer
     * @throws Exception
     */
    public function finalizeOrder(Basket $oBasket, $oUser, $blRecalculatingOrder = false)
    {
        // check if this order is already stored
        $orderId = Registry::getSession()->getVariable('sess_challenge');
        if ($this->_checkOrderExist($orderId)) {
            Registry::getLogger()->debug('finalizeOrder: Order already exists: ' . $orderId, [$oBasket, $oUser]);
            // we might use this later, this means that somebody clicked like mad on order button
            return self::ORDER_STATE_ORDEREXISTS;
        }

        // if not recalculating order, use sess_challenge id, else leave old order id
        if (!$blRecalculatingOrder) {
            // use this ID
            $this->setId($orderId);

            // validating various order/basket parameters before finalizing
            if ($iOrderState = $this->validateOrder($oBasket, $oUser)) {
                return $iOrderState;
            }
        }

        // copies user info
        $this->_setUser($oUser);

        // copies basket info
        $this->_loadFromBasket($oBasket);

        // payment information
        $oUserPayment = $this->_setPayment($oBasket->getPaymentId());

        // set folder information, if order is new
        // #M575 in recalculating order case folder must be the same as it was
        if (!$blRecalculatingOrder) {
            $this->_setFolder();
        }

        // marking as not finished
        $this->_setOrderStatus('NOT_FINISHED');

        //saving all order data to DB
        $this->save();

        // executing payment (on failure deletes order and returns error code)
        // in case when recalculating order, payment execution is skipped
        if (!$blRecalculatingOrder) {
            $blRet = $this->_executePayment($oBasket, $oUserPayment);
            if ($blRet !== true) {
                return $blRet;
            }
        }

        if (!$this->oxorder__oxordernr->value) {
            $this->_setNumber();
        } else {
            oxNew(Counter::class)->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
        }

        // deleting remark info only when order is finished
        Registry::getSession()->deleteVariable('ordrem');

        //#4005: Order creation time is not updated when order processing is complete
        if (!$blRecalculatingOrder) {
            $this->_updateOrderDate();
        }

        // updating order trans status (success status)
        $this->_setOrderStatus('OK');

        // store order-ID
        $oBasket->setOrderId($this->getId());

        // updating wish lists
        $this->_updateWishlist($oBasket->getContents(), $oUser);

        // updating users notice list
        $this->_updateNoticeList($oBasket->getContents(), $oUser);

        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        // skipping this action in case of order recalculation
        if (!$blRecalculatingOrder) {
            $this->_markVouchers($oBasket, $oUser);
        }

        // send order by email to shop owner and current user
        // skipping this action in case of order recalculation
        if (!$blRecalculatingOrder) {
            $iRet = $this->_sendOrderByEmail($oUser, $oBasket, $oUserPayment);
        } else {
            $iRet = self::ORDER_STATE_OK;
        }

        return $iRet;
    }

    /**
     * Return true if order store in netto mode
     *
     * @return bool
     */
    public function isNettoMode()
    {
        return (bool) $this->oxorder__oxisnettomode->value;
    }


    /**
     * Updates order transaction status. Faster than saving whole object
     *
     * @param string $sStatus order transaction status
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setOrderStatus" in next major
     */
    protected function _setOrderStatus($sStatus) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sQ = 'update oxorder set oxtransstatus = :oxtransstatus where oxid = :oxid';
        $oDb->execute($sQ, [
            ':oxtransstatus' => $sStatus,
            ':oxid' => $this->getId()
        ]);

        //updating order object
        $this->oxorder__oxtransstatus = new Field($sStatus, Field::T_RAW);
    }

    /**
     * Converts string VAT representation into float e.g. 7,6 to 7.6
     *
     * @param string $sVat vat value
     *
     * @return float
     * @deprecated underscore prefix violates PSR12, will be renamed to "convertVat" in next major
     */
    protected function _convertVat($sVat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (strpos($sVat, '.') < strpos($sVat, ',')) {
            $sVat = str_replace(['.', ','], ['', '.'], $sVat);
        } else {
            $sVat = str_replace(',', '', $sVat);
        }

        return (float) $sVat;
    }

    /**
     * Reset Vat info
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetVats" in next major
     */
    protected function _resetVats() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->oxorder__oxartvat1 = new Field(null);
        $this->oxorder__oxartvatprice1 = new Field(null);
        $this->oxorder__oxartvat2 = new Field(null);
        $this->oxorder__oxartvatprice2 = new Field(null);
    }

    /**
     * Gathers and assigns to new oxOrder object customer data, payment, delivery
     * and shipping info, customer order remark, currency, voucher, language data.
     * Additionally, stores general discount and wrapping. Sets order status to "error"
     * and creates oxOrderArticle objects and assigns to them basket articles.
     *
     * @param Basket $oBasket Shopping basket object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromBasket" in next major
     */
    protected function _loadFromBasket(Basket $oBasket) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();

        // store IP Address - default must be FALSE as it is illegal to store
        if ($myConfig->getConfigParam('blStoreIPs') && $this->oxorder__oxip->value === null) {
            $this->oxorder__oxip = new Field(Registry::getUtilsServer()->getRemoteAddress(), Field::T_RAW);
        }

        //setting view mode
        $this->oxorder__oxisnettomode = new Field($oBasket->isCalculationModeNetto());

        // copying main price info
        $this->oxorder__oxtotalnetsum = new Field($oBasket->getNettoSum());
        $this->oxorder__oxtotalbrutsum = new Field($oBasket->getBruttoSum());
        $this->oxorder__oxtotalordersum = new Field($oBasket->getPrice()->getBruttoPrice(), Field::T_RAW);

        // copying discounted VAT info
        $this->_resetVats();
        $iVatIndex = 1;
        foreach ($oBasket->getProductVats(false) as $iVat => $dPrice) {
            $this->{"oxorder__oxartvat$iVatIndex"} = new Field($this->_convertVat($iVat), Field::T_RAW);
            $this->{"oxorder__oxartvatprice$iVatIndex"} = new Field($dPrice, Field::T_RAW);
            $iVatIndex++;
        }

        // payment costs if available
        if (($oPaymentCost = $oBasket->getCosts('oxpayment'))) {
            $this->oxorder__oxpaycost = new Field($oPaymentCost->getBruttoPrice(), Field::T_RAW);
            $this->oxorder__oxpayvat = new Field($oPaymentCost->getVAT(), Field::T_RAW);
        }

        // delivery info
        if (($oDeliveryCost = $oBasket->getCosts('oxdelivery'))) {
            $this->oxorder__oxdelcost = new Field($oDeliveryCost->getBruttoPrice(), Field::T_RAW);
            //V #M382: Save VAT, not VAT value for delivery costs
            $this->oxorder__oxdelvat = new Field($oDeliveryCost->getVAT(), Field::T_RAW); //V #M382
            $this->oxorder__oxdeltype = new Field($oBasket->getShippingId(), Field::T_RAW);
        }

        // user remark
        if (!isset($this->oxorder__oxremark) || $this->oxorder__oxremark->value === null) {
            $this->oxorder__oxremark = new Field(Registry::getSession()->getVariable('ordrem'), Field::T_RAW);
        }

        // currency
        $oCur = $myConfig->getActShopCurrencyObject();
        $this->oxorder__oxcurrency = new Field($oCur->name);
        $this->oxorder__oxcurrate = new Field($oCur->rate, Field::T_RAW);

        // store voucher discount
        if (($oVoucherDiscount = $oBasket->getVoucherDiscount())) {
            $this->oxorder__oxvoucherdiscount = new Field($oVoucherDiscount->getBruttoPrice(), Field::T_RAW);
        }

        // general discount
        if ($this->_blReloadDiscount) {
            $dDiscount = 0;
            $aDiscounts = $oBasket->getDiscounts();
            if (is_array($aDiscounts) && count($aDiscounts) > 0) {
                foreach ($aDiscounts as $oDiscount) {
                    $dDiscount += $oDiscount->dDiscount;
                }
            }
            $this->oxorder__oxdiscount = new Field($dDiscount, Field::T_RAW);
        }

        //order language
        $this->oxorder__oxlang = new Field($this->getOrderLanguage());


        // initial status - 'ERROR'
        $this->oxorder__oxtransstatus = new Field('ERROR', Field::T_RAW);

        // copies basket product info ...
        $this->_setOrderArticles($oBasket->getContents());

        // copies wrapping info
        $this->_setWrapping($oBasket);
    }

    /**
     * Returns language id of current order object. If order already has
     * language defined - checks if this language is defined in shops config
     *
     * @return int
     */
    public function getOrderLanguage()
    {
        if ($this->_iOrderLang === null) {
            if (isset($this->oxorder__oxlang->value)) {
                $this->_iOrderLang = Registry::getLang()->validateLanguage($this->oxorder__oxlang->value);
            } else {
                $this->_iOrderLang = Registry::getLang()->getBaseLanguage();
            }
        }

        return $this->_iOrderLang;
    }

    /**
     * Assigns to new oxorder object customer delivery and shipping info
     *
     * @param object $oUser user object
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignUserInformation" in next major
     */
    protected function _setUser($oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->oxorder__oxuserid = new Field($oUser->getId());

        // bill address
        $this->oxorder__oxbillcompany = clone $oUser->oxuser__oxcompany;
        $this->oxorder__oxbillemail = clone $oUser->oxuser__oxusername;
        $this->oxorder__oxbillfname = clone $oUser->oxuser__oxfname;
        $this->oxorder__oxbilllname = clone $oUser->oxuser__oxlname;
        $this->oxorder__oxbillstreet = clone $oUser->oxuser__oxstreet;
        $this->oxorder__oxbillstreetnr = clone $oUser->oxuser__oxstreetnr;
        $this->oxorder__oxbilladdinfo = clone $oUser->oxuser__oxaddinfo;
        $this->oxorder__oxbillustid = clone $oUser->oxuser__oxustid;
        $this->oxorder__oxbillcity = clone $oUser->oxuser__oxcity;
        $this->oxorder__oxbillcountryid = clone $oUser->oxuser__oxcountryid;
        $this->oxorder__oxbillstateid = clone $oUser->oxuser__oxstateid;
        $this->oxorder__oxbillzip = clone $oUser->oxuser__oxzip;
        $this->oxorder__oxbillfon = clone $oUser->oxuser__oxfon;
        $this->oxorder__oxbillfax = clone $oUser->oxuser__oxfax;
        $this->oxorder__oxbillsal = clone $oUser->oxuser__oxsal;


        // delivery address
        if (($oDelAddress = $this->getDelAddressInfo())) {
            // set delivery address
            $this->oxorder__oxdelcompany = clone $oDelAddress->oxaddress__oxcompany;
            $this->oxorder__oxdelfname = clone $oDelAddress->oxaddress__oxfname;
            $this->oxorder__oxdellname = clone $oDelAddress->oxaddress__oxlname;
            $this->oxorder__oxdelstreet = clone $oDelAddress->oxaddress__oxstreet;
            $this->oxorder__oxdelstreetnr = clone $oDelAddress->oxaddress__oxstreetnr;
            $this->oxorder__oxdeladdinfo = clone $oDelAddress->oxaddress__oxaddinfo;
            $this->oxorder__oxdelcity = clone $oDelAddress->oxaddress__oxcity;
            $this->oxorder__oxdelcountryid = clone $oDelAddress->oxaddress__oxcountryid;
            $this->oxorder__oxdelstateid = clone $oDelAddress->oxaddress__oxstateid;
            $this->oxorder__oxdelzip = clone $oDelAddress->oxaddress__oxzip;
            $this->oxorder__oxdelfon = clone $oDelAddress->oxaddress__oxfon;
            $this->oxorder__oxdelfax = clone $oDelAddress->oxaddress__oxfax;
            $this->oxorder__oxdelsal = clone $oDelAddress->oxaddress__oxsal;
        }
    }

    /**
     * Assigns wrapping VAT and card price + card message info
     *
     * @param Basket $oBasket basket object
     * @deprecated underscore prefix violates PSR12, will be renamed to "setWrapping" in next major
     */
    protected function _setWrapping(Basket $oBasket) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // wrapping price
        if (($oWrappingCost = $oBasket->getCosts('oxwrapping'))) {
            $this->oxorder__oxwrapcost = new Field($oWrappingCost->getBruttoPrice(), Field::T_RAW);
            // wrapping VAT will be always calculated (#3757)
            $this->oxorder__oxwrapvat = new Field($oWrappingCost->getVAT(), Field::T_RAW);
        }

        if (($oGiftCardCost = $oBasket->getCosts('oxgiftcard'))) {
            $this->oxorder__oxgiftcardcost = new Field($oGiftCardCost->getBruttoPrice(), Field::T_RAW);
            $this->oxorder__oxgiftcardvat = new Field($oGiftCardCost->getVAT(), Field::T_RAW);
        }

        // greetings card
        $this->oxorder__oxcardid = new Field($oBasket->getCardId(), Field::T_RAW);

        // card text will be stored in database
        $this->oxorder__oxcardtext = new Field($oBasket->getCardMessage(), Field::T_RAW);
    }

    /**
     * Creates OrderArticle objects and assigns to them basket articles.
     * Updates quantity of sold articles (Article::updateSoldAmount()).
     *
     * @param array $aArticleList article list
     * @deprecated underscore prefix violates PSR12, will be renamed to "setOrderArticles" in next major
     */
    protected function _setOrderArticles($aArticleList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // reset articles list
        $this->_oArticles = oxNew(ListModel::class);
        $iCurrLang = $this->getOrderLanguage();

        // add all the products we have on basket to the order
        foreach ($aArticleList as $oContent) {
            //$oContent->oProduct = $oContent->getArticle();
            // #M773 Do not use article lazy loading on order save
            $oProduct = $oContent->getArticle(true, null, true);

            // copy only if object is oxarticle type
            if ($oProduct->isOrderArticle()) {
                $oOrderArticle = $oProduct;
            } else {
                // if order language does not match product language article must be reloaded in order language
                if ($iCurrLang != $oProduct->getLanguage()) {
                    $oProduct->loadInLang($iCurrLang, $oProduct->getProductId());
                }

                // set chosen select list
                $sSelList = '';
                if (count($aChosenSelList = $oContent->getChosenSelList())) {
                    foreach ($aChosenSelList as $oItem) {
                        if ($sSelList) {
                            $sSelList .= ", ";
                        }
                        $sSelList .= "{$oItem->name} : {$oItem->value}";
                    }
                    if ($sSelList !== '' && $oContent->getVarSelect() !== '') {
                        $sSelList .= ' ||';
                    }
                }

                $oOrderArticle = oxNew(OrderArticle::class);
                $oOrderArticle->setIsNewOrderItem(true);
                $oOrderArticle->copyThis($oProduct);
                $oOrderArticle->setId();

                $oOrderArticle->oxorderarticles__oxartnum = clone $oProduct->oxarticles__oxartnum;
                $oOrderArticle->oxorderarticles__oxselvariant = new Field(trim($sSelList . ' ' . $oContent->getVarSelect()), Field::T_RAW);
                $oOrderArticle->oxorderarticles__oxshortdesc = new Field($oProduct->oxarticles__oxshortdesc->getRawValue(), Field::T_RAW);
                // #M974: duplicated entries for the name of variants in orders
                $oOrderArticle->oxorderarticles__oxtitle = new Field(trim($oProduct->oxarticles__oxtitle->getRawValue()), Field::T_RAW);

                // copying persistent parameters ...
                if (!is_array($aPersParams = $oProduct->getPersParams())) {
                    $aPersParams = $oContent->getPersParams();
                }
                if (is_array($aPersParams) && count($aPersParams)) {
                    $oOrderArticle->oxorderarticles__oxpersparam = new Field(serialize($aPersParams), Field::T_RAW);
                }
            }

            // ids, titles, numbers ...
            $oOrderArticle->oxorderarticles__oxorderid = new Field($this->getId());
            $oOrderArticle->oxorderarticles__oxartid = new Field($oContent->getProductId());
            $oOrderArticle->oxorderarticles__oxamount = new Field($oContent->getAmount());

            // prices
            $oPrice = $oContent->getPrice();
            $oOrderArticle->oxorderarticles__oxnetprice = new Field($oPrice->getNettoPrice(), Field::T_RAW);
            $oOrderArticle->oxorderarticles__oxvatprice = new Field($oPrice->getVatValue(), Field::T_RAW);
            $oOrderArticle->oxorderarticles__oxbrutprice = new Field($oPrice->getBruttoPrice(), Field::T_RAW);
            $oOrderArticle->oxorderarticles__oxvat = new Field($oPrice->getVat(), Field::T_RAW);

            $oUnitPrice = $oContent->getUnitPrice();
            $oOrderArticle->oxorderarticles__oxnprice = new Field($oUnitPrice->getNettoPrice(), Field::T_RAW);
            $oOrderArticle->oxorderarticles__oxbprice = new Field($oUnitPrice->getBruttoPrice(), Field::T_RAW);

            // wrap id
            $oOrderArticle->oxorderarticles__oxwrapid = new Field($oContent->getWrappingId(), Field::T_RAW);

            // items shop id
            $oOrderArticle->oxorderarticles__oxordershopid = new Field($oContent->getShopId(), Field::T_RAW);

            // bundle?
            $oOrderArticle->oxorderarticles__oxisbundle = new Field($oContent->isBundle());

            // add information for eMail
            //P
            //TODO: check if this assign is needed at all
            $oOrderArticle->oProduct = $oProduct;

            $oOrderArticle->setArticle($oProduct);

            // simulation order article list
            $this->_oArticles->offsetSet($oOrderArticle->getId(), $oOrderArticle);
        }
    }

    /**
     * Executes payment. Additionally, loads oxPaymentGateway object, initiates
     * it by adding payment parameters (oxPaymentGateway::setPaymentParams())
     * and finally executes it (oxPaymentGateway::executePayment()). On failure -
     * deletes order and returns * error code 2.
     *
     * @param Basket $oBasket basket object
     * @param $oUserPayment
     * @return  integer 2 or an error code
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "executePayment" in next major
     */
    protected function _executePayment(Basket $oBasket, $oUserPayment) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oPayTransaction = $this->_getGateway();
        $oPayTransaction->setPaymentParams($oUserPayment);

        if (!$oPayTransaction->executePayment($oBasket->getPrice()->getBruttoPrice(), $this)) {
            $this->delete();

            // checking for error messages
            if (method_exists($oPayTransaction, 'getLastError')) {
                if (($sLastError = $oPayTransaction->getLastError())) {
                    return $sLastError;
                }
            }

            // checking for error codes
            if (method_exists($oPayTransaction, 'getLastErrorNo')) {
                if (($iLastErrorNo = $oPayTransaction->getLastErrorNo())) {
                    return $iLastErrorNo;
                }
            }

            return self::ORDER_STATE_PAYMENTERROR; // means no authentication
        }

        return true; // everything fine
    }

    /**
     * Returns the correct gateway. At the moment only switch between default
     * and IPayment, can be extended later.
     *
     * @return object $oPayTransaction payment gateway object
     * @deprecated underscore prefix violates PSR12, will be renamed to "getGateway" in next major
     */
    protected function _getGateway() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return oxNew(PaymentGateway::class);
    }

    /**
     * Creates and returns user payment.
     *
     * @param string $sPaymentid used payment id
     *
     * @return UserPayment
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "setPayment" in next major
     */
    protected function _setPayment($sPaymentid) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oPayment = oxNew(EshopPayment::class);

        if (!$oPayment->load($sPaymentid)) {
            return null;
        }

        $aDynvalue = $this->getDynamicValues();

        $oPayment->setDynValues(Registry::getUtils()->assignValuesFromText($oPayment->oxpayments__oxvaldesc->value));

        // collecting dynamic values
        $aDynVal = [];

        if (is_array($aPaymentDynValues = $oPayment->getDynValues())) {
            foreach ($aPaymentDynValues as $key => $oVal) {
                if (isset($aDynvalue[$oVal->name])) {
                    $oVal->value = $aDynvalue[$oVal->name];
                }

                //$oPayment->setDynValue($key, $oVal);
                $aPaymentDynValues[$key] = $oVal;
                $aDynVal[$oVal->name] = $oVal->value;
            }
        }

        // Store this payment information, we might allow users later to
        // reactivate already give payment information

        $oUserPayment = oxNew(UserPayment::class);
        $oUserPayment->oxuserpayments__oxuserid = clone $this->oxorder__oxuserid;
        $oUserPayment->oxuserpayments__oxpaymentsid = new Field($sPaymentid, Field::T_RAW);
        $oUserPayment->oxuserpayments__oxvalue = new Field(Registry::getUtils()->assignValuesToText($aDynVal), Field::T_RAW);
        $oUserPayment->oxpayments__oxdesc = clone $oPayment->oxpayments__oxdesc;
        $oUserPayment->oxpayments__oxlongdesc = clone $oPayment->oxpayments__oxlongdesc;
        $oUserPayment->setDynValues($aPaymentDynValues);
        $oUserPayment->save();

        // storing payment information to order
        $this->oxorder__oxpaymentid = new Field($oUserPayment->getId(), Field::T_RAW);
        $this->oxorder__oxpaymenttype = clone $oUserPayment->oxuserpayments__oxpaymentsid;

        // returning user payment object which will be used later in code ...
        return $oUserPayment;
    }

    /**
     * Assigns oxfolder as new
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFolder" in next major
     */
    protected function _setFolder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $this->oxorder__oxfolder = new Field(key($myConfig->getShopConfVar('aOrderfolder', $myConfig->getShopId())), Field::T_RAW);
    }

    /**
     * aAdds/removes user chosen article to/from his noticelist
     * or wishlist (oxuserbasket::addItemToBasket()).
     *
     * @param array $aArticleList basket products
     * @param object $oUser user object
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateWishlist" in next major
     */
    protected function _updateWishlist($aArticleList, $oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        foreach ($aArticleList as $oContent) {
            if (($sWishId = $oContent->getWishId())) {
                // checking which wishlist user uses ..
                if ($sWishId == $oUser->getId()) {
                    $oUserBasket = $oUser->getBasket('wishlist');
                } else {
                    $aWhere = ['oxuserbaskets.oxuserid' => $sWishId, 'oxuserbaskets.oxtitle' => 'wishlist'];
                    $oUserBasket = oxNew(UserBasket::class);
                    $oUserBasket->assignRecord($oUserBasket->buildSelectString($aWhere));
                }

                // updating users wish list
                if ($oUserBasket) {
                    if (!($sProdId = $oContent->getWishArticleId())) {
                        $sProdId = $oContent->getProductId();
                    }
                    $oUserBasketItem = $oUserBasket->getItem($sProdId, $oContent->getSelList());
                    $dNewAmount = $oUserBasketItem->oxuserbasketitems__oxamount->value - $oContent->getAmount();
                    if ($dNewAmount < 0) {
                        $dNewAmount = 0;
                    }
                    $oUserBasket->addItemToBasket($sProdId, $dNewAmount, $oContent->getSelList(), true);
                }
            }
        }
    }

    /**
     * After order is finished this method cleans up users notice list, by
     * removing bought items from users notice list
     *
     * @param array $aArticleList array of basket products
     * @param User $oUser basket user object
     *
     * @return void
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateNoticeList" in next major
     */
    protected function _updateNoticeList($aArticleList, $oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        /*
         * #6141
         * If there is no noticelist, don't create an empty one.
         * Because loading the list via $user->getBasket('noticelist') will create it if there isn't one, but it will
         * only exists in the session for now. So it is possible to check if it has an oxid. If yes then we had a list.
         * If no then it was just created and will cause a new row in oxuserbaskets without content in oxuserbasketitems.
         * Also it will prevent creating a row for guests.
         */
        if ($oUser->getBasket('noticelist')->oxuserbaskets__oxid->value === null) {
            return;
        }

        // loading users notice list ...
        if ($oUserBasket = $oUser->getBasket('noticelist')) {
            // only if wishlist is enabled
            foreach ($aArticleList as $oContent) {
                $sProdId = $oContent->getProductId();

                // updating users notice list
                $oUserBasketItem = $oUserBasket->getItem($sProdId, $oContent->getSelList(), $oContent->getPersParams());
                $dNewAmount = $oUserBasketItem->oxuserbasketitems__oxamount->value - $oContent->getAmount();
                if ($dNewAmount < 0) {
                    $dNewAmount = 0;
                }
                $oUserBasket->addItemToBasket($sProdId, $dNewAmount, $oContent->getSelList(), true, $oContent->getPersParams());
            }
        }
    }

    /**
     * Updates order date to current date
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateOrderDate" in next major
     */
    protected function _updateOrderDate() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sDate = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
        $sQ = 'update oxorder set oxorderdate = :oxorderdate where oxid = :oxid';
        $this->oxorder__oxorderdate = new Field($sDate, Field::T_RAW);
        $oDb->execute($sQ, [
            ':oxorderdate' => $sDate,
            ':oxid' => $this->getId()
        ]);
    }

    /**
     * Marks voucher as used (oxvoucher::markAsUsed())
     * and sets them to $this->_aVoucherList.
     *
     * @param Basket $oBasket basket object
     * @param User $oUser user object
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "markVouchers" in next major
     */
    protected function _markVouchers($oBasket, $oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_aVoucherList = $oBasket->getVouchers();

        if (is_array($this->_aVoucherList)) {
            foreach ($this->_aVoucherList as $sVoucherId => $oSimpleVoucher) {
                $oVoucher = oxNew(EshopVoucherModel::class);
                $oVoucher->load($sVoucherId);
                $oVoucher->markAsUsed($this->oxorder__oxid->value, $oUser->oxuser__oxid->value, $oSimpleVoucher->dVoucherdiscount);

                $this->_aVoucherList[$sVoucherId] = $oVoucher;
            }
        }
    }

    /**
     * Updates/inserts order object and related info to DB
     *
     * @return null
     * @throws Exception
     */
    public function save()
    {
        if (($blSave = parent::save())) {
            // saving order articles
            $oOrderArticles = $this->getOrderArticles();
            if ($oOrderArticles && count($oOrderArticles) > 0) {
                foreach ($oOrderArticles as $oOrderArticle) {
                    $oOrderArticle->save();
                }
            }
        }

        return $blSave;
    }

    /**
     * Loads and returns delivery address object or null
     * if deladrid is not configured, or object was not loaded
     *
     * @return Address|null
     */
    public function getDelAddressInfo()
    {
        $oDelAddress = null;
        if (!($soxAddressId = Registry::getRequest()->getRequestEscapedParameter('deladrid'))) {
            $soxAddressId = Registry::getSession()->getVariable('deladrid');
        }
        if ($soxAddressId) {
            $oDelAddress = oxNew(Address::class);
            $oDelAddress->load($soxAddressId);

            //get delivery country name from delivery country id
            if ($oDelAddress->oxaddress__oxcountryid->value && $oDelAddress->oxaddress__oxcountryid->value != -1) {
                $oCountry = oxNew(Country::class);
                $oCountry->load($oDelAddress->oxaddress__oxcountryid->value);
                $oDelAddress->oxaddress__oxcountry = clone $oCountry->oxcountry__oxtitle;
            }
        }

        return $oDelAddress;
    }

    /**
     * Function which checks if article stock is valid.
     * If not displays error and returns false.
     *
     * @param object $oBasket basket object
     *
     * @throws NoArticleException
     * @throws ArticleInputException
     * @throws OutOfStockException
     */
    public function validateStock($oBasket)
    {
        foreach ($oBasket->getContents() as $key => $oContent) {
            try {
                $oProd = $oContent->getArticle(true, null, true);
            } catch (NoArticleException $oEx) {
                $oBasket->removeItem($key);
                throw $oEx;
            } catch (ArticleInputException $oEx) {
                $oBasket->removeItem($key);
                throw $oEx;
            }

            // check if it's still available
            $dArtStockAmount = $oBasket->getArtStockInBasket($oProd->getId(), $key);
            $iOnStock = $oProd->checkForStock($oContent->getAmount(), $dArtStockAmount);
            if ($iOnStock !== true) {
                /** @var OutOfStockException $oEx */
                $oEx = oxNew(OutOfStockException::class);
                $oEx->setMessage('ERROR_MESSAGE_OUTOFSTOCK_OUTOFSTOCK');
                $oEx->setArticleNr($oProd->oxarticles__oxartnum->value);
                $oEx->setProductId($oProd->getId());
                $oEx->setBasketIndex($key);

                if (!is_numeric($iOnStock)) {
                    $iOnStock = 0;
                }
                $oEx->setRemainingAmount($iOnStock);
                throw $oEx;
            }
        }
    }

    /**
     * Inserts order object information in DB. Returns true on success.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $oUtilsDate = Registry::getUtilsDate();

        //V #M525 oxorderdate must be the same as it was
        if (!$this->oxorder__oxorderdate || !$this->oxorder__oxorderdate->value) {
            $this->oxorder__oxorderdate = new Field(date('Y-m-d H:i:s', $oUtilsDate->getTime()), Field::T_RAW);
        } else {
            $this->oxorder__oxorderdate = new Field(
                $oUtilsDate->formatDBDate(
                    $this->oxorder__oxorderdate ? $this->oxorder__oxorderdate->value : null,
                    true
                )
            );
        }

        $this->oxorder__oxshopid = new Field($myConfig->getShopId(), Field::T_RAW);
        $this->oxorder__oxsenddate = new Field(
            $oUtilsDate->formatDBDate(
                $this->oxorder__oxsenddate ? $this->oxorder__oxsenddate->value : null,
                true
            )
        );

        $blInsert = parent::_insert();

        return $blInsert;
    }

    /**
     * creates counter ident
     *
     * @return String
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCounterIdent" in next major
     */
    protected function _getCounterIdent() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCounterIdent = ($this->_blSeparateNumbering) ? 'oxOrder_' . Registry::getConfig()->getShopId() : 'oxOrder';

        return $sCounterIdent;
    }


    /**
     * Tries to fetch and set next record number in DB. Returns true on success
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setNumber" in next major
     */
    protected function _setNumber() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();

        $iCnt = oxNew(Counter::class)->getNext($this->_getCounterIdent());
        $sQ = "update oxorder set oxordernr = :oxordernr where oxid = :oxid";
        $blUpdate = (bool) $oDb->execute($sQ, [
            ':oxordernr' => $iCnt,
            ':oxid' => $this->getId()
        ]);

        if ($blUpdate) {
            $this->oxorder__oxordernr = new Field($iCnt);
        }

        return $blUpdate;
    }

    /**
     * Updates object parameters to DB.
     *
     * @return null
     * @throws DatabaseException
     * @deprecated underscore prefix violates PSR12, will be renamed to "update" in next major
     */
    protected function _update() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_aSkipSaveFields = ['oxtimestamp', 'oxorderdate'];
        $this->oxorder__oxsenddate = new Field(
            Registry::getUtilsDate()->formatDBDate($this->oxorder__oxsenddate->value, true));

        return parent::_update();
    }

    /**
     * Updates stock information, deletes current ordering details from DB,
     * returns true on success.
     *
     * @param null $sOxId Ordering ID (default null)
     *
     * @return bool
     * @throws DatabaseConnectionException|DatabaseErrorException
     */
    public function delete($sOxId = null)
    {
        if ($sOxId) {
            if (!$this->load($sOxId)) {
                // such order does not exist
                return false;
            }
        } elseif (!$sOxId) {
            $sOxId = $this->getId();
        }

        // no order id is passed
        if (!$sOxId) {
            return false;
        }

        // delete order articles
        $oOrderArticles = $this->getOrderArticles(false);
        foreach ($oOrderArticles as $oOrderArticle) {
            $oOrderArticle->delete();
        }

        // #440 - deleting user payment info
        if ($oPaymentType = $this->getPaymentType()) {
            $oPaymentType->delete();
        }

        return parent::delete($sOxId);
    }

    /**
     * Recalculates order. Starts transactions, deletes current order and order articles from DB,
     * adds current order articles to virtual basket and finally recalculates order by calling Order::finalizeOrder()
     * If no errors, finishing transaction.
     *
     * @param array $aNewArticles article list of new order
     *
     * @throws Exception
     */
    public function recalculateOrder($aNewArticles = [])
    {
        DatabaseProvider::getDb()->startTransaction();
        try {
            $oBasket = $this->_getOrderBasket();

            // add this order articles to virtual basket and recalculates basket
            $this->_addOrderArticlesToBasket($oBasket, $this->getOrderArticles(true));

            // adding new articles to existing order
            $this->_addArticlesToBasket($oBasket, $aNewArticles);

            // recalculating basket
            $oBasket->calculateBasket(true);

            //finalizing order (skipping payment execution, vouchers marking and mail sending)
            $iRet = $this->finalizeOrder($oBasket, $this->getOrderUser(), true);

            //if finalizing order failed, rollback transaction
            if ($iRet !== 1) {
                DatabaseProvider::getDb()->rollbackTransaction();
            } else {
                DatabaseProvider::getDb()->commitTransaction();
            }
        } catch (Exception $exception) {
            DatabaseProvider::getDb()->rollbackTransaction();

            throw $exception;
        }
    }

    /**
     * Returns basket object filled up with discount, delivery, wrapping and all other info
     *
     * @param bool $blStockCheck perform stock check or not (default true)
     *
     * @return Basket
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getOrderBasket" in next major
     */
    protected function _getOrderBasket($blStockCheck = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_oOrderBasket = oxNew(Basket::class);
        $this->_oOrderBasket->enableSaveToDataBase(false);

        //setting recalculation mode
        $this->_oOrderBasket->setCalculationModeNetto($this->isNettoMode());

        // setting stock check mode
        $this->_oOrderBasket->setStockCheckMode($blStockCheck);

        // setting virtual basket user
        $this->_oOrderBasket->setBasketUser($this->getOrderUser());

        // transferring order id
        $this->_oOrderBasket->setOrderId($this->getId());

        // setting basket currency order uses
        $aCurrencies = Registry::getConfig()->getCurrencyArray();
        foreach ($aCurrencies as $oCur) {
            if ($oCur->name == $this->oxorder__oxcurrency->value) {
                $oBasketCur = $oCur;
                break;
            }
        }

        // setting currency
        $this->_oOrderBasket->setBasketCurrency($oBasketCur);

        // set basket card id and message
        $this->_oOrderBasket->setCardId($this->oxorder__oxcardid->value);
        $this->_oOrderBasket->setCardMessage($this->oxorder__oxcardtext->value);

        if ($this->_blReloadDiscount) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            // disabling availability check
            $this->_oOrderBasket->setSkipVouchersChecking(true);

            // add previously used vouchers
            $sQ = 'select oxid from oxvouchers where oxorderid = :oxorderid';
            $aVouchers = $oDb->getAll($sQ, [
                ':oxorderid' => $this->getId()
            ]);
            foreach ($aVouchers as $aVoucher) {
                $this->_oOrderBasket->addVoucher($aVoucher['oxid']);
            }
        } else {
            $this->_oOrderBasket->setDiscountCalcMode(false);
            $this->_oOrderBasket->setVoucherDiscount($this->oxorder__oxvoucherdiscount->value);
            $this->_oOrderBasket->setTotalDiscount($this->oxorder__oxdiscount->value);
        }

        // must be kept old delivery?
        if (!$this->_blReloadDelivery) {
            $this->_oOrderBasket->setDeliveryPrice($this->getOrderDeliveryPrice());
        } else {
            //  set shipping
            $this->_oOrderBasket->setShipping($this->oxorder__oxdeltype->value);
            $this->_oOrderBasket->setDeliveryPrice(null);
        }

        //set basket payment
        $this->_oOrderBasket->setPayment($this->oxorder__oxpaymenttype->value);

        return $this->_oOrderBasket;
    }

    /**
     * Sets new delivery id for order and forces order to recalculate using new delivery type.
     * Order is not recalculated automatically, to do this Order::recalculateOrder() must be called ;
     *
     * @param string $sDeliveryId new delivery id
     */
    public function setDelivery($sDeliveryId)
    {
        $this->reloadDelivery(true);
        $this->oxorder__oxdeltype = new Field($sDeliveryId);
    }

    /**
     * Returns current order user object
     *
     * @return User
     */
    public function getOrderUser()
    {
        if ($this->_oUser === null) {
            $this->_oUser = oxNew(User::class);
            $this->_oUser->load($this->oxorder__oxuserid->value);

            // if object is loaded then reusing its order info
            if ($this->_isLoaded) {
                // bill address
                $this->_oUser->oxuser__oxcompany = clone $this->oxorder__oxbillcompany;
                $this->_oUser->oxuser__oxusername = clone $this->oxorder__oxbillemail;
                $this->_oUser->oxuser__oxfname = clone $this->oxorder__oxbillfname;
                $this->_oUser->oxuser__oxlname = clone $this->oxorder__oxbilllname;
                $this->_oUser->oxuser__oxstreet = clone $this->oxorder__oxbillstreet;
                $this->_oUser->oxuser__oxstreetnr = clone $this->oxorder__oxbillstreetnr;
                $this->_oUser->oxuser__oxaddinfo = clone $this->oxorder__oxbilladdinfo;
                $this->_oUser->oxuser__oxustid = clone $this->oxorder__oxbillustid;

                $this->_oUser->oxuser__oxcity = clone $this->oxorder__oxbillcity;
                $this->_oUser->oxuser__oxcountryid = clone $this->oxorder__oxbillcountryid;
                $this->_oUser->oxuser__oxstateid = clone $this->oxorder__oxbillstateid;
                $this->_oUser->oxuser__oxzip = clone $this->oxorder__oxbillzip;
                $this->_oUser->oxuser__oxfon = clone $this->oxorder__oxbillfon;
                $this->_oUser->oxuser__oxfax = clone $this->oxorder__oxbillfax;
                $this->_oUser->oxuser__oxsal = clone $this->oxorder__oxbillsal;
            }
        }

        return $this->_oUser;
    }

    /**
     * Fake entries, pdf is generated in modules...
     *
     * @param mixed $oPdf pdf object
     */
    public function pdfFooter($oPdf)
    {
    }

    /**
     * Fake entries, pdf is generated in modules...
     *
     * @param mixed $oPdf pdf object
     */
    public function pdfHeaderplus($oPdf)
    {
    }

    /**
     * Fake entries, pdf is generated in modules...
     *
     * @param mixed $oPdf pdf object
     */
    public function pdfHeader($oPdf)
    {
    }

    /**
     * Fake entries, pdf is generated in modules...
     *
     * @param string $sFilename file name
     * @param int    $iSelLang  selected language
     */
    public function genPdf($sFilename, $iSelLang = 0)
    {
    }

    /**
     * Returns order invoice number.
     *
     * @return integer
     * @throws DatabaseConnectionException
     */
    public function getInvoiceNum()
    {
        $sQ = 'select max(oxorder.oxinvoicenr) from oxorder 
            where oxorder.oxshopid = :oxshopid ';
        $params = [
            ':oxshopid' => Registry::getConfig()->getShopId()
        ];

        return ((int) DatabaseProvider::getDb()->getOne($sQ, $params) + 1);
    }

    /**
     * Returns next possible (free) order bill number.
     *
     * @return integer
     * @throws DatabaseConnectionException
     */
    public function getNextBillNum()
    {
        $sQ = 'select max(cast(oxorder.oxbillnr as unsigned)) from oxorder 
            where oxorder.oxshopid = :oxshopid ';
        $params = [
            ':oxshopid' => Registry::getConfig()->getShopId()
        ];

        return ((int) DatabaseProvider::getDb()->getOne($sQ, $params) + 1);
    }

    /**
     * Loads possible shipping sets for this order
     *
     * @return Deliverysetlist
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function getShippingSetList()
    {
        // in which country we deliver
        if (!($sShipId = $this->oxorder__oxdelcountryid->value)) {
            $sShipId = $this->oxorder__oxbillcountryid->value;
        }

        $oBasket = $this->_getOrderBasket(false);

        // unsetting bundles
        $oOrderArticles = $this->getOrderArticles();
        foreach ($oOrderArticles as $sItemId => $oItem) {
            if ($oItem->isBundle()) {
                $oOrderArticles->offsetUnset($sItemId);
            }
        }

        // add this order articles to basket and recalculate basket
        $this->_addOrderArticlesToBasket($oBasket, $oOrderArticles);

        // recalculating basket
        $oBasket->calculateBasket(true);

        // load fitting deliveries list
        $oDeliveryList = oxNew(DeliveryList::class, "core");
        $oDeliveryList->setCollectFittingDeliveriesSets(true);

        return $oDeliveryList->getDeliveryList($oBasket, $this->getOrderUser(), $sShipId);
    }

    /**
     * Get vouchers numbers list which were used with this order
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getVoucherNrList()
    {
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $aVouchers = [];
        $sSelect = "select oxvouchernr from oxvouchers 
            where oxorderid = :oxorderid";
        $rs = $oDb->select($sSelect, [
            ':oxorderid' => $this->oxorder__oxid->value
        ]);
        if ($rs && $rs->count() > 0) {
            while (!$rs->EOF) {
                $aVouchers[] = $rs->fields['oxvouchernr'];
                $rs->fetchRow();
            }
        }

        return $aVouchers;
    }

    /**
     * Returns orders total price
     *
     * @param bool $blToday if true calculates only current day orders
     *
     * @return double
     * @throws DatabaseConnectionException
     */
    public function getOrderSum($blToday = false)
    {
        $sSelect = 'select sum(oxtotalordersum / oxcurrate) from oxorder where ';
        $sSelect .= 'oxshopid = :oxshopid and oxorder.oxstorno != "1" ';

        if ($blToday) {
            $sSelect .= 'and oxorderdate like "' . date('Y-m-d') . '%" ';
        }

        $params = [
            ':oxshopid' => Registry::getConfig()->getShopId()
        ];

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (double) DatabaseProvider::getMaster()->getOne($sSelect, $params);
    }

    /**
     * Returns orders count
     *
     * @param bool $blToday if true calculates only current day orders
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    public function getOrderCnt($blToday = false)
    {
        $sSelect = 'select count(*) from oxorder where ';
        $sSelect .= 'oxshopid = :oxshopid  and oxorder.oxstorno != "1" ';

        if ($blToday) {
            $sSelect .= 'and oxorderdate like "' . date('Y-m-d') . '%" ';
        }

        $params = [
            ':oxshopid' => Registry::getConfig()->getShopId()
        ];

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (int) DatabaseProvider::getMaster()->getOne($sSelect, $params);
    }


    /**
     * Checking if this order is already stored.
     *
     * @param null $sOxId order ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkOrderExist" in next major
     */
    protected function _checkOrderExist($sOxId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$sOxId) {
            return false;
        }

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();
        $params = [
            ':oxid' => $sOxId
        ];
        if ($masterDb->getOne('select oxid from oxorder where oxid = :oxid', $params)) {
            return true;
        }

        return false;
    }

    /**
     * Send order to shop owner and user
     *
     * @param User        $oUser    order user
     * @param Basket      $oBasket  current order basket
     * @param UserPayment $oPayment order payment
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "sendOrderByEmail" in next major
     */
    protected function _sendOrderByEmail($oUser = null, $oBasket = null, $oPayment = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iRet = self::ORDER_STATE_MAILINGERROR;

        // add user, basket and payment to order
        $this->_oUser = $oUser;
        $this->_oBasket = $oBasket;
        $this->_oPayment = $oPayment;

        $oxEmail = oxNew(Email::class);

        // send order email to user
        if ($oxEmail->sendOrderEMailToUser($this)) {
            // mail to user was successfully sent
            $iRet = self::ORDER_STATE_OK;
        }

        // send order email to shop owner
        $oxEmail->sendOrderEMailToOwner($this);

        return $iRet;
    }

    /**
     * Returns order basket
     *
     * @return Basket
     */
    public function getBasket()
    {
        return $this->_oBasket;
    }

    /**
     * Returns order payment
     *
     * @return UserPayment
     */
    public function getPayment()
    {
        return $this->_oPayment;
    }

    /**
     * Returns order vouchers marked as used
     *
     * @return array
     */
    public function getVoucherList()
    {
        return $this->_aVoucherList;
    }

    /**
     * Returns order deliveryset object
     *
     * @return DeliverySet
     */
    public function getDelSet()
    {
        if ($this->_oDelSet == null) {
            // load deliveryset info
            $this->_oDelSet = oxNew(DeliverySet::class);
            $this->_oDelSet->load($this->oxorder__oxdeltype->value);
        }

        return $this->_oDelSet;
    }

    /**
     * Get payment type
     *
     * @return UserPayment
     * @throws DatabaseConnectionException
     */
    public function getPaymentType()
    {
        if ($this->oxorder__oxpaymentid->value && $this->_oPaymentType === null) {
            $this->_oPaymentType = false;
            $oPaymentType = oxNew(UserPayment::class);
            if ($oPaymentType->load($this->oxorder__oxpaymentid->value)) {
                $this->_oPaymentType = $oPaymentType;
            }
        }

        return $this->_oPaymentType;
    }

    /**
     * Get gift card
     *
     * @return Wrapping
     */
    public function getGiftCard()
    {
        if ($this->oxorder__oxcardid->value && $this->_oGiftCard == null) {
            $this->_oGiftCard = oxNew(Wrapping::class);
            $this->_oGiftCard->load($this->oxorder__oxcardid->value);
        }

        return $this->_oGiftCard;
    }

    /**
     * Set usage of separate orders numbering for different shops
     *
     * @param bool $blSeparateNumbering use or not separate orders numbering
     */
    public function setSeparateNumbering($blSeparateNumbering = null)
    {
        $this->_blSeparateNumbering = $blSeparateNumbering;
    }

    /**
     * Get users payment type from last order
     *
     * @param string $sUserId order user id
     *
     * @return string $sLastPaymentId payment id
     * @throws DatabaseConnectionException
     */
    public function getLastUserPaymentType($sUserId)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();
        $sQ = 'select oxorder.oxpaymenttype from oxorder 
            where oxorder.oxshopid = :oxshopid 
                and oxorder.oxuserid = :oxuserid 
            order by oxorder.oxorderdate desc ';

        $sLastPaymentId = $masterDb->getOne($sQ, [
            ':oxshopid' => Registry::getConfig()->getShopId(),
            ':oxuserid' => $sUserId
        ]);

        return $sLastPaymentId;
    }

    /**
     * Adds order articles back to virtual basket. Needed for recalculating order.
     *
     * @param Basket $oBasket basket object
     * @param ListModel $aOrderArticles order articles
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     * @deprecated underscore prefix violates PSR12, will be renamed to "addOrderArticlesToBasket" in next major
     */
    protected function _addOrderArticlesToBasket($oBasket, $aOrderArticles) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // if no order articles, return empty basket
        if (count($aOrderArticles) > 0) {
            //adding order articles to basket
            foreach ($aOrderArticles as $oOrderArticle) {
                $oBasket->addOrderArticleToBasket($oOrderArticle);
            }
        }
    }

    /**
     * Adds new products to basket/order
     *
     * @param Basket $oBasket basket to add articles
     * @param array $aArticles article array
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     * @deprecated underscore prefix violates PSR12, will be renamed to "addArticlesToBasket" in next major
     */
    protected function _addArticlesToBasket($oBasket, $aArticles) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // if no order articles
        if (count($aArticles) > 0) {
            //adding order articles to basket
            foreach ($aArticles as $oArticle) {
                $aSel = isset($oArticle->oxorderarticles__oxselvariant) ? $oArticle->oxorderarticles__oxselvariant->value : null;
                $aPersParam = isset($oArticle->oxorderarticles__oxpersparam) ? $oArticle->getPersParams() : null;
                $oBasket->addToBasket(
                    $oArticle->oxorderarticles__oxartid->value,
                    $oArticle->oxorderarticles__oxamount->value,
                    $aSel,
                    $aPersParam
                );
            }
        }
    }

    /**
     * Get total sum from last order
     *
     * @return string
     */
    public function getTotalOrderSum()
    {
        $oCur = Registry::getConfig()->getActShopCurrencyObject();

        return number_format((double) $this->oxorder__oxtotalordersum->value, $oCur->decimal, '.', '');
    }

    /**
     * Returns array of plain formatted VATs stored in order
     *
     * @param bool $blFormatCurrency enables currency formatting
     *
     * @return array
     */
    public function getProductVats($blFormatCurrency = true)
    {
        $aVats = [];
        if ($this->oxorder__oxartvat1->value) {
            $aVats[$this->oxorder__oxartvat1->value] = $this->oxorder__oxartvatprice1->value;
        }
        if ($this->oxorder__oxartvat2->value) {
            $aVats[$this->oxorder__oxartvat2->value] = $this->oxorder__oxartvatprice2->value;
        }

        if ($blFormatCurrency) {
            $oLang = Registry::getLang();
            $oCur = Registry::getConfig()->getActShopCurrencyObject();
            foreach ($aVats as $sKey => $dVat) {
                $aVats[$sKey] = $oLang->formatCurrency($dVat, $oCur);
            }
        }

        return $aVats;
    }

    /**
     * Get billing country name from billing country id
     *
     * @return Field
     */
    public function getBillCountry()
    {
        if (!$this->oxorder__oxbillcountry->value) {
            $this->oxorder__oxbillcountry = new Field($this->_getCountryTitle($this->oxorder__oxbillcountryid->value));
        }

        return $this->oxorder__oxbillcountry;
    }

    /**
     * Get delivery country name from delivery country id
     *
     * @return Field
     */
    public function getDelCountry()
    {
        if (!$this->oxorder__oxdelcountry->value) {
            $this->oxorder__oxdelcountry = new Field($this->_getCountryTitle($this->oxorder__oxdelcountryid->value));
        }

        return $this->oxorder__oxdelcountry;
    }

    /**
     * Tells to keep old or reload delivery costs while recalculating order
     *
     * @param bool $blReload reload state marker
     */
    public function reloadDelivery($blReload)
    {
        $this->_blReloadDelivery = $blReload;
    }

    /**
     * Tells to keep old or reload discount while recalculating order
     *
     * @param bool $blReload reload state marker
     */
    public function reloadDiscount($blReload)
    {
        $this->_blReloadDiscount = $blReload;
    }

    /**
     * Performs order cancel process
     */
    public function cancelOrder()
    {
        $this->oxorder__oxstorno = new Field(1);
        if ($this->save()) {
            // canceling ordered products
            foreach ($this->getOrderArticles() as $oOrderArticle) {
                $oOrderArticle->cancelOrderArticle();
            }
        }
    }

    /**
     * Returns actual order currency object. In case currency was not recognized
     * due to changed name returns first shop currency object
     *
     * @return stdClass
     */
    public function getOrderCurrency()
    {
        if ($this->_oOrderCurrency === null) {
            // setting default in case unrecognized currency was set during order
            $aCurrencies = Registry::getConfig()->getCurrencyArray();
            $this->_oOrderCurrency = current($aCurrencies);

            foreach ($aCurrencies as $oCurr) {
                if ($oCurr->name == $this->oxorder__oxcurrency->value) {
                    $this->_oOrderCurrency = $oCurr;
                    break;
                }
            }
        }

        return $this->_oOrderCurrency;
    }

    /**
     * Validates order parameters like stock, delivery and payment
     * parameters
     *
     * @param Basket $oBasket basket object
     * @param User $oUser order user
     *
     * @return null
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function validateOrder($oBasket, $oUser)
    {
        // validating stock
        $iValidState = $this->validateStock($oBasket);

        if (!$iValidState) {
            // validating delivery
            $iValidState = $this->validateDelivery($oBasket);
        }

        if (!$iValidState) {
            // validating payment
            $iValidState = $this->validatePayment($oBasket, $oUser);
        }

        if (!$iValidState) {
            //0003110 validating delivery address, it is not be changed during checkout process
            $iValidState = $this->validateDeliveryAddress($oUser);
        }

        if (!$iValidState) {
            // validating minimum price
            $iValidState = $this->validateBasket($oBasket);
        }

        if (!$iValidState) {
            // validating vouchers
            $iValidState = $this->validateVouchers($oBasket);
        }

        return $iValidState;
    }

    /**
     * @return int|void
     */
    public function validateVouchers($basket)
    {
        $voucherIds = array_keys($basket->getVouchers());
        foreach ($voucherIds as $voucherId) {
            $voucher = oxNew(EshopVoucherModel::class);
            $voucher->load($voucherId);
            if ($voucher->getFieldData('oxorderid')) {
                return self::ORDER_STATE_VOUCHERERROR;
            }
        }
    }

    /**
     * Validates basket. Currently, checks if minimum order price > basket price
     *
     * @param Basket $oBasket basket object
     *
     * @return int|null
     */
    public function validateBasket($oBasket)
    {
        return $oBasket->isBelowMinOrderPrice() ? self::ORDER_STATE_BELOWMINPRICE : null;
    }

    /**
     * Checks if delivery address (billing or shipping) was not changed during checkout
     * Throws exception if not available
     *
     * @param User $oUser user object
     *
     * @return int
     */
    public function validateDeliveryAddress($oUser)
    {
        $sDelAddressMD5 = Registry::getRequest()->getRequestEscapedParameter('sDeliveryAddressMD5');

        $sDeliveryAddress = $oUser->getEncodedDeliveryAddress();

        /** @var RequiredAddressFields $oRequiredAddressFields */
        $oRequiredAddressFields = oxNew(RequiredAddressFields::class);

        /** @var RequiredFieldsValidator $oFieldsValidator */
        $oFieldsValidator = oxNew(RequiredFieldsValidator::class);
        $oFieldsValidator->setRequiredFields($oRequiredAddressFields->getBillingFields());
        $blFieldsValid = $oFieldsValidator->validateFields($oUser);

        /** @var Address $oDeliveryAddress */
        $oDeliveryAddress = $this->getDelAddressInfo();
        if ($blFieldsValid && $oDeliveryAddress) {
            $sDeliveryAddress .= $oDeliveryAddress->getEncodedDeliveryAddress();

            $oFieldsValidator->setRequiredFields($oRequiredAddressFields->getDeliveryFields());
            $blFieldsValid = $oFieldsValidator->validateFields($oDeliveryAddress);
        }

        $iState = 0;
        if ($sDelAddressMD5 != $sDeliveryAddress || !$blFieldsValid) {
            $iState = self::ORDER_STATE_INVALIDDELADDRESSCHANGED;
        }

        return $iState;
    }


    /**
     * Checks if delivery set used for current order is available and active.
     * Throws exception if not available
     *
     * @param Basket $oBasket basket object
     *
     * @return int|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function validateDelivery($oBasket)
    {
        // proceed with no delivery
        // used for other countries
        if ($oBasket->getPaymentId() == 'oxempty') {
            return;
        }
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();

        $oDelSet = oxNew(DeliverySet::class);
        $sTable = $oDelSet->getViewName();

        $sQ = "select 1 from {$sTable} where {$sTable}.oxid = :oxid and " . $oDelSet->getSqlActiveSnippet();
        $params = [
            ':oxid' => $oBasket->getShippingId()
        ];

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        if (!$masterDb->getOne($sQ, $params)) {
            // throwing exception
            return self::ORDER_STATE_INVALIDDELIVERY;
        }
    }

    /**
     * Checks if payment used for current order is available and active.
     * Throws exception if not available
     *
     * @param Basket $oBasket basket object
     * @param User|null $oUser user object
     *
     * @return int|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function validatePayment($oBasket, $oUser = null)
    {
        $paymentId = $oBasket->getPaymentId();

        if (!$this->isValidPaymentId($paymentId) || !$this->isValidPayment($oBasket, $oUser)) {
            return self::ORDER_STATE_INVALIDPAYMENT;
        }
    }

    /**
     * Get total net sum formatted
     *
     * @return string
     */
    public function getFormattedTotalNetSum()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxtotalnetsum->value, $this->getOrderCurrency());
    }

    /**
     * Get total brut sum formatted
     *
     * @return string
     */
    public function getFormattedTotalBrutSum()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxtotalbrutsum->value, $this->getOrderCurrency());
    }

    /**
     * Get Delivery cost sum formatted
     *
     * @return string
     */
    public function getFormattedDeliveryCost()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxdelcost->value, $this->getOrderCurrency());
    }

    /**
     * Get Delivery cost sum formatted
     *
     * @deprecated in v5.2.0 on 2014-04-12; Typo use Order::getFormattedPayCost()
     *
     * @return string
     */
    public function getFormattedeliveryCost()
    {
        return $this->getFormattedDeliveryCost();
    }

    /**
     * Get pay cost sum formatted
     *
     * @return string
     */
    public function getFormattedPayCost()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxpaycost->value, $this->getOrderCurrency());
    }

    /**
     * Get wrap cost sum formatted
     *
     * @return string
     */
    public function getFormattedWrapCost()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxwrapcost->value, $this->getOrderCurrency());
    }

    /**
     * Get wrap cost sum formatted
     *
     * @return string
     */
    public function getFormattedGiftCardCost()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxgiftcardcost->value, $this->getOrderCurrency());
    }

    /**
     * Get total vouchers formatted
     *
     * @return string
     */
    public function getFormattedTotalVouchers()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxvoucherdiscount->value, $this->getOrderCurrency());
    }

    /**
     * Get Discount formatted
     *
     * @return string
     */
    public function getFormattedDiscount()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxdiscount->value, $this->getOrderCurrency());
    }

    /**
     * Get formatted total sum from last order
     *
     * @return string
     */
    public function getFormattedTotalOrderSum()
    {
        return Registry::getLang()->formatCurrency($this->oxorder__oxtotalordersum->value, $this->getOrderCurrency());
    }

    /**
     * Returns shipment tracking code
     *
     * @return string
     */
    public function getTrackCode()
    {
        return $this->oxorder__oxtrackcode->value;
    }

    /**
     * Returns shipment tracking url if oxtrackcode and shipment tracking url are supplied
     *
     * @return string
     */
    public function getShipmentTrackingUrl()
    {
        $oConfig = Registry::getConfig();
        if ($this->_sShipTrackUrl === null) {
            $sParcelService = $oConfig->getConfigParam('sParcelService');
            $sTrackingCode = $this->getTrackCode();
            if ($sParcelService && $sTrackingCode) {
                $this->_sShipTrackUrl = str_replace("##ID##", $sTrackingCode, $sParcelService);
            }
        }

        return $this->_sShipTrackUrl;
    }

    /**
     * Returns true if paymentId is valid.
     *
     * @param int $paymentId
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    private function isValidPaymentId($paymentId)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();

        $paymentModel = oxNew(EshopPayment::class);
        $tableName = $paymentModel->getViewName();

        $sql = "
            select
                1 
            from 
                {$tableName}
            where 
                {$tableName}.oxid = :oxid
                and {$paymentModel->getSqlActiveSnippet()}
        ";

        return (bool) $masterDb->getOne($sql, [
            ':oxid' => $paymentId
        ]);
    }

    /**
     * Returns true if payment is valid.
     *
     * @param Basket $basket
     * @param User|null $oUser user object
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    private function isValidPayment($basket, $oUser = null)
    {
        $paymentId = $basket->getPaymentId();
        $paymentModel = oxNew(EshopPayment::class);
        $paymentModel->load($paymentId);

        $dynamicValues = $this->getDynamicValues();
        $shopId = Registry::getConfig()->getShopId();

        if (!$oUser) {
            $oUser = $this->getUser();
        }

        return $paymentModel->isValidPayment(
            $dynamicValues,
            $shopId,
            $oUser,
            $basket->getPriceForPayment(),
            $basket->getShippingId()
        );
    }

    /**
     * @return mixed
     * @throws DatabaseConnectionException
     */
    private function getDynamicValues()
    {
        $dynamicValues = Registry::getSession()->getVariable('dynvalue');

        if (!$dynamicValues) {
            $dynamicValues = Registry::getRequest()->getRequestParameter('dynvalue');
        }

        if (!$dynamicValues && $this->getPaymentType()) {
            $dynamicValues = $this->getDynamicValuesFromPaymentType();
        }

        return $dynamicValues;
    }

    /**
     * @return array
     * @throws DatabaseConnectionException
     */
    private function getDynamicValuesFromPaymentType()
    {
        $dynamicValues = null;
        $dynamicValuesList = $this->getPaymentType()->getDynValues();

        if (is_array($dynamicValuesList)) {
            foreach ($dynamicValuesList as $value) {
                $dynamicValues[$value->name] = $value->value;
            }
        }

        return $dynamicValues;
    }
}
