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
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Virtual basket manager class. Virtual baskets are user article lists which are stored in database (notice-lists, wishlists).
 * The name of the class is left like this because of historic reasons.
 * It is more relevant to wishlist and noticelist than to shopping basket.
 * Collects shopping basket information, updates it (DB level), removes or adds
 * articles to it.
 *
 */
class UserBasket extends BaseModel
{
    /**
     * Array of fields which must be skipped when updating object data
     *
     * @var array
     */
    protected $_aSkipSaveFields = ['oxcreate', 'oxtimestamp'];

    /**
     * Current object class name
     *
     * @var string
     */
    protected $_sClassName = 'oxUserbasket';

    /**
     * Array of basket items
     *
     * @var array
     */
    protected $_aBasketItems = null;

    /**
     * Marker if basket is newly created. This avoids empty basket storing to DB
     *
     * @var bool
     */
    protected $_blNewBasket = false;

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxuserbaskets');
    }

    /**
     * Inserts object data to DB, returns true on success.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // marking basket as not new anymore
        $this->_blNewBasket = false;

        if (!isset($this->oxuserbaskets__oxpublic->value)) {
            $this->oxuserbaskets__oxpublic = new Field(1, Field::T_RAW);
        }

        $iTime = Registry::getUtilsDate()->getTime();
        $this->oxuserbaskets__oxupdate = new Field($iTime);

        return parent::_insert();
    }

    /**
     * Sets basket as newly created. This usually means that it is not
     * yet stored in DB and will only be stored if some item is added
     */
    public function setIsNewBasket()
    {
        $this->_blNewBasket = true;
        $iTime = Registry::getUtilsDate()->getTime();
        $this->oxuserbaskets__oxupdate = new Field($iTime);
    }

    /**
     * Checks if user basket is newly created
     *
     * @return bool
     */
    public function isNewBasket()
    {
        return $this->_blNewBasket;
    }

    /**
     * Checks if user basket is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        if ($this->isNewBasket() || $this->getItemCount() < 1) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array of articles belonging to the Items in the basket
     *
     * @return array of oxArticle
     */
    public function getArticles()
    {
        $aRes = [];
        $aItems = $this->getItems();
        if (is_array($aItems)) {
            foreach ($aItems as $sId => $oItem) {
                $oArticle = $oItem->getArticle($sId);
                $aRes[$this->_getItemKey($oArticle->getId(), $oItem->getSelList(), $oItem->getPersParams())] = $oArticle;
            }
        }

        return $aRes;
    }

    /**
     * Returns list of basket items
     *
     * @param bool $blReload      if TRUE forces to reload list
     * @param bool $blActiveCheck should articles be checked for active state?
     *
     * @return array of oxUserBasketItems
     */
    public function getItems($blReload = false, $blActiveCheck = true)
    {
        // cached ?
        if ($this->_aBasketItems !== null && !$blReload) {
            return $this->_aBasketItems;
        }

        // initializing
        $this->_aBasketItems = [];

        // loading basket items
        $oArticle = oxNew(Article::class);
        $sViewName = $oArticle->getViewName();

        $sSelect = "select oxuserbasketitems.* from oxuserbasketitems 
            left join $sViewName on oxuserbasketitems.oxartid = $sViewName.oxid ";
        if ($blActiveCheck) {
            $sSelect .= 'and ' . $oArticle->getSqlActiveSnippet() . ' ';
        }
        $sSelect .= "where oxuserbasketitems.oxbasketid = :oxbasketid and $sViewName.oxid is not null ";

        $sSelect .= " order by oxartnum, oxsellist, oxpersparam ";

        $oItems = oxNew(ListModel::class);
        $oItems->init('oxuserbasketitem');
        $oItems->selectstring($sSelect, [
            ':oxbasketid' => $this->getId()
        ]);

        foreach ($oItems as $oItem) {
            $sKey = $this->_getItemKey($oItem->oxuserbasketitems__oxartid->value, $oItem->getSelList(), $oItem->getPersParams());
            $this->_aBasketItems[$sKey] = $oItem;
        }

        return $this->_aBasketItems;
    }

    /**
     * Creates and returns  oxuserbasketitem object
     *
     * @param string $sProductId Product ID
     * @param null $aSelList product select lists
     * @param null $aPersParams persistent parameters
     *
     * @return UserBasketItem
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "createItem" in next major
     */
    protected function _createItem($sProductId, $aSelList = null, $aPersParams = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oNewItem = oxNew(UserBasketItem::class);
        $oNewItem->oxuserbasketitems__oxartid = new Field($sProductId, Field::T_RAW);
        $oNewItem->oxuserbasketitems__oxbasketid = new Field($this->getId(), Field::T_RAW);
        if ($aPersParams && count($aPersParams)) {
            $oNewItem->setPersParams($aPersParams);
        }

        if (!$aSelList) {
            $oArticle = oxNew(Article::class);
            $oArticle->load($sProductId);
            $aSelectLists = $oArticle->getSelectLists();
            if (($iSelCnt = count($aSelectLists))) {
                $aSelList = array_fill(0, $iSelCnt, '0');
            }
        }

        $oNewItem->setSelList($aSelList);

        return $oNewItem;
    }


    /**
     * Searches for item in basket items array and returns it. If not item was
     * found - new item is created.
     *
     * @param string $sProductId product id, basket item id or basket item index
     * @param array $aSelList select lists
     * @param null $aPersParams persistent parameters
     *
     * @return UserBasketItem
     * @throws DatabaseConnectionException
     */
    public function getItem($sProductId, $aSelList, $aPersParams = null)
    {
        // loading basket item list
        $aItems = $this->getItems();
        $sItemKey = $this->_getItemKey($sProductId, $aSelList, $aPersParams);

        // returning existing item
        if (isset($aItems[$sProductId])) {
            $oItem = $aItems[$sProductId];
        } elseif (isset($aItems[$sItemKey])) {
            $oItem = $aItems[$sItemKey];
        } else {
            $oItem = $this->_createItem($sProductId, $aSelList, $aPersParams);
        }

        return $oItem;
    }

    /**
     * Returns unique item key according to its ID and user chosen select
     *
     * @param string $sProductId Product ID
     * @param array  $aSel       product select lists
     * @param array  $aPersParam basket item persistent parameters
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getItemKey" in next major
     */
    protected function _getItemKey($sProductId, $aSel = null, $aPersParam = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aSel = ($aSel != null) ? $aSel : [0 => '0'];

        return md5($sProductId . '|' . serialize($aSel) . '|' . serialize($aPersParam));
    }

    /**
     * Returns current basket item count
     *
     * @param bool $blReload if TRUE forces to reload list
     *
     * @return int
     */
    public function getItemCount($blReload = false)
    {
        return count($this->getItems($blReload));
    }

    /**
     * Method adds/removes user chosen article to/from his noticelist or wishlist. Returns total amount
     * of articles in list.
     *
     * @param string|null $sProductId Article ID
     * @param double|null $dAmount Product amount
     * @param array|null $aSel product select lists
     * @param bool $blOverride if true overrides $dAmount, else sums previous with current it
     * @param array|null $aPersParam product persistent parameters (default null)
     *
     * @return integer|void
     * @throws Exception
     */
    public function addItemToBasket($sProductId = null, $dAmount = null, $aSel = null, $blOverride = false, $aPersParam = null)
    {
        // basket info is only written in DB when something is in it
        if ($this->_blNewBasket) {
            $this->save();
        }

        if (($oUserBasketItem = $this->getItem($sProductId, $aSel, $aPersParam))) {
            // updating object info and adding (if not yet added) item into basket items array
            if (!$blOverride && !empty($oUserBasketItem->oxuserbasketitems__oxamount->value)) {
                $dAmount += $oUserBasketItem->oxuserbasketitems__oxamount->value;
            }

            if (!$dAmount) {
                // amount = 0 removes the item
                $oUserBasketItem->delete();
                if (isset($this->_aBasketItems[$this->_getItemKey($sProductId, $aSel, $aPersParam)])) {
                    unset($this->_aBasketItems[$this->_getItemKey($sProductId, $aSel, $aPersParam)]);
                }
            } else {
                $oUserBasketItem->oxuserbasketitems__oxamount = new Field($dAmount, Field::T_RAW);
                $oUserBasketItem->save();

                $this->_aBasketItems[$this->_getItemKey($sProductId, $aSel, $aPersParam)] = $oUserBasketItem;
            }

            //update timestamp
            $this->oxuserbaskets__oxupdate = new Field(Registry::getUtilsDate()->getTime());
            $this->save();

            return $dAmount;
        }
    }

    /**
     * Deletes current basket history
     *
     * @param null $sOXID Object ID(default null)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($sOXID = null)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }

        $blDelete = false;
        if ($sOXID && ($blDelete = parent::delete($sOXID))) {
            // cleaning up related data
            $oDb = DatabaseProvider::getDb();
            $sQ = "delete from oxuserbasketitems where oxbasketid = :oxbasketid";
            $oDb->execute($sQ, [
                ':oxbasketid' => $sOXID
            ]);
            $this->_aBasketItems = null;
        }

        return $blDelete;
    }

    /**
     * Checks if user basket is visible for current user (public or own basket)
     *
     * @return bool
     */
    public function isVisible()
    {
        $oActiveUser = Registry::getConfig()->getUser();
        $sActiveUserId = null;
        if ($oActiveUser) {
            $sActiveUserId = $oActiveUser->getId();
        }

        $blIsVisible = (bool) ($this->oxuserbaskets__oxpublic->value) ||
                       ($sActiveUserId && ($this->oxuserbaskets__oxuserid->value == $sActiveUserId));

        return $blIsVisible;
    }
}
