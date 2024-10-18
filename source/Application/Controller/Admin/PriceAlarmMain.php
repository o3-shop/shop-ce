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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main pricealarm manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Customer Info -> pricealarm -> Main.
 */
class PriceAlarmMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxpricealarm object
     * and passes its data to Smarty engine. Returns name of template file
     * "pricealarm_main.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function render()
    {
        $config = Registry::getConfig();

        $this->_aViewData['iAllCnt'] = $this->getActivePriceAlarmsCount();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oPricealarm = oxNew(PriceAlarm::class);
            $oPricealarm->load($soxId);

            // customer info
            if ($oPricealarm->oxpricealarm__oxuserid->value) {
                $oUser = oxNew(User::class);
                $oUser->load($oPricealarm->oxpricealarm__oxuserid->value);
                $oPricealarm->oUser = $oUser;
            }

            //
            $oShop = oxNew(Shop::class);
            $oShop->load($config->getShopId());
            $this->addGlobalParams($oShop);

            if (!($iLang = $oPricealarm->oxpricealarm__oxlang->value)) {
                $iLang = 0;
            }

            $oLang = Registry::getLang();
            $aLanguages = $oLang->getLanguageNames();
            $this->_aViewData["edit_lang"] = $aLanguages[$iLang];
            // rendering mail message text
            $oLetter = new stdClass();
            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
            if (isset($aParams['oxpricealarm__oxlongdesc']) && $aParams['oxpricealarm__oxlongdesc']) {
                $oLetter->oxpricealarm__oxlongdesc = new Field(stripslashes($aParams['oxpricealarm__oxlongdesc']), Field::T_RAW);
            } else {
                $oEmail = oxNew(Email::class);
                $sDesc = $oEmail->sendPricealarmToCustomer($oPricealarm->oxpricealarm__oxemail->value, $oPricealarm, null, true);

                $iOldLang = $oLang->getTplLanguage();
                $oLang->setTplLanguage($iLang);
                $oLetter->oxpricealarm__oxlongdesc = new Field($sDesc, Field::T_RAW);
                $oLang->setTplLanguage($iOldLang);
            }

            $this->_aViewData["editor"] = $this->_generateTextEditor("100%", 300, $oLetter, "oxpricealarm__oxlongdesc", "details.tpl.css");
            $this->_aViewData["edit"] = $oPricealarm;
            $this->_aViewData["actshop"] = $config->getShopId();
        }

        parent::render();

        return "pricealarm_main.tpl";
    }

    /**
     * Sending email to selected customer
     */
    public function send()
    {
        $blError = true;

        // error
        if (($sOxid = $this->getEditObjectId())) {
            $oPricealarm = oxNew(PriceAlarm::class);
            $oPricealarm->load($sOxid);

            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
            $sMailBody = isset($aParams['oxpricealarm__oxlongdesc']) ? stripslashes($aParams['oxpricealarm__oxlongdesc']) : '';
            if ($sMailBody) {
                $sMailBody = Registry::getUtilsView()->parseThroughSmarty($sMailBody, $oPricealarm->getId());
            }

            $sRecipient = $oPricealarm->oxpricealarm__oxemail->value;

            $oEmail = oxNew(Email::class);
            $blSuccess = (int) $oEmail->sendPricealarmToCustomer($sRecipient, $oPricealarm, $sMailBody);

            // setting result message
            if ($blSuccess) {
                $oPricealarm->oxpricealarm__oxsended->setValue(date("Y-m-d H:i:s"));
                $oPricealarm->save();
                $blError = false;
            }
        }

        if (!$blError) {
            $this->_aViewData["mail_succ"] = 1;
        } else {
            $this->_aViewData["mail_err"] = 1;
        }
    }

    /**
     * Returns number of active price alarms.
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException|ObjectException
     */
    protected function getActivePriceAlarmsCount()
    {
        // #1140 R - price must be checked from the object.
        $query = "
            SELECT oxarticles.oxid, oxpricealarm.oxprice
            FROM oxpricealarm, oxarticles
            WHERE oxarticles.oxid = oxpricealarm.oxartid AND oxpricealarm.oxsended = '000-00-00 00:00:00'";
        $result = DatabaseProvider::getDb()->select($query);
        $count = 0;

        if ($result && $result->count() > 0) {
            while (!$result->EOF) {
                $article = oxNew(Article::class);
                $article->load($result->fields[0]);
                if ($article->getPrice()->getBruttoPrice() <= $result->fields[1]) {
                    $count++;
                }
                $result->fetchRow();
            }
        }

        return $count;
    }
}
