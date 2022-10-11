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

namespace OxidEsales\EshopCommunity\Application\Component;

use oxRegistry;

/**
 * Currency manager class.
 *
 * @subpackage oxcmp
 */
class CurrencyComponent extends \OxidEsales\Eshop\Core\Controller\BaseController
{
    /**
     * Array of available currencies.
     *
     * @var array
     */
    public $aCurrencies = null;

    /**
     * Active currency object.
     *
     * @var object
     */
    protected $_oActCur = null;

    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Checks for currency parameter set in URL, session or post
     * variables. If such were found - loads all currencies possible
     * in shop, searches if passed is available (if no - default
     * currency is set the first defined in admin). Then sets currency
     * parameter so session ($myConfig->setActShopCurrency($iCur)),
     * loads basket and forces ir to recalculate (oBasket->blCalcNeeded
     * = true). Finally executes parent::init().
     *
     * @return null
     */
    public function init()
    {
        // Performance
        $myConfig = $this->getConfig();
        if (!$myConfig->getConfigParam('bl_perfLoadCurrency')) {
            //#861C -  show first currency
            $aCurrencies = $myConfig->getCurrencyArray();
            $this->_oActCur = current($aCurrencies);

            return;
        }

        $iCur = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('cur');
        if (isset($iCur)) {
            $aCurrencies = $myConfig->getCurrencyArray();
            if (!isset($aCurrencies[$iCur])) {
                $iCur = 0;
            }

            // set new currency
            $myConfig->setActShopCurrency($iCur);

            // recalc basket
            $oBasket = $this->getSession()->getBasket();
            $oBasket->onUpdate();
        }

        $iActCur = $myConfig->getShopCurrency();
        $this->aCurrencies = $myConfig->getCurrencyArray($iActCur);

        $this->_oActCur = $this->aCurrencies[$iActCur];

        //setting basket currency (M:825)
        if (!isset($oBasket)) {
            $oBasket = $this->getSession()->getBasket();
        }
        $oBasket->setBasketCurrency($this->_oActCur);
        parent::init();
    }

    /**
     * Executes parent::render(), passes currency object to template
     * engine and returns currencies array.
     *
     * Template variables:
     * <b>currency</b>
     *
     * @return array
     */
    public function render()
    {
        parent::render();
        $oParentView = $this->getParent();
        $oParentView->setActCurrency($this->_oActCur);

        $oUrlUtils = \OxidEsales\Eshop\Core\Registry::getUtilsUrl();
        $sUrl = $oUrlUtils->cleanUrl($this->getConfig()->getTopActiveView()->getLink(), ["cur"]);

        if ($this->getConfig()->getConfigParam('bl_perfLoadCurrency')) {
            reset($this->aCurrencies);
            foreach ($this->aCurrencies as $oItem) {
                $oItem->link = $oUrlUtils->processUrl($sUrl, true, ["cur" => $oItem->id]);
            }
        }

        return $this->aCurrencies;
    }
}
