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

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File: insert.oxid_content.php
 * Type: string, html
 * Name: oxid_content
 * Purpose: Output content snippet
 * add [{insert name="oxid_content" ident="..."}] where you want to display content
 * -------------------------------------------------------------
 *
 * @param array $params params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_oxcontent($params, &$smarty)
{
    $text = null;
    $id = $params['oxid'] ?? null;
    $loadId = $params['ident'] ?? null;

    if (!Registry::getConfig()->getActiveShop()->isProductiveMode()) {
        $text = sprintf('<b>content not found ! check ident(%s) !</b>', $loadId ?? 'undefined');
    }
    $smarty->oxidcache = new Field($text, Field::T_RAW);

    if ($id || $loadId) {
        $content = oxNew('oxcontent');
        $contentFound = $id ? $content->load($id) : $content->loadbyIdent($loadId);
        if ($contentFound && $content->isActive()) {
            $field = $params['field'] ?? 'oxcontent';
            $property = "oxcontents__{$field}";
            if (Registry::getConfig()->getConfigParam('deactivateSmartyForCmsContent')) {
                $text = $content->$property->value;
            } else {
                $smarty->oxidcache = clone $content->$property;
                $smarty->compile_check = true;
                $resourceName = sprintf(
                    'ox:%s%s%s%s%s',
                    (string)$loadId,
                    (string)$id,
                    $field,
                    Registry::getLang()->getBaseLanguage(),
                    Registry::getConfig()->getShopId()
                );
                $text = $smarty->fetch($resourceName);
                $smarty->compile_check = Registry::getConfig()->getConfigParam('blCheckTemplates');
            }
        }
    }
    // if we write '[{oxcontent ident="oxemailfooterplain" assign="fs_text"}]' the content wont be outputted.
    // instead of this the content will be assigned to variable.
    if (isset($params['assign']) && $params['assign']) {
        $smarty->assign($params['assign'], $text);
    } else {
        return $text;
    }
}
