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

namespace OxidEsales\EshopCommunity\Application\Component\Widget;

/**
 * Cookie note widget
 */
class CookieNote extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/header/cookienote.tpl';

    /**
     * Executes parent::render(). Check if need to hide cookie note.
     * Returns name of template file to render.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * Return if cookie notification is enabled by config.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool) $this->getConfig()->getConfigParam('blShowCookiesNotification');
    }
}
