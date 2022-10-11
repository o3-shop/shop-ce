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

use oxDb;

/**
 * Group manager.
 * Base class for user groups. Does nothing special yet.
 *
 */
class Groups extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel
{
    /**
     * Name of current class
     *
     * @var string
     */
    protected $_sClassName = 'oxgroups';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxgroups');
    }

    /**
     * Deletes user group from database. Returns true/false, according to deleting status.
     *
     * @param string $sOXID Object ID (default null)
     *
     * @return bool
     */
    public function delete($sOXID = null)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }
        if (!$sOXID) {
            return false;
        }

        parent::delete($sOXID);

        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        // deleting related data records
        $sDelete = 'delete from oxobject2group where oxobject2group.oxgroupsid = :oxid';
        $oDb->execute($sDelete, [
            ':oxid' => $sOXID
        ]);

        $sDelete = 'delete from oxobject2delivery where oxobject2delivery.oxobjectid = :oxid';
        $oDb->execute($sDelete, [
            ':oxid' => $sOXID
        ]);

        $sDelete = 'delete from oxobject2discount where oxobject2discount.oxobjectid = :oxid';
        $oDb->execute($sDelete, [
            ':oxid' => $sOXID
        ]);

        $sDelete = 'delete from oxobject2payment where oxobject2payment.oxobjectid = :oxid';
        $rs = $oDb->execute($sDelete, [
            ':oxid' => $sOXID
        ]);

        return $rs->EOF;
    }
}
