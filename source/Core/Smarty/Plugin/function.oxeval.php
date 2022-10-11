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

use OxidEsales\Eshop\Core\Registry;

/**
 * Smarty function
 * -------------------------------------------------------------
 * Purpose: eval given string
 * add [{oxeval var="..."}] where you want to display content
 * -------------------------------------------------------------
 *
 * @param array  $aParams  parameters to process
 * @param smarty &$oSmarty smarty object
 *
 * @return string
 */
function smarty_function_oxeval($aParams, &$oSmarty)
{
    if ($aParams['var'] && ($aParams['var'] instanceof \OxidEsales\Eshop\Core\Field)) {
        $aParams['var'] = trim($aParams['var']->getRawValue());
    }
    $deactivateSmarty = Registry::getConfig()->getConfigParam('deactivateSmartyForCmsContent');
    $processLongDescriptions = Registry::getConfig()->getConfigParam('bl_perfParseLongDescinSmarty') || isset($aParams['force']);
    if (!$deactivateSmarty && $processLongDescriptions) {
        include_once $oSmarty->_get_plugin_filepath('function', 'eval');
        return smarty_function_eval($aParams, $oSmarty);
    }

    return $aParams['var'];
}
