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
use OxidEsales\Eshop\Application\Controller\Admin\AttributeMainAjax;
use OxidEsales\Eshop\Application\Model\Attribute;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main attributes' manager.
 * There is possibility to change attribute description, assign articles to
 * this attribute, etc.
 * Admin Menu: Manage Products -> Attributes -> Main.
 */
class AttributeMain extends AdminDetailsController
{
    /**
     * Loads article Attributes info, passes it to Smarty engine and
     * returns name of template file "attribute_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $oAttr = oxNew(Attribute::class);
        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();

        // copy this tree for our article choose
        if (isset($soxId) && $soxId != "-1") {
            // generating category tree for select list
            $this->createCategoryTree("artcattree", $soxId);
            // load object
            $oAttr->loadInLang($this->_iEditLang, $soxId);

            //Disable editing for derived items
            if ($oAttr->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            $oOtherLang = $oAttr->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oAttr->loadInLang(key($oOtherLang), $soxId);
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

        $this->_aViewData["edit"] = $oAttr;

        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oAttributeMainAjax = oxNew(AttributeMainAjax::class);
            $this->_aViewData['oxajax'] = $oAttributeMainAjax->getColumns();

            return "popups/attribute_main.tpl";
        }

        return "attribute_main.tpl";
    }

    /**
     * Saves article attributes.
     *
     * @return void
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oAttr = oxNew(Attribute::class);

        if ($soxId != "-1") {
            $oAttr->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxattribute__oxid'] = null;
            //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        }

        //Disable editing for derived items
        if ($oAttr->isDerived()) {
            return;
        }

        $oAttr->setLanguage(0);
        $oAttr->assign($aParams);
        $oAttr->setLanguage($this->_iEditLang);
        $oAttr = Registry::getUtilsFile()->processFiles($oAttr);
        $oAttr->save();

        $this->setEditObjectId($oAttr->getId());
    }

    /**
     * Saves attribute data to different language (eg. english).
     *
     * @return void
     * @throws Exception
     */
    public function saveinnlang()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oAttr = oxNew(Attribute::class);

        if ($soxId != "-1") {
            $oAttr->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxattribute__oxid'] = null;
        }

        //Disable editing for derived items
        if ($oAttr->isDerived()) {
            return;
        }

        $oAttr->setLanguage(0);
        $oAttr->assign($aParams);

        // apply new language
        $oAttr->setLanguage(Registry::getRequest()->getRequestEscapedParameter('new_lang'));
        $oAttr->save();

        // set oxid if inserted
        $this->setEditObjectId($oAttr->getId());
    }
}
