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
use OxidEsales\Eshop\Application\Controller\Admin\DeliveryMainAjax;
use OxidEsales\Eshop\Application\Model\Delivery;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main delivery manager.
 * There is possibility to change delivery name, article, user etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main.
 */
class DeliveryMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates delivery category tree,
     * passes data to Smarty engine and returns name of template file "delivery_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $oLang = Registry::getLang();

        // remove itm from list
        unset($this->_aViewData["sumtype"][2]);

        // delivery-types
        $aDelTypes = $this->getDeliveryTypes();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oDelivery = oxNew(Delivery::class);
            $oDelivery->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oDelivery->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oDelivery->loadInLang(key($oOtherLang), $soxId);
            }

            $this->_aViewData["edit"] = $oDelivery;

            //Disable editing for derived articles
            if ($oDelivery->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            // remove already created languages
            $aLang = array_diff($oLang->getLanguageNames(), $oOtherLang);
            if (count($aLang)) {
                $this->_aViewData["posslang"] = $aLang;
            }

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }

            // set selected delivery type
            if (!$oDelivery->oxdelivery__oxdeltype->value) {
                $oDelivery->oxdelivery__oxdeltype = new Field("a"); // default
            }
            $aDelTypes[$oDelivery->oxdelivery__oxdeltype->value]->selected = true;
        }

        $this->_aViewData["deltypes"] = $aDelTypes;

        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oDeliveryMainAjax = oxNew(DeliveryMainAjax::class);
            $this->_aViewData['oxajax'] = $oDeliveryMainAjax->getColumns();

            return "popups/delivery_main.tpl";
        }

        return "delivery_main.tpl";
    }

    /**
     * Saves delivery information changes.
     *
     * @return void
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oDelivery = oxNew(Delivery::class);

        if ($soxId != "-1") {
            $oDelivery->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxdelivery__oxid'] = null;
        }

        // checkbox handling
        if (!isset($aParams['oxdelivery__oxactive'])) {
            $aParams['oxdelivery__oxactive'] = 0;
        }

        if (!isset($aParams['oxdelivery__oxfixed'])) {
            $aParams['oxdelivery__oxfixed'] = 0;
        }

        if (!isset($aParams['oxdelivery__oxfinalize'])) {
            $aParams['oxdelivery__oxfinalize'] = 0;
        }

        if (!isset($aParams['oxdelivery__oxsort'])) {
            $aParams['oxdelivery__oxsort'] = 9999;
        }

        //Disable editing for derived articles
        if ($oDelivery->isDerived()) {
            return;
        }

        $oDelivery->setLanguage(0);
        $oDelivery->assign($aParams);
        $oDelivery->setLanguage($this->_iEditLang);
        $oDelivery = Registry::getUtilsFile()->processFiles($oDelivery);
        $oDelivery->save();

        // set oxid if inserted
        $this->setEditObjectId($oDelivery->getId());
    }

    /**
     * Saves delivery information changes.
     *
     * @return void
     */
    public function saveinnlang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oDelivery = oxNew(Delivery::class);

        if ($soxId != "-1") {
            $oDelivery->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxdelivery__oxid'] = null;
        }

        // checkbox handling
        if (!isset($aParams['oxdelivery__oxactive'])) {
            $aParams['oxdelivery__oxactive'] = 0;
        }
        if (!isset($aParams['oxdelivery__oxfixed'])) {
            $aParams['oxdelivery__oxfixed'] = 0;
        }

        //Disable editing for derived articles
        if ($oDelivery->isDerived()) {
            return;
        }

        $oDelivery->setLanguage(0);
        $oDelivery->assign($aParams);
        $oDelivery->setLanguage($this->_iEditLang);
        $oDelivery = Registry::getUtilsFile()->processFiles($oDelivery);
        $oDelivery->save();

        // set oxid if inserted
        $this->setEditObjectId($oDelivery->getId());
    }

    /**
     * returns delivery types
     *
     * @return array
     */
    public function getDeliveryTypes()
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getTplLanguage();

        $aDelTypes = [];
        $oType = new stdClass();
        $oType->sType = "a";      // amount
        $oType->sDesc = $oLang->translateString("amount", $iLang);
        $aDelTypes['a'] = $oType;
        $oType = new stdClass();
        $oType->sType = "s";      // Size
        $oType->sDesc = $oLang->translateString("size", $iLang);
        $aDelTypes['s'] = $oType;
        $oType = new stdClass();
        $oType->sType = "w";      // Weight
        $oType->sDesc = $oLang->translateString("weight", $iLang);
        $aDelTypes['w'] = $oType;
        $oType = new stdClass();
        $oType->sType = "p";      // Price
        $oType->sDesc = $oLang->translateString("price", $iLang);
        $aDelTypes['p'] = $oType;

        return $aDelTypes;
    }
}
