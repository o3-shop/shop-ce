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
use OxidEsales\Eshop\Application\Model\Vendor;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin vendor main screen.
 * Performs collection and updating (on user submit) main item information.
 */
class VendorMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(),
     * and returns name of template file
     * "vendor_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oVendor = oxNew(Vendor::class);
            $oVendor->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oVendor->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oVendor->loadInLang(key($oOtherLang), $soxId);
            }
            $this->_aViewData["edit"] = $oVendor;

            // category tree
            $this->_createCategoryTree("artcattree");

            //Disable editing for derived articles
            if ($oVendor->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

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
            $oVendorMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\VendorMainAjax::class);
            $this->_aViewData['oxajax'] = $oVendorMainAjax->getColumns();

            return "popups/vendor_main.tpl";
        }

        return "vendor_main.tpl";
    }

    /**
     * Saves selection list parameters changes.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['oxvendor__oxactive'])) {
            $aParams['oxvendor__oxactive'] = 0;
        }

        $oVendor = oxNew(Vendor::class);
        if ($soxId != "-1") {
            $oVendor->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxvendor__oxid'] = null;
        }

        //Disable editing for derived articles
        if ($oVendor->isDerived()) {
            return;
        }

        $oVendor->setLanguage(0);
        $oVendor->assign($aParams);
        $oVendor->setLanguage($this->_iEditLang);
        $oVendor = Registry::getUtilsFile()->processFiles($oVendor);
        $oVendor->save();

        // set oxid if inserted
        $this->setEditObjectId($oVendor->getId());
    }

    /**
     * Saves selection list parameters changes in different language (eg. english).
     *
     * @return mixed
     */
    public function saveinnlang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['oxvendor__oxactive'])) {
            $aParams['oxvendor__oxactive'] = 0;
        }

        $oVendor = oxNew(Vendor::class);

        if ($soxId != "-1") {
            $oVendor->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxvendor__oxid'] = null;
        }

        //Disable editing for derived articles
        if ($oVendor->isDerived()) {
            return;
        }

        $oVendor->setLanguage(0);
        $oVendor->assign($aParams);
        $oVendor->setLanguage($this->_iEditLang);
        $oVendor = Registry::getUtilsFile()->processFiles($oVendor);
        $oVendor->save();

        // set oxid if inserted
        $this->setEditObjectId($oVendor->getId());
    }
}
