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

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

class StyleLogic
{
    /**
     * @param array $params
     * @param bool  $isDynamic
     *
     * @return string
     */
    public function collectStyleSheets($params, $isDynamic)
    {
        $params = $this->fillDefaultParams($params);
        $output = $this->getOutput($params, $isDynamic);

        return $output;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function fillDefaultParams($params)
    {
        $defaults = [
            'widget'   => '',
            'inWidget' => false,
            'if'       => null,
            'include'  => null,
        ];
        $params = array_merge($defaults, $params);

        return $params;
    }

    /**
     * @param array $params
     * @param bool  $isDynamic
     *
     * @return string
     */
    private function getOutput($params, $isDynamic)
    {
        $output = '';
        $widget = $params['widget'];
        $forceRender = $params['inWidget'];
        if (!empty($params['include'])) {
            $registrator = oxNew(\OxidEsales\Eshop\Core\ViewHelper\StyleRegistrator::class);
            $registrator->addFile($params['include'], $params['if'], $isDynamic);
        } else {
            $renderer = oxNew(\OxidEsales\Eshop\Core\ViewHelper\StyleRenderer::class);
            $output = $renderer->render($widget, $forceRender, $isDynamic);
        }

        return $output;
    }
}
