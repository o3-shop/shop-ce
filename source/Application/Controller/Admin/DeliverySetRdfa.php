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

use OxidEsales\Eshop\Application\Controller\Admin\PaymentRdfa;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article RDFa deliveryset manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Shipping & Handling -> RDFa.
 */
class DeliverySetRdfa extends PaymentRdfa
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = "deliveryset_rdfa.tpl";

    /**
     * Predefined delivery methods
     *
     * @var array
     */
    protected $_aRDFaDeliveries = [
        "DeliveryModeDirectDownload" => 0,
        "DeliveryModeFreight"        => 0,
        "DeliveryModeMail"           => 0,
        "DeliveryModeOwnFleet"       => 0,
        "DeliveryModePickUp"         => 0,
        "DHL"                        => 1,
        "FederalExpress"             => 1,
        "UPS"                        => 1
    ];

    /**
     * Saves changed mapping configurations
     */
    public function save()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        $aRDFaDeliveries = (array) Registry::getRequest()->getRequestEscapedParameter('ardfadeliveries');

        // Delete old mappings
        $oDb = DatabaseProvider::getDb();
        $sOxIdParameter = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSql = "DELETE FROM oxobject2delivery WHERE oxdeliveryid = :oxdeliveryid AND OXTYPE = 'rdfadeliveryset'";
        $oDb->execute($sSql, [
            ':oxdeliveryid' => $sOxIdParameter
        ]);

        // Save new mappings
        foreach ($aRDFaDeliveries as $sDelivery) {
            $oMapping = oxNew(BaseModel::class);
            $oMapping->init("oxobject2delivery");
            $oMapping->assign($aParams);
            $oMapping->oxobject2delivery__oxobjectid = new Field($sDelivery);
            $oMapping->save();
        }
    }

    /**
     * Returns an array including all available RDFa deliveries.
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getAllRDFaDeliveries()
    {
        $aRDFaDeliveries = [];
        $aAssignedRDFaDeliveries = $this->getAssignedRDFaDeliveries();
        foreach ($this->_aRDFaDeliveries as $sName => $iType) {
            $oDelivery = new stdClass();
            $oDelivery->name = $sName;
            $oDelivery->type = $iType;
            $oDelivery->checked = in_array($sName, $aAssignedRDFaDeliveries);
            $aRDFaDeliveries[] = $oDelivery;
        }

        return $aRDFaDeliveries;
    }

    /**
     * Returns array of RDFa deliveries which are assigned to current delivery
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getAssignedRDFaDeliveries()
    {
        $oDb = DatabaseProvider::getDb();
        $aRDFaDeliveries = [];
        $sSelect = 'select oxobjectid from oxobject2delivery where oxdeliveryid = :oxdeliveryid and oxtype = "rdfadeliveryset" ';
        $rs = $oDb->select($sSelect, [
            ':oxdeliveryid' => Registry::getRequest()->getRequestEscapedParameter('oxid')
        ]);
        if ($rs && $rs->count()) {
            while (!$rs->EOF) {
                $aRDFaDeliveries[] = $rs->fields[0];
                $rs->fetchRow();
            }
        }

        return $aRDFaDeliveries;
    }
}
