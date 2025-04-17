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
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElement;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList;

class AdminViewSetting
{
    public const ALL_MENU_ITEMS = 'showAllMenuItems';
    public const TREEDEPTH_OXMENU = 1;
    public const TREEDEPTH_MAINMENU = 2;
    public const TREEDEPTH_SUBMENU = 3;
    public const TREEDEPTH_TABS = 4;

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

    public function canHaveRestrictedView(array $restrictedViewElements, array $rightElements, \DOMXPath $xPath = null)
    {
        $filterTypes = [RightsRolesElement::TYPE_READONLY, RightsRolesElement::TYPE_EDITABLE];

        $availableRestrictedViewElements = $this->filterListByTypes($restrictedViewElements, $filterTypes);
        $availableRightElements  = $this->filterListByTypes($rightElements, $filterTypes);

        return count($availableRightElements) ?
            count($this->getDisplayableMenuItems($availableRestrictedViewElements, $availableRightElements, $xPath)) :
            count($availableRestrictedViewElements);
    }

    protected function getDisplayableMenuItems($availableRestrictedViewElements, $availableRightElements, \DOMXPath $xPath = null)
    {
        return !$xPath ?
            array_intersect_key($availableRestrictedViewElements, $availableRightElements) :
            array_filter(
                array_intersect_key($availableRestrictedViewElements, $availableRightElements),
                function ($right, $menuId) use ($xPath)
                {
                    $node = $xPath->query('//*[@id="'.$menuId.'"]')->item(0);

                    $depth = -2;
                    while ($node != null)
                    {
                        $depth++;
                        $node = $node->parentNode;
                    }

                    return $depth >= self::TREEDEPTH_TABS;
                },
                ARRAY_FILTER_USE_BOTH
            );
    }

    public function getDepthInTree($node)
    {
        $depth = -1;
        while ($node != null)
        {
            $depth++;
            $node = $node->parentNode;
        }

        return $depth;
    }

    public function filterListByTypes(array $list, array $rightTypes)
    {
        return array_filter(
            $list,
            function($rightType) use ($rightTypes) {
                return in_array($rightType, $rightTypes);
            }
        );
    }
}