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

/**
 * Article file link manager.
 *
 */
class OrderFileList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxorderfile';

    /**
     * Returns orders
     *
     * @param string $sUserId - user id
     */
    public function loadUserFiles($sUserId)
    {
        $oOrderFile = $this->getBaseObject();
        $sFields = $oOrderFile->getSelectFields();

        $oOrderFile->addFieldName('oxorderfiles__oxarticletitle');
        $oOrderFile->addFieldName('oxorderfiles__oxarticleartnum');
        $oOrderFile->addFieldName('oxorderfiles__oxordernr');
        $oOrderFile->addFieldName('oxorderfiles__oxorderdate');

        $sSql = "SELECT " . $sFields . " ,
                      `oxorderarticles`.`oxtitle` AS `oxorderfiles__oxarticletitle`,
                      `oxorderarticles`.`oxartnum` AS `oxorderfiles__oxarticleartnum`,
                      `oxfiles`.`oxpurchasedonly` AS `oxorderfiles__oxpurchasedonly`,
                      `oxorder`.`oxordernr` AS `oxorderfiles__oxordernr`,
                      `oxorder`.`oxorderdate` AS `oxorderfiles__oxorderdate`,
                      IF( `oxorder`.`oxpaid` != '0000-00-00 00:00:00', 1, 0 ) AS `oxorderfiles__oxispaid`
                    FROM `oxorderfiles`
                        LEFT JOIN `oxorderarticles` ON `oxorderarticles`.`oxid` = `oxorderfiles`.`oxorderarticleid`
                        LEFT JOIN `oxfiles` ON `oxfiles`.`oxid` = `oxorderfiles`.`oxfileid`
                        LEFT JOIN `oxorder` ON `oxorder`.`oxid` = `oxorderfiles`.`oxorderid`
                    WHERE `oxorder`.`oxuserid` = :oxuserid
                        AND `oxorderfiles`.`oxshopid` = :oxshopid
                        AND `oxorder`.`oxstorno` = 0
                        AND `oxorderarticles`.`oxstorno` = 0
                    ORDER BY `oxorder`.`oxordernr`";

        $this->selectString($sSql, [
            ':oxuserid' => $sUserId,
            ':oxshopid' => $this->getConfig()->getShopId()
        ]);
    }

    /**
     * Returns oxorderfiles list
     *
     * @param string $sOrderId - order id
     */
    public function loadOrderFiles($sOrderId)
    {
        $oOrderFile = $this->getBaseObject();
        $sFields = $oOrderFile->getSelectFields();

        $oOrderFile->addFieldName('oxorderfiles__oxarticletitle');
        $oOrderFile->addFieldName('oxorderfiles__oxarticleartnum');

        $sSql = "SELECT " . $sFields . " ,
                      `oxorderarticles`.`oxtitle` AS `oxorderfiles__oxarticletitle`,
                      `oxorderarticles`.`oxartnum` AS `oxorderfiles__oxarticleartnum`,
                      `oxfiles`.`oxpurchasedonly` AS `oxorderfiles__oxpurchasedonly`
                    FROM `oxorderfiles`
                        LEFT JOIN `oxorderarticles` ON `oxorderarticles`.`oxid` = `oxorderfiles`.`oxorderarticleid`
                        LEFT JOIN `oxfiles` ON `oxfiles`.`oxid` = `oxorderfiles`.`oxfileid`
                    WHERE `oxorderfiles`.`oxorderid` = :oxorderid AND `oxorderfiles`.`oxshopid` = :oxshopid
                        AND `oxorderarticles`.`oxstorno` = 0";

        $this->selectString($sSql, [
            ':oxorderid' => $sOrderId,
            ':oxshopid' => $this->getConfig()->getShopId()
        ]);
    }
}
