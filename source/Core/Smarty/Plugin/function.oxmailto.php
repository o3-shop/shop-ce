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

/**
 * Smarty {mailto} function plugin extension, fixes character encoding problem
 *
 * @param array  $aParams  parameters
 * @param Smarty &$oSmarty smarty object
 *
 * @return string
 */
function smarty_function_oxmailto($aParams, &$oSmarty)
{
    if (isset($aParams['encode']) && $aParams['encode'] == 'javascript') {
        $sAddress = isset($aParams['address']) ? $aParams['address'] : '';
        $sText = $sAddress;

        $aMailParms = [];
        foreach ($aParams as $sVarName => $sValue) {
            switch ($sVarName) {
                case 'cc':
                case 'bcc':
                case 'followupto':
                    if ($sValue) {
                        $aMailParms[] = $sVarName . '=' . str_replace([ '%40', '%2C' ], [ '@', ',' ], rawurlencode($sValue));
                    }
                    break;
                case 'subject':
                case 'newsgroups':
                    $aMailParms[] = $sVarName . '=' . rawurlencode($sValue);
                    break;
                case 'extra':
                case 'text':
                    $sName  = "s" . ucfirst($sVarName);
                    $$sName = $sValue;
                    // no break
                default:
            }
        }

        for ($iCtr = 0; $iCtr < count($aMailParms); $iCtr++) {
            $sAddress .= ($iCtr == 0) ? '?' : '&';
            $sAddress .= $aMailParms[$iCtr];
        }

        $sString = 'document.write(\'<a href="mailto:' . $sAddress . '" ' . $sExtra . '>' . $sText . '</a>\');';
        $sEncodedString = "%" . wordwrap(current(unpack("H*", $sString)), 2, "%", true);
        return '<script type="text/javascript">eval(decodeURIComponent(\'' . $sEncodedString . '\'))</script>';
    } else {
        include_once $oSmarty->_get_plugin_filepath('function', 'mailto');
        return smarty_function_mailto($aParams, $oSmarty);
    }
}
