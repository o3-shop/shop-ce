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
use OxidEsales\Eshop\Application\Model\Wrapping;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin wrapping main manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: System Administration -> Wrapping -> Main.
 */
class WrappingMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates oxwrapping, oxshops and oxlist
     * objects, passes data to Smarty engine and returns name of template
     * file "wrapping_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oWrapping = oxNew(Wrapping::class);
            $oWrapping->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oWrapping->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oWrapping->loadInLang(key($oOtherLang), $soxId);
            }
            $this->_aViewData["edit"] = $oWrapping;

            //Disable editing for derived articles
            if ($oWrapping->isDerived()) {
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

        return "wrapping_main.tpl";
    }

    /**
     * Saves main wrapping parameters.
     *
     * @return void
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        // checkbox handling
        if (!isset($aParams['oxwrapping__oxactive'])) {
            $aParams['oxwrapping__oxactive'] = 0;
        }

        $oWrapping = oxNew(Wrapping::class);

        if ($soxId != "-1") {
            $oWrapping->loadInLang($this->_iEditLang, $soxId);
            // #1173M - not all pic are deleted, after article is removed
            Registry::getUtilsPic()->overwritePic($oWrapping, 'oxwrapping', 'oxpic', 'WP', '0', $aParams, Registry::getConfig()->getPictureDir(false));
        } else {
            $aParams['oxwrapping__oxid'] = null;
            //$aParams = $oWrapping->ConvertNameArray2Idx( $aParams);
        }

        //Disable editing for derived articles
        if ($oWrapping->isDerived()) {
            return;
        }

        $oWrapping->setLanguage(0);
        $oWrapping->assign($aParams);
        $oWrapping->setLanguage($this->_iEditLang);

        $oWrapping = Registry::getUtilsFile()->processFiles($oWrapping);
        $oWrapping->save();

        // set oxid if inserted
        $this->setEditObjectId($oWrapping->getId());
    }

    /**
     * Saves main wrapping parameters.
     *
     * @return void
     */
    public function saveinnlang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        // checkbox handling
        if (!isset($aParams['oxwrapping__oxactive'])) {
            $aParams['oxwrapping__oxactive'] = 0;
        }

        $oWrapping = oxNew(Wrapping::class);

        if ($soxId != "-1") {
            $oWrapping->load($soxId);
        } else {
            $aParams['oxwrapping__oxid'] = null;
            //$aParams = $oWrapping->ConvertNameArray2Idx( $aParams);
        }

        //Disable editing for derived articles
        if ($oWrapping->isDerived()) {
            return;
        }

        $oWrapping->setLanguage(0);
        $oWrapping->assign($aParams);
        $oWrapping->setLanguage($this->_iEditLang);

        $oWrapping = Registry::getUtilsFile()->processFiles($oWrapping);
        $oWrapping->save();

        // set oxid if inserted
        $this->setEditObjectId($oWrapping->getId());
    }
}
