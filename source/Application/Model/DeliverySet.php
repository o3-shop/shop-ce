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
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Order delivery set manager.
 *
 */
class DeliverySet extends MultiLanguageModel
{
    /**
     * Current object class name
     *
     * @var string
     */
    protected $_sClassName = 'oxdeliveryset';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxdeliveryset');
    }

    /**
     * Delete this object from the database, returns true on success.
     *
     * @param null $sOxId Object ID(default null)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($sOxId = null)
    {
        if (!$sOxId) {
            $sOxId = $this->getId();
        }
        if (!$sOxId) {
            return false;
        }

        $oDb = DatabaseProvider::getDb();

        $oDb->execute('delete from oxobject2payment where oxobjectid = :oxid', [
            ':oxid' => $sOxId,
        ]);
        $oDb->execute('delete from oxobject2delivery where oxdeliveryid = :oxid', [
            ':oxid' => $sOxId,
        ]);
        $oDb->execute('delete from oxdel2delset where oxdelsetid = :oxid', [
            ':oxid' => $sOxId,
        ]);

        return parent::delete($sOxId);
    }

    /**
     * returns delivery set id
     *
     * @param string $sTitle delivery name
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getIdByName($sTitle)
    {
        $oDb = DatabaseProvider::getDb();
        $sQ = 'SELECT `oxid` FROM `' . Registry::get(TableViewNameGenerator::class)->getViewName('oxdeliveryset') . '` 
            WHERE  `oxtitle` = :oxtitle';
        $sId = $oDb->getOne($sQ, [
            ':oxtitle' => $sTitle,
        ]);

        return $sId;
    }
}
