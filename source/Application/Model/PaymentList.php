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
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Payment list manager.
 *
 */
class PaymentList extends ListModel
{
    /**
     * Home country id
     *
     * @var string
     */
    protected $_sHomeCountry = null;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        $this->setHomeCountry(Registry::getConfig()->getConfigParam('aHomeCountry'));
        parent::__construct('oxpayment');
    }

    /**
     * Home country setter
     *
     * @param string $sHomeCountry country id
     */
    public function setHomeCountry($sHomeCountry)
    {
        if (is_array($sHomeCountry)) {
            $this->_sHomeCountry = current($sHomeCountry);
        } else {
            $this->_sHomeCountry = $sHomeCountry;
        }
    }

    /**
     * Creates payment list filter SQL to load current state payment list
     *
     * @param string $sShipSetId user chosen delivery set
     * @param double $dPrice basket products price
     * @param User $oUser session user object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilterSelect" in next major
     */
    protected function _getFilterSelect($sShipSetId, $dPrice, $oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sBoni = ($oUser && $oUser->oxuser__oxboni->value) ? $oUser->oxuser__oxboni->value : 0;

        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxpayments');
        $sQ = "select {$sTable}.* from ( select distinct {$sTable}.* from {$sTable} ";
        $sQ .= "left join oxobject2group ON oxobject2group.oxobjectid = {$sTable}.oxid ";
        $sQ .= "inner join oxobject2payment ON oxobject2payment.oxobjectid = " . $oDb->quote($sShipSetId) . " and oxobject2payment.oxpaymentid = {$sTable}.oxid ";
        $sQ .= "where {$sTable}.oxactive='1' ";
        $sQ .= " and {$sTable}.oxfromboni <= " . $oDb->quote($sBoni) . " and {$sTable}.oxfromamount <= " . $oDb->quote($dPrice) . " and {$sTable}.oxtoamount >= " . $oDb->quote($dPrice);

        // defining initial filter parameters
        $sGroupIds = '';
        $sCountryId = $this->getCountryId($oUser);

        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($oUser) {
            // user groups ( maybe would be better to fetch by function User::getUserGroups() ? )
            foreach ($oUser->getUserGroups() as $oGroup) {
                if ($sGroupIds) {
                    $sGroupIds .= ', ';
                }
                $sGroupIds .= "'" . $oGroup->getId() . "'";
            }
        }

        $sGroupTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxgroups');
        $sCountryTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxcountry');

        $sCountrySql = $sCountryId ? "exists( select 1 from oxobject2payment as s1 where s1.oxpaymentid={$sTable}.OXID and s1.oxtype='oxcountry' and s1.OXOBJECTID=" . $oDb->quote($sCountryId) . " limit 1 )" : '0';
        $sGroupSql = $sGroupIds ? "exists( select 1 from oxobject2group as s3 where s3.OXOBJECTID={$sTable}.OXID and s3.OXGROUPSID in ( {$sGroupIds} ) limit 1 )" : '0';

        $sQ .= "  order by {$sTable}.oxsort asc ) as $sTable where (
                if( exists( select 1 from oxobject2payment as ss1, $sCountryTable where $sCountryTable.oxid=ss1.oxobjectid and ss1.oxpaymentid={$sTable}.OXID and ss1.oxtype='oxcountry' limit 1 ),
                    {$sCountrySql},
                    1) &&
                if( exists( select 1 from oxobject2group as ss3, $sGroupTable where $sGroupTable.oxid=ss3.oxgroupsid and ss3.OXOBJECTID={$sTable}.OXID limit 1 ),
                    {$sGroupSql},
                    1)
                )  order by {$sTable}.oxsort asc ";

        return $sQ;
    }

    /**
     * Returns user country id for payment selection
     *
     * @param User $oUser oxuser object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCountryId($oUser)
    {
        $sCountryId = null;
        if ($oUser) {
            $sCountryId = $oUser->getActiveCountry();
        }

        if (!$sCountryId) {
            $sCountryId = $this->_sHomeCountry;
        }

        return $sCountryId;
    }

    /**
     * Loads and returns list of user payments.
     *
     * @param string $sShipSetId user chosen delivery set
     * @param double $dPrice basket product price excl. discount
     * @param User|null $oUser session user object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getPaymentList($sShipSetId, $dPrice, $oUser = null)
    {
        $this->selectString($this->_getFilterSelect($sShipSetId, $dPrice, $oUser));

        return $this->_aArray;
    }

    /**
     * Loads an object including all payments which are not mapped to a
     * predefined GoodRelations payment method.
     */
    public function loadNonRDFaPaymentList()
    {
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxpayments');
        $sSubSql = "SELECT * FROM oxobject2payment WHERE oxobject2payment.OXPAYMENTID = $sTable.OXID AND oxobject2payment.OXTYPE = 'rdfapayment'";
        $this->selectString("SELECT $sTable.* FROM $sTable WHERE NOT EXISTS($sSubSql) AND $sTable.OXACTIVE = 1");
    }

    /**
     * Loads payments mapped to a
     * predefined GoodRelations payment method.
     *
     * @param null $dPrice product price
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadRDFaPaymentList($dPrice = null)
    {
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxpayments');
        $sQ = "select $sTable.*, oxobject2payment.oxobjectid from $sTable left join (select oxobject2payment.* from oxobject2payment where oxobject2payment.oxtype = 'rdfapayment') as oxobject2payment on oxobject2payment.oxpaymentid=$sTable.oxid ";
        $sQ .= "where $sTable.oxactive = 1 ";
        if ($dPrice !== null) {
            $sQ .= "and $sTable.oxfromamount <= :amount and $sTable.oxtoamount >= :amount";
        }
        $rs = $oDb->select($sQ, [
            ':amount' => $dPrice
        ]);
        if ($rs && $rs->count() > 0) {
            $oSaved = clone $this->getBaseObject();
            while (!$rs->EOF) {
                $oListObject = clone $oSaved;
                $this->_assignElement($oListObject, $rs->fields);
                $this->_aArray[] = $oListObject;
                $rs->fetchRow();
            }
        }
    }
}
