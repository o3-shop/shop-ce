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
use OxidEsales\Eshop\Application\Model\Remark;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin user history settings manager.
 * Collects user history settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> History.
 */
class UserRemark extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxuser, oxlist and
     * oxRemark objects, passes data to Smarty engine and returns name of
     * template file "user_remark.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->getEditObjectId();
        $sRemoxId = Registry::getRequest()->getRequestEscapedParameter('rem_oxid');
        if (isset($soxId) && $soxId != '-1') {
            // load object
            $oUser = oxNew(User::class);
            $oUser->load($soxId);
            $this->_aViewData['edit'] = $oUser;

            // all remark
            $oRems = oxNew(ListModel::class);
            $oRems->init('oxremark');
            $sSelect = 'select * from oxremark where oxparentid = :oxparentid order by oxcreate desc';
            $oRems->selectString($sSelect, [
                ':oxparentid' => $oUser->getId(),
            ]);
            foreach ($oRems as $key => $val) {
                if ($val->oxremark__oxid->value == $sRemoxId) {
                    $val->selected = 1;
                    $oRems[$key] = $val;
                    break;
                }
            }

            $this->_aViewData['allremark'] = $oRems;

            if (isset($sRemoxId)) {
                $oRemark = oxNew(Remark::class);
                $oRemark->load($sRemoxId);
                $this->_aViewData['remarktext'] = $oRemark->oxremark__oxtext->value;
                $this->_aViewData['remarkheader'] = $oRemark->oxremark__oxheader->value;
            }
        }

        return 'user_remark.tpl';
    }

    /**
     * Saves user history text changes.
     */
    public function save()
    {
        parent::save();

        $oRemark = oxNew(Remark::class);

        // try to load if exists
        $oRemark->load(Registry::getRequest()->getRequestEscapedParameter('rem_oxid'));

        $oRemark->oxremark__oxtext = new Field(
            Registry::getRequest()->getRequestEscapedParameter('remarktext')
        );
        $oRemark->oxremark__oxheader = new Field(
            Registry::getRequest()->getRequestEscapedParameter('remarkheader')
        );
        $oRemark->oxremark__oxparentid = new Field($this->getEditObjectId());
        $oRemark->oxremark__oxtype = new Field('r');
        $oRemark->save();
    }

    /**
     * Deletes user actions history record.
     */
    public function delete()
    {
        $oRemark = oxNew(Remark::class);
        $oRemark->delete(Registry::getRequest()->getRequestEscapedParameter('rem_oxid'));
    }
}
