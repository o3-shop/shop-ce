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
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Wrapping manager.
 * Performs Wrapping data/objects loading, deleting.
 *
 */
class Wrapping extends MultiLanguageModel
{
    /**
     * Class name
     *
     * @var string name of current class
     */
    protected $_sClassName = 'oxwrapping';

    /**
     * Wrapping oxprice object.
     *
     * @var Price
     */
    protected $_oPrice = null;

    /**
     * Wrapping Vat
     *
     * @var double
     */
    protected $_dVat = 0;

    /**
     * Wrapping VAT config
     *
     * @var bool
     */
    protected $_blWrappingVatOnTop = false;

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()), loads
     * base shop objects.
     */
    public function __construct()
    {
        $oConfig = Registry::getConfig();
        $this->setWrappingVat($oConfig->getConfigParam('dDefaultVAT'));
        $this->setWrappingVatOnTop($oConfig->getConfigParam('blWrappingVatOnTop'));
        parent::__construct();
        $this->init('oxwrapping');
    }

    /**
     * Wrapping Vat setter
     *
     * @param double $dVat vat
     */
    public function setWrappingVat($dVat)
    {
        $this->_dVat = $dVat;
    }

    /**
     * Wrapping VAT config setter
     *
     * @param bool $blOnTop wrapping vat config
     */
    public function setWrappingVatOnTop($blOnTop)
    {
        $this->_blWrappingVatOnTop = $blOnTop;
    }

    /**
     * Returns oxprice object for wrapping
     *
     * @param int $dAmount article amount
     *
     * @return object
     */
    public function getWrappingPrice($dAmount = 1)
    {
        if ($this->_oPrice === null) {
            $this->_oPrice = oxNew(Price::class);

            if (!$this->_blWrappingVatOnTop) {
                $this->_oPrice->setBruttoPriceMode();
            } else {
                $this->_oPrice->setNettoPriceMode();
            }

            $oCur = Registry::getConfig()->getActShopCurrencyObject();
            $this->_oPrice->setPrice($this->oxwrapping__oxprice->value * $oCur->rate, $this->_dVat);
            $this->_oPrice->multiply($dAmount);
        }

        return $this->_oPrice;
    }

    /**
     * Loads wrapping list for specific wrap type
     *
     * @param string $sWrapType wrap type
     *
     * @return array $oEntries wrapping list
     */
    public function getWrappingList($sWrapType)
    {
        // load wrapping
        $oEntries = oxNew(ListModel::class);
        $oEntries->init('oxwrapping');
        $sWrappingViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxwrapping');
        $sSelect = "select * from $sWrappingViewName 
            where $sWrappingViewName.oxactive = :oxactive
              and $sWrappingViewName.oxtype = :oxtype";
        $oEntries->selectString($sSelect, [
            ':oxactive' => '1',
            ':oxtype' => $sWrapType
        ]);

        return $oEntries;
    }

    /**
     * Counts amount of wrapping/card options
     *
     * @param string $sWrapType type - wrapping paper (WRAP) or card (CARD)
     *
     * @return int
     * @throws DatabaseConnectionException
     */
    public function getWrappingCount($sWrapType)
    {
        $sWrappingViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxwrapping');
        $oDb = DatabaseProvider::getDb();
        $sQ = "select count(*) from $sWrappingViewName 
            where $sWrappingViewName.oxactive = :oxactive 
              and $sWrappingViewName.oxtype = :oxtype";

        return (int) $oDb->getOne($sQ, [
            ':oxactive' => '1',
            ':oxtype' => $sWrapType
        ]);
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
        $oUser = $this->getUser();
        if ($oUser) {
            $blResult = $oUser->isPriceViewModeNetto();
        }

        return $blResult;
    }

    /**
     * Returns formatted wrapping price
     *
     * @deprecated since v5.1 (2013-10-13); use oxPrice smarty plugin for formatting in templates
     *
     * @return string
     */
    public function getFPrice()
    {
        $dPrice = $this->getPrice();

        return Registry::getLang()->formatCurrency($dPrice, Registry::getConfig()->getActShopCurrencyObject());
    }

    /**
     * Gets price.
     *
     * @return double
     */
    public function getPrice()
    {
        if ($this->_isPriceViewModeNetto()) {
            $dPrice = $this->getWrappingPrice()->getNettoPrice();
        } else {
            $dPrice = $this->getWrappingPrice()->getBruttoPrice();
        }

        return $dPrice;
    }

    /**
     * Returns returns dyn image dir (not ssl)
     *
     * @return string
     */
    public function getNoSslDynImageDir()
    {
        return Registry::getConfig()->getPictureUrl(null, false, false, null, $this->oxwrapping__oxshopid->value);
    }

    /**
     * Returns returns dyn image dir
     *
     * @return string|void
     */
    public function getPictureUrl()
    {
        if ($this->oxwrapping__oxpic->value) {
            return Registry::getConfig()->getPictureUrl("master/wrapping/" . $this->oxwrapping__oxpic->value, false, Registry::getConfig()->isSsl(), null, $this->oxwrapping__oxshopid->value);
        }
    }
}
