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

use oxField;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

class Object2Role extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /** @var boolean Load the relation even if from other shop */
    protected $_blDisableShopCheck = true;

    /** @var string Current class name */
    protected $_sClassName = 'o3object2role';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('o3object2role');
        $this->o3object2role__oxshopid = new Field(Registry::getConfig()->getShopId(), Field::T_RAW);
    }

    /**
     * Extends the default save method
     * to prevent from exception if same relationship already exist.
     * The table oxobject2group has an UNIQUE index on (OXGROUPSID, OXOBJECTID, OXSHOPID)
     * which ensures that a relationship would not be duplicated.
     *
     * @throws DatabaseErrorException
     *
     * @return bool
     */
    public function save()
    {
        try {
            return parent::save();
        } catch (\OxidEsales\Eshop\Core\Exception\DatabaseErrorException $exception) {
            if ($exception->getCode() !== \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database::DUPLICATE_KEY_ERROR_CODE) {
                throw $exception;
            }
        }
    }
}
