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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList;

class AdminViewSetting
{
    public const ALL_MENU_ITEMS = 'showAllMenuItems';

    public function toggleShowAllMenuItems(): void
    {
        $session = Registry::getSession();
        $this->canShowAllMenuItems() ?
            $session->deleteVariable(self::ALL_MENU_ITEMS) :
            $session->setVariable(self::ALL_MENU_ITEMS, true);
    }

    public function canShowAllMenuItems(): bool
    {
        $session = Registry::getSession();
        return $session->hasVariable(self::ALL_MENU_ITEMS) && $session->getVariable(self::ALL_MENU_ITEMS);
    }

    public function canHaveRestrictedView(array $restrictedViewElements, array $rightElements)
    {
        return count($rightElements) ?
            count(array_intersect_key($restrictedViewElements, $rightElements)) :
            count($restrictedViewElements);
    }
}