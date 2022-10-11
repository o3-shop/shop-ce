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
 * exception for invalid or non existin external files, e.g.:
 * - file does not exist
 * - file is not valid xml
 */
class FileException extends \OxidEsales\Eshop\Core\Exception\StandardException
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxFileException';

    /**
     * File connected to this exception.
     *
     * @var string
     */
    protected $_sErrFileName;

    /**
     * Error occured with the file, if provided
     *
     * @var string
     */
    protected $_sFileError;

    /**
     *  Sets the file name of the file related to the exception
     *
     * @param string $sFileName file name
     */
    public function setFileName($sFileName)
    {
        $this->_sErrFileName = $sFileName;
    }

    /**
     * Gives file name related to the exception
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->_sErrFileName;
    }

    /**
     * sets the error returned by the file operation
     *
     * @param string $sFileError Error
     */
    public function setFileError($sFileError)
    {
        $this->_sFileError = $sFileError;
    }

    /**
     * return the file error
     *
     * @return string
     */
    public function getFileError()
    {
        return $this->_sFileError;
    }

    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function getString()
    {
        return __CLASS__ . '-' . parent::getString() . " Faulty File --> " . $this->_sErrFileName . "\n" . "Error Code --> " . $this->_sFileError;
    }

    /**
     * Override of oxException::getValues()
     *
     * @return array
     */
    public function getValues()
    {
        $aRes = parent::getValues();
        $aRes['fileName'] = $this->getFileName();

        return $aRes;
    }
}
