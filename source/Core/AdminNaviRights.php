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

use DOMNode;
use DOMXPath;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Exception\AccessDeniedException;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElement;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList;

class AdminNaviRights extends Base
{
    protected $doLoad = false;

    protected $menuItemRights = null;

    public function load()
    {
        $this->doLoad = true;
    }

    public function cleanTree($tree)
    {
        $xPath = new DOMXPath($tree);
        $menuItemRights = (oxNew(AdminViewSetting::class))->filterListByTypes(
            $this->getMenuItemRights($xPath),
            [RightsRolesElement::TYPE_HIDDEN]
        );

        if (count($menuItemRights)) {
            $oNodeList = $xPath->query('//*');
            /** @var DOMNode $node */
            foreach ($oNodeList as $node) {
                /** @var DOMElement $node */
                $nodeId = strtolower($node->getAttribute( 'id' ));

                if ( $nodeId && in_array( $nodeId, array_keys($menuItemRights))) {
                    $node->parentNode->removeChild( $node );
                }
            }
        }
    }

    public function applyRights(BaseController $view)
    {
        if (!$this->getUser() || !$view->getViewId()) return;

        if ($this->isDenied($view)) {
            throw oxNew(AccessDeniedException::class);
        }
    }

    protected function isDenied(BaseController $view)
    {
        $menuItemRights = $this->getMenuItemRights() ?? [];

        if (!count($menuItemRights)) return;

        return in_array($view->getViewId(), array_keys($menuItemRights)) &&
            $menuItemRights[$view->getViewId()] == RightsRolesElement::TYPE_HIDDEN;
    }

    protected function getMenuItemRights(DOMXPath $xPath = null)
    {
        $controllersWithoutViewRights = [
            'adminnavigation',
            'adminrights_main'
        ];

        if ($this->menuItemRights === null) {
            $roleRights = $this->doLoad ? $this->getRoleRights() : [];
            $viewRights = !in_array(Registry::getConfig()->getActiveView()->getClassKey(), $controllersWithoutViewRights) ?
                $this->getRestrictedViewRights(
                    oxNew( AdminViewSetting::class )->canShowAllMenuItems(),
                    $roleRights,
                    $xPath
                ) : [];

            if ( count( $roleRights ) && count( $viewRights ) ) {
                $this->menuItemRights = $this->intersectRightLists( $roleRights, $viewRights );
            } else {
                $this->menuItemRights = count( $roleRights ) ? $roleRights : $viewRights;
            }
        }

        $this->menuItemRights = array_change_key_case($this->menuItemRights, CASE_LOWER);

        return $this->menuItemRights;
    }

    protected function getRoleRights()
    {
        return oxNew(RightsRolesElementsList::class)->getElementsByUserId(
            Registry::getConfig()->getUser()->getId()
        );
    }

    protected function getRestrictedViewRights(bool $showAllMenuItem, array $roleRights, DOMXPath $xPath = null)
    {
        $restrictedViewElements = oxNew(RightsRolesElementsList::class)->getRestrictedViewElements();
        $adminViewSettings = oxNew(AdminViewSetting::class);

        if (!$showAllMenuItem &&
            $adminViewSettings->canHaveRestrictedView($restrictedViewElements, $roleRights, $xPath)
        ) {
            return $restrictedViewElements;
        }

        return [];
    }

    public function canHaveRestrictedView($tree)
    {
        return (bool) count($this->getRestrictedViewRights(false, $this->getRoleRights(), new DOMXPath($tree)));
    }

    public function intersectRightLists(array $list1, array $list2)
    {
        foreach ($list2 as $key => $value) {
            $list1[$key] = min(
                (int) $list1[$key] ?? RightsRolesElement::TYPE_EDITABLE,
                (int) $list2[$key] ?? RightsRolesElement::TYPE_EDITABLE
            );
        }

        return $list1;
    }
}