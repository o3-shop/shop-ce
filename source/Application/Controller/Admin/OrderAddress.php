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
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin order address manager.
 * Collects order addressing information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Address.
 */
class OrderAddress extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxorder object
     * and passes its data to Smarty engine. Returns name of template
     * file "order_address.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oOrder = oxNew(Order::class);
            $oOrder->load($soxId);

            $this->_aViewData["edit"] = $oOrder;
        }

        $oCountryList = oxNew(\OxidEsales\Eshop\Application\Model\CountryList::class);
        $oCountryList->loadActiveCountries(Registry::getLang()->getObjectTplLanguage());

        $this->_aViewData["countrylist"] = $oCountryList;

        return "order_address.tpl";
    }

    /**
     * Iterates through data array, checks if specified fields are filled
     * in, cleanups not needed data
     *
     * @param array  $aData          data to process
     * @param string $sTypeToProcess data type to process e.g. "oxorder__oxdel"
     * @param array  $aIgnore        fields which must be ignored while processing
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "processAddress" in next major
     */
    protected function _processAddress($aData, $sTypeToProcess, $aIgnore) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // empty address fields?
        $blEmpty = true;

        // here we will store names of fields which needs to be cleaned up
        $aFields = [];

        foreach ($aData as $sName => $sValue) {
            // if field type matches ...
            if (strpos($sName, $sTypeToProcess) !== false) {
                // storing which fields must be unset ...
                $aFields[] = $sName;

                // ignoring what's need to be ignored and testing values
                if (!in_array($sName, $aIgnore) && $sValue) {
                    // something was found - means leaving as is ...
                    $blEmpty = false;
                    break;
                }
            }
        }

        // cleanup if empty
        if ($blEmpty) {
            foreach ($aFields as $sName) {
                $aData[$sName] = "";
            }
        }

        return $aData;
    }

    /**
     * Saves ordering address information.
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = (array) Registry::getRequest()->getRequestEscapedParameter('editval');

        $oOrder = oxNew(Order::class);
        if ($soxId != "-1") {
            $oOrder->load($soxId);
        } else {
            $aParams['oxorder__oxid'] = null;
        }

        $aParams = $this->_processAddress($aParams, "oxorder__oxdel", ["oxorder__oxdelsal"]);
        $oOrder->assign($aParams);
        $oOrder->save();

        // set oxid if inserted
        $this->setEditObjectId($oOrder->getId());
    }
}
