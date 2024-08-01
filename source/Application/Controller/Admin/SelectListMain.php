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
use oxField;
use OxidEsales\Eshop\Application\Model\SelectList;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

if (!defined('ERR_SUCCESS')) {
    DEFINE("ERR_SUCCESS", 1);
}
if (!defined('ERR_REQUIREDMISSING')) {
    DEFINE("ERR_REQUIREDMISSING", -1);
}
if (!defined('ERR_POSOUTOFBOUNDS')) {
    DEFINE("ERR_POSOUTOFBOUNDS", -2);
}

/**
 * Admin article main selectlist manager.
 * Performs collection and updatind (on user submit) main item information.
 */
class SelectListMain extends AdminDetailsController
{
    /**
     * Keeps all act. fields to store
     */
    public $aFieldArray = null;

    /**
     * Executes parent method parent::render(), creates oxCategoryList object,
     * passes it's data to Smarty engine and returns name of template file
     * "selectlist_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $sOxId = $this->_aViewData["oxid"] = $this->getEditObjectId();

        //create empty edit object
        $this->_aViewData["edit"] = oxNew(SelectList::class);

        if (isset($sOxId) && $sOxId != "-1") {
            // generating category tree for select list
            // A. hack - passing language by post as lists uses only language passed by POST/GET/SESSION
            $_POST["language"] = $this->_iEditLang;
            $this->_createCategoryTree("artcattree", $sOxId);

            // load object
            $oAttr = oxNew(SelectList::class);
            $oAttr->loadInLang($this->_iEditLang, $sOxId);

            $aFieldList = $oAttr->getFieldList();
            if (is_array($aFieldList)) {
                foreach ($aFieldList as $key => $oField) {
                    if ($oField->priceUnit == '%') {
                        $oField->price = $oField->fprice;
                    }
                }
            }

            $oOtherLang = $oAttr->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oAttr->loadInLang(key($oOtherLang), $sOxId);
            }
            $this->_aViewData["edit"] = $oAttr;

            // Disable editing for derived items.
            if ($oAttr->isDerived()) {
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

            $iErr = Registry::getSession()->getVariable("iErrorCode");

            if (!$iErr) {
                $iErr = ERR_SUCCESS;
            }

            $this->_aViewData["iErrorCode"] = $iErr;
            Registry::getSession()->setVariable("iErrorCode", ERR_SUCCESS);
        }
        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oSelectlistMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\SelectListMainAjax::class);
            $this->_aViewData['oxajax'] = $oSelectlistMainAjax->getColumns();

            return "popups/selectlist_main.tpl";
        }

        return "selectlist_main.tpl";
    }

    /**
     * Saves selection list parameters changes.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();

        $sOxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oAttr = oxNew(SelectList::class);

        if ($sOxId != "-1") {
            $oAttr->loadInLang($this->_iEditLang, $sOxId);
        } else {
            $aParams['oxselectlist__oxid'] = null;
        }

        //Disable editing for derived items
        if ($oAttr->isDerived()) {
            return;
        }

        //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        $oAttr->setLanguage(0);
        $oAttr->assign($aParams);

        //#708
        if (!is_array($this->aFieldArray)) {
            $this->aFieldArray = Registry::getUtils()->assignValuesFromText($oAttr->oxselectlist__oxvaldesc->getRawValue());
        }
        // build value
        $oAttr->oxselectlist__oxvaldesc = new Field("", Field::T_RAW);
        foreach ($this->aFieldArray as $oField) {
            $oAttr->oxselectlist__oxvaldesc->setValue($oAttr->oxselectlist__oxvaldesc->getRawValue() . $oField->name, Field::T_RAW);
            if (isset($oField->price) && $oField->price) {
                $oAttr->oxselectlist__oxvaldesc->setValue($oAttr->oxselectlist__oxvaldesc->getRawValue() . "!P!" . trim(str_replace(",", ".", $oField->price)), Field::T_RAW);
                if ($oField->priceUnit == '%') {
                    $oAttr->oxselectlist__oxvaldesc->setValue($oAttr->oxselectlist__oxvaldesc->getRawValue() . '%', Field::T_RAW);
                }
            }
            $oAttr->oxselectlist__oxvaldesc->setValue($oAttr->oxselectlist__oxvaldesc->getRawValue() . "__@@", Field::T_RAW);
        }

        $oAttr->setLanguage($this->_iEditLang);
        $oAttr->save();

        // set oxid if inserted
        $this->setEditObjectId($oAttr->getId());
    }

    /**
     * Saves selection list parameters changes in different language (eg. english).
     *
     * @return null
     */
    public function saveinnlang()
    {
        $sOxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oObj = oxNew(SelectList::class);

        if ($sOxId != "-1") {
            $oObj->loadInLang($this->_iEditLang, $sOxId);
        } else {
            $aParams['oxselectlist__oxid'] = null;
        }

        //Disable editing for derived items
        if ($oObj->isDerived()) {
            return;
        }

        parent::save();

        //$aParams = $oObj->ConvertNameArray2Idx( $aParams);
        $oObj->setLanguage(0);
        $oObj->assign($aParams);

        // apply new language
        $oObj->setLanguage(Registry::getRequest()->getRequestEscapedParameter('new_lang'));
        $oObj->save();

        // set oxid if inserted
        $this->setEditObjectId($oObj->getId());
    }

    /**
     * Deletes field from field array and stores object
     *
     * @return null
     */
    public function delFields()
    {
        $oSelectlist = oxNew(SelectList::class);
        if ($oSelectlist->loadInLang($this->_iEditLang, $this->getEditObjectId())) {
            // Disable editing for derived items.
            if ($oSelectlist->isDerived()) {
                return;
            }

            $aDelFields = Registry::getRequest()->getRequestEscapedParameter('aFields');
            $this->aFieldArray = Registry::getUtils()->assignValuesFromText($oSelectlist->oxselectlist__oxvaldesc->getRawValue());

            if (is_array($aDelFields) && count($aDelFields)) {
                foreach ($aDelFields as $sDelField) {
                    $sDel = $this->parseFieldName($sDelField);
                    foreach ($this->aFieldArray as $sKey => $oField) {
                        if ($oField->name == $sDel) {
                            unset($this->aFieldArray[$sKey]);
                            break;
                        }
                    }
                }
                $this->save();
            }
        }
    }

    /**
     * Adds a field to field array and stores object
     *
     * @return null
     */
    public function addField()
    {
        $oSelectlist = oxNew(SelectList::class);
        if ($oSelectlist->loadInLang($this->_iEditLang, $this->getEditObjectId())) {
            //Disable editing for derived items.
            if ($oSelectlist->isDerived()) {
                return;
            }

            $sAddField = Registry::getRequest()->getRequestEscapedParameter('sAddField');
            if (empty($sAddField)) {
                Registry::getSession()->setVariable("iErrorCode", ERR_REQUIREDMISSING);

                return;
            }

            $this->aFieldArray = Registry::getUtils()->assignValuesFromText($oSelectlist->oxselectlist__oxvaldesc->getRawValue());

            $oField = new stdClass();
            $oField->name = $sAddField;
            $oField->price = Registry::getRequest()->getRequestEscapedParameter('sAddFieldPriceMod');
            $oField->priceUnit = Registry::getRequest()->getRequestEscapedParameter('sAddFieldPriceModUnit');

            $this->aFieldArray[] = $oField;
            if ($iPos = Registry::getRequest()->getRequestEscapedParameter('sAddFieldPos')) {
                if ($this->_rearrangeFields($oField, $iPos - 1)) {
                    return;
                }
            }

            $this->save();
        }
    }

    /**
     * Modifies field from field array's first elem. and stores object
     *
     * @return null
     */
    public function changeField()
    {
        $sAddField = Registry::getRequest()->getRequestEscapedParameter('sAddField');
        if (empty($sAddField)) {
            Registry::getSession()->setVariable("iErrorCode", ERR_REQUIREDMISSING);

            return;
        }

        $aChangeFields = Registry::getRequest()->getRequestEscapedParameter('aFields');
        if (is_array($aChangeFields) && count($aChangeFields)) {
            $oSelectlist = oxNew(SelectList::class);
            if ($oSelectlist->loadInLang($this->_iEditLang, $this->getEditObjectId())) {
                $this->aFieldArray = Registry::getUtils()->assignValuesFromText($oSelectlist->oxselectlist__oxvaldesc->getRawValue());
                $sChangeFieldName = $this->parseFieldName($aChangeFields[0]);

                foreach ($this->aFieldArray as $sKey => $oField) {
                    if ($oField->name == $sChangeFieldName) {
                        $this->aFieldArray[$sKey]->name = $sAddField;
                        $this->aFieldArray[$sKey]->price = Registry::getRequest()->getRequestEscapedParameter('sAddFieldPriceMod');
                        $this->aFieldArray[$sKey]->priceUnit = Registry::getRequest()->getRequestEscapedParameter('sAddFieldPriceModUnit');
                        if ($iPos = Registry::getRequest()->getRequestEscapedParameter('sAddFieldPos')) {
                            if ($this->_rearrangeFields($this->aFieldArray[$sKey], $iPos - 1)) {
                                return;
                            }
                        }
                        break;
                    }
                }
                $this->save();
            }
        }
    }

    /**
     * Resorts fields list and moves $oField to $iPos,
     * uses $this->aFieldArray for fields storage.
     *
     * @param object  $oField field to be moved
     * @param integer $iPos   new pos of the field
     *
     * @return bool - true if failed.
     * @deprecated underscore prefix violates PSR12, will be renamed to "rearrangeFields" in next major
     */
    protected function _rearrangeFields($oField, $iPos) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!isset($this->aFieldArray) || !is_array($this->aFieldArray)) {
            return true;
        }

        $iFieldCount = count($this->aFieldArray);
        if ($iPos < 0 || $iPos >= $iFieldCount) {
            Registry::getSession()->setVariable("iErrorCode", ERR_POSOUTOFBOUNDS);

            return true;
        }

        $iCurrentPos = -1;
        for ($i = 0; $i < $iFieldCount; $i++) {
            if ($this->aFieldArray[$i] == $oField) {
                $iCurrentPos = $i;
                break;
            }
        }

        if ($iCurrentPos == -1) {
            return true;
        }

        if ($iCurrentPos == $iPos) {
            return false;
        }

        $sField = $this->aFieldArray[$iCurrentPos];
        if ($iCurrentPos < $iPos) {
            for ($i = $iCurrentPos; $i < $iPos; $i++) {
                $this->aFieldArray[$i] = $this->aFieldArray[$i + 1];
            }
            $this->aFieldArray[$iPos] = $sField;

            return false;
        } else {
            for ($i = $iCurrentPos; $i > $iPos; $i--) {
                $this->aFieldArray[$i] = $this->aFieldArray[$i - 1];
            }
            $this->aFieldArray[$iPos] = $sField;

            return false;
        }
    }

    /**
     * Parses field name from given string
     * String format is: "someNr__@@someName__@@someTxt"
     *
     * @param string $sInput given string
     *
     * @return string - name
     */
    public function parseFieldName($sInput)
    {
        $aInput = explode('__@@', $sInput, 3);

        return $aInput[1];
    }
}
