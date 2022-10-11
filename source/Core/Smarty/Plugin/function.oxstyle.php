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
 * File: function.oxstyle.php
 * Type: string, html
 * Name: oxstyle
 * Purpose: Collect given css files. but include them only at the top of the page.
 *
 * Add [{oxstyle include="oxid.css"}] to include local css file.
 * Add [{oxstyle include="oxid.css?20120413"}] to include local css file with query string part.
 * Add [{oxstyle include="http://www.oxid-esales.com/oxid.css"}] to include external css file.
 *
 * IMPORTANT!
 * Do not forget to add plain [{oxstyle}] tag where you need to output all collected css includes.
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_oxstyle($params, &$smarty)
{
    $defaults = [
        'widget' => '',
        'inWidget' => false,
        'if' => null,
        'include' => null,
    ];
    $params = array_merge($defaults, $params);

    $widget = $params['widget'];
    $forceRender = $params['inWidget'];
    $isDynamic = isset($smarty->_tpl_vars["__oxid_include_dynamic"]) ? (bool)$smarty->_tpl_vars["__oxid_include_dynamic"] : false;

    $output = '';
    if (!empty($params['include'])) {
        $registrator = oxNew(\OxidEsales\Eshop\Core\ViewHelper\StyleRegistrator::class);
        $registrator->addFile($params['include'], $params['if'], $isDynamic);
    } else {
        $renderer = oxNew(\OxidEsales\Eshop\Core\ViewHelper\StyleRenderer::class);
        $output = $renderer->render($widget, $forceRender, $isDynamic);
    }

    return $output;
}
