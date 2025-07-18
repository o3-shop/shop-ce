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
use OxidEsales\Eshop\Application\Model\Groups;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main usergroup manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: User Administration -> User Groups -> Main.
 */
class UserGroupMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxgroups object,
     * passes data to Smarty engine and returns name of template file
     * "usergroup_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oGroup = oxNew(Groups::class);
            $oGroup->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oGroup->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oGroup->loadInLang(key($oOtherLang), $soxId);
            }

            $this->_aViewData["edit"] = $oGroup;

            // remove already created languages
            $aLang = array_diff(Registry::getLang()->getLanguageNames(), $oOtherLang);

            if (count($aLang)) {
                $this->_aViewData["posslang"] = $aLang;
            }

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }
        }
        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oUsergroupMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\UserGroupMainAjax::class);
            $this->_aViewData['oxajax'] = $oUsergroupMainAjax->getColumns();

            return "popups/usergroup_main.tpl";
        }

        return "usergroup_main.tpl";
    }

    /**
     * Saves changed usergroup parameters.
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        // checkbox handling
        if (!isset($aParams['oxgroups__oxactive'])) {
            $aParams['oxgroups__oxactive'] = 0;
        }

        $oGroup = oxNew(Groups::class);
        if ($soxId != "-1") {
            $oGroup->load($soxId);
        } else {
            $aParams['oxgroups__oxid'] = null;
        }

        $oGroup->setLanguage(0);
        $oGroup->assign($aParams);
        $oGroup->setLanguage($this->_iEditLang);
        $oGroup->save();

        // set oxid if inserted
        $this->setEditObjectId($oGroup->getId());
    }

    /**
     * Saves changed selected group parameters in different language.
     */
    public function saveinnlang()
    {
        $this->save();
    }
}
