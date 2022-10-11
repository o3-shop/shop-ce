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

class IfContentLogic
{
    /**
     * @param string $sIdent
     * @param string $sOxid
     *
     * @return mixed
     */
    public function getContent(string $sIdent = null, string $sOxid = null)
    {
        static $aContentCache = [];

        if (
            ($sIdent && isset($aContentCache[$sIdent])) ||
            ($sOxid && isset($aContentCache[$sOxid]))
        ) {
            $oContent = $sOxid ? $aContentCache[$sOxid] : $aContentCache[$sIdent];
        } else {
            $oContent = oxNew("oxContent");
            $blLoaded = $sOxid ? $oContent->load($sOxid) : ($oContent->loadbyIdent($sIdent));
            if ($blLoaded && $oContent->isActive()) {
                $aContentCache[$oContent->getId()] = $aContentCache[$oContent->getLoadId()] = $oContent;
            } else {
                $oContent = false;
                if ($sOxid) {
                    $aContentCache[$sOxid] = $oContent;
                } else {
                    $aContentCache[$sIdent] = $oContent;
                }
            }
        }

        return $oContent;
    }
}
