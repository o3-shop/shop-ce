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

namespace OxidEsales\EshopCommunity\Core\ViewHelper;

/**
 * Class for preparing JavaScript.
 */
class StyleRenderer
{
    /**
     * @param string $widget
     * @param bool   $forceRender
     * @param bool   $isDynamic
     *
     * @return string
     */
    public function render($widget, $forceRender, $isDynamic)
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $suffix = $isDynamic ? '_dynamic' : '';
        $output = '';

        if (!$widget || $this->shouldForceRender($forceRender)) {
            $styles = (array) $config->getGlobalParameter(\OxidEsales\Eshop\Core\ViewHelper\StyleRegistrator::STYLES_PARAMETER_NAME . $suffix);
            $output .= $this->formStylesOutput($styles);
            $output .= PHP_EOL;
            $conditionalStyles = (array) $config->getGlobalParameter(\OxidEsales\Eshop\Core\ViewHelper\StyleRegistrator::CONDITIONAL_STYLES_PARAMETER_NAME . $suffix);
            $output .= $this->formConditionalStylesOutput($conditionalStyles);
        }

        return $output;
    }

    /**
     * Returns whether rendering of scripts should be forced.
     *
     * @param bool $forceRender
     *
     * @return bool
     */
    protected function shouldForceRender($forceRender)
    {
        return $forceRender;
    }

    /**
     * @param array $styles
     *
     * @return string
     */
    protected function formStylesOutput($styles)
    {
        $preparedStyles = [];
        $template = '<link rel="stylesheet" type="text/css" href="%s" />';
        foreach ($styles as $style) {
            $preparedStyles[] = sprintf($template, $style);
        }

        return implode(PHP_EOL, $preparedStyles);
    }

    /**
     * @param array $styles
     *
     * @return string
     */
    protected function formConditionalStylesOutput($styles)
    {
        $preparedStyles = [];
        $template = '<!--[if %s]><link rel="stylesheet" type="text/css" href="%s"><![endif]-->';
        foreach ($styles as $style => $condition) {
            $preparedStyles[] = sprintf($template, $condition, $style);
        }

        return implode(PHP_EOL, $preparedStyles);
    }
}
