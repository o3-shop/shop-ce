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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\EshopCommunity\Application\Model\RightsRoles;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList;
use OxidEsales\EshopCommunity\Core\Registry;

class AdminNavigation extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    public function init()
    {
        parent::init();
        $this->setEditObjectId(Registry::getSession()->getUser()->getId());
    }

    public function render()
    {
        $this->setEditObjectId(Registry::getSession()->getUser()->getId());

        parent::render();

        $this->addTplParam("oxid", $this->getEditObjectId());

        $roleElementsList = oxNew(RightsRolesElementsList::class);
        $this->addTplParam('roleElementsList', $roleElementsList);

        return "adminnavigation.tpl";
    }

    public function save()
    {
        $this->setEditObjectId(Registry::getSession()->getUser()->getId());

        /** @var RightsRolesElementsList $rightsRolesElementsList */
        $rightsRolesElementsList = oxNew(RightsRolesElementsList::class);
        $rightsRolesElementsList->setNaviSettings(
            Registry::getRequest()->getRequestEscapedParameter('roleElements') ?? [],
            $this->getEditObjectId()
        );

        parent::save();
    }

    public function getMenuTree()
    {
        $navTree = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class);
        return $navTree->getDomXml()->documentElement->childNodes;
    }
}
