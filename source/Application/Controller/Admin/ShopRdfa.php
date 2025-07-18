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

use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Admin shop system RDFa manager.
 * Collects shop system settings, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> RDFa.
 *
 */
class ShopRdfa extends ShopConfiguration
{
    /**
     * Template name
     *
     * @var array
     */
    protected $_sThisTemplate = 'shop_rdfa.tpl';

    /**
     * Predefined customer types
     *
     * @var array
     */
    protected $_aCustomers = [
        "Enduser"           => 0,
        "Reseller"          => 0,
        "Business"          => 0,
        "PublicInstitution" => 0,
    ];

    /**
     * Gets list of content pages which could be used for embedding
     * business entity, price specification, and delivery specification data
     *
     * @return ContentList
     */
    public function getContentList()
    {
        $oContentList = oxNew(\OxidEsales\Eshop\Application\Model\ContentList::class);
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName("oxcontents", $this->_iEditLang);
        $oContentList->selectString(
            "SELECT * 
             FROM {$sTable} 
             WHERE OXACTIVE = 1 AND OXTYPE = 0
                AND OXLOADID IN ('oxagb', 'oxdeliveryinfo', 'oximpressum', 'oxrightofwithdrawal')
                AND OXSHOPID = :OXSHOPID
             ORDER BY OXLOADID ASC",
            [':OXSHOPID' => Registry::getRequest()->getRequestEscapedParameter('oxid')]
        );

        return $oContentList;
    }

    /**
     * Handles and returns customer array
     *
     * @return array
     */
    public function getCustomers()
    {
        $aCustomersConf = Registry::getConfig()->getShopConfVar("aRDFaCustomers");
        if (isset($aCustomersConf)) {
            foreach ($this->_aCustomers as $sCustomer => $iValue) {
                $aCustomers[$sCustomer] = (in_array($sCustomer, $aCustomersConf)) ? 1 : 0;
            }
        } else {
            $aCustomers = [];
        }

        return $aCustomers;
    }

    /**
     * Submits shop main page to web search engines.
     *
     * @deprecated since v6.0-rc.3 (2017-10-16); GR-Notify registration feature is removed.
     */
    public function submitUrl()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter('aSubmitUrl');
        if ($aParams['url']) {
            $sNotificationUrl = "http://gr-notify.appspot.com/submit?uri=" . urlencode($aParams['url']) . "&agent=oxid";
            if ($aParams['email']) {
                $sNotificationUrl .= "&contact=" . urlencode($aParams['email']);
            }
            $aHeaders = $this->getHttpResponseCode($sNotificationUrl);
            if (substr($aHeaders[2], -4) === "True") {
                $this->_aViewData["submitMessage"] = 'SHOP_RDFA_SUBMITED_SUCCESSFULLY';
            } else {
                Registry::getUtilsView()->addErrorToDisplay(substr($aHeaders[3], strpos($aHeaders[3], ":") + 2));
            }
        } else {
            Registry::getUtilsView()->addErrorToDisplay('SHOP_RDFA_MESSAGE_NOURL');
        }
    }

    /**
     * Returns an array with the headers
     *
     * @param string $sURL target URL
     *
     * @deprecated since v6.0-rc.3 (2017-10-16); GR-Notify registration feature is removed.
     *
     * @return array
     */
    public function getHttpResponseCode($sURL)
    {
        return get_headers($sURL);
    }
}
