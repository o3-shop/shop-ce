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

namespace OxidEsales\EshopCommunity\Core\Exception;

/**
 * Exception class for a non existing language local
 *
 * @deprecated since 5.2.8 (2016.02.05); Will be removed as not used in code.
 */
class LanguageException extends \OxidEsales\Eshop\Core\Exception\StandardException
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxLanguageException';

    /**
     * Language constant
     *
     * @var string
     */
    private $_sLangConstant = "";

    /**
     * sets the language constant which is missing
     *
     * @param string $sLangConstant language constant
     */
    public function setLangConstant($sLangConstant)
    {
        $this->_sLangConstant = $sLangConstant;
    }

    /**
     * Get language constant
     *
     * @return string
     */
    public function getLangConstant()
    {
        return $this->_sLangConstant;
    }

    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function getString()
    {
        return __CLASS__ . '-' . parent::getString() . " Faulty Constant --> " . $this->_sLangConstant . "\n";
    }

    /**
     * Creates an array of field name => field value of the object
     * to make a easy conversion of exceptions to error messages possible
     * Overrides oxException::getValues()
     * should be extended when additional fields are used!
     *
     * @return array
     */
    public function getValues()
    {
        $aRes = parent::getValues();
        $aRes['langConstant'] = $this->getLangConstant();

        return $aRes;
    }
}
