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
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin manufacturer main screen.
 * Performs collection and updating (on user submit) main item information.
 */
class ManufacturerMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(),
     * and returns name of template file
     * "manufacturer_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oManufacturer = oxNew(Manufacturer::class);
            $oManufacturer->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oManufacturer->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                $oManufacturer->loadInLang(key($oOtherLang), $soxId);
            }
            $this->_aViewData["edit"] = $oManufacturer;

            // category tree
            $this->createCategoryTree("artcattree");

            //Disable editing for derived articles
            if ($oManufacturer->isDerived()) {
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
            $oManufacturerMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\ManufacturerMainAjax::class);
            $this->_aViewData['oxajax'] = $oManufacturerMainAjax->getColumns();

            return "popups/manufacturer_main.tpl";
        }

        return "manufacturer_main.tpl";
    }

    /**
     * Saves selection list parameters changes.
     *
     * @return void
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['oxmanufacturers__oxactive'])) {
            $aParams['oxmanufacturers__oxactive'] = 0;
        }

        $oManufacturer = oxNew(Manufacturer::class);

        if ($soxId != "-1") {
            $oManufacturer->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxmanufacturers__oxid'] = null;
        }

        //Disable editing for derived articles
        if ($oManufacturer->isDerived()) {
            return;
        }

        //$aParams = $oManufacturer->ConvertNameArray2Idx( $aParams);
        $oManufacturer->setLanguage(0);
        $oManufacturer->assign($aParams);
        $oManufacturer->setLanguage($this->_iEditLang);
        $oManufacturer = Registry::getUtilsFile()->processFiles($oManufacturer);
        $oManufacturer->save();

        // set oxid if inserted
        $this->setEditObjectId($oManufacturer->getId());
    }

    /**
     * Saves selection list parameters changes in different language (e.g. english).
     *
     * @return void
     */
    public function saveInnLang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['oxmanufacturers__oxactive'])) {
            $aParams['oxmanufacturers__oxactive'] = 0;
        }

        $oManufacturer = oxNew(Manufacturer::class);

        if ($soxId != "-1") {
            $oManufacturer->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxmanufacturers__oxid'] = null;
        }

        //Disable editing for derived articles
        if ($oManufacturer->isDerived()) {
            return;
        }

        //$aParams = $oManufacturer->ConvertNameArray2Idx( $aParams);
        $oManufacturer->setLanguage(0);
        $oManufacturer->assign($aParams);
        $oManufacturer->setLanguage($this->_iEditLang);
        $oManufacturer = Registry::getUtilsFile()->processFiles($oManufacturer);
        $oManufacturer->save();

        // set oxid if inserted
        $this->setEditObjectId($oManufacturer->getId());
    }
}
