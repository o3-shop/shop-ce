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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Delivery;
use OxidEsales\Eshop\Application\Model\Groups;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article main delivery manager.
 * There is possibility to change delivery name, article, user etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main.
 */
class DeliveryUsers extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates delivery category tree,
     * passes data to Smarty engine and returns name of template file "delivery_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->getEditObjectId();

        $sViewName = getViewName("oxgroups", $this->_iEditLang);
        // all user-groups
        $oGroups = oxNew(ListModel::class);
        $oGroups->init('oxgroups');
        $oGroups->selectString("select * from {$sViewName}");

        $oRoot = new Groups();
        $oRoot->oxgroups__oxid = new Field("");
        $oRoot->oxgroups__oxtitle = new Field("-- ");
        // rebuild list as we need the "no value" entry at the first position
        $aNewList = [];
        $aNewList[] = $oRoot;

        foreach ($oGroups as $val) {
            $aNewList[$val->oxgroups__oxid->value] = new Groups();
            $aNewList[$val->oxgroups__oxid->value]->oxgroups__oxid = new Field($val->oxgroups__oxid->value);
            $aNewList[$val->oxgroups__oxid->value]->oxgroups__oxtitle = new Field($val->oxgroups__oxtitle->value);
        }

        $oGroups = $aNewList;

        if (isset($soxId) && $soxId != "-1") {
            $oDelivery = oxNew(Delivery::class);
            $oDelivery->load($soxId);

            //Disable editing for derived articles
            if ($oDelivery->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }
        }

        $this->_aViewData["allgroups2"] = $oGroups;

        $iAoc = Registry::getRequest()->getRequestEscapedParameter('aoc');
        if ($iAoc == 1) {
            $oDeliveryUsersAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\DeliveryUsersAjax::class);
            $this->_aViewData['oxajax'] = $oDeliveryUsersAjax->getColumns();

            return "popups/delivery_users.tpl";
        } elseif ($iAoc == 2) {
            $oDeliveryGroupsAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\DeliveryGroupsAjax::class);
            $this->_aViewData['oxajax'] = $oDeliveryGroupsAjax->getColumns();

            return "popups/delivery_groups.tpl";
        }

        return "delivery_users.tpl";
    }
}
