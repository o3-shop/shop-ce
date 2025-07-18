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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main payment manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Payment Methods -> Main.
 */
class PaymentCountry extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxlist object,
     * passes its data to Smarty engine and returns name of template
     * file "payment_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        // remove itm from list
        unset($this->_aViewData["sumtype"][2]);

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oPayment = oxNew(Payment::class);
            $oPayment->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oPayment->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oPayment->loadInLang(key($oOtherLang), $soxId);
            }
            $this->_aViewData["edit"] = $oPayment;

            // remove already created languages
            $aLang = array_diff(Registry::getLang()->getLanguageNames(), $oOtherLang);
            if (count($aLang)) {
                $this->_aViewData["posslang"] = $aLang;
            }

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }
        }

        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oPaymentCountryAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\PaymentCountryAjax::class);
            $this->_aViewData['oxajax'] = $oPaymentCountryAjax->getColumns();

            return "popups/payment_country.tpl";
        }

        return "payment_country.tpl";
    }

    /**
     * Adds chosen user group (groups) to delivery list
     */
    public function addcountry()
    {
        $sOxId = $this->getEditObjectId();
        $aChosenCntr = Registry::getRequest()->getRequestEscapedParameter('allcountries');
        if (isset($sOxId) && $sOxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Payment = oxNew(BaseModel::class);
                $oObject2Payment->init('oxobject2payment');
                $oObject2Payment->oxobject2payment__oxpaymentid = new Field($sOxId);
                $oObject2Payment->oxobject2payment__oxobjectid = new Field($sChosenCntr);
                $oObject2Payment->oxobject2payment__oxtype = new Field("oxcountry");
                $oObject2Payment->save();
            }
        }
    }

    /**
     * Removes chosen user group (groups) from delivery list
     */
    public function removecountry()
    {
        $sOxId = $this->getEditObjectId();
        $aChosenCntr = Registry::getRequest()->getRequestEscapedParameter('countries');
        if (isset($sOxId) && $sOxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Payment = oxNew(BaseModel::class);
                $oObject2Payment->init('oxobject2payment');
                $oObject2Payment->delete($sChosenCntr);
            }
        }
    }
}
