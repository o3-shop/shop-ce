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

namespace OxidEsales\EshopCommunity\Core;

/**
 * SEPA (Single Euro Payments Area) validation class
 *
 */
class SepaIBANValidator
{
    const IBAN_ALGORITHM_MOD_VALUE = 97;

    protected $_aCodeLengths = [];

    /**
     * International bank account number validation
     *
     * An IBAN is validated by converting it into an integer and performing a basic mod-97 operation (as described in ISO 7064) on it.
     * If the IBAN is valid, the remainder equals 1.
     *
     * @param string $sIBAN code to check
     *
     * @return bool
     */
    public function isValid($sIBAN)
    {
        $blValid = false;
        $sIBAN = strtoupper(trim($sIBAN));

        if ($this->_isLengthValid($sIBAN)) {
            $blValid = $this->_isAlgorithmValid($sIBAN);
        }

        return $blValid;
    }

    /**
     * Validation of IBAN registry
     *
     * @param array $aCodeLengths
     *
     * @return bool
     */
    public function isValidCodeLengths($aCodeLengths)
    {
        $blValid = false;
        if ($this->_isNotEmptyArray($aCodeLengths)) {
            $blValid = $this->_isEachCodeLengthValid($aCodeLengths);
        }

        return $blValid;
    }

    /**
     * Set IBAN Registry
     *
     * @param array $aCodeLengths
     *
     * @return bool
     */
    public function setCodeLengths($aCodeLengths)
    {
        if ($this->isValidCodeLengths($aCodeLengths)) {
            $this->_aCodeLengths = $aCodeLengths;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Get IBAN length by country data
     *
     * @return array
     */
    public function getCodeLengths()
    {
        return $this->_aCodeLengths;
    }


    /**
     * Check if the total IBAN length is correct as per country. If not, the IBAN is invalid.
     *
     * @param string $sIBAN IBAN
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isLengthValid" in next major
     */
    protected function _isLengthValid($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iActualLength = getStr()->strlen($sIBAN);

        $iCorrectLength = $this->_getLengthForCountry($sIBAN);

        return !is_null($iCorrectLength) && $iActualLength === $iCorrectLength;
    }


    /**
     * Gets length for country.
     *
     * @param string $sIBAN IBAN
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLengthForCountry" in next major
     */
    protected function _getLengthForCountry($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aIBANRegistry = $this->getCodeLengths();

        $sCountryCode = getStr()->substr($sIBAN, 0, 2);

        $iCorrectLength = (isset($aIBANRegistry[$sCountryCode])) ? $aIBANRegistry[$sCountryCode] : null;

        return $iCorrectLength;
    }

    /**
     * Checks if IBAN is valid according to checksum algorithm
     *
     * @param string $sIBAN IBAN
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isAlgorithmValid" in next major
     */
    protected function _isAlgorithmValid($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sIBAN = $this->_moveInitialCharactersToEnd($sIBAN);

        $sIBAN = $this->_replaceLettersToNumbers($sIBAN);

        return $this->_isIBANChecksumValid($sIBAN);
    }

    /**
     * Move the four initial characters to the end of the string.
     *
     * @param string $sIBAN IBAN
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "moveInitialCharactersToEnd" in next major
     */
    protected function _moveInitialCharactersToEnd($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oStr = getStr();

        $sInitialChars = $oStr->substr($sIBAN, 0, 4);
        $sIBAN = $oStr->substr($sIBAN, 4);

        return $sIBAN . $sInitialChars;
    }

    /**
     * Replace each letter in the string with two digits, thereby expanding the string, where A = 10, B = 11, ..., Z = 35.
     *
     * @param string $sIBAN IBAN
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "replaceLettersToNumbers" in next major
     */
    protected function _replaceLettersToNumbers($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aReplaceArray = [
            'A' => 10,
            'B' => 11,
            'C' => 12,
            'D' => 13,
            'E' => 14,
            'F' => 15,
            'G' => 16,
            'H' => 17,
            'I' => 18,
            'J' => 19,
            'K' => 20,
            'L' => 21,
            'M' => 22,
            'N' => 23,
            'O' => 24,
            'P' => 25,
            'Q' => 26,
            'R' => 27,
            'S' => 28,
            'T' => 29,
            'U' => 30,
            'V' => 31,
            'W' => 32,
            'X' => 33,
            'Y' => 34,
            'Z' => 35
        ];

        return str_replace(
            array_keys($aReplaceArray),
            $aReplaceArray,
            $sIBAN
        );
    }

    /**
     * Interpret the string as a decimal integer and compute the remainder of that number on division by 97.
     *
     * @param string $sIBAN IBAN
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isIBANChecksumValid" in next major
     */
    protected function _isIBANChecksumValid($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return (int) bcmod($sIBAN, self::IBAN_ALGORITHM_MOD_VALUE) === 1;
    }

    /**
     * Checks if Code length is non empty array
     *
     * @param array $aCodeLengths Code lengths
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isNotEmptyArray" in next major
     */
    protected function _isNotEmptyArray($aCodeLengths) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return is_array($aCodeLengths) && !empty($aCodeLengths);
    }

    /**
     * Checks if each code length is valid.
     *
     * @param array $aCodeLengths Code lengths
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isEachCodeLengthValid" in next major
     */
    protected function _isEachCodeLengthValid($aCodeLengths) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blValid = true;

        foreach ($aCodeLengths as $sCountryAbbr => $iLength) {
            if (
                !$this->_isCodeLengthKeyValid($sCountryAbbr) ||
                !$this->_isCodeLengthValueValid($iLength)
            ) {
                $blValid = false;
                break;
            }
        }

        return $blValid;
    }

    /**
     * Checks if country code is valid
     *
     * @param string $sCountryAbbr Country abbreviation
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isCodeLengthKeyValid" in next major
     */
    protected function _isCodeLengthKeyValid($sCountryAbbr) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return (int) preg_match("/^[A-Z]{2}$/", $sCountryAbbr) !== 0;
    }

    /**
     * Checks if value is numeric and does not contain whitespaces
     *
     * @param integer $iLength Length
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isCodeLengthValueValid" in next major
     */
    protected function _isCodeLengthValueValid($iLength) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return is_numeric($iLength) && (int) preg_match("/\./", $iLength) !== 1;
    }
}
