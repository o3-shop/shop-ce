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
use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;

/**
 * Basket reservations handler class
 *
 */
class BasketReservation extends Base
{
    /**
     * Reservations list
     *
     * @var UserBasket
     */
    protected $_oReservations = null;

    /**
     * Currently reserved products array
     *
     * @var array
     */
    protected $_aCurrentlyReserved = null;

    /**
     * return the ID of active reservations user basket
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getReservationsId" in next major
     */
    protected function _getReservationsId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sId = Registry::getSession()->getVariable('basketReservationToken');
        if (!$sId) {
            $utilsObject = $this->getUtilsObjectInstance();
            $sId = $utilsObject->generateUId();
            Registry::getSession()->setVariable('basketReservationToken', $sId);
        }

        return $sId;
    }

    /**
     * load reservation or create new reservation user basket
     *
     * @param string $sBasketId basket id for this user basket
     *
     * @return UserBasket
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadReservations" in next major
     */
    protected function _loadReservations($sBasketId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oReservations = oxNew(UserBasket::class);
        $aWhere = ['oxuserbaskets.oxuserid' => $sBasketId, 'oxuserbaskets.oxtitle' => 'reservations'];

        // creating if it does not exist
        if (!$oReservations->assignRecord($oReservations->buildSelectString($aWhere))) {
            $oReservations->oxuserbaskets__oxtitle = new Field('reservations');
            $oReservations->oxuserbaskets__oxuserid = new Field($sBasketId);
            // marking basket as new (it will not be saved in DB yet)
            $oReservations->setIsNewBasket();
        }

        return $oReservations;
    }

    /**
     * get reservations collection
     *
     * @return UserBasket
     */
    public function getReservations()
    {
        if ($this->_oReservations) {
            return $this->_oReservations;
        }

        if (!$sBasketId = $this->_getReservationsId()) {
            return null;
        }

        $this->_oReservations = $this->_loadReservations($sBasketId);

        return $this->_oReservations;
    }

    /**
     * return currently reserved items in an array format array (artId => amount)
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getReservedItems" in next major
     */
    protected function _getReservedItems() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($this->_aCurrentlyReserved)) {
            return $this->_aCurrentlyReserved;
        }

        $oReserved = $this->getReservations();
        if (!$oReserved) {
            return [];
        }

        $this->_aCurrentlyReserved = [];
        foreach ($oReserved->getItems(false, false) as $oItem) {
            if (!isset($this->_aCurrentlyReserved[$oItem->oxuserbasketitems__oxartid->value])) {
                $this->_aCurrentlyReserved[$oItem->oxuserbasketitems__oxartid->value] = 0;
            }
            $this->_aCurrentlyReserved[$oItem->oxuserbasketitems__oxartid->value] += $oItem->oxuserbasketitems__oxamount->value;
        }

        return $this->_aCurrentlyReserved;
    }

    /**
     * return currently reserved amount for an article
     *
     * @param string $sArticleId article id
     *
     * @return double
     */
    public function getReservedAmount($sArticleId)
    {
        $aCurrentlyReserved = $this->_getReservedItems();
        if (isset($aCurrentlyReserved[$sArticleId])) {
            return $aCurrentlyReserved[$sArticleId];
        }

        return 0;
    }

    /**
     * compute difference of reserved amounts vs basket items
     *
     * @param Basket $oBasket basket object
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "basketDifference" in next major
     */
    protected function _basketDifference(Basket $oBasket) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aDiff = $this->_getReservedItems();
        // refreshing history
        foreach ($oBasket->getContents() as $oItem) {
            $sProdId = $oItem->getProductId();
            if (!isset($aDiff[$sProdId])) {
                $aDiff[$sProdId] = -$oItem->getAmount();
            } else {
                $aDiff[$sProdId] -= $oItem->getAmount();
            }
        }

        return $aDiff;
    }

    /**
     * reserve articles given the basket difference array
     *
     * @param array $aBasketDiff basket difference array
     *
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @see oxBasketReservation::_basketDifference
     * @deprecated underscore prefix violates PSR12, will be renamed to "reserveArticles" in next major
     */
    protected function _reserveArticles($aBasketDiff) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blAllowNegativeStock = Registry::getConfig()->getConfigParam('blAllowNegativeStock');

        $oReserved = $this->getReservations();
        foreach ($aBasketDiff as $sId => $dAmount) {
            if ($dAmount != 0) {
                $oArticle = oxNew(Article::class);
                if ($oArticle->load($sId)) {
                    $oArticle->reduceStock(-$dAmount, $blAllowNegativeStock);
                    $oReserved->addItemToBasket($sId, -$dAmount);
                }
            }
        }
        $this->_aCurrentlyReserved = null;
    }

    /**
     * reserve given basket items, only when not in admin mode
     *
     * @param Basket $oBasket basket object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function reserveBasket(Basket $oBasket)
    {
        if (!$this->isAdmin()) {
            $this->_reserveArticles($this->_basketDifference($oBasket));
        }
    }

    /**
     * commit reservation of given article amount
     * deletes this amount from active reservations userBasket,
     * update sold amount
     *
     * @param string $sArticleId article id
     * @param double $dAmount amount to use
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function commitArticleReservation($sArticleId, $dAmount)
    {
        $dReserved = $this->getReservedAmount($sArticleId);

        if ($dReserved < $dAmount) {
            $dAmount = $dReserved;
        }

        $oArticle = oxNew(Article::class);
        $oArticle->load($sArticleId);

        $this->getReservations()->addItemToBasket($sArticleId, -$dAmount);
        $oArticle->beforeUpdate();
        $oArticle->updateSoldAmount($dAmount);
        $this->_aCurrentlyReserved = null;
    }

    /**
     * discard one article reservation
     * return the reserved stock to article
     *
     * @param string $sArticleId article id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function discardArticleReservation($sArticleId)
    {
        $dReserved = $this->getReservedAmount($sArticleId);
        if ($dReserved) {
            $oArticle = oxNew(Article::class);
            if ($oArticle->load($sArticleId)) {
                $oArticle->reduceStock(-$dReserved, true);
                $this->getReservations()->addItemToBasket($sArticleId, 0, null, true);
                $this->_aCurrentlyReserved = null;
            }
        }
    }

    /**
     * discard all reserved articles
     */
    public function discardReservations()
    {
        foreach (array_keys($this->_getReservedItems()) as $sArticleId) {
            $this->discardArticleReservation($sArticleId);
        }
        if ($this->_oReservations) {
            $this->_oReservations->delete();
            $this->_oReservations = null;
            $this->_aCurrentlyReserved = null;
        }
    }

    /**
     * periodic cleanup: discards timed out reservations even if they are not
     * for the current user
     *
     * @param int $iLimit limit for discarding (performance related)
     *
     * @throws Exception
     *
     * @return void
     */
    public function discardUnusedReservations($iLimit)
    {
        $database = DatabaseProvider::getMaster(DatabaseProvider::FETCH_MODE_ASSOC);

        $psBasketReservationTimeout = (int)Registry::getConfig()->getConfigParam('iPsBasketReservationTimeout');
        $startTime = Registry::getUtilsDate()->getTime() - $psBasketReservationTimeout;

        $parameters = [
            ':oxtitle'  => 'reservations',
            ':oxupdate' => $startTime
        ];

        $reservation = $database->select("select oxid from oxuserbaskets 
            where oxtitle = :oxtitle and oxupdate <= :oxupdate limit $iLimit", $parameters);
        if ($reservation->EOF) {
            return;
        }

        $finished = [];
        while (!$reservation->EOF) {
            $finished[] = $database->quote($reservation->fields['oxid']);
            $reservation->fetchRow();
        }

        $database->startTransaction();
        try {
            $finished = implode(',', $finished);

            $reservation = $database->select(
                'select oxartid, oxamount from oxuserbasketitems where oxbasketid in (' . $finished . ')',
                false
            );

            while (!$reservation->EOF) {
                $article = oxNew(Article::class);

                if ($article->load($reservation->fields['oxartid'])) {
                    $article->reduceStock(-$reservation->fields['oxamount'], true);
                }

                $reservation->fetchRow();
            }

            $shopId = Registry::getConfig()->getShopId();

            $database->execute('delete from oxuserbasketitems where oxbasketid in (' . $finished . ')');
            $database->execute(
                "delete from oxuserbasketitems where oxbasketid in (select oxid from oxuserbaskets where 
                        oxuserid in (select oxid from oxuser where oxshopid= :oxshopid))",
                [
                    ':oxshopid' => $shopId
                ]
            );

            $database->execute('delete from oxuserbaskets where oxid in (' . $finished . ')');
            $database->execute(
                "delete from oxuserbaskets where 
                        oxuserid in (select oxid from oxuser where oxshopid= :oxshopid) and 
                        oxuserbaskets.oxtitle = 'savedbasket' and oxuserbaskets.oxupdate <= :startTime",
                [
                    ':startTime' => $startTime,
                    ':oxshopid'  => $shopId
                ]
            );

            $database->commitTransaction();
        } catch (Exception $exception) {
            $database->rollbackTransaction();

            throw $exception;
        }

        $this->_aCurrentlyReserved = null;
    }

    /**
     * return time left (in seconds) for basket before expiration
     *
     * @return int
     */
    public function getTimeLeft()
    {
        $iTimeout = Registry::getConfig()->getConfigParam('iPsBasketReservationTimeout');
        if ($iTimeout > 0) {
            $oRev = $this->getReservations();
            if ($oRev && $oRev->getId()) {
                $iTimeout -= (Registry::getUtilsDate()->getTime() - (int) $oRev->oxuserbaskets__oxupdate->value);
                Registry::getSession()->setVariable("iBasketReservationTimeout", $oRev->oxuserbaskets__oxupdate->value);
            } elseif (($iSessionTimeout = Registry::getSession()->getVariable("iBasketReservationTimeout"))) {
                $iTimeout -= (Registry::getUtilsDate()->getTime() - (int) $iSessionTimeout);
            }

            return $iTimeout < 0 ? 0 : $iTimeout;
        }

        return 0;
    }

    /**
     * renews expiration timer to maximum value
     */
    public function renewExpiration()
    {
        if ($oReserved = $this->getReservations()) {
            $iTime = Registry::getUtilsDate()->getTime();
            $oReserved->oxuserbaskets__oxupdate = new Field($iTime);
            $oReserved->save();

            Registry::getSession()->deleteVariable("iBasketReservationTimeout");
        }
    }

    /**
     * @return UtilsObject
     */
    protected function getUtilsObjectInstance()
    {
        return Registry::getUtilsObject();
    }
}
