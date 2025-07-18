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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\VariantHandler;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Admin article variants manager.
 * Collects and updates article variants data.
 * Admin Menu: Manage Products -> Articles -> Variants.
 */
class ArticleVariant extends AdminDetailsController
{
    /**
     * Variant parent product object
     *
     * @var Article
     */
    protected $_oProductParent = null;

    /**
     * Loads article variants data, passes it to Smarty engine and returns name of
     * template file "article_variant.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $soxId = $this->getEditObjectId();
        $sSLViewName = getViewName('oxselectlist');

        // all selectlists
        $oAllSel = oxNew(ListModel::class);
        $oAllSel->init("oxselectlist");
        $sQ = "select * from $sSLViewName";
        $oAllSel->selectString($sQ);
        $this->_aViewData["allsel"] = $oAllSel;

        $oArticle = oxNew(Article::class);
        $this->_aViewData["edit"] = $oArticle;

        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oArticle->loadInLang($this->_iEditLang, $soxId);

            if ($oArticle->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            $_POST["language"] = $_GET["language"] = $this->_iEditLang;
            $oVariants = $oArticle->getAdminVariants($this->_iEditLang);

            $this->_aViewData["mylist"] = $oVariants;

            // load object in other languages
            $oOtherLang = $oArticle->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oArticle->loadInLang(key($oOtherLang), $soxId);
            }

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }

            if ($oArticle->oxarticles__oxparentid->value) {
                $this->_aViewData["parentarticle"] = $this->getProductParent($oArticle->oxarticles__oxparentid->value);
                $this->_aViewData["oxparentid"] = $oArticle->oxarticles__oxparentid->value;
                $this->_aViewData["issubvariant"] = 1;
                // A. disable variant information editing for variant
                $this->_aViewData["readonly"] = 1;
            }
            $this->_aViewData['editlanguage'] = $this->_iEditLang;

            $aLang = array_diff(Registry::getLang()->getLanguageNames(), $oOtherLang);
            if (count($aLang)) {
                $this->_aViewData["posslang"] = $aLang;
            }

            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = $oLang;
            }
        }

        return "article_variant.tpl";
    }

    /**
     * Saves article variant.
     *
     * @param string $sOXID Object ID
     * @param array $aParams Parameters
     *
     * @return void
     * @throws Exception
     */
    public function savevariant($sOXID = null, $aParams = null)
    {
        if (!isset($sOXID) && !isset($aParams)) {
            $sOXID = Registry::getRequest()->getRequestEscapedParameter('voxid');
            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        }

        // variant-handling
        $sParentId = $this->getEditObjectId();
        if (isset($sParentId) && $sParentId && $sParentId != "-1") {
            $aParams['oxarticles__oxparentid'] = $sParentId;
        } else {
            unset($aParams['oxarticles__oxparentid']);
        }
        /** @var Article $oArticle */
        $oArticle = oxNew(Article::class);

        if ($sOXID != "-1") {
            $oArticle->loadInLang($this->_iEditLang, $sOXID);
        }

        // checkbox handling
        if (is_array($aParams) && !isset($aParams['oxarticles__oxactive'])) {
            $aParams['oxarticles__oxactive'] = 0;
        }

        if (!$this->isAnythingChanged($oArticle, $aParams)) {
            return;
        }

        $oArticle->setLanguage(0);
        $oArticle->assign($aParams);
        $oArticle->setLanguage($this->_iEditLang);

        // #0004473
        $oArticle->resetRemindStatus();

        if ($sOXID == "-1") {
            if ($oParent = $this->getProductParent($oArticle->oxarticles__oxparentid->value)) {
                // assign field from parent for new variant
                // #4406
                $oArticle->oxarticles__oxisconfigurable = new Field($oParent->oxarticles__oxisconfigurable->value);
                $oArticle->oxarticles__oxremindactive = new Field($oParent->oxarticles__oxremindactive->value);
            }
        }

        $oArticle->save();
    }

    /**
     * Checks if anything is changed in given data compared with existing product values.
     *
     * @param Article $oProduct Product to be checked.
     * @param array                                       $aData    Data provided for check.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isAnythingChanged" in next major
     */
    protected function _isAnythingChanged($oProduct, $aData) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isAnythingChanged($oProduct, $aData);
    }

    /**
     * Checks if anything is changed in given data compared with existing product values.
     *
     * @param Article $oProduct Product to be checked.
     * @param array                                       $aData    Data provided for check.
     *
     * @return bool
     */
    protected function isAnythingChanged($oProduct, $aData)
    {
        if (!is_array($aData)) {
            return true;
        }
        foreach ($aData as $sKey => $sValue) {
            if (isset($oProduct->$sKey) && $oProduct->$sKey->value != $sValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns variant parent object
     *
     * @param string $sParentId parent product id
     *
     * @return Article
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductParent" in next major
     */
    protected function _getProductParent($sParentId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getProductParent($sParentId);
    }

    /**
     * Returns variant parent object
     *
     * @param string $sParentId parent product id
     *
     * @return Article
     * @throws DatabaseConnectionException
     */
    protected function getProductParent($sParentId)
    {
        if (
            $this->_oProductParent === null ||
            ($this->_oProductParent !== false && $this->_oProductParent->getId() != $sParentId)
        ) {
            $this->_oProductParent = false;
            $oProduct = oxNew(Article::class);
            if ($oProduct->load($sParentId)) {
                $this->_oProductParent = $oProduct;
            }
        }

        return $this->_oProductParent;
    }

    /**
     * Saves all article variants at once.
     */
    public function savevariants()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        if (is_array($aParams)) {
            foreach ($aParams as $soxId => $aVarParams) {
                $this->savevariant($soxId, $aVarParams);
            }
        }

        $this->resetContentCache();
    }

    /**
     * Deletes article variant.
     *
     * @return void
     * @throws Exception
     */
    public function deleteVariant()
    {
        $editObjectOxid = $this->getEditObjectId();
        $editObject = oxNew(Article::class);
        $editObject->load($editObjectOxid);
        if ($editObject->isDerived()) {
            return;
        }

        $this->resetContentCache();

        $variantOxid = Registry::getRequest()->getRequestRawParameter('voxid');
        $variant = oxNew(Article::class);
        $variant->delete($variantOxid);
    }

    /**
     * Changes name of variant.
     */
    public function changename()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $this->resetContentCache();

        $oArticle = oxNew(Article::class);
        if ($soxId != "-1") {
            $oArticle->loadInLang($this->_iEditLang, $soxId);
        }

        $oArticle->setLanguage(0);
        $oArticle->assign($aParams);
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle->save();
    }


    /**
     * Add selection list
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addsel()
    {
        $oArticle = oxNew(Article::class);
        if ($oArticle->load($this->getEditObjectId())) {
            //Disable editing for derived articles
            if ($oArticle->isDerived()) {
                return;
            }

            $this->resetContentCache();

            if ($aSels = Registry::getRequest()->getRequestEscapedParameter('allsel')) {
                $oVariantHandler = oxNew(VariantHandler::class);
                $oVariantHandler->genVariantFromSell($aSels, $oArticle);
            }
        }
    }
}
