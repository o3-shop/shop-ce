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

namespace  OxidEsales\EshopCommunity\Application\Component\Widget;

/**
 * Beta note widget
 *
 * @deprecated since v6.5.3 (2020-03-23); Betanote is not used anymore.
 */
class BetaNote extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/header/betanote.tpl';

    protected $_sBetaNoteLink = '';

    /**
     * Gets beta note link
     *
     * @return string
     */
    public function getBetaNoteLink()
    {
        return $this->_sBetaNoteLink;
    }

    /**
     * Sets beta note link
     *
     * @param string $sLink link to set
     */
    public function setBetaNoteLink($sLink)
    {
        $this->_sBetaNoteLink = $sLink;
    }
}
