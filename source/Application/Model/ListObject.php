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

/**
 * Simple list object
 */
class ListObject
{
    /**
     * @var string
     */
    private $_sTableName = '';

    /**
     * Class constructor
     *
     * @param string $sTableName Table name
     */
    public function __construct($sTableName)
    {
        $this->_sTableName = $sTableName;
    }

    /**
     * Assigns database record to object
     *
     * @param object $aData Database record
     *
     * @return null
     */
    public function assign($aData)
    {
        if (!is_array($aData)) {
            return;
        }
        foreach ($aData as $sKey => $sValue) {
            $sFieldName = strtolower($this->_sTableName . '__' . $sKey);
            $this->$sFieldName = new \OxidEsales\Eshop\Core\Field($sValue);
        }
    }

    /**
     * Returns object id
     *
     * @return int
     */
    public function getId()
    {
        $sFieldName = strtolower($this->_sTableName . '__oxid');
        return $this->$sFieldName->value;
    }
}
