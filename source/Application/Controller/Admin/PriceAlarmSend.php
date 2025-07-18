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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * pricealarm sending manager.
 * Performs sending of pricealarm to selected iAllCnt groups.
 */
class PriceAlarmSend extends AdminListController
{
    /**
     * Default tab number
     *
     * @var int
     */
    protected $_iDefEdit = 1;

    /**
     * Executes parent method parent::render(), creates oxpricealarm object,
     * sends pricealarm to iAllCnts of chosen groups and returns name of template
     * file "pricealarm_send.tpl"/"pricealarm_done.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function render()
    {
        parent::render();

        $oRequest = Registry::getRequest();

        ini_set("session.gc_maxlifetime", 36000);

        $start = (int)$oRequest->getRequestEscapedParameter('iStart');
        $limit = Registry::getConfig()->getConfigParam('iCntofMails');
        $activeAlertsAmount = $oRequest->getRequestEscapedParameter('iAllCnt');
        if (!isset($activeAlertsAmount)) {
            $activeAlertsAmount = $this->countActivePriceAlerts();
        }

        $this->sendPriceChangeNotifications($start, $limit);

        // Advance mail pointer and set parameter
        $start += $limit;

        $this->_aViewData["iStart"] = $start;
        $this->_aViewData["iAllCnt"] = $activeAlertsAmount;
        $this->_aViewData["actlang"] = Registry::getLang()->getBaseLanguage();

        if ($start < $activeAlertsAmount) {
            $template = "pricealarm_send.tpl";
        } else {
            $template = "pricealarm_done.tpl";
        }

        return $template;
    }

    /**
     * Overrides parent method to pass referred id.
     *
     * @param string $node Class name
     * @deprecated underscore prefix violates PSR12, will be renamed to "setupNavigation" in next major
     */
    protected function _setupNavigation($node) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        parent::_setupNavigation('pricealarm_list');
    }

    /**
     * Counts active price alerts and returns this number.
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    protected function countActivePriceAlerts()
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $config = Registry::getConfig();
        $shopId = $config->getShopId();

        $activeAlarmsQuery =
            "SELECT oxprice, oxartid FROM oxpricealarm
                    WHERE oxsended = '000-00-00 00:00:00' AND oxshopid = :oxshopid";
        $result = $database->select($activeAlarmsQuery, [
            ':oxshopid' => $shopId
        ]);
        $count = 0;
        while ($result != false && !$result->EOF) {
            $alarmPrice = $result->fields['oxprice'];
            $article = oxNew(Article::class);
            $article->load($result->fields['oxartid']);
            if ($article->getPrice()->getBruttoPrice() <= $alarmPrice) {
                $count++;
            }
            $result->fetchRow();
        }

        return $count;
    }

    /**
     * Sends price alert notifications about changed article prices.
     *
     * @param int $start How much price alerts was already sent.
     * @param int $limit How much price alerts to send.
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    protected function sendPriceChangeNotifications($start, $limit)
    {
        $config = Registry::getConfig();
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $shopId = $config->getShopId();

        $alarmsQuery =
            "SELECT oxid, oxemail, oxartid, oxprice FROM oxpricealarm
            WHERE oxsended = '000-00-00 00:00:00' AND oxshopid = :oxshopid";
        $result = $database->selectLimit($alarmsQuery, $limit, $start, [
            ':oxshopid' => $shopId
        ]);
        while ($result != false && !$result->EOF) {
            $article = oxNew(Article::class);
            $article->load($result->fields['oxartid']);
            if ($article->getPrice()->getBruttoPrice() <= $result->fields['oxprice']) {
                $this->sendeMail(
                    $result->fields['oxemail'],
                    $result->fields['oxartid'],
                    $result->fields['oxid'],
                    $result->fields['oxprice']
                );
            }
            $result->fetchRow();
        }
    }

    /**
     * Creates and sends email with price alarm information.
     *
     * @param string $emailAddress Email address
     * @param string $productID Product id
     * @param string $priceAlarmId Price alarm id
     * @param string $bidPrice Bid price
     * @throws Exception
     */
    public function sendeMail($emailAddress, $productID, $priceAlarmId, $bidPrice)
    {
        $alarm = oxNew(PriceAlarm::class);
        $alarm->load($priceAlarmId);

        $language = Registry::getLang();
        $languageId = (int) $alarm->oxpricealarm__oxlang->value;

        $oldLanguageId = $language->getTplLanguage();
        $language->setTplLanguage($languageId);

        $email = oxNew(Email::class);
        $success = (int) $email->sendPricealarmToCustomer($emailAddress, $alarm);

        $language->setTplLanguage($oldLanguageId);

        if ($success) {
            $alarm->oxpricealarm__oxsended = new Field(date("Y-m-d H:i:s"));
            $alarm->save();
        }
    }
}
