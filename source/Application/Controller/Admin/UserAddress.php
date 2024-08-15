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
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin user address setting manager.
 * Collects user address settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> Addresses.
 */
class UserAddress extends AdminDetailsController
{
    /**
     * If true, means that address was deleted
     *
     * @var bool
     */
    protected $_blDelete = false;

    /**
     * Executes parent method parent::render(), creates oxuser and oxbase objects,
     * passes data to Smarty engine and returns name of template file
     * "user_address.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oUser = oxNew(User::class);
            $oUser->load($soxId);

            // load adress
            $sAddressIdParameter = Registry::getRequest()->getRequestEscapedParameter('oxaddressid');
            $sAddressId = isset($this->sSavedOxid) ? $this->sSavedOxid : $sAddressIdParameter;
            if ($sAddressId != "-1" && isset($sAddressId)) {
                $oAdress = oxNew(Address::class);
                $oAdress->load($sAddressId);
                $this->_aViewData["edit"] = $oAdress;
            }

            $this->_aViewData["oxaddressid"] = $sAddressId;

            // generate selected
            $oAddressList = $oUser->getUserAddresses();
            foreach ($oAddressList as $oAddress) {
                if ($oAddress->oxaddress__oxid->value == $sAddressId) {
                    $oAddress->selected = 1;
                    break;
                }
            }

            $this->_aViewData["edituser"] = $oUser;
        }

        $oCountryList = oxNew(\OxidEsales\Eshop\Application\Model\CountryList::class);
        $oCountryList->loadActiveCountries(Registry::getLang()->getObjectTplLanguage());

        $this->_aViewData["countrylist"] = $oCountryList;

        if (!$this->_allowAdminEdit($soxId)) {
            $this->_aViewData['readonly'] = true;
        }

        return "user_address.tpl";
    }

    /**
     * Saves user addressing information.
     */
    public function save()
    {
        parent::save();

        if ($this->_allowAdminEdit($this->getEditObjectId())) {
            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
            $oAdress = oxNew(Address::class);
            if (isset($aParams['oxaddress__oxid']) && $aParams['oxaddress__oxid'] == "-1") {
                $aParams['oxaddress__oxid'] = null;
            } else {
                $oAdress->load($aParams['oxaddress__oxid']);
            }

            $oAdress->assign($aParams);
            $oAdress->save();

            $this->sSavedOxid = $oAdress->getId();
        }
    }

    /**
     * Deletes user addressing information.
     */
    public function delAddress()
    {
        $this->_blDelete = false;
        if ($this->_allowAdminEdit($this->getEditObjectId())) {
            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
            if (isset($aParams['oxaddress__oxid']) && $aParams['oxaddress__oxid'] != "-1") {
                $oAdress = oxNew(Address::class);
                $this->_blDelete = $oAdress->delete($aParams['oxaddress__oxid']);
            }
        }
    }
}
