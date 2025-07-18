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

use Exception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Exception\DatabaseException;

/**
 * Newsletter Subscriptions manager
 * Performs user managing function
 * information, deletion and other.
 *
 */
class NewsSubscribed extends BaseModel
{
    /**
     * Subscription marker
     *
     * @var bool
     */
    protected $_blWasSubscribed = false;

    /**
     * Subscription marker. Marks that newsletter was subscribed but wasn't confirmed.
     *
     * @var bool
     */
    protected $_blWasPreSubscribed = false;

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxnewssubscribed';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();

        $this->init('oxnewssubscribed');
    }

    /**
     * Loads object (Newssubscription) details from DB. Returns true on success.
     *
     * @param string $oxId oxnewssubscribed ID
     *
     * @return bool
     */
    public function load($oxId)
    {
        $blRet = parent::load($oxId);

        if ($this->getFieldData('oxnewssubscribed__oxdboptin') == 1) {
            $this->_blWasSubscribed = true;
        } elseif ($this->getFieldData('oxnewssubscribed__oxdboptin') == 2) {
            $this->_blWasPreSubscribed = true;
        }

        return $blRet;
    }

    /**
     * Loader which loads news subscription according to subscribers email address
     *
     * @param string $sEmailAddress subscribers email address
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function loadFromEmail($sEmailAddress)
    {
        $userOxid = $this->getSubscribedUserIdByEmail($sEmailAddress);
        return $this->load($userOxid);
    }

    /**
     * Get subscribed user id by email.
     *
     * @param string $email
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getSubscribedUserIdByEmail($email)
    {
        $database = DatabaseProvider::getDb();
        $params = [
            ':oxemail' => (string) $email
        ];

        $userOxid = $database->getOne("select oxid from oxnewssubscribed 
            where oxemail = :oxemail ", $params);

        return $userOxid;
    }

    /**
     * Loader which loads news subscription according to subscribers oxid
     *
     * @param string $sOxUserId subscribers oxid
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function loadFromUserId($sOxUserId)
    {
        $oDb = DatabaseProvider::getDb();
        $params = [
            ':oxuserid' => $sOxUserId,
            ':oxshopid' => Registry::getConfig()->getShopId()
        ];

        $sOxId = $oDb->getOne("select oxid from oxnewssubscribed 
            where oxuserid = :oxuserid and oxshopid = :oxshopid", $params);

        return $this->load($sOxId);
    }

    /**
     * Inserts news object data to DB. Returns true on success.
     *
     * @return bool oxid on success or false on failure
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // set subscription date
        $this->oxnewssubscribed__oxsubscribed = new Field(date('Y-m-d H:i:s'), Field::T_RAW);

        return parent::_insert();
    }

    /**
     * We need to check if we unsubscribe here
     *
     * @return bool oxid on success or false on failure
     * @throws DatabaseException
     * @deprecated underscore prefix violates PSR12, will be renamed to "update" in next major
     */
    protected function _update() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($this->_blWasSubscribed || $this->_blWasPreSubscribed) && !$this->oxnewssubscribed__oxdboptin->value) {
            // set unsubscription date
            $this->oxnewssubscribed__oxunsubscribed->setValue(date('Y-m-d H:i:s'));
            // 0001974 Same object can be called many times without requiring to renew date.
            // If so happens, it would have _aSkipSaveFields set to skip date field. So need to check and
            // release if _aSkipSaveFields are set for field oxunsubscribed.
            $aSkipSaveFieldsKeys = array_keys($this->_aSkipSaveFields, 'oxunsubscribed');
            foreach ($aSkipSaveFieldsKeys as $iSkipSaveFieldKey) {
                unset($this->_aSkipSaveFields[$iSkipSaveFieldKey]);
            }
        } else {
            // don't update date
            $this->_aSkipSaveFields[] = 'oxunsubscribed';
        }

        return parent::_update();
    }

    /**
     * Newsletter subscription status getter
     *
     * @return int
     */
    public function getOptInStatus()
    {
        return $this->oxnewssubscribed__oxdboptin->value;
    }

    /**
     * Newsletter subscription status setter
     *
     * @param int $iStatus subscription status
     * @throws Exception
     */
    public function setOptInStatus($iStatus)
    {
        $this->oxnewssubscribed__oxdboptin = new Field($iStatus, Field::T_RAW);
        $this->save();
    }

    /**
     * Newsletter subscription email sending status getter
     *
     * @return int
     */
    public function getOptInEmailStatus()
    {
        return $this->oxnewssubscribed__oxemailfailed->value;
    }

    /**
     * Newsletter subscription email sending status setter
     *
     * @param int $iStatus subscription status
     * @throws Exception
     */
    public function setOptInEmailStatus($iStatus)
    {
        $this->oxnewssubscribed__oxemailfailed = new Field($iStatus, Field::T_RAW);
        $this->save();
    }

    /**
     * Check if was ever unsubscribed by unsubscribed field.
     *
     * @return bool
     */
    public function wasUnsubscribed()
    {
        if ('0000-00-00 00:00:00' != $this->oxnewssubscribed__oxunsubscribed->value) {
            return true;
        }

        return false;
    }

    /**
     * This method is called from User::update. Currently, it updates user
     * information kept in db
     *
     * @param User $oUser subscription user object
     *
     * @return bool
     * @throws Exception
     */
    public function updateSubscription($oUser)
    {
        // user email changed ?
        if ($oUser->oxuser__oxusername->value && $this->oxnewssubscribed__oxemail->value != $oUser->oxuser__oxusername->value) {
            $this->oxnewssubscribed__oxemail = new Field($oUser->oxuser__oxusername->value, Field::T_RAW);
        }

        // updating some other fields
        $this->oxnewssubscribed__oxsal = new Field($oUser->oxuser__oxsal->value, Field::T_RAW);
        $this->oxnewssubscribed__oxfname = new Field($oUser->oxuser__oxfname->value, Field::T_RAW);
        $this->oxnewssubscribed__oxlname = new Field($oUser->oxuser__oxlname->value, Field::T_RAW);

        return (bool) $this->save();
    }
}
