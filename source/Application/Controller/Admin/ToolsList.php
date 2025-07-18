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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use Exception;

/**
 * Admin systeminfo manager.
 * Returns template, that arranges two other templates ("tools_list.tpl"
 * and "tools_main.tpl") to frame.
 */
class ToolsList extends AdminListController
{
    /**
     * Current class template name
     *
     * @var string
     */
    protected $_sThisTemplate = 'tools_list.tpl';

    /**
     * Performs full view update
     */
    public function updateViews()
    {
        //preventing edit for anyone except malladmin
        if (Registry::getSession()->getVariable("malladmin")) {
            $oMetaData = oxNew(DbMetaDataHandler::class);
            $this->_aViewData["blViewSuccess"] = $oMetaData->updateViews();
        }
    }

    /**
     * Method performs user passed SQL query
     */
    public function performsql()
    {
        $oAuthUser = oxNew(User::class);
        $oAuthUser->loadAdminUser();
        if ($oAuthUser->oxuser__oxrights->value === "malladmin") {
            $sUpdateSQL = Registry::getRequest()->getRequestEscapedParameter('updatesql');
            $sUpdateSQLFile = $this->_processFiles();

            if ($sUpdateSQLFile && strlen($sUpdateSQLFile) > 0) {
                if (isset($sUpdateSQL) && strlen($sUpdateSQL)) {
                    $sUpdateSQL .= ";\r\n" . $sUpdateSQLFile;
                } else {
                    $sUpdateSQL = $sUpdateSQLFile;
                }
            }

            $sUpdateSQL = trim(stripslashes($sUpdateSQL));
            $oStr = Str::getStr();
            $iLen = $oStr->strlen($sUpdateSQL);
            if ($this->_prepareSQL($sUpdateSQL, $iLen)) {
                $aQueries = $this->aSQLs;
                $this->_aViewData["aQueries"] = [];
                $aPassedQueries = [];
                $aQAffectedRows = [];
                $aQErrorMessages = [];
                $aQErrorNumbers = [];

                if (!empty($aQueries) && is_array($aQueries)) {
                    $blStop = false;
                    $oDB = DatabaseProvider::getDb();
                    $iQueriesCounter = 0;
                    for ($i = 0; $i < count($aQueries); $i++) {
                        $sUpdateSQL = $aQueries[$i];
                        $sUpdateSQL = trim($sUpdateSQL);

                        if ($oStr->strlen($sUpdateSQL) > 0) {
                            $aPassedQueries[$iQueriesCounter] = nl2br(Str::getStr()->htmlentities($sUpdateSQL));
                            if ($oStr->strlen($aPassedQueries[$iQueriesCounter]) > 200) {
                                $aPassedQueries[$iQueriesCounter] = $oStr->substr($aPassedQueries[$iQueriesCounter], 0, 200) . "...";
                            }

                            while ($sUpdateSQL[$oStr->strlen($sUpdateSQL) - 1] == ";") {
                                $sUpdateSQL = $oStr->substr($sUpdateSQL, 0, ($oStr->strlen($sUpdateSQL) - 1));
                            }

                            $aQAffectedRows [$iQueriesCounter] = null;
                            $aQErrorMessages[$iQueriesCounter] = null;
                            $aQErrorNumbers [$iQueriesCounter] = null;

                            try {
                                $aQAffectedRows[$iQueriesCounter] = $oDB->execute($sUpdateSQL);
                            } catch (Exception $exception) {
                                // Report errors
                                $aQErrorMessages[$iQueriesCounter] = Str::getStr()->htmlentities($exception->getMessage());
                                $aQErrorNumbers[$iQueriesCounter] = Str::getStr()->htmlentities($exception->getCode());
                                // Trigger breaking the loop
                                $blStop = true;
                            }

                            $iQueriesCounter++;

                            // stopping on first error..
                            if ($blStop) {
                                break;
                            }
                        }
                    }
                }
                $this->_aViewData["aQueries"] = $aPassedQueries;
                $this->_aViewData["aAffectedRows"] = $aQAffectedRows;
                $this->_aViewData["aErrorMessages"] = $aQErrorMessages;
                $this->_aViewData["aErrorNumbers"] = $aQErrorNumbers;
            }
            $this->_iDefEdit = 1;
        }
    }

    /**
     * Processes files containing SQL queries
     *
     * @return false|string|void
     * @deprecated underscore prefix violates PSR12, will be renamed to "processFiles" in next major
     */
    protected function _processFiles() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($_FILES['myfile']['name'])) {
            // process all files
            foreach ($_FILES['myfile']['name'] as $key => $value) {
                $aSource = $_FILES['myfile']['tmp_name'];
                $sSource = $aSource[$key];
                $value = strtolower($value);
                // add type to name
                $aFilename = explode(".", $value);

                //hack?

                $aBadFiles = ["php", 'php4', 'php5', "jsp", "cgi", "cmf", "exe"];

                if (in_array($aFilename[1], $aBadFiles)) {
                    Registry::getUtils()->showMessageAndExit("File didn't pass our allowed files filter.");
                }

                //reading SQL dump file
                if (filesize($sSource) > 0) {
                    $rHandle = fopen($sSource, "r");
                    $sContents = fread($rHandle, filesize($sSource));
                    fclose($rHandle);

                    //reading only one SQL dump file
                    return $sContents;
                }

                return;
            }
        }
    }

    /**
     * Methode parses given SQL queries string and returns array on success
     *
     * @param string  $sSQL    SQL queries
     * @param integer $iSQLlen query length
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareSQL" in next major
     */
    protected function _prepareSQL($sSQL, $iSQLlen) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sStrStart = "";
        $blString = false;
        $oStr = Str::getStr();

        //removing "mysqldump" application comments
        while ($oStr->preg_match("/^\-\-.*\n/", $sSQL)) {
            $sSQL = trim($oStr->preg_replace("/^\-\-.*\n/", "", $sSQL));
        }
        while ($oStr->preg_match("/\n\-\-.*\n/", $sSQL)) {
            $sSQL = trim($oStr->preg_replace("/\n\-\-.*\n/", "\n", $sSQL));
        }

        for ($iPos = 0; $iPos < $iSQLlen; ++$iPos) {
            $sChar = $sSQL[$iPos];
            if ($blString) {
                while (true) {
                    $iPos = $oStr->strpos($sSQL, $sStrStart, $iPos);
                    //we are at the end of string ?
                    if (!$iPos) {
                        $this->aSQLs[] = $sSQL;

                        return true;
                    } elseif ($sStrStart == '`' || $sSQL[$iPos - 1] != '\\') {
                        //found some query separators
                        $blString = false;
                        $sStrStart = "";
                        break;
                    } else {
                        $iNext = 2;
                        $blBackslash = false;
                        while ($iPos - $iNext > 0 && $sSQL[$iPos - $iNext] == '\\') {
                            $blBackslash = !$blBackslash;
                            $iNext++;
                        }
                        if ($blBackslash) {
                            $blString = false;
                            $sStrStart = "";
                            break;
                        } else {
                            $iPos++;
                        }
                    }
                }
            } elseif ($sChar == ";") {
                // delimiter found, appending query array
                $this->aSQLs[] = $oStr->substr($sSQL, 0, $iPos);
                $sSQL = ltrim($oStr->substr($sSQL, min($iPos + 1, $iSQLlen)));
                $iSQLlen = $oStr->strlen($sSQL);
                if ($iSQLlen) {
                    $iPos = -1;
                } else {
                    return true;
                }
            } elseif (($sChar == '"') || ($sChar == '\'') || ($sChar == '`')) {
                $blString = true;
                $sStrStart = $sChar;
            } elseif ($sChar == "#" || ($sChar == ' ' && $iPos > 1 && $sSQL[$iPos - 2] . $sSQL[$iPos - 1] == '--')) {
                // removing # commented query code
                $iCommStart = (($sSQL[$iPos] == "#") ? $iPos : $iPos - 2);
                $iCommEnd = ($oStr->strpos(' ' . $sSQL, "\012", $iPos + 2))
                    ? $oStr->strpos(' ' . $sSQL, "\012", $iPos + 2)
                    : $oStr->strpos(' ' . $sSQL, "\015", $iPos + 2);
                if (!$iCommEnd) {
                    if ($iCommStart > 0) {
                        $this->aSQLs[] = trim($oStr->substr($sSQL, 0, $iCommStart));
                    }

                    return true;
                } else {
                    $sSQL = $oStr->substr($sSQL, 0, $iCommStart) . ltrim($oStr->substr($sSQL, $iCommEnd));
                    $iSQLlen = $oStr->strlen($sSQL);
                    $iPos--;
                }
            } elseif (32358 < 32270 && ($sChar == '!' && $iPos > 1 && $sSQL[$iPos - 2] . $sSQL[$iPos - 1] == '/*')) {
                // removing comments like /**/
                $sSQL[$iPos] = ' ';
            }
        }

        if (!empty($sSQL) && $oStr->preg_match("/[^[:space:]]+/", $sSQL)) {
            $this->aSQLs[] = $sSQL;
        }

        return true;
    }
}
