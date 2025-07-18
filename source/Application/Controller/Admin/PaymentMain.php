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
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main payment manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Payment Methods -> Main.
 */
class PaymentMain extends AdminDetailsController
{
    /**
     * Keeps all act. fields to store
     */
    protected $_aFieldArray = null;

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
        $oPayment = oxNew(Payment::class);

        if (isset($soxId) && $soxId != "-1") {
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

            // #708
            $this->_aViewData['aFieldNames'] = Registry::getUtils()->assignValuesFromText($oPayment->oxpayments__oxvaldesc->value);
        }

        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oPaymentMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\PaymentMainAjax::class);
            $this->_aViewData['oxajax'] = $oPaymentMainAjax->getColumns();

            return "popups/payment_main.tpl";
        }

        $this->_aViewData["editor"] = $this->_generateTextEditor("100%", 300, $oPayment, "oxpayments__oxlongdesc");

        return "payment_main.tpl";
    }

    /**
     * Saves payment parameters changes.
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        // checkbox handling
        if (!isset($aParams['oxpayments__oxactive'])) {
            $aParams['oxpayments__oxactive'] = 0;
        }
        if (!isset($aParams['oxpayments__oxchecked'])) {
            $aParams['oxpayments__oxchecked'] = 0;
        }

        $oPayment = oxNew(Payment::class);

        if ($soxId != "-1") {
            $oPayment->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxpayments__oxid'] = null;
            //$aParams = $oPayment->ConvertNameArray2Idx( $aParams);
        }

        $oPayment->setLanguage(0);
        $oPayment->assign($aParams);

        // setting add sum calculation rules
        $aRules = (array) Registry::getRequest()->getRequestEscapedParameter('oxpayments__oxaddsumrules');
        // if sum equals 0, show notice, that default value will be used.
        if (empty($aRules)) {
            $this->_aViewData["noticeoxaddsumrules"] = 1;
        }
        $oPayment->oxpayments__oxaddsumrules = new Field(array_sum($aRules));


        //#708
        if (!is_array($this->_aFieldArray)) {
            $this->_aFieldArray = Registry::getUtils()->assignValuesFromText($oPayment->oxpayments__oxvaldesc->value);
        }

        // build value
        $sValdesc = "";
        foreach ($this->_aFieldArray as $oField) {
            $sValdesc .= $oField->name . "__@@";
        }

        $oPayment->oxpayments__oxvaldesc = new Field($sValdesc, Field::T_RAW);
        $oPayment->setLanguage($this->_iEditLang);
        $oPayment->save();

        // set oxid if inserted
        $this->setEditObjectId($oPayment->getId());
    }

    /**
     * Saves payment parameters data in different language (eg. english).
     */
    public function saveinnlang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oObj = oxNew(Payment::class);

        if ($soxId != "-1") {
            $oObj->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxpayments__oxid'] = null;
            //$aParams = $oObj->ConvertNameArray2Idx( $aParams);
        }

        $oObj->setLanguage(0);
        $oObj->assign($aParams);

        // apply new language
        $oObj->setLanguage(Registry::getRequest()->getRequestEscapedParameter('new_lang'));
        $oObj->save();

        // set oxid if inserted
        $this->setEditObjectId($oObj->getId());
    }

    /**
     * Deletes field from field array and stores object
     */
    public function delFields()
    {
        $oPayment = oxNew(Payment::class);
        if ($oPayment->loadInLang($this->_iEditLang, $this->getEditObjectId())) {
            $aDelFields = Registry::getRequest()->getRequestEscapedParameter('aFields');
            $this->_aFieldArray = Registry::getUtils()->assignValuesFromText($oPayment->oxpayments__oxvaldesc->value);

            if (is_array($aDelFields) && count($aDelFields)) {
                foreach ($aDelFields as $sDelField) {
                    foreach ($this->_aFieldArray as $sKey => $oField) {
                        if ($oField->name == $sDelField) {
                            unset($this->_aFieldArray[$sKey]);
                            break;
                        }
                    }
                }
                $this->save();
            }
        }
    }

    /**
     * Adds a field to field array and stores object
     */
    public function addField()
    {
        $oPayment = oxNew(Payment::class);
        if ($oPayment->loadInLang($this->_iEditLang, $this->getEditObjectId())) {
            $this->_aFieldArray = Registry::getUtils()->assignValuesFromText($oPayment->oxpayments__oxvaldesc->value);

            $oField = new stdClass();
            $oField->name = Registry::getRequest()->getRequestEscapedParameter('sAddField');

            if (!empty($oField->name)) {
                $this->_aFieldArray[] = $oField;
            }
            $this->save();
        }
    }
}
