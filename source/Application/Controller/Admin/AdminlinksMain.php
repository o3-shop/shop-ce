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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Links;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use stdClass;

/**
 * Admin links details manager.
 * Creates form for submitting new admin links or modifying old ones.
 * Admin Menu: Customer Info -> Links.
 */
class AdminlinksMain extends AdminDetailsController
{
    /**
     * Sets link information data (or leaves empty), returns name of template
     * file "adminlinks_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        $oLinks = oxNew(Links::class, Registry::get(TableViewNameGenerator::class)->getViewName('oxlinks'));

        if (isset($soxId) && $soxId != "-1") {
            $oLinks->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oLinks->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oLinks->loadInLang(key($oOtherLang), $soxId);
            }
            $this->_aViewData["edit"] = $oLinks;

            //Disable editing for derived items
            if ($oLinks->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            // remove already created languages
            $this->_aViewData["posslang"] = array_diff(Registry::getLang()->getLanguageNames(), $oOtherLang);

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }
        }

        // generate editor
        $this->_aViewData["editor"] = $this->generateTextEditor(
            "100%",
            255,
            $oLinks,
            "oxlinks__oxurldesc",
            "links.tpl.css"
        );

        return "adminlinks_main.tpl";
    }

    /**
     * Saves information about link (active, date, URL, description, etc.) to DB.
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        // checkbox handling
        if (!isset($aParams['oxlinks__oxactive'])) {
            $aParams['oxlinks__oxactive'] = 0;
        }

        // adds space to the end of URL description to keep new added links visible
        // if URL description left empty
        if (isset($aParams['oxlinks__oxurldesc']) && strlen($aParams['oxlinks__oxurldesc']) == 0) {
            $aParams['oxlinks__oxurldesc'] .= " ";
        }

        if (!$aParams['oxlinks__oxinsert']) {
            // sets default (?) date format to output
            // else if possible - changes date format to system compatible
            $sDate = date(Registry::getLang()->translateString("simpleDateFormat"));
            if ($sDate == "simpleDateFormat") {
                $aParams['oxlinks__oxinsert'] = date("Y-m-d");
            } else {
                $aParams['oxlinks__oxinsert'] = $sDate;
            }
        }

        $iEditLanguage = Registry::getRequest()->getRequestEscapedParameter('editlanguage');
        $oLinks = oxNew(Links::class, Registry::get(TableViewNameGenerator::class)->getViewName('oxlinks'));

        if ($soxId != "-1") {
            //$oLinks->load( $soxId );
            $oLinks->loadInLang($iEditLanguage, $soxId);

            //Disable editing for derived items
            if ($oLinks->isDerived()) {
                return;
            }
        } else {
            $aParams['oxlinks__oxid'] = null;
        }

        //$aParams = $oLinks->ConvertNameArray2Idx( $aParams);

        $oLinks->setLanguage(0);
        $oLinks->assign($aParams);
        $oLinks->setLanguage($iEditLanguage);
        $oLinks->save();

        parent::save();

        // set oxid if inserted
        $this->setEditObjectId($oLinks->getId());
    }

    /**
     * Saves link description in different languages (eg. english).
     *
     * @return void
     * @throws Exception
     */
    public function saveinnlang()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        // checkbox handling
        if (!isset($aParams['oxlinks__oxactive'])) {
            $aParams['oxlinks__oxactive'] = 0;
        }

        $oLinks = oxNew(Links::class, Registry::get(TableViewNameGenerator::class)->getViewName('oxlinks'));
        $iEditLanguage = Registry::getRequest()->getRequestEscapedParameter('editlanguage');

        if ($soxId != "-1") {
            $oLinks->loadInLang($iEditLanguage, $soxId);
        } else {
            $aParams['oxlinks__oxid'] = null;
            //$aParams = $oLinks->ConvertNameArray2Idx( $aParams);
        }

        //Disable editing for derived items
        if ($oLinks->isDerived()) {
            return;
        }

        $oLinks->setLanguage(0);
        $oLinks->assign($aParams);

        // apply new language
        $oLinks->setLanguage(Registry::getRequest()->getRequestEscapedParameter('new_lang'));
        $oLinks->save();

        // set oxid if inserted
        $this->setEditObjectId($oLinks->getId());
    }
}
