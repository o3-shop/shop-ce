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

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Controller\TextEditorHandler;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\CategoryList;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin selectlist list manager.
 */
class AdminDetailsController extends AdminController
{
    /**
     * Global editor object.
     *
     * @var object
     */
    protected $_oEditor = null;

    /**
     * Get language id for documentation by current language id.
     *
     * @return int
     */
    protected function getDocumentationLanguageId()
    {
        $language = Registry::getLang();
        $languageAbbr = $language->getLanguageAbbr($language->getTplLanguage());

        return $languageAbbr === "de" ? 0 : 1;
    }

    /**
     * Returns string which must be edited by editor.
     *
     * @param BaseModel $oObject object used for editing
     * @param string                                 $sField  name of editable field
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEditValue" in next major
     */
    protected function _getEditValue($oObject, $sField) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getEditValue($oObject, $sField);
    }

    /**
     * Returns string which must be edited by editor.
     *
     * @param BaseModel $oObject object used for editing
     * @param string                                 $sField  name of editable field
     *
     * @return string
     */
    protected function getEditValue($oObject, $sField)
    {
        $sEditObjectValue = '';
        if ($oObject && $sField && isset($oObject->$sField)) {
            if ($oObject->$sField instanceof Field) {
                $sEditObjectValue = $oObject->$sField->getRawValue();
            } else {
                $sEditObjectValue = $oObject->$sField->value;
            }

            $sEditObjectValue = $this->processEditValue($sEditObjectValue);
            $oObject->$sField = new Field($sEditObjectValue, Field::T_RAW);
        }

        return $sEditObjectValue;
    }

    /**
     * Processes edit value.
     *
     * @param string $sValue string to process
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "processEditValue" in next major
     */
    protected function _processEditValue($sValue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->processEditValue($sValue);
    }

    /**
     * Processes edit value.
     *
     * @param string $sValue string to process
     *
     * @return string
     */
    protected function processEditValue($sValue)
    {
        // A. replace ONLY if long description is not processed by smarty, or users will not be able to
        // store smarty tags ([{$shop->currenthomedir}]/[{$oViewConf->getCurrentHomeDir()}]) in long
        // descriptions, which are filled dynamically
        if (!Registry::getConfig()->getConfigParam('bl_perfParseLongDescinSmarty')) {
            $aReplace = ['[{$shop->currenthomedir}]', '[{$oViewConf->getCurrentHomeDir()}]'];
            $sValue = str_replace($aReplace, Registry::getConfig()->getCurrentShopURL(false), $sValue);
        }

        return $sValue;
    }

    /**
     * Returns textarea filled with text to edit.
     *
     * @param int                                    $width  editor width
     * @param int                                    $height editor height
     * @param BaseModel $object object passed to editor
     * @param string                                 $field  object field which content is passed to editor
     *
     * @deprecated since v6.0 (2017-06-29); Please use TextEditorHandler::renderPlainTextEditor() method.
     *
     * @return string
     */
    protected function _getPlainEditor($width, $height, $object, $field) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $objectValue = $this->getEditValue($object, $field);

        $textEditor = oxNew(TextEditorHandler::class);

        return $textEditor->renderPlainTextEditor($width, $height, $objectValue, $field);
    }

    /**
     * Generates Text editor html code.
     *
     * @param int                                    $width      editor width
     * @param int                                    $height     editor height
     * @param BaseModel $object     object passed to editor
     * @param string                                 $field      object field which content is passed to editor
     * @param string                                 $stylesheet stylesheet to use in editor
     *
     * @deprecated since v6.0 (2017-06-29); Please use generateTextEditor() method.
     *
     * @return string Editor output
     */
    protected function _generateTextEditor($width, $height, $object, $field, $stylesheet = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->generateTextEditor($width, $height, $object, $field, $stylesheet);
    }

    /**
     * Generates Text editor html code.
     *
     * @param int                                    $width      editor width
     * @param int                                    $height     editor height
     * @param BaseModel $object     object passed to editor
     * @param string                                 $field      object field which content is passed to editor
     * @param string                                 $stylesheet stylesheet to use in editor
     *
     * @return string Editor output
     */
    protected function generateTextEditor($width, $height, $object, $field, $stylesheet = null)
    {
        $objectValue = $this->getEditValue($object, $field);

        $textEditorHandler = $this->createTextEditorHandler();
        $this->configureTextEditorHandler($textEditorHandler, $object, $field, $stylesheet);

        return $textEditorHandler->renderTextEditor($width, $height, $objectValue, $field);
    }

    /**
     * Resets number of articles in current shop categories.
     */
    public function resetNrOfCatArticles()
    {
        // resetting categories article count cache
        $this->resetContentCache();
    }

    /**
     * Resets number of articles in current shop vendors.
     */
    public function resetNrOfVendorArticles()
    {
        // resetting vendors cache
        $this->resetContentCache();
    }

    /**
     * Resets number of articles in current shop manufacturers.
     */
    public function resetNrOfManufacturerArticles()
    {
        // resetting manufacturers cache
        $this->resetContentCache();
    }

    /**
     * Function creates category tree for select list used in "Category main", "Article extend" etc.
     *
     * @param string $sTplVarName     name of template variable where is stored category tree
     * @param string $sEditCatId      ID of category witch we are editing
     * @param bool   $blForceNonCache Set to true to disable caching
     * @param int    $iTreeShopId     tree shop id
     *
     * @return object
     * @deprecated underscore prefix violates PSR12, will be renamed to "createCategoryTree" in next major
     */
    protected function _createCategoryTree($sTplVarName, $sEditCatId = '', $blForceNonCache = false, $iTreeShopId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->createCategoryTree($sTplVarName, $sEditCatId, $blForceNonCache, $iTreeShopId);
    }

    /**
     * Function creates category tree for select list used in "Category main", "Article extend" etc.
     *
     * @param string $sTplVarName     name of template variable where is stored category tree
     * @param string $sEditCatId      ID of category witch we are editing
     * @param bool   $blForceNonCache Set to true to disable caching
     * @param int    $iTreeShopId     tree shop id
     *
     * @return object
     */
    protected function createCategoryTree($sTplVarName, $sEditCatId = '', $blForceNonCache = false, $iTreeShopId = null)
    {
        // caching category tree, to load it once, not many times
        if (!isset($this->oCatTree) || $blForceNonCache) {
            $this->oCatTree = oxNew(CategoryList::class);
            $this->oCatTree->setShopID($iTreeShopId);

            // setting language
            $oBase = $this->oCatTree->getBaseObject();
            $oBase->setLanguage($this->_iEditLang);

            $this->oCatTree->loadList();
        }

        // copying tree
        $oCatTree = $this->oCatTree;
        //removing current category
        if ($sEditCatId && isset($oCatTree[$sEditCatId])) {
            unset($oCatTree[$sEditCatId]);
        }

        // add first fake category for not assigned articles
        $oRoot = oxNew(Category::class);
        $oRoot->oxcategories__oxtitle = new Field('--');

        $oCatTree->assign(array_merge(['' => $oRoot], $oCatTree->getArray()));

        // passing to view
        $this->_aViewData[$sTplVarName] = $oCatTree;

        return $oCatTree;
    }

    /**
     * Function creates category tree for select list used in "Category main", "Article extend" etc.
     * Returns ID of selected category if available.
     *
     * @param string $sTplVarName     name of template variable where is stored category tree
     * @param string $sSelectedCatId  ID of category witch was selected in select list
     * @param string $sEditCatId      ID of category witch we are editing
     * @param bool   $blForceNonCache Set to true to disable caching
     * @param int    $iTreeShopId     tree shop id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategoryTree" in next major
     */
    protected function _getCategoryTree( // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
        $sTplVarName,
        $sSelectedCatId,
        $sEditCatId = '',
        $blForceNonCache = false,
        $iTreeShopId = null
    ) {
        return $this->getCategoryTree($sTplVarName, $sSelectedCatId, $sEditCatId, $blForceNonCache, $iTreeShopId);
    }

    /**
     * Function creates category tree for select list used in "Category main", "Article extend" etc.
     * Returns ID of selected category if available.
     *
     * @param string $sTplVarName     name of template variable where is stored category tree
     * @param string $sSelectedCatId  ID of category witch was selected in select list
     * @param string $sEditCatId      ID of category witch we are editing
     * @param bool   $blForceNonCache Set to true to disable caching
     * @param int    $iTreeShopId     tree shop id
     *
     * @return string
     */
    protected function getCategoryTree(
        $sTplVarName,
        $sSelectedCatId,
        $sEditCatId = '',
        $blForceNonCache = false,
        $iTreeShopId = null
    ) {
        $oCatTree = $this->createCategoryTree($sTplVarName, $sEditCatId, $blForceNonCache, $iTreeShopId);

        // mark selected
        if ($sSelectedCatId) {
            // fixed parent category in select list
            foreach ($oCatTree as $oCategory) {
                if (strcmp($oCategory->getId(), $sSelectedCatId) == 0) {
                    $oCategory->selected = 1;
                    break;
                }
            }
        } else {
            // no category selected - opening first available
            $oCatTree->rewind();
            if ($oCat = $oCatTree->current()) {
                $oCat->selected = 1;
                $sSelectedCatId = $oCat->getId();
            }
        }

        // passing to view
        $this->_aViewData[$sTplVarName] = $oCatTree;

        return $sSelectedCatId;
    }

    /**
     * Updates object folder parameters.
     */
    public function changeFolder()
    {
        $sFolder = Registry::getRequest()->getRequestEscapedParameter('setfolder');
        $sFolderClass = Registry::getRequest()->getRequestEscapedParameter('folderclass');

        if ($sFolderClass == 'oxcontent' && $sFolder == 'CMSFOLDER_NONE') {
            $sFolder = '';
        }

        $oObject = oxNew($sFolderClass);
        if ($oObject->load($this->getEditObjectId())) {
            $oObject->{$oObject->getCoreTableName() . '__oxfolder'} = new Field($sFolder);
            $oObject->save();
        }
    }

    /**
     * Sets-up navigation parameters.
     *
     * @param string $sNode active view id
     * @deprecated underscore prefix violates PSR12, will be renamed to "setupNavigation" in next major
     */
    protected function _setupNavigation($sNode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setupNavigation($sNode);
    }

    /**
     * Sets-up navigation parameters.
     *
     * @param string $sNode active view id
     */
    protected function setupNavigation($sNode)
    {
        // navigation according to class
        if ($sNode) {
            $myAdminNavig = $this->getNavigation();

            // default tab
            $this->_aViewData['default_edit'] = $myAdminNavig->getActiveTab($sNode, $this->_iDefEdit);

            // buttons
            $this->_aViewData['bottom_buttons'] = $myAdminNavig->getBtn($sNode);
        }
    }

    /**
     * Resets count of vendor/manufacturer category items.
     *
     * @param array $aIds to reset type => id
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetCounts" in next major
     */
    protected function _resetCounts($aIds) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->resetCounts($aIds);
    }

    /**
     * Resets count of vendor/manufacturer category items.
     *
     * @param array $aIds to reset type => id
     */
    protected function resetCounts($aIds)
    {
        foreach ($aIds as $sType => $aResetInfo) {
            foreach ($aResetInfo as $sResetId => $iPos) {
                switch ($sType) {
                    case 'vendor':
                        $this->resetCounter("vendorArticle", $sResetId);
                        break;
                    case 'manufacturer':
                        $this->resetCounter("manufacturerArticle", $sResetId);
                        break;
                }
            }
        }
    }

    /**
     * Create the handler for the text editor.
     *
     * Note: the parameters editedObject and field are not used here but in the enterprise edition.
     *
     * @param TextEditorHandler $textEditorHandler
     * @param mixed             $editedObject      The object we want to edit, either type of
     *                                             BaseModel if you want to persist or anything
     *                                             else
     * @param string            $field             The input field we want to edit
     * @param string            $stylesheet        The name of the CSS file
     *
     */
    protected function configureTextEditorHandler(
        TextEditorHandler $textEditorHandler,
        $editedObject,
        $field,
        $stylesheet
    ) {
        $textEditorHandler->setStyleSheet($stylesheet);
    }

    /**
     * Create the handler for the text editor.
     *
     * @return TextEditorHandler The text editor handler
     */
    protected function createTextEditorHandler()
    {
        return oxNew(TextEditorHandler::class);
    }
}
