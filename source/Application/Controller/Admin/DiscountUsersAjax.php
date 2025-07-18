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

use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages discount users
 */
class DiscountUsersAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [ 
            // field, table, visible, multilanguage, ident
            ['oxusername', 'oxuser', 1, 0, 0],
            ['oxlname', 'oxuser', 0, 0, 0],
            ['oxfname', 'oxuser', 0, 0, 0],
            ['oxstreet', 'oxuser', 0, 0, 0],
            ['oxstreetnr', 'oxuser', 0, 0, 0],
            ['oxcity', 'oxuser', 0, 0, 0],
            ['oxzip', 'oxuser', 0, 0, 0],
            ['oxfon', 'oxuser', 0, 0, 0],
            ['oxbirthdate', 'oxuser', 0, 0, 0],
            ['oxid', 'oxuser', 0, 0, 1],
        ],
        'container2' => [
            ['oxusername', 'oxuser', 1, 0, 0],
            ['oxlname', 'oxuser', 0, 0, 0],
            ['oxfname', 'oxuser', 0, 0, 0],
            ['oxstreet', 'oxuser', 0, 0, 0],
            ['oxstreetnr', 'oxuser', 0, 0, 0],
            ['oxcity', 'oxuser', 0, 0, 0],
            ['oxzip', 'oxuser', 0, 0, 0],
            ['oxfon', 'oxuser', 0, 0, 0],
            ['oxbirthdate', 'oxuser', 0, 0, 0],
            ['oxid', 'oxobject2discount', 0, 0, 1],
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getQuery();
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getQuery()
    {
        $oConfig = Registry::getConfig();
        $oRequest = Registry::getRequest();

        $sUserTable = $this->getViewName('oxuser');
        $oDb = DatabaseProvider::getDb();
        $sId = $oRequest->getRequestEscapedParameter('oxid');
        $sSynchId = $oRequest->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from $sUserTable where 1 ";
            if (!$oConfig->getConfigParam('blMallUsers')) {
                $sQAdd .= " and oxshopid = '" . $oConfig->getShopId() . "' ";
            }
        } else {
            // selected group ?
            if ($sSynchId && $sSynchId != $sId) {
                $sQAdd = " from oxobject2group left join $sUserTable on $sUserTable.oxid = oxobject2group.oxobjectid where oxobject2group.oxgroupsid = " . $oDb->quote($sId);
                if (!$oConfig->getConfigParam('blMallUsers')) {
                    $sQAdd .= " and $sUserTable.oxshopid = '" . $oConfig->getShopId() . "' ";
                }
            } else {
                $sQAdd = " from oxobject2discount, $sUserTable where $sUserTable.oxid=oxobject2discount.oxobjectid ";
                $sQAdd .= " and oxobject2discount.oxdiscountid = " . $oDb->quote($sId) . " and oxobject2discount.oxtype = 'oxuser' ";
            }
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= " and $sUserTable.oxid not in ( select $sUserTable.oxid from oxobject2discount, $sUserTable where $sUserTable.oxid=oxobject2discount.oxobjectid ";
            $sQAdd .= " and oxobject2discount.oxdiscountid = " . $oDb->quote($sSynchId) . " and oxobject2discount.oxtype = 'oxuser' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes user from discount config
     */
    public function removeDiscUser()
    {
        $aRemoveGroups = $this->getActionIds('oxobject2discount.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter("delete oxobject2discount.* " . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sQ = "delete from oxobject2discount where oxobject2discount.oxid in (" . implode(", ", DatabaseProvider::getDb()->quoteArray($aRemoveGroups)) . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds user to discount config
     */
    public function addDiscUser()
    {
        $oRequest = Registry::getRequest();
        $aChosenUsr = $this->getActionIds('oxuser.oxid');
        $soxId = $oRequest->getRequestEscapedParameter('synchoxid');

        if ($oRequest->getRequestEscapedParameter('all')) {
            $sUserTable = $this->getViewName('oxuser');
            $aChosenUsr = $this->getAll($this->addFilter("select $sUserTable.oxid " . $this->getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aChosenUsr)) {
            foreach ($aChosenUsr as $sChosenUsr) {
                $oObject2Discount = oxNew(BaseModel::class);
                $oObject2Discount->init('oxobject2discount');
                $oObject2Discount->oxobject2discount__oxdiscountid = new Field($soxId);
                $oObject2Discount->oxobject2discount__oxobjectid = new Field($sChosenUsr);
                $oObject2Discount->oxobject2discount__oxtype = new Field("oxuser");
                $oObject2Discount->save();
            }
        }
    }
}
