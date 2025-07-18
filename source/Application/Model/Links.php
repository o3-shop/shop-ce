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

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;

/**
 * Links manager.
 * Collects stored in DB links data (URL, description).
 */
class Links extends MultiLanguageModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxlinks';

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxlinks');
    }

    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (e.g. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFieldData" in next major
     */
    protected function _setFieldData($sFieldName, $sValue, $iDataType = Field::T_TEXT) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ('oxurldesc' === strtolower($sFieldName) || 'oxlinks__oxurldesc' === strtolower($sFieldName)) {
            $iDataType = Field::T_RAW;
        }

        return parent::_setFieldData($sFieldName, $sValue, $iDataType);
    }
}
