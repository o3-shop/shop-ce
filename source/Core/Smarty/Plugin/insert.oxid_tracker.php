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
 * Smarty plugin
 * -------------------------------------------------------------
 * File: insert.oxid_tracker.php
 * Type: string, html
 * Name: oxid_tracker
 * Purpose: Output etracker code or Econda Code
 * add [{insert name="oxid_tracker" title="..."}] after Body Tag in Templates
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @deprecated v5.3 (2016-05-10); Econda will be moved to own module.
 *
 * @return string
 */
function smarty_insert_oxid_tracker($params, &$smarty)
{
    $config = \OxidEsales\Eshop\Core\Registry::getConfig();
    if ($config->getConfigParam('blEcondaActive')) {
        $output = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\Smarty\Plugin\EmosAdapter::class)->getCode($params, $smarty);

        // returning JS code to output
        if (strlen(trim($output))) {
            return "<div style=\"display:none;\">{$output}</div>";
        }
    }
}
