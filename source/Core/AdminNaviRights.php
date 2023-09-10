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
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList;

class AdminNaviRights extends Base
{
    protected $doLoad = false;

    public function load()
    {
        $this->doLoad = true;
    }

    public function cleanTree($tree)
    {
        $xPath = new DOMXPath($tree);
        $allowedMenuItemIds = $this->getAllowedMenuItemIds();

        if (count($allowedMenuItemIds)) {
            $oXPath = new DOMXPath($tree);
            $oNodeList = $oXPath->query('//*');
            /** @var DOMNode $node */
            foreach ($oNodeList as $node) {
                /** @var DOMElement $node */
                if ( $node->getAttribute( 'id' ) && ! in_array( $node->getAttribute( 'id' ), array_keys($allowedMenuItemIds) ) ) {
                    $node->parentNode->removeChild( $node );
                }
            }
        }
    }

    public function applyRights(BaseController $oView)
    {
        if (!$this->getUser()) {
            return;
        }

        if ($oView->getViewId() && in_array($oView->getViewId(), ['login'])) {
            return;
        }

        $allowedMenuItemIds = $this->getAllowedMenuItemIds() ?? [];

        if (count($allowedMenuItemIds) && $oView->getViewId() &&
            !in_array($oView->getViewId(), array_keys($allowedMenuItemIds))
        ) {
            throw oxNew(AccessDeniedException::class);
        }
    }

    protected function getAllowedMenuItemIds()
    {
        $roleRights = $this->doLoad ? $this->getRoleRights() : [];
        $viewRights = $this->getRestrictedViewRights(oxNew(AdminViewSetting::class)->canShowAllMenuItems());

        if (count($roleRights) && count($viewRights)) {
            return array_intersect_key($roleRights, $viewRights);
        }

        return count($roleRights) ? $roleRights : $viewRights;
    }

    protected function getRoleRights()
    {
        return oxNew(RightsRolesElementsList::class)->getElementsByUserId(
            Registry::getConfig()->getUser()->getId()
        );
    }

    protected function getRestrictedViewRights(bool $showAllMenuItem)
    {
        $restrictedViewElements = oxNew(RightsRolesElementsList::class)->getRestrictedViewElements();
        $adminViewSettings = oxNew(AdminViewSetting::class);

        if ($adminViewSettings->canHaveRestrictedView($restrictedViewElements, $this->getRoleRights()) &&
            !$showAllMenuItem
        ) {
            return $restrictedViewElements;
        }

        return [];
    }

    public function canHaveRestrictedView()
    {
        return (bool) count($this->getRestrictedViewRights(false));
    }
}