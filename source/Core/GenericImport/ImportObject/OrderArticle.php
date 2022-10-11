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

namespace OxidEsales\EshopCommunity\Core\GenericImport\ImportObject;

/**
 * Import object for Order Articles.
 */
class OrderArticle extends \OxidEsales\Eshop\Core\GenericImport\ImportObject\ImportObject
{
    /** @var string Database table name. */
    protected $tableName = 'oxorderarticles';

    /** @var string Shop object name. */
    protected $shopObjectName = 'oxorderarticle';

    /**
     * issued before saving an object. can modify aData for saving
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $shopObject        oxBase child for object
     * @param array                                  $data              Data for object
     * @param bool                                   $allowCustomShopId If true then AllowCustomShopId
     *
     * @return array
     */
    protected function preAssignObject($shopObject, $data, $allowCustomShopId)
    {
        $data = parent::preAssignObject($shopObject, $data, $allowCustomShopId);

        // check if data is not serialized
        $persParamValues = @unserialize($data['OXPERSPARAM']);
        if (!is_array($persParamValues)) {
            // data is a string with | separation, prepare for oxid
            $persParamValues = explode("|", $data['OXPERSPARAM']);
            $data['OXPERSPARAM'] = serialize($persParamValues);
        }
        if (array_key_exists('OXORDERSHOPID', $data)) {
            $data['OXORDERSHOPID'] = $this->getOrderShopId($data['OXORDERSHOPID']);
        }

        return $data;
    }

    /**
     * Returns formed order shop id, which should be set to data array.
     *
     * @param string $currentShopId
     *
     * @return string
     */
    protected function getOrderShopId($currentShopId)
    {
        return 1;
    }
}
