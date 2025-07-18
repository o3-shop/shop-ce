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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Voucher serie manager.
 * Manages list of available Vouchers (fetches, deletes, etc.).
 *
 */
class VoucherSerie extends BaseModel
{
    /**
     * User groups array (default null).
     *
     * @var object
     */
    protected $_oGroups = null;

    /**
     * @var string name of current class
     */
    protected $_sClassName = 'oxvoucherserie';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxvoucherseries');
    }

    /**
     * Override delete function, so we can delete user group and article or category relations first.
     *
     * @param string $sOxId object ID (default null)
     *
     * @return null
     */
    public function delete($sOxId = null)
    {
        if (!$sOxId) {
            $sOxId = $this->getId();
        }

        $this->unsetDiscountRelations();
        $this->unsetUserGroups();
        $this->deleteVoucherList();

        return parent::delete($sOxId);
    }

    /**
     * Collects and returns user group list.
     *
     * @return object
     */
    public function setUserGroups()
    {
        if ($this->_oGroups === null) {
            $this->_oGroups = oxNew(ListModel::class);
            $this->_oGroups->init('oxgroups');
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName("oxgroups");
            $sSelect = "select gr.* from {$sViewName} as gr, oxobject2group as o2g where
                         o2g.oxobjectid = :oxobjectid and gr.oxid = o2g.oxgroupsid ";
            $this->_oGroups->selectString($sSelect, [
                ':oxobjectid' => $this->getId()
            ]);
        }

        return $this->_oGroups;
    }

    /**
     * Removes user groups relations.
     */
    public function unsetUserGroups()
    {
        $oDb = DatabaseProvider::getDb();
        $sDelete = 'delete from oxobject2group where oxobjectid = :oxobjectid';
        $oDb->execute($sDelete, [
            ':oxobjectid' => $this->getId()
        ]);
    }

    /**
     * Removes product or category relations.
     */
    public function unsetDiscountRelations()
    {
        $oDb = DatabaseProvider::getDb();
        $sDelete = 'delete from oxobject2discount where oxobject2discount.oxdiscountid = :oxdiscountid';
        $oDb->execute($sDelete, [
            ':oxdiscountid' => $this->getId()
        ]);
    }

    /**
     * Returns array of a vouchers assigned to this serie.
     *
     * @return array
     */
    public function getVoucherList()
    {
        $oVoucherList = oxNew(VoucherList::class);
        $sSelect = 'select * from oxvouchers 
            where oxvoucherserieid = :oxvoucherserieid';
        $oVoucherList->selectString($sSelect, [
            ':oxvoucherserieid' => $this->getId()
        ]);

        return $oVoucherList;
    }

    /**
     * Deletes assigned voucher list.
     */
    public function deleteVoucherList()
    {
        $oDb = DatabaseProvider::getDb();
        $sDelete = 'delete from oxvouchers where oxvoucherserieid = :oxvoucherserieid';
        $oDb->execute($sDelete, [
            ':oxvoucherserieid' => $this->getId()
        ]);
    }

    /**
     * Returns array of vouchers counts.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function countVouchers()
    {
        $aStatus = [];

        $oDb = DatabaseProvider::getDb();
        $sQuery = 'select count(*) as total from oxvouchers 
            where oxvoucherserieid = :oxvoucherserieid';
        $aStatus['total'] = $oDb->getOne($sQuery, [
            ':oxvoucherserieid' => $this->getId()
        ]);

        $sQuery = 'select count(*) as used from oxvouchers 
            where oxvoucherserieid = :oxvoucherserieid 
                and ((oxorderid is not NULL and oxorderid != "") or (oxdateused is not NULL and oxdateused != 0))';
        $aStatus['used'] = $oDb->getOne($sQuery, [
            ':oxvoucherserieid' => $this->getId()
        ]);

        $aStatus['available'] = $aStatus['total'] - $aStatus['used'];

        return $aStatus;
    }

    /**
     * Get voucher status base on given date (if nothing was passed, current datetime will be used as a measure).
     *
     * @param string|null $sNow Date
     *
     * @return int
     */
    public function getVoucherStatusByDatetime($sNow = null)
    {
        //return content
        $iActive = 1;
        $iInactive = 0;

        $oUtilsDate = Registry::getUtilsDate();
        //current object datetime
        $sBeginDate = $this->oxvoucherseries__oxbegindate->value;
        $sEndDate = $this->oxvoucherseries__oxenddate->value;

        //If nothing pass, use current server time
        if ($sNow == null) {
            $sNow = date('Y-m-d H:i:s', $oUtilsDate->getTime());
        }

        //Check for active status.
        if (
            ($sBeginDate == '0000-00-00 00:00:00' && $sEndDate == '0000-00-00 00:00:00') || //If both dates are empty => treat it as always active
            ($sBeginDate == '0000-00-00 00:00:00' && $sNow <= $sEndDate) || //check for end date without start date
            ($sBeginDate <= $sNow && $sEndDate == '0000-00-00 00:00:00') || //check for start date without end date
            ($sBeginDate <= $sNow && $sNow <= $sEndDate)
        ) { //check for both start date and end date.
            return $iActive;
        }

        //If active status code was reached, return as inactive
        return $iInactive;
    }
}
