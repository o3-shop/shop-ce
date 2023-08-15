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

class AdminRightsMain extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    public function render()
    {
        parent::render();

        if (Registry::getRequest()->getRequestEscapedParameter("aoc")) {
            $rightsUserAjax = oxNew(AdminRightsMainAjax::class);
            $this->_aViewData['oxajax'] = $rightsUserAjax->getColumns();

            return "popups/adminrights_user.tpl";
        }

        $roleElementsList = oxNew(RightsRolesElementsList::class);
        $role = oxNew(RightsRoles::class);

        if ($this->getEditObjectId() != '-1') {
            $role->load($this->getEditObjectId());
        }
        $this->addTplParam('roleElementsList', $roleElementsList);
        $this->addTplParam('edit', $role);

        return "adminrights_main.tpl";
    }

    public function save()
    {
        $soxId = $this->getEditObjectId();

        $rightsRole = oxNew(RightsRoles::class);
        $aParams = Registry::getRequest()->getRequestEscapedParameter("editval");

        if ($soxId != "-1") {
            $rightsRole->load($soxId);
            $rightsRole->assign($aParams);
        } else {
            $rightsRole->assign(
                array_merge(
                    $aParams,
                    [
                        'o3rightsroles__oxid' => null,
                        'o3rightsroles__oxshopid' => Registry::getConfig()->getShopId()
                    ]
                )
            );
        }
        $rightsRole->save();

        $this->setEditObjectId($rightsRole->getId());

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

    public function getTabs($parentId)
    {
        $navTree = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class);
        return $navTree->getTabs($parentId, 1, false);
    }

    public function getButtons($parentClass)
    {
        $navTree = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class);
        return $navTree->getBtn($parentClass);
    }
}
