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

use DateTime;

/**
 * Date manipulation utility class
 */
class UtilsDate extends \OxidEsales\Eshop\Core\Base
{
    /**
     * Format date to user defined format.
     *
     * @param string $sDBDateIn         Date to reformat
     * @param bool   $blForceEnglishRet Force to return primary value(default false)
     *
     * @return string
     */
    public function formatDBDate($sDBDateIn, $blForceEnglishRet = false)
    {
        // convert english format to output format
        if (!$sDBDateIn) {
            return null;
        }

        $oStr = getStr();
        if ($blForceEnglishRet && $oStr->strstr($sDBDateIn, '-')) {
            return $sDBDateIn;
        }

        if ($this->isEmptyDate($sDBDateIn) && $sDBDateIn != '-') {
            return '-';
        } elseif ($sDBDateIn == '-') {
            return '0000-00-00 00:00:00';
        }

        // is it a timestamp ?
        if (is_numeric($sDBDateIn)) {
            // db timestamp : 20030322100409
            $sNew = substr($sDBDateIn, 0, 4) . '-' . substr($sDBDateIn, 4, 2) . '-' . substr($sDBDateIn, 6, 2) . ' ';
            // check if it is a timestamp or wrong data: 20030322
            if (strlen($sDBDateIn) > 8) {
                $sNew .= substr($sDBDateIn, 8, 2) . ':' . substr($sDBDateIn, 10, 2) . ':' . substr($sDBDateIn, 12, 2);
            }
            // convert it to english format
            $sDBDateIn = $sNew;
        }

        // remove time as it is same in english as in german
        $aData = explode(' ', trim($sDBDateIn));

        // preparing time array
        $sTime = (isset($aData[1]) && $oStr->strstr($aData[1], ':')) ? $aData[1] : '';
        $aTime = $sTime ? explode(':', $sTime) : [0, 0, 0];

        // preparing date array
        $sDate = isset($aData[0]) ? $aData[0] : '';
        $aDate = preg_split('/[\/.-]/', $sDate);

        // choosing format..
        if ($sTime) {
            $sFormat = $blForceEnglishRet ? 'Y-m-d H:i:s' : \OxidEsales\Eshop\Core\Registry::getLang()->translateString('fullDateFormat');
        } else {
            $sFormat = $blForceEnglishRet ? 'Y-m-d' : \OxidEsales\Eshop\Core\Registry::getLang()->translateString('simpleDateFormat');
        }

        if (count($aDate) != 3) {
            return date($sFormat);
        } else {
            return $this->_processDate($aTime, $aDate, $oStr->strstr($sDate, '.'), $sFormat);
        }
    }

    /**
     * Bidirectional converter for date/datetime field
     *
     * @param object $oObject       data field object
     * @param bool   $blToTimeStamp set TRUE to format MySQL compatible value
     * @param bool   $blOnlyDate    set TRUE to format "date" type field
     *
     * @return string
     */
    public function convertDBDateTime($oObject, $blToTimeStamp = false, $blOnlyDate = false)
    {
        $sDate = $oObject->value;

        // defining time format
        $sLocalDateFormat = $this->_defineAndCheckDefaultDateValues($blToTimeStamp);
        $sLocalTimeFormat = $this->_defineAndCheckDefaultTimeValues($blToTimeStamp);

        // default date/time patterns
        $aDefDatePatterns = $this->_defaultDatePattern();

        // regexps to validate input
        $aDatePatterns = $this->_regexp2ValidateDateInput();
        $aTimePatterns = $this->_regexp2ValidateTimeInput();

        // date/time formatting rules
        $aDFormats = $this->_defineDateFormattingRules();
        $aTFormats = $this->_defineTimeFormattingRules();

        // empty date field value ? setting default value
        if (!$sDate) {
            $this->_setDefaultDateTimeValue($oObject, $sLocalDateFormat, $sLocalTimeFormat, $blOnlyDate);

            return $oObject->value;
        }

        $blDefDateFound = false;
        $oStr = getStr();

        // looking for default values that are formatted by MySQL
        foreach (array_keys($aDefDatePatterns) as $sDefDatePattern) {
            if ($oStr->preg_match($sDefDatePattern, $sDate)) {
                $blDefDateFound = true;
                break;
            }
        }

        // default value is set ?
        if ($blDefDateFound) {
            $this->_setDefaultFormatedValue($oObject, $sDate, $sLocalDateFormat, $sLocalTimeFormat, $blOnlyDate);

            return $oObject->value;
        }

        $blDateFound = false;
        $blTimeFound = false;
        $aDateMatches = [];
        $aTimeMatches = [];

        // looking for date field
        foreach ($aDatePatterns as $sPattern => $sType) {
            if ($oStr->preg_match($sPattern, $sDate, $aDateMatches)) {
                $blDateFound = true;

                // now we know the type of passed date
                $sDateFormat = $aDFormats[$sLocalDateFormat][0];
                $aDFields = $aDFormats[$sType][1];
                break;
            }
        }

        // no such date field available ?
        if (!$blDateFound) {
            return $sDate;
        }

        if ($blOnlyDate) {
            $this->_setDate($oObject, $sDateFormat, $aDFields, $aDateMatches);

            return $oObject->value;
        }

        // looking for time field
        foreach ($aTimePatterns as $sPattern => $sType) {
            if ($oStr->preg_match($sPattern, $sDate, $aTimeMatches)) {
                $blTimeFound = true;

                // now we know the type of passed time
                $sTimeFormat = $aTFormats[$sLocalTimeFormat][0];
                $aTFields = $aTFormats[$sType][1];

                //
                if ($sType == "USA" && isset($aTimeMatches[4])) {
                    $iIntVal = (int) $aTimeMatches[1];
                    if ($aTimeMatches[4] == "PM") {
                        if ($iIntVal < 13) {
                            $iIntVal += 12;
                        }
                    } elseif ($aTimeMatches[4] == "AM" && $aTimeMatches[1] == "12") {
                        $iIntVal = 0;
                    }

                    $aTimeMatches[1] = sprintf("%02d", $iIntVal);
                }

                break;
            }
        }

        if (!$blTimeFound) {
            //return $sDate;
            // #871A. trying to keep date as possible correct
            $this->_setDate($oObject, $sDateFormat, $aDFields, $aDateMatches);

            return $oObject->value;
        }

        $this->_formatCorrectTimeValue($oObject, $sDateFormat, $sTimeFormat, $aDateMatches, $aTimeMatches, $aTFields, $aDFields);

        // on some cases we get empty value
        if (!$oObject->fldmax_length) {
            return $this->convertDBDateTime($oObject, $blToTimeStamp, $blOnlyDate);
        }

        return $oObject->value;
    }

    /**
     * Bidirectional converter for timestamp field
     *
     * @param object $oObject       oxField type object that keeps db field info
     * @param bool   $blToTimeStamp if true - converts value to database compatible timestamp value
     *
     * @return string
     */
    public function convertDBTimestamp($oObject, $blToTimeStamp = false)
    {
        // on this case usually means that we gonna save value, and value is formatted, not plain
        $sSQLTimeStampPattern = "/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/";
        $sISOTimeStampPattern = "/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/";
        $aMatches = [];
        $oStr = getStr();

        // preparing value to save
        if ($blToTimeStamp) {
            // reformatting value to ISO
            $this->convertDBDateTime($oObject, $blToTimeStamp);

            if ($oStr->preg_match($sISOTimeStampPattern, $oObject->value, $aMatches)) {
                // changing layout
                $oObject->setValue($aMatches[1] . $aMatches[2] . $aMatches[3] . $aMatches[4] . $aMatches[5] . $aMatches[6]);
                $oObject->fldmax_length = strlen($oObject->value);

                return $oObject->value;
            }
        } else {
            // loading and formatting value
            // checking and parsing SQL timestamp value
            //$sSQLTimeStampPattern = "/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/";
            if ($oStr->preg_match($sSQLTimeStampPattern, $oObject->value, $aMatches)) {
                $iTimestamp = mktime(
                    $aMatches[4], //h
                    $aMatches[5], //m
                    $aMatches[6], //s
                    $aMatches[2], //M
                    $aMatches[3], //d
                    $aMatches[1]
                ); //y
                if (!$iTimestamp) {
                    $iTimestamp = "0";
                }

                $oObject->setValue(trim(date("Y-m-d H:i:s", $iTimestamp)));
                $oObject->fldmax_length = strlen($oObject->value);
                $this->convertDBDateTime($oObject, $blToTimeStamp);

                return $oObject->value;
            }
        }
    }

    /**
     * Bidirectional converter for date field
     *
     * @param object $oObject       oxField type object that keeps db field info
     * @param bool   $blToTimeStamp if true - converts value to database compatible timestamp value
     *
     * @return string
     */
    public function convertDBDate($oObject, $blToTimeStamp = false)
    {
        return $this->convertDBDateTime($oObject, $blToTimeStamp, true);
    }

    /**
     * sets default formatted value
     *
     * @param object $oObject          date field object
     * @param string $sDate            preferred date
     * @param string $sLocalDateFormat input format
     * @param string $sLocalTimeFormat local format
     * @param bool   $blOnlyDate       marker to format only date field (no time)
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setDefaultFormatedValue" in next major
     */
    protected function _setDefaultFormatedValue($oObject, $sDate, $sLocalDateFormat, $sLocalTimeFormat, $blOnlyDate) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aDefTimePatterns = $this->_defaultTimePattern();
        $aDFormats = $this->_defineDateFormattingRules();
        $aTFormats = $this->_defineTimeFormattingRules();
        $oStr = getStr();

        foreach (array_keys($aDefTimePatterns) as $sDefTimePattern) {
            if ($oStr->preg_match($sDefTimePattern, $sDate)) {
                $blDefTimeFound = true;
                break;
            }
        }

        // setting and returning default formatted value
        if ($blOnlyDate) {
            $oObject->setValue(trim($aDFormats[$sLocalDateFormat][2])); // . " " . @$aTFormats[$sLocalTimeFormat][2]);
            // increasing(decreasing) field length
            $oObject->fldmax_length = strlen($oObject->value);

            return;
        } elseif ($blDefTimeFound) {
            // setting value
            $oObject->setValue(trim($aDFormats[$sLocalDateFormat][2] . " " . $aTFormats[$sLocalTimeFormat][2]));
            // increasing(decreasing) field length
            $oObject->fldmax_length = strlen($oObject->value);

            return;
        }
    }

    /**
     * defines and checks default time values
     *
     * @param bool $blToTimeStamp -
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "defineAndCheckDefaultTimeValues" in next major
     */
    protected function _defineAndCheckDefaultTimeValues($blToTimeStamp) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // defining time format
        // checking for default values
        $sLocalTimeFormat = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sLocalTimeFormat');
        if (!$sLocalTimeFormat || $blToTimeStamp) {
            $sLocalTimeFormat = "ISO";
        }

        return $sLocalTimeFormat;
    }

    /**
     * defines and checks default date values
     *
     * @param bool $blToTimeStamp marker how to format
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "defineAndCheckDefaultDateValues" in next major
     */
    protected function _defineAndCheckDefaultDateValues($blToTimeStamp) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // defining time format
        // checking for default values
        $sLocalDateFormat = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sLocalDateFormat');
        if (!$sLocalDateFormat || $blToTimeStamp) {
            $sLocalDateFormat = "ISO";
        }

        return $sLocalDateFormat;
    }

    /**
     * sets default date pattern
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "defaultDatePattern" in next major
     */
    protected function _defaultDatePattern() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ["/^0000-00-00/"   => "ISO",
                     "/^00\.00\.0000/" => "EUR",
                     "/^00\/00\/0000/" => "USA"
        ];
    }

    /**
     * sets default time pattern
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "defaultTimePattern" in next major
     */
    protected function _defaultTimePattern() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ["/00:00:00$/"    => "ISO",
                     "/00\.00\.00$/"  => "EUR",
                     "/00:00:00 AM$/" => "USA"
        ];
    }

    /**
     * regular expressions to validate date input
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "regexp2ValidateDateInput" in next major
     */
    protected function _regexp2ValidateDateInput() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ["/^([0-9]{4})-([0-9]{2})-([0-9]{2})/"   => "ISO",
                     "/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})/" => "EUR",
                     "/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})/" => "USA"
        ];
    }

    /**
     * regular expressions to validate time input
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "regexp2ValidateTimeInput" in next major
     */
    protected function _regexp2ValidateTimeInput() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ["/([0-9]{2}):([0-9]{2}):([0-9]{2})$/"                 => "ISO",
                     "/([0-9]{2})\.([0-9]{2})\.([0-9]{2})$/"               => "EUR",
                     "/([0-9]{2}):([0-9]{2}):([0-9]{2}) ([AP]{1}[M]{1})$/" => "USA"
        ];
    }

    /**
     * define date formatting rules
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "defineDateFormattingRules" in next major
     */
    protected function _defineDateFormattingRules() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ["ISO" => ["Y-m-d", [2, 3, 1], "0000-00-00"],
                     "EUR" => ["d.m.Y", [2, 1, 3], "00.00.0000"],
                     "USA" => ["m/d/Y", [1, 2, 3], "00/00/0000"]
        ];
    }

    /**
     * defines time formatting rules
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "defineTimeFormattingRules" in next major
     */
    protected function _defineTimeFormattingRules() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return ["ISO" => ["H:i:s", [1, 2, 3], "00:00:00"],
                     "EUR" => ["H.i.s", [1, 2, 3], "00.00.00"],
                     "USA" => ["h:i:s A", [1, 2, 3], "00:00:00 AM"]
        ];
    }

    /**
     * Sets default date time value
     *
     * @param object $oObject          date field object
     * @param string $sLocalDateFormat input format
     * @param string $sLocalTimeFormat local format
     * @param bool   $blOnlyDate       marker to format only date field (no time)
     * @deprecated underscore prefix violates PSR12, will be renamed to "setDefaultDateTimeValue" in next major
     */
    protected function _setDefaultDateTimeValue($oObject, $sLocalDateFormat, $sLocalTimeFormat, $blOnlyDate) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aDFormats = $this->_defineDateFormattingRules();
        $aTFormats = $this->_defineTimeFormattingRules();

        $sReturn = $aDFormats[$sLocalDateFormat][2];
        if (!$blOnlyDate) {
            $sReturn .= " " . $aTFormats[$sLocalTimeFormat][2];
        }

        if ($oObject instanceof \OxidEsales\Eshop\Core\Field) {
            $oObject->setValue(trim($sReturn));
        } else {
            $oObject->value = trim($sReturn);
        }
        // increasing(decreasing) field lenght
        $oObject->fldmax_length = strlen($oObject->value);
    }

    /**
     * sets date
     *
     * @param object $oObject      date field object
     * @param string $sDateFormat  date format
     * @param array  $aDFields     days
     * @param array  $aDateMatches new date as array (month, year)
     * @deprecated underscore prefix violates PSR12, will be renamed to "setDate" in next major
     */
    protected function _setDate($oObject, $sDateFormat, $aDFields, $aDateMatches) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // formatting correct time value
        $iTimestamp = mktime(
            0,
            0,
            0,
            $aDateMatches[$aDFields[0]],
            $aDateMatches[$aDFields[1]],
            $aDateMatches[$aDFields[2]]
        );

        if ($oObject instanceof \OxidEsales\Eshop\Core\Field) {
            $oObject->setValue(@date($sDateFormat, $iTimestamp));
        } else {
            $oObject->value = @date($sDateFormat, $iTimestamp);
        }
        // we should increase (decrease) field lenght
        $oObject->fldmax_length = strlen($oObject->value);
    }

    /**
     * Formatting correct time value
     *
     * @param object $oObject      data field object
     * @param string $sDateFormat  date format
     * @param string $sTimeFormat  time format
     * @param array  $aDateMatches new new date
     * @param array  $aTimeMatches new time
     * @param array  $aTFields     defines the time fields
     * @param array  $aDFields     defines the date fields
     * @deprecated underscore prefix violates PSR12, will be renamed to "formatCorrectTimeValue" in next major
     */
    protected function _formatCorrectTimeValue($oObject, $sDateFormat, $sTimeFormat, $aDateMatches, $aTimeMatches, $aTFields, $aDFields) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // formatting correct time value
        $iTimestamp = @mktime(
            (int) $aTimeMatches[$aTFields[0]],
            (int) $aTimeMatches[$aTFields[1]],
            (int) $aTimeMatches[$aTFields[2]],
            (int) $aDateMatches[$aDFields[0]],
            (int) $aDateMatches[$aDFields[1]],
            (int) $aDateMatches[$aDFields[2]]
        );

        if ($oObject instanceof \OxidEsales\Eshop\Core\Field) {
            $oObject->setValue(trim(@date($sDateFormat . " " . $sTimeFormat, $iTimestamp)));
        } else {
            $oObject->value = trim(@date($sDateFormat . " " . $sTimeFormat, $iTimestamp));
        }

        // we should increase (decrease) field lenght
        $oObject->fldmax_length = strlen($oObject->value);
    }

    /**
     * Returns time according shop timezone configuration. Configures in
     * Admin -> Main menu -> Core Settings -> General
     * @see getRequestTime
     * @return int current (modified according timezone) time
     */
    public function getTime()
    {
        return $this->shiftServerTime(time());
    }

    /**
     * Returns time wen the request was started according shop timezone configuration. Configures in
     * Admin -> Main menu -> Core Settings -> General
     * REQUEST TIME is faster because it is not an syscall like time
     * @return int current (modified according timezone) time
     */
    public function getRequestTime()
    {
        return $this->shiftServerTime($_SERVER['REQUEST_TIME']);
    }

    /**
     * Returns the the timestamp formatted as date string for the database
     *
     * @param int $iTimestamp the timestamp to be formatted
     *
     * @return bool|string timestamp formatted as date string for the database, false on error
     */
    public function formatDBTimestamp($iTimestamp)
    {
        return date('Y-m-d H:i:s', $iTimestamp);
    }

    /**
     * Returns the the timestamp formatted as date string for the database
     * @param int $roundTo a amount of seconds to be rounded to e.g. 300 for rounding to 5 minutes
     *
     * @return bool|string  the data string formatted for the database (SQL), false on error
     */
    public function getRoundedRequestDateDBFormatted($roundTo)
    {
        $timestamp = $this->getRequestTime();
        //round up x minutes so query cache can work
        $timestamp = ceil($timestamp / $roundTo) * $roundTo;

        //format date for sql query
        return $this->formatDBTimestamp($timestamp);
    }

    /**
     * Returns the the request time formatted as date string for the database
     *
     * @return bool|string
     */
    public function getRequestTimeDBFormated()
    {
        return $this->formatDBTimestamp($this->getRequestTime());
    }

    /**
     * Form time
     *
     * @param string $sTime  time to create timestamp.
     * @param string $sTime2 hours, minutes and seconds to update created timestamp.
     *
     * @return int formed (modified according timezone) time
     */
    public function formTime($sTime = 'now', $sTime2 = null)
    {
        $oDate = new DateTime($sTime);

        if ($sTime2) {
            $aHourToCheck = explode(':', $sTime2);
            $iHour = $aHourToCheck[0];
            $iMinutes = $aHourToCheck[1];
            $iSecond = $aHourToCheck[2];
            $oDate->setTime($iHour, $iMinutes, $iSecond);
        }

        return $this->shiftServerTime($oDate->getTimestamp());
    }

    /**
     * Shift time if needed by configured timezone.
     *
     * @param int $iTime
     *
     * @return int
     */
    public function shiftServerTime($iTime)
    {
        $iServerTimeShift = $this->getConfig()->getConfigParam('iServerTimeShift');
        if ($iServerTimeShift) {
            $iTime = $iTime + ((int) $iServerTimeShift * 3600);
        }
        return $iTime;
    }

    /**
     * Returns number of the week according to numeration standards (configurable in admin):
     * %U - week number, starting with the first Sunday as the first day of the first week;
     * %W - week number, starting with the first Monday as the first day of the first week.
     *
     * @param int    $iFirstWeekDay if set formats with %U, otherwise with %W ($myConfig->getConfigParam( 'iFirstWeekDay' ))
     * @param string $sTimestamp    timestamp, default is null (returns current week number);
     * @param string $sFormat       calculation format ( "%U" or "%w"), default is null (returns "%W" or defined in admin ).
     *
     * @return int
     */
    public function getWeekNumber($iFirstWeekDay, $sTimestamp = null, $sFormat = null)
    {
        if ($sTimestamp == null) {
            $sTimestamp = time();
        }
        if ($sFormat == null) {
            $sFormat = '%W';
            if ($iFirstWeekDay) {
                $sFormat = '%U';
            }
        }

        return (int) strftime($sFormat, $sTimestamp);
    }

    /**
     * Reformats and returns German date string to English.
     *
     * @param string $sDate German format date string
     *
     * @return string
     */
    public function german2English($sDate)
    {
        $aDate = explode(".", $sDate);

        if (isset($aDate) && count($aDate) > 1) {
            if (count($aDate) == 2) {
                $sDate = $aDate[1] . "-" . $aDate[0];
            } else {
                $sDate = $aDate[2] . "-" . $aDate[1] . "-" . $aDate[0];
            }
        }

        return $sDate;
    }

    /**
     * Checks if date string is empty date field. Empty string or string with
     * all date values equal to 0 is treated as empty.
     *
     * @param array $sDate date or date time string
     *
     * @return bool
     */
    public function isEmptyDate($sDate)
    {
        if (!empty($sDate)) {
            $sDate = preg_replace("/[^0-9a-z]/i", "", $sDate);
            if (!is_numeric($sDate) || $sDate != 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Processes amd formats date / time.
     *
     * @param string $aTime    splitted time ( array( H, m, s ) )
     * @param array  $aDate    splitted date ( array( Y, m, d ) )
     * @param bool   $blGerman true if incoming string is in German format (dotted)
     * @param string $sFormat  date format to produce
     *
     * @return string formatted string
     * @deprecated underscore prefix violates PSR12, will be renamed to "processDate" in next major
     */
    protected function _processDate($aTime, $aDate, $blGerman, $sFormat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($blGerman) {
            return date($sFormat, mktime($aTime[0], $aTime[1], $aTime[2], $aDate[1], $aDate[0], $aDate[2]));
        }

        return date($sFormat, mktime($aTime[0], $aTime[1], $aTime[2], $aDate[1], $aDate[2], $aDate[0]));
    }
}
