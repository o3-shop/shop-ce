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
use OxidEsales\Eshop\Application\Controller\Admin\CategoryMainAjax;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsFile;
use OxidEsales\Eshop\Core\UtilsPic;
use OxidEsales\Eshop\Core\UtilsView;
use stdClass;

/**
 * Admin article main categories' manager.
 * There is possibility to change categories description, sorting, range of price etc.
 * Admin Menu: Manage Products -> Categories -> Main.
 */
class CategoryMain extends AdminDetailsController
{
    const NEW_CATEGORY_ID = "-1";

    /**
     * Loads article category data,
     * returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        parent::render();

        $oCategory = $this->createCategory();
        $categoryId = $this->getEditObjectId();

        $this->_aViewData["edit"] = $oCategory;
        $this->_aViewData["oxid"] = $categoryId;

        if (isset($categoryId) && $categoryId != self::NEW_CATEGORY_ID) {
            // generating category tree for select list
            $this->createCategoryTree("artcattree", $categoryId);

            // load object
            $oCategory->loadInLang($this->_iEditLang, $categoryId);

            //Disable editing for derived items
            if ($oCategory->isDerived()) {
                $this->_aViewData['readonly_fields'] = true;
            }

            $oOtherLang = $oCategory->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oCategory->loadInLang(key($oOtherLang), $categoryId);
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

            if ($oCategory->oxcategories__oxparentid->value == 'oxrootid') {
                $oCategory->oxcategories__oxparentid->setValue('');
            }

            $this->getCategoryTree("cattree", $oCategory->oxcategories__oxparentid->value, $oCategory->oxcategories__oxid->value, true, $oCategory->oxcategories__oxshopid->value);

            $this->_aViewData["defsort"] = $oCategory->oxcategories__oxdefsort->value;
        } else {
            $this->createCategoryTree("cattree", "", true, $myConfig->getShopId());
        }

        $this->_aViewData["sortableFields"] = $this->getSortableFields();

        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            /** @var CategoryMainAjax $oCategoryMainAjax */
            $oCategoryMainAjax = oxNew(CategoryMainAjax::class);
            $this->_aViewData['oxajax'] = $oCategoryMainAjax->getColumns();

            return "popups/category_main.tpl";
        }

        return "category_main.tpl";
    }

    /**
     * Returns an array of article object DB fields, without multi-language and unsortable fields.
     *
     * @return array
     */
    public function getSortableFields()
    {
        $aSkipFields = ["OXID", "OXSHOPID", "OXMAPID", "OXPARENTID", "OXACTIVE", "OXACTIVEFROM"
        , "OXACTIVETO", "OXSHORTDESC"
        , "OXUNITNAME", "OXUNITQUANTITY", "OXEXTURL", "OXURLDESC", "OXURLIMG", "OXVAT"
        , "OXTHUMB", "OXPIC1", "OXPIC2", "OXPIC3", "OXPIC4", "OXPIC5"
        , "OXPIC6", "OXPIC7", "OXPIC8", "OXPIC9", "OXPIC10", "OXPIC11", "OXPIC12", "OXSTOCKFLAG"
        , "OXSTOCKTEXT", "OXNOSTOCKTEXT", "OXDELIVERY", "OXFILE", "OXSEARCHKEYS", "OXTEMPLATE"
        , "OXQUESTIONEMAIL", "OXISSEARCH", "OXISCONFIGURABLE", "OXBUNDLEID", "OXFOLDER", "OXSUBCLASS"
        , "OXREMINDACTIVE", "OXREMINDAMOUNT", "OXVENDORID", "OXMANUFACTURERID", "OXSKIPDISCOUNTS"
        , "OXBLFIXEDPRICE", "OXICON", "OXVARSELECT", "OXAMITEMID", "OXAMTASKID", "OXPIXIEXPORT", "OXPIXIEXPORTED", "OXSORT"
        , "OXUPDATEPRICE", "OXUPDATEPRICEA", "OXUPDATEPRICEB", "OXUPDATEPRICEC", "OXUPDATEPRICETIME", "OXISDOWNLOADABLE"
        , "OXVARMAXPRICE", "OXSHOWCUSTOMAGREEMENT"
        ];
        /** @var DbMetaDataHandler $oDbHandler */
        $oDbHandler = oxNew(DbMetaDataHandler::class);
        $aFields = array_merge($oDbHandler->getMultilangFields('oxarticles'), array_keys($oDbHandler->getSinglelangFields('oxarticles', 0)));
        $aFields = array_diff($aFields, $aSkipFields);
        $aFields = array_unique($aFields);

        return $aFields;
    }

    /**
     * Saves article category data.
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();

        $aParams = $this->parseRequestParametersForSave(
            Registry::getRequest()->getRequestEscapedParameter('editval')
        );

        $oCategory = $this->createCategory();

        if ($soxId != self::NEW_CATEGORY_ID) {
            $this->resetCounter("catArticle", $soxId);
            $this->resetCategoryPictures($oCategory, $aParams, $soxId);
        }

        //Disable editing for derived items
        if ($oCategory->isDerived()) {
            return;
        }

        $oCategory = $this->updateCategoryOnSave($oCategory, $aParams);

        $oCategory->save();

        $this->setEditObjectId($oCategory->getId());
    }

    /**
     * Fixes html broken by html editor
     *
     * @param string $sValue value to fix
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "processLongDesc" in next major
     */
    protected function _processLongDesc($sValue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->processLongDesc($sValue);
    }

    /**
     * Fixes html broken by html editor
     *
     * @param string $sValue value to fix
     *
     * @return string
     */
    protected function processLongDesc($sValue)
    {
        // workaround for firefox showing &lang= as &9001;= entity, mantis#0001272
        return str_replace('&lang=', '&amp;lang=', $sValue);
    }

    /**
     * Saves article category data to different language (eg. english).
     */
    public function saveinnlang()
    {
        $this->save();
    }

    /**
     * Deletes selected master picture.
     *
     * @return void
     * @throws Exception
     */
    public function deletePicture()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = new ExceptionToDisplay();
            $oEx->setMessage('CATEGORY_PICTURES_UPLOADISDISABLED');

            /** @var UtilsView $oUtilsView */
            $oUtilsView = Registry::getUtilsView();

            $oUtilsView->addErrorToDisplay($oEx, false);

            return;
        }

        $sOxId = $this->getEditObjectId();
        $sField = Registry::getRequest()->getRequestEscapedParameter('masterPicField');
        if (empty($sField)) {
            return;
        }

        /** @var Category $oItem */
        $oItem = oxNew(Category::class);
        $oItem->load($sOxId);
        $this->deleteCatPicture($oItem, $sField);
    }

    /**
     * Delete category picture, specified in $sField parameter
     *
     * @param Category $item active category object
     * @param string $field picture field name
     *
     * @return void
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteCatPicture" in next major
     */
    protected function _deleteCatPicture($item, $field) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->deleteCatPicture($item, $field);
    }

    /**
     * Delete category picture, specified in $sField parameter
     *
     * @param Category $item active category object
     * @param string $field picture field name
     *
     * @return void
     * @throws Exception
     */
    protected function deleteCatPicture($item, $field)
    {
        if ($item->isDerived()) {
            return;
        }

        $myConfig = Registry::getConfig();
        $sItemKey = 'oxcategories__' . $field;

        switch ($field) {
            case 'oxthumb':
                $sImgType = 'TC';
                break;

            case 'oxicon':
                $sImgType = 'CICO';
                break;

            case 'oxpromoicon':
                $sImgType = 'PICO';
                break;

            default:
                $sImgType = false;
        }

        if ($sImgType !== false) {
            /** @var UtilsPic $myUtilsPic */
            $myUtilsPic = Registry::getUtilsPic();
            /** @var UtilsFile $oUtilsFile */
            $oUtilsFile = Registry::getUtilsFile();

            $sDir = $myConfig->getPictureDir(false);
            $myUtilsPic->safePictureDelete($item->$sItemKey->value, $sDir . $oUtilsFile->getImageDirByType($sImgType), 'oxcategories', $field);

            $item->$sItemKey = new Field();
            $item->save();
        }
    }

    /**
     * Parse parameters prior to saving category.
     *
     * @param array $aReqParams Request parameters.
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "parseRequestParametersForSave" in next major
     */
    protected function _parseRequestParametersForSave($aReqParams) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->parseRequestParametersForSave($aReqParams);
    }

    /**
     * Parse parameters prior to saving category.
     *
     * @param array $aReqParams Request parameters.
     *
     * @return array
     */
    protected function parseRequestParametersForSave($aReqParams)
    {
        // checkbox handling
        if (!isset($aReqParams['oxcategories__oxactive'])) {
            $aReqParams['oxcategories__oxactive'] = 0;
        }
        if (!isset($aReqParams['oxcategories__oxhidden'])) {
            $aReqParams['oxcategories__oxhidden'] = 0;
        }
        if (!isset($aReqParams['oxcategories__oxdefsortmode'])) {
            $aReqParams['oxcategories__oxdefsortmode'] = 0;
        }

        // null values
        if (!isset($aReqParams['oxcategories__oxvat']) || $aReqParams['oxcategories__oxvat'] === '') {
            $aReqParams['oxcategories__oxvat'] = null;
        }

        if ($this->getEditObjectId() == self::NEW_CATEGORY_ID) {
            //#550A - if new category is made then is must be default active
            //#4051: Impossible to create inactive category
            //$aReqParams['oxcategories__oxactive'] = 1;
            $aReqParams['oxcategories__oxid'] = null;
        }

        if (isset($aReqParams["oxcategories__oxlongdesc"])) {
            $aReqParams["oxcategories__oxlongdesc"] = $this->processLongDesc($aReqParams["oxcategories__oxlongdesc"]);
        }

        if (empty($aReqParams['oxcategories__oxpricefrom'])) {
            $aReqParams['oxcategories__oxpricefrom'] = 0;
        }
        if (empty($aReqParams['oxcategories__oxpriceto'])) {
            $aReqParams['oxcategories__oxpriceto'] = 0;
        }

        return $aReqParams;
    }

    /**
     * Set parameters, language and files to category object.
     *
     * @param Category $category
     * @param array $params
     * @param string $categoryId
     * @throws DatabaseConnectionException
     */
    protected function resetCategoryPictures($category, $params, $categoryId)
    {
        $config = Registry::getConfig();
        $category->load($categoryId);
        $category->loadInLang($this->_iEditLang, $categoryId);

        /** @var UtilsPic $utilsPic */
        $utilsPic = Registry::getUtilsPic();

        // #1173M - not all pic are deleted, after article is removed
        $utilsPic->overwritePic($category, 'oxcategories', 'oxthumb', 'TC', '0', $params, $config->getPictureDir(false));
        $utilsPic->overwritePic($category, 'oxcategories', 'oxicon', 'CICO', 'icon', $params, $config->getPictureDir(false));
        $utilsPic->overwritePic($category, 'oxcategories', 'oxpromoicon', 'PICO', 'icon', $params, $config->getPictureDir(false));
    }

    /**
     * Set parameters, language and files to category object.
     *
     * @param Category $category
     * @param array                                        $params
     *
     * @return object
     */
    protected function updateCategoryOnSave($category, $params)
    {
        $category->assign($params);
        $category->setLanguage($this->_iEditLang);

        $utilsFile = Registry::getUtilsFile();

        return $utilsFile->processFiles($category);
    }

    /**
     * @return Category
     */
    protected function createCategory()
    {
        return oxNew(Category::class);
    }
}
