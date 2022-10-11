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
 * Manages product assignment to category.
 */
class Object2Category extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxobject2category';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()) and sets table name.
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxobject2category');
    }

    /**
     * Returns assigned product id
     *
     * @return string
     */
    public function getProductId()
    {
        return $this->oxobject2category__oxobjectid->value;
    }

    /**
     * Sets assigned product id
     *
     * @param string $sId assigned product id
     */
    public function setProductId($sId)
    {
        $this->oxobject2category__oxobjectid = new \OxidEsales\Eshop\Core\Field($sId);
    }

    /**
     * Returns assigned category id
     *
     * @return string
     */
    public function getCategoryId()
    {
        return $this->oxobject2category__oxcatnid->value;
    }

    /**
     * Sets assigned category id
     *
     * @param string $sId assigned category id
     */
    public function setCategoryId($sId)
    {
        $this->oxobject2category__oxcatnid = new \OxidEsales\Eshop\Core\Field($sId);
    }
}
