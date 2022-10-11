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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Smarty\Bridge;

class SmartyEngineBridge implements SmartyEngineBridgeInterface
{
    /**
     * Renders a fragment of the template.
     *
     * @param \Smarty $engine
     * @param string  $fragment   The template fragment to render
     * @param string  $fragmentId The Id of the fragment
     * @param array   $context    An array of parameters to pass to the template
     *
     * @return string
     */
    public function renderFragment(\Smarty $engine, string $fragment, string $fragmentId, array $context = []): string
    {
        // save old tpl data
        $tplVars = $engine->_tpl_vars;
        $forceRecompile = $engine->force_compile;
        $engine->force_compile = true;
        foreach ($context as $key => $value) {
            $engine->assign($key, $value);
        }
        $engine->oxidcache = new \OxidEsales\Eshop\Core\Field($fragment, \OxidEsales\Eshop\Core\Field::T_RAW);
        $result = $engine->fetch($fragmentId);
        $engine->_tpl_vars = $tplVars;
        $engine->force_compile = $forceRecompile;
        return $result;
    }
}
