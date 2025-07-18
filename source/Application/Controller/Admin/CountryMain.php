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
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main selectlist manager.
 * Performs collection and updating (on user submit) main item information.
 */
class CountryMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxCategoryList object,
     * passes its data to Smarty engine and returns name of template file
     * "selectlist_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oCountry = oxNew(Country::class);
            $oCountry->loadInLang($this->_iEditLang, $soxId);

            if ($oCountry->isForeignCountry()) {
                $this->_aViewData["blForeignCountry"] = true;
            } else {
                $this->_aViewData["blForeignCountry"] = false;
            }

            $oOtherLang = $oCountry->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oCountry->loadInLang(key($oOtherLang), $soxId);
            }
            $this->_aViewData["edit"] = $oCountry;

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
        } else {
            $this->_aViewData["blForeignCountry"] = true;
        }

        return "country_main.tpl";
    }

    /**
     * Saves selection list parameters changes.
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['oxcountry__oxactive'])) {
            $aParams['oxcountry__oxactive'] = 0;
        }

        $oCountry = oxNew(Country::class);

        if ($soxId != "-1") {
            $oCountry->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxcountry__oxid'] = null;
        }

        //$aParams = $oCountry->ConvertNameArray2Idx( $aParams);
        $oCountry->setLanguage(0);
        $oCountry->assign($aParams);
        $oCountry->setLanguage($this->_iEditLang);
        $oCountry = Registry::getUtilsFile()->processFiles($oCountry);
        $oCountry->save();

        // set oxid if inserted
        $this->setEditObjectId($oCountry->getId());
    }

    /**
     * Saves selection list parameters changes in different language (e.g. english).
     */
    public function saveinnlang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['oxcountry__oxactive'])) {
            $aParams['oxcountry__oxactive'] = 0;
        }

        $oCountry = oxNew(Country::class);

        if ($soxId != "-1") {
            $oCountry->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxcountry__oxid'] = null;
            //$aParams = $oCountry->ConvertNameArray2Idx( $aParams);
        }

        $oCountry->setLanguage(0);
        $oCountry->assign($aParams);
        $oCountry->setLanguage($this->_iEditLang);

        $oCountry->save();

        // set oxid if inserted
        $this->setEditObjectId($oCountry->getId());
    }
}
