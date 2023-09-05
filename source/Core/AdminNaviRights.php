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
//dumpvar($allowedMenuItemIds);
        if (count($allowedMenuItemIds)) {
            $oXPath = new DOMXPath($tree);
            $oNodeList = $oXPath->query('//*');
            /** @var DOMNode $node */
            foreach ($oNodeList as $node) {
                /** @var DOMElement $node */
                if ( $node->getAttribute( 'id' ) && ! in_array( $node->getAttribute( 'id' ), $allowedMenuItemIds )
                     && $node->getAttribute( 'id' ) !== 'NAVIGATION_ESHOPADMIN'
                ) {
                    $node->parentNode->removeChild( $node );
                }
            }
        }
    }

    protected function getAllowedMenuItemIds()
    {
        $roleRights = $this->doLoad ? $this->getRoleRights() : [];
        $viewRights = $this->getRestrictedViewRights();

        if (count($roleRights) && count($viewRights)) {
            return array_intersect($roleRights, $viewRights);
        }

        return count($roleRights) ? $roleRights : $viewRights;
    }

    protected function getRoleRights()
    {
        return oxNew(RightsRolesElementsList::class)->getElementsByUserId(
            Registry::getConfig()->getUser()->getId()
        );
    }

    protected function getRestrictedViewRights()
    {
        if ((oxNew(AdminViewSetting::class))->canShowAllMenuItems()) {
            return [];
        }

        return [
            'mxmainmenu',
            'mxshopsett',
            'mxextensions',
            'mxcustnews',
            'mxservice',
            'mxcategories',
            'mxattributes',
            'mxsellist',
            'mxugroups',
            'mxlist'
        ];
    }
}