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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\CategoryList;
use OxidEsales\Eshop\Application\Model\File;
use OxidEsales\Eshop\Application\Model\ManufacturerList;
use OxidEsales\Eshop\Application\Model\VendorList;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article main manager.
 * Collects and updates (on user submit) article base parameters data (such as
 * title, article No., short Description etc.).
 * Admin Menu: Manage Products -> Articles -> Main.
 */
class ArticleMain extends AdminDetailsController
{
    /**
     * Loads article parameters and passes them to Smarty engine, returns
     * name of template file "article_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        Registry::getConfig()->setConfigParam('bl_perfLoadPrice', true);

        $oArticle = $this->createArticle();
        $oArticle->enablePriceLoad();

        $this->_aViewData['edit'] = $oArticle;

        $sOxId = $this->getEditObjectId();
        $sVoxId = Registry::getRequest()->getRequestEscapedParameter('voxid');
        $sParentId = Registry::getRequest()->getRequestEscapedParameter('oxparentid');

        // new variant ?
        if (isset($sVoxId) && $sVoxId == "-1" && isset($sParentId) && $sParentId && $sParentId != "-1") {
            $oParentArticle = oxNew(Article::class);
            $oParentArticle->load($sParentId);
            $this->_aViewData["parentarticle"] = $oParentArticle;
            $this->_aViewData["oxparentid"] = $sParentId;

            $this->_aViewData["oxid"] = $sOxId = "-1";
        }

        if ($sOxId && $sOxId != "-1") {
            // load object
            $oArticle = $this->updateArticle($oArticle, $sOxId);

            // load object in other languages
            $oOtherLang = $oArticle->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oArticle->loadInLang(key($oOtherLang), $sOxId);
            }

            // variant handling
            if ($oArticle->oxarticles__oxparentid->value) {
                $oParentArticle = oxNew(Article::class);
                $oParentArticle->load($oArticle->oxarticles__oxparentid->value);
                $this->_aViewData["parentarticle"] = $oParentArticle;
                $this->_aViewData["oxparentid"] = $oArticle->oxarticles__oxparentid->value;
                $this->_aViewData["issubvariant"] = 1;
            }

            // #381A
            $this->_formJumpList($oArticle, $oParentArticle);

            //hook for modules
            $oArticle = $this->customizeArticleInformation($oArticle);

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

        $this->_aViewData["editor"] = $this->_generateTextEditor(
            "100%",
            300,
            $oArticle,
            "oxarticles__oxlongdesc",
            "details.tpl.css"
        );
        $this->_aViewData["blUseTimeCheck"] = Registry::getConfig()->getConfigParam('blUseTimeCheck');

        return "article_main.tpl";
    }

    /**
     * Returns string which must be edited by editor
     *
     * @param BaseModel $oObject object with field will be used for editing
     * @param string                                 $sField  name of editable field
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEditValue" in next major
     */
    protected function _getEditValue($oObject, $sField) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sEditObjectValue = '';
        if ($oObject) {
            $oDescField = $oObject->getLongDescription();
            $sEditObjectValue = $this->_processEditValue($oDescField->getRawValue());
        }

        return $sEditObjectValue;
    }

    /**
     * Saves changes of article parameters.
     */
    public function save()
    {
        parent::save();

        $oDb = DatabaseProvider::getDb();
        $oRequest = Registry::getResquest();
        $soxId = $this->getEditObjectId();
        $aParams = $oRequest->getRequestEscapedParameter('editval');

        // default values
        $aParams = $this->addDefaultValues($aParams);

        // null values
        if (isset($aParams['oxarticles__oxvat']) && $aParams['oxarticles__oxvat'] === '') {
            $aParams['oxarticles__oxvat'] = null;
        }

        // variant-handling
        $sParentId = $oRequest->getRequestEscapedParameter('oxparentid');
        if (isset($sParentId) && $sParentId && $sParentId != "-1") {
            $aParams['oxarticles__oxparentid'] = $sParentId;
        } else {
            unset($aParams['oxarticles__oxparentid']);
        }

        $oArticle = $this->createArticle();
        $oArticle->setLanguage($this->_iEditLang);

        if ($soxId != "-1") {
            $oArticle->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxarticles__oxid'] = null;
            $aParams['oxarticles__oxissearch'] = 1;
            $aParams['oxarticles__oxstockflag'] = 1;
            if (empty($aParams['oxarticles__oxstock'])) {
                $aParams['oxarticles__oxstock'] = 0;
            }

            if (!isset($aParams['oxarticles__oxactive'])) {
                $aParams['oxarticles__oxactive'] = 0;
            }
        }

        //article number handling, warns for artnum duplicates
        if (
            isset($aParams['oxarticles__oxartnum']) && strlen($aParams['oxarticles__oxartnum']) > 0 &&
            Registry::getConfig()->getConfigParam('blWarnOnSameArtNums') &&
            $oArticle->oxarticles__oxartnum->value != $aParams['oxarticles__oxartnum']
        ) {
            $sSelect = "select oxid from " . getViewName('oxarticles');
            $sSelect .= " where oxartnum = " . $oDb->quote($aParams['oxarticles__oxartnum']) . "";
            $sSelect .= " and oxid != " . $oDb->quote($aParams['oxarticles__oxid']) . "";
            if ($oArticle->assignRecord($sSelect)) {
                $this->_aViewData["errorsavingatricle"] = 1;
            }
        }

        $oArticle->setLanguage(0);
        // trimming spaces from article title (M:876)
        if (isset($aParams['oxarticles__oxtitle'])) {
            $aParams['oxarticles__oxtitle'] = trim($aParams['oxarticles__oxtitle']);
        }

        $oArticle->assign($aParams);
        $oArticle->setArticleLongDesc($this->_processLongDesc($aParams['oxarticles__oxlongdesc']));
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle = Registry::getUtilsFile()->processFiles($oArticle);
        $oArticle->save();

        // set oxid if inserted
        if ($soxId == "-1") {
            $sFastCat = $oRequest->getRequestEscapedParameter('art_category');
            if ($sFastCat != "-1") {
                $this->addToCategory($sFastCat, $oArticle->getId());
            }
        }

        $oArticle = $this->saveAdditionalArticleData($oArticle, $aParams);

        $this->setEditObjectId($oArticle->getId());
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
        // TODO: the code below is redundant, optimize it, assignments should go smooth without conversions
        // hack, if editor screws up text (htmledit tends to do so)
        $sValue = str_replace('&amp;nbsp;', '&nbsp;', $sValue);
        $sValue = str_replace('&amp;', '&', $sValue);
        $sValue = str_replace('&quot;', '"', $sValue);
        $sValue = str_replace('&lang=', '&amp;lang=', $sValue);
        $sValue = str_replace('<p>&nbsp;</p>', '', $sValue);
        $sValue = str_replace('<p>&nbsp; </p>', '', $sValue);

        return $sValue;
    }

    /**
     * Resets article categories counters
     *
     * @param string $sArticleId Article id
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetCategoriesCounter" in next major
     */
    protected function _resetCategoriesCounter($sArticleId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sQ = "select oxcatnid from oxobject2category where oxobjectid = :oxobjectid";
        $oRs = $oDb->select($sQ, [
            ':oxobjectid' => $sArticleId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $this->resetCounter("catArticle", $oRs->fields[0]);
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Add article to category.
     *
     * @param string $sCatID Category id
     * @param string $sOXID  Article id
     */
    public function addToCategory($sCatID, $sOXID)
    {
        $base = oxNew(BaseModel::class);
        $base->init("oxobject2category");
        $base->oxobject2category__oxtime = new Field(0);
        $base->oxobject2category__oxobjectid = new Field($sOXID);
        $base->oxobject2category__oxcatnid = new Field($sCatID);

        $base = $this->updateBase($base);

        $base->save();
    }

    /**
     * Copies article (with all parameters) to new articles.
     *
     * @param string $sOldId    old product id (default null)
     * @param string $sNewId    new product id (default null)
     * @param string $sParentId product parent id
     */
    public function copyArticle($sOldId = null, $sNewId = null, $sParentId = null)
    {
        $myConfig = Registry::getConfig();

        $sOldId = $sOldId ? $sOldId : $this->getEditObjectId();
        $sNewId = $sNewId ? $sNewId : Registry::getUtilsObject()->generateUID();

        $oArticle = oxNew(BaseModel::class);
        $oArticle->init('oxarticles');
        if ($oArticle->load($sOldId)) {
            if ($myConfig->getConfigParam('blDisableDublArtOnCopy')) {
                $oArticle->oxarticles__oxactive->setValue(0);
                $oArticle->oxarticles__oxactivefrom->setValue(0);
                $oArticle->oxarticles__oxactiveto->setValue(0);
            }

            // setting parent id
            if ($sParentId) {
                $oArticle->oxarticles__oxparentid->setValue($sParentId);
            }

            // setting oxinsert/oxtimestamp
            $iNow = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
            $oArticle->oxarticles__oxinsert = new Field($iNow);

            // mantis#0001590: OXRATING and OXRATINGCNT not set to 0 when copying article
            $oArticle->oxarticles__oxrating = new Field(0);
            $oArticle->oxarticles__oxratingcnt = new Field(0);

            $oArticle->setId($sNewId);
            $oArticle->save();

            //copy categories
            $this->_copyCategories($sOldId, $sNewId);

            //attributes
            $this->_copyAttributes($sOldId, $sNewId);

            //select-list
            $this->_copySelectlists($sOldId, $sNewId);

            //cross-selling
            $this->_copyCrossseling($sOldId, $sNewId);

            //accessoire
            $this->_copyAccessoires($sOldId, $sNewId);

            // #983A copying staffelpreis info
            $this->_copyStaffelpreis($sOldId, $sNewId);

            //copy article extends (long-description)
            $this->_copyArtExtends($sOldId, $sNewId);

            //files
            $this->_copyFiles($sOldId, $sNewId);

            $this->resetContentCache();

            $myUtilsObject = Registry::getUtilsObject();
            $oDb = DatabaseProvider::getDb();

            //copy variants
            $sQ = "select oxid from oxarticles where oxparentid = :oxparentid";
            $oRs = $oDb->select($sQ, [
                ':oxparentid' => $sOldId
            ]);
            if ($oRs !== false && $oRs->count() > 0) {
                while (!$oRs->EOF) {
                    $this->copyArticle($oRs->fields[0], $myUtilsObject->generateUid(), $sNewId);
                    $oRs->fetchRow();
                }
            }

            // only for top articles
            if (!$sParentId) {
                $this->setEditObjectId($oArticle->getId());

                //article number handling, warns for artnum duplicates
                $sFncParameter = Registry::getRequest()->getRequestEscapedParameter('fnc');
                $sArtNumField = 'oxarticles__oxartnum';
                if (
                    $myConfig->getConfigParam('blWarnOnSameArtNums') &&
                    $oArticle->$sArtNumField->value && $sFncParameter == 'copyArticle'
                ) {
                    $sSelect = "select oxid from " . $oArticle->getCoreTableName() .
                               " where oxartnum = " . $oDb->quote($oArticle->$sArtNumField->value) .
                               " and oxid != " . $oDb->quote($sNewId);

                    if ($oArticle->assignRecord($sSelect)) {
                        $this->_aViewData["errorsavingatricle"] = 1;
                    }
                }
            }
        }
    }

    /**
     * Copying category assignments
     *
     * @param string $sOldId       ID from old article
     * @param string $newArticleId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyCategories" in next major
     */
    protected function _copyCategories($sOldId, $newArticleId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsObject = Registry::getUtilsObject();
        $oDb = DatabaseProvider::getDb();

        $sO2CView = getViewName('oxobject2category');
        $sQ = "select oxcatnid, oxtime from {$sO2CView} where oxobjectid = :oxobjectid";
        $oRs = $oDb->select($sQ, [
            ':oxobjectid' => $sOldId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $uniqueId = $myUtilsObject->generateUid();
                $sCatId = $oRs->fields[0];
                $sTime = $oRs->fields[1];
                $sSql = $this->formQueryForCopyingToCategory($newArticleId, $uniqueId, $sCatId, $sTime);
                $oDb->execute($sSql);
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Copying attributes assignments
     *
     * @param string $sOldId ID from old article
     * @param string $sNewId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyAttributes" in next major
     */
    protected function _copyAttributes($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsObject = Registry::getUtilsObject();
        $oDb = DatabaseProvider::getDb();

        $sQ = "select oxid from oxobject2attribute where oxobjectid = :oxobjectid";
        $oRs = $oDb->select($sQ, [
            ':oxobjectid' => $sOldId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                // #1055A
                $oAttr = oxNew(BaseModel::class);
                $oAttr->init("oxobject2attribute");
                $oAttr->load($oRs->fields[0]);
                $oAttr->setId($myUtilsObject->generateUID());
                $oAttr->oxobject2attribute__oxobjectid->setValue($sNewId);
                $oAttr->save();
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Copying files
     *
     * @param string $sOldId ID from old article
     * @param string $sNewId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyFiles" in next major
     */
    protected function _copyFiles($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsObject = Registry::getUtilsObject();
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $sQ = "SELECT * FROM `oxfiles` WHERE `oxartid` = :oxartid";
        $oRs = $oDb->select($sQ, [
            ':oxartid' => $sOldId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $oFile = oxNew(File::class);
                $oFile->setId($myUtilsObject->generateUID());
                $oFile->oxfiles__oxartid = new Field($sNewId);
                $oFile->oxfiles__oxfilename = new Field($oRs->fields['OXFILENAME']);
                $oFile->oxfiles__oxfilesize = new Field($oRs->fields['OXFILESIZE']);
                $oFile->oxfiles__oxstorehash = new Field($oRs->fields['OXSTOREHASH']);
                $oFile->oxfiles__oxpurchasedonly = new Field($oRs->fields['OXPURCHASEDONLY']);
                $oFile->save();
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Copying selectlists assignments
     *
     * @param string $sOldId ID from old article
     * @param string $sNewId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copySelectlists" in next major
     */
    protected function _copySelectlists($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsObject = Registry::getUtilsObject();
        $oDb = DatabaseProvider::getDb();

        $sQ = "select oxselnid from oxobject2selectlist where oxobjectid = :oxobjectid";
        $oRs = $oDb->select($sQ, [
            ':oxobjectid' => $sOldId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $sUid = $myUtilsObject->generateUID();
                $sId = $oRs->fields[0];
                $sSql = "INSERT INTO oxobject2selectlist (oxid, oxobjectid, oxselnid) " .
                        "VALUES (:oxid, :oxobjectid, :oxselnid)";
                $oDb->execute($sSql, [
                    ':oxid' => $sUid,
                    ':oxobjectid' => $sNewId,
                    ':oxselnid' => $sId,
                ]);
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Copying cross-selling assignments
     *
     * @param string $sOldId ID from old article
     * @param string $sNewId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyCrossseling" in next major
     */
    protected function _copyCrossseling($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsObject = Registry::getUtilsObject();
        $oDb = DatabaseProvider::getDb();

        $sQ = "select oxobjectid from oxobject2article where oxarticlenid = :oxarticlenid";
        $oRs = $oDb->select($sQ, [
            ':oxarticlenid' => $sOldId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $sUid = $myUtilsObject->generateUID();
                $sId = $oRs->fields[0];
                $sSql = "INSERT INTO oxobject2article (oxid, oxobjectid, oxarticlenid) " .
                        "VALUES (:oxid, :oxobjectid, :oxarticlenid)";
                $oDb->execute($sSql, [
                    ':oxid' => $sUid,
                    ':oxobjectid' => $sId,
                    ':oxarticlenid' => $sNewId
                ]);
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Copying accessoires assignments
     *
     * @param string $sOldId ID from old article
     * @param string $sNewId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyAccessoires" in next major
     */
    protected function _copyAccessoires($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsObject = Registry::getUtilsObject();
        $oDb = DatabaseProvider::getDb();

        $sQ = "select oxobjectid from oxaccessoire2article where oxarticlenid = :oxarticlenid";
        $oRs = $oDb->select($sQ, [
            ':oxarticlenid' => $sOldId
        ]);
        if ($oRs !== false && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $sUId = $myUtilsObject->generateUid();
                $sId = $oRs->fields[0];
                $sSql = "INSERT INTO oxaccessoire2article (oxid, oxobjectid, oxarticlenid) " .
                        "VALUES (:oxid, :oxobjectid, :oxarticlenid)";
                $oDb->execute($sSql, [
                    ':oxid' => $sUId,
                    ':oxobjectid' => $sId,
                    ':oxarticlenid' => $sNewId
                ]);
                $oRs->fetchRow();
            }
        }
    }

    /**
     * Copying staffelpreis assignments
     *
     * @param string $sOldId ID from old article
     * @param string $sNewId ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyStaffelpreis" in next major
     */
    protected function _copyStaffelpreis($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sShopId = Registry::getConfig()->getShopId();
        $oPriceList = oxNew(ListModel::class);
        $oPriceList->init("oxbase", "oxprice2article");
        $sQ = "select * from oxprice2article where oxartid = :oxartid and oxshopid = :oxshopid " .
              "and (oxamount > 0 or oxamountto > 0) order by oxamount ";
        $oPriceList->selectString($sQ, [
            ':oxartid' => $sOldId,
            ':oxshopid' => $sShopId
        ]);
        if ($oPriceList->count()) {
            foreach ($oPriceList as $oItem) {
                $oItem->oxprice2article__oxid->setValue($oItem->setId());
                $oItem->oxprice2article__oxartid->setValue($sNewId);
                $oItem->save();
            }
        }
    }

    /**
     * Copying article extends
     *
     * @param string $sOldId - ID from old article
     * @param string $sNewId - ID from new article
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyArtExtends" in next major
     */
    protected function _copyArtExtends($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oExt = oxNew(BaseModel::class);
        $oExt->init("oxartextends");
        $oExt->load($sOldId);
        $oExt->setId($sNewId);
        $oExt->save();
    }

    /**
     * Saves article parameters in different language.
     */
    public function saveinnlang()
    {
        $this->save();
    }

    /**
     * Sets default values for empty article (currently does nothing), returns
     * array with parameters.
     *
     * @param array $aParams Parameters, to set default values
     *
     * @return array
     */
    public function addDefaultValues($aParams)
    {
        return $aParams;
    }

    /**
     * Function forms article variants jump list.
     *
     * @param object $oArticle       article object
     * @param object $oParentArticle article parent object
     * @deprecated underscore prefix violates PSR12, will be renamed to "formJumpList" in next major
     */
    protected function _formJumpList($oArticle, $oParentArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aJumpList = [];
        //fetching parent article variants
        $sOxIdField = 'oxarticles__oxid';
        if (isset($oParentArticle)) {
            $aJumpList[] = [$oParentArticle->$sOxIdField->value, $this->_getTitle($oParentArticle)];
            $sEditLanguageParameter = Registry::getRequest()->getRequestEscapedParameter('editlanguage');
            $oParentVariants = $oParentArticle->getAdminVariants($sEditLanguageParameter);
            if ($oParentVariants->count()) {
                foreach ($oParentVariants as $oVar) {
                    $aJumpList[] = [$oVar->$sOxIdField->value, " - " . $this->_getTitle($oVar)];
                    if ($oVar->$sOxIdField->value == $oArticle->$sOxIdField->value) {
                        $oVariants = $oArticle->getAdminVariants($sEditLanguageParameter);
                        if ($oVariants->count()) {
                            foreach ($oVariants as $oVVar) {
                                $aJumpList[] = [$oVVar->$sOxIdField->value, " -- " . $this->_getTitle($oVVar)];
                            }
                        }
                    }
                }
            }
        } else {
            $aJumpList[] = [$oArticle->$sOxIdField->value, $this->_getTitle($oArticle)];
            //fetching this article variants data
            $oVariants = $oArticle->getAdminVariants(Registry::getRequest()->getRequestEscapedParameter('editlanguage'));
            if ($oVariants && $oVariants->count()) {
                foreach ($oVariants as $oVar) {
                    $aJumpList[] = [$oVar->$sOxIdField->value, " - " . $this->_getTitle($oVar)];
                }
            }
        }
        if (count($aJumpList) > 1) {
            $this->_aViewData["thisvariantlist"] = $aJumpList;
        }
    }

    /**
     * Returns formed variant title
     *
     * @param object $oObj product object
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getTitle" in next major
     */
    protected function _getTitle($oObj) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sTitle = $oObj->oxarticles__oxtitle->value;
        if (!strlen($sTitle)) {
            $sTitle = $oObj->oxarticles__oxvarselect->value;
        }

        return $sTitle;
    }

    /**
     * Returns shop manufacturers list
     *
     * @return ManufacturerList
     */
    public function getCategoryList()
    {
        $oCatTree = oxNew(CategoryList::class);
        $oCatTree->loadList();

        return $oCatTree;
    }

    /**
     * Returns shop manufacturers list
     *
     * @return ManufacturerList
     */
    public function getVendorList()
    {
        $oVendorlist = oxNew(VendorList::class);
        $oVendorlist->loadVendorList();

        return $oVendorlist;
    }

    /**
     * Returns shop manufacturers list
     *
     * @return ManufacturerList
     */
    public function getManufacturerList()
    {
        $oManufacturerList = oxNew(ManufacturerList::class);
        $oManufacturerList->loadManufacturerList();

        return $oManufacturerList;
    }

    /**
     * Loads language for article.
     *
     * @param Article $oArticle
     * @param string                                      $sOxId
     *
     * @return Article
     */
    protected function updateArticle($oArticle, $sOxId)
    {
        $oArticle->loadInLang($this->_iEditLang, $sOxId);

        return $oArticle;
    }

    /**
     * Forms query which is used for adding article to category.
     *
     * @param string $newArticleId
     * @param string $sUid
     * @param string $sCatId
     * @param string $sTime
     *
     * @return string
     */
    protected function formQueryForCopyingToCategory($newArticleId, $sUid, $sCatId, $sTime)
    {
        $oDb = DatabaseProvider::getDb();
        return "insert into oxobject2category (oxid, oxobjectid, oxcatnid, oxtime) " .
            "VALUES (" . $oDb->quote($sUid) . ", " . $oDb->quote($newArticleId) . ", " .
            $oDb->quote($sCatId) . ", " . $oDb->quote($sTime) . ") ";
    }

    /**
     * @param BaseModel $base
     *
     * @return BaseModel $base
     */
    protected function updateBase($base)
    {
        return $base;
    }

    /**
     * Customize article data for rendering.
     * Intended to be used by modules.
     *
     * @param Article $article
     *
     * @return Article
     */
    protected function customizeArticleInformation($article)
    {
        return $article;
    }

    /**
     * Save non-standard article information if needed.
     * Intended to be used by modules.
     *
     * @param Article $article
     * @param array                                       $parameters
     *
     * @return Article
     */
    protected function saveAdditionalArticleData($article, $parameters)
    {
        return $article;
    }

    /**
     * @return Article
     */
    protected function createArticle()
    {
        $oArticle = oxNew(Article::class);

        return $oArticle;
    }
}
