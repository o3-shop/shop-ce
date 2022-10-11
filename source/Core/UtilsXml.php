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

use DOMDocument;

/**
 * XML document handler
 */
class UtilsXml extends \OxidEsales\Eshop\Core\Base
{
    /**
     * Takes XML string and makes DOMDocument
     * Returns DOMDocument or false, if it can't be loaded
     *
     * @param string      $sXml         XML as a string
     * @param DOMDocument $oDomDocument DOM handler
     *
     * @return DOMDocument|bool
     */
    public function loadXml($sXml, $oDomDocument = null)
    {
        if (!$oDomDocument) {
            $oDomDocument = new DOMDocument('1.0', 'utf-8');
        }

        libxml_use_internal_errors(true);
        $oDomDocument->loadXML($sXml);
        $errors = libxml_get_errors();
        $blLoaded = empty($errors);
        libxml_clear_errors();

        if ($blLoaded) {
            return $oDomDocument;
        }

        return false;
    }
}
