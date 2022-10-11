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

namespace OxidEsales\EshopCommunity\Core;

/**
 * HTTP headers formator.
 * Collects HTTP headers and form HTTP header.
 */
class Header
{
    protected $_aHeader = [];

    /**
     * Sets header.
     *
     * @param string $header header value.
     */
    public function setHeader($header)
    {
        $header = str_replace(["\n", "\r"], '', $header);
        $this->_aHeader[] = (string) $header . "\r\n";
    }

    /**
     * Return header.
     *
     * @return array
     */
    public function getHeader()
    {
        return $this->_aHeader;
    }

    /**
     * Outputs HTTP header.
     */
    public function sendHeader()
    {
        foreach ($this->_aHeader as $header) {
            if (isset($header)) {
                header($header);
            }
        }
    }

    /**
     * Set to not cacheable.
     *
     * @todo check browser for different no-cache signs.
     */
    public function setNonCacheable()
    {
        $header = "Cache-Control: no-cache;";
        $this->setHeader($header);
    }
}
