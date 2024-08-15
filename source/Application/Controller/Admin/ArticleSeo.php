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

use OxidEsales\Eshop\Application\Controller\Admin\ObjectSeo;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Application\Model\SeoEncoderArticle;
use OxidEsales\Eshop\Application\Model\SeoEncoderCategory;
use OxidEsales\Eshop\Application\Model\Vendor;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article seo config class
 */
class ArticleSeo extends ObjectSeo
{
    /**
     * Chosen category id
     *
     * @var string
     */
    protected $_sActCatId = null;

    /**
     * Product selections (categories, vendors etc assigned)
     *
     * @var array
     */
    protected $_aSelectionList = null;

    /**
     * Returns active selection type - oxcategory, oxmanufacturer, oxvendor
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getActCatType()
    {
        $sType = false;
        $aData = Registry::getRequest()->getRequestEscapedParameter('aSeoData');
        if ($aData && isset($aData["oxparams"])) {
            $oStr = getStr();
            $iEndPos = $oStr->strpos($aData["oxparams"], "#");
            $sType = $oStr->substr($aData["oxparams"], 0, $iEndPos);
        } elseif ($aList = $this->getSelectionList()) {
            reset($aList);
            $sType = key($aList);
        }

        return $sType;
    }

    /**
     * Returns active category (manufacturer/vendor) language id
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getActCatLang()
    {
        if (Registry::getRequest()->getRequestEscapedParameter('editlanguage') !== null) {
            return $this->_iEditLang;
        }

        $iLang = false;
        $aData = Registry::getRequest()->getRequestEscapedParameter('aSeoData');
        if ($aData && isset($aData["oxparams"])) {
            $oStr = getStr();
            $iStartPos = $oStr->strpos($aData["oxparams"], "#");
            $iEndPos = $oStr->strpos($aData["oxparams"], "#", $iStartPos + 1);
            $iLang = $oStr->substr($aData["oxparams"], $iEndPos + 1);
        } elseif ($aList = $this->getSelectionList()) {
            $aList = reset($aList);
            $iLang = key($aList);
        }

        return (int) $iLang;
    }

    /**
     * Returns active category (manufacturer/vendor) id
     *
     * @return false|string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getActCatId()
    {
        $sId = false;
        $aData = Registry::getRequest()->getRequestEscapedParameter('aSeoData');
        if ($aData && isset($aData["oxparams"])) {
            $oStr = getStr();
            $iStartPos = $oStr->strpos($aData["oxparams"], "#");
            $iEndPos = $oStr->strpos($aData["oxparams"], "#", $iStartPos + 1);
            $iLen = $oStr->strlen($aData["oxparams"]);

            $sId = $oStr->substr($aData["oxparams"], $iStartPos + 1, $iEndPos - $iLen);
        } elseif ($aList = $this->getSelectionList()) {
            $oItem = reset($aList[$this->getActCatType()][$this->getActCatLang()]);

            $sId = $oItem->getId();
        }

        return $sId;
    }

    /**
     * Returns product selections array [type][language] (categories, vendors etc. assigned)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getSelectionList()
    {
        if ($this->_aSelectionList === null) {
            $this->_aSelectionList = [];

            $oProduct = oxNew(Article::class);
            $oProduct->load($this->getEditObjectId());

            if ($oCatList = $this->_getCategoryList($oProduct)) {
                $this->_aSelectionList["oxcategory"][$this->_iEditLang] = $oCatList;
            }

            if ($oVndList = $this->_getVendorList($oProduct)) {
                $this->_aSelectionList["oxvendor"][$this->_iEditLang] = $oVndList;
            }

            if ($oManList = $this->_getManufacturerList($oProduct)) {
                $this->_aSelectionList["oxmanufacturer"][$this->_iEditLang] = $oManList;
            }
        }

        return $this->_aSelectionList;
    }

    /**
     * Returns array of product categories
     *
     * @param Article $oArticle Article object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategoryList" in next major
     */
    protected function _getCategoryList($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sMainCatId = false;
        if ($oMainCat = $oArticle->getCategory()) {
            $sMainCatId = $oMainCat->getId();
        }

        $aCatList = [];
        $iLang = $this->getEditLang();

        // adding categories
        $sView = getViewName('oxobject2category');
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sSqlForPriceCategories = $oArticle->getSqlForPriceCategories('oxid');
        $sQ = "select oxobject2category.oxcatnid as oxid from {$sView} as oxobject2category " .
              "where oxobject2category.oxobjectid = :oxobjectid union " . $sSqlForPriceCategories;

        $oRs = $oDb->select($sQ, [
            ':oxobjectid' => $oArticle->getId()
        ]);
        if ($oRs && $oRs->count() > 0) {
            while (!$oRs->EOF) {
                $oCat = oxNew(Category::class);
                if ($oCat->loadInLang($iLang, current($oRs->fields))) {
                    if ($sMainCatId == $oCat->getId()) {
                        $sSuffix = Registry::getLang()->translateString('(main category)', $this->getEditLang());
                        $sTitleField = 'oxcategories__oxtitle';
                        $sTitle = $oCat->$sTitleField->getRawValue() . " " . $sSuffix;
                        $oCat->$sTitleField = new Field($sTitle, Field::T_RAW);
                    }
                    $aCatList[] = $oCat;
                }
                $oRs->fetchRow();
            }
        }

        return $aCatList;
    }

    /**
     * Returns array containing product vendor object
     *
     * @param Article $oArticle Article object
     *
     * @return array|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVendorList" in next major
     */
    protected function _getVendorList($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($oArticle->oxarticles__oxvendorid->value) {
            $oVendor = oxNew(Vendor::class);
            if ($oVendor->loadInLang($this->getEditLang(), $oArticle->oxarticles__oxvendorid->value)) {
                return [$oVendor];
            }
        }
    }

    /**
     * Returns array containing product manufacturer object
     *
     * @param Article $oArticle Article object
     *
     * @return array|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getManufacturerList" in next major
     */
    protected function _getManufacturerList($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($oArticle->oxarticles__oxmanufacturerid->value) {
            $oManufacturer = oxNew(Manufacturer::class);
            if ($oManufacturer->loadInLang($this->getEditLang(), $oArticle->oxarticles__oxmanufacturerid->value)) {
                return [$oManufacturer];
            }
        }
    }

    /**
     * Returns active category object, used for seo url getter
     *
     * @return Category | null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getActCategory()
    {
        $oCat = oxNew(Category::class);

        return ($oCat->load($this->getActCatId())) ? $oCat : null;
    }

    /**
     * Returns active vendor object if available
     *
     * @return Vendor | null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getActVendor()
    {
        $oVendor = oxNew(Vendor::class);

        return ($this->getActCatType() == 'oxvendor' && $oVendor->load($this->getActCatId())) ? $oVendor : null;
    }

    /**
     * Returns active manufacturer object if available
     *
     * @return Manufacturer | null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getActManufacturer()
    {
        $oManufacturer = oxNew(Manufacturer::class);
        $blLoaded = $this->getActCatType() == 'oxmanufacturer' && $oManufacturer->load($this->getActCatId());

        return ($blLoaded) ? $oManufacturer : null;
    }

    /**
     * Returns list type for current seo url
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getListType()
    {
        switch ($this->getActCatType()) {
            case 'oxvendor':
                return 'vendor';
            case 'oxmanufacturer':
                return 'manufacturer';
        }
    }

    /**
     * Returns editable object language id
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getEditLang()
    {
        return $this->getActCatLang();
    }

    /**
     * Returns alternative seo entry id
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAltSeoEntryId" in next major
     */
    protected function _getAltSeoEntryId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getEditObjectId();
    }

    /**
     * Returns url type
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getType" in next major
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'oxarticle';
    }

    /**
     * Processes parameter before writing to db
     *
     * @param string $sParam parameter to process
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function processParam($sParam)
    {
        return $this->getActCatId();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderCategory
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEncoder" in next major
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::get(SeoEncoderArticle::class);
    }

    /**
     * Returns seo uri
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getEntryUri()
    {
        $product = oxNew(Article::class);

        if ($product->load($this->getEditObjectId())) {
            $seoEncoder = $this->_getEncoder();

            switch ($this->getActCatType()) {
                case 'oxvendor':
                    return $seoEncoder->getArticleVendorUri($product, $this->getEditLang());
                case 'oxmanufacturer':
                    return $seoEncoder->getArticleManufacturerUri($product, $this->getEditLang());
                default:
                    if ($this->getActCatId()) {
                        return $seoEncoder->getArticleUri($product, $this->getEditLang());
                    } else {
                        return $seoEncoder->getArticleMainUri($product, $this->getEditLang());
                    }
            }
        }
    }

    /**
     * Returns TRUE, as this view support category selector
     *
     * @return bool
     */
    public function showCatSelect()
    {
        return true;
    }

    /**
     * Returns id of object which must be saved
     *
     * @deprecated since v6.0.0 (2017-12-05); Use getEditObjectId() instead.
     *
     * @return string
     */
    protected function _getSaveObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getEditObjectId();
    }

    /**
     * Returns TRUE if current seo entry has fixed state
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function isEntryFixed()
    {
        $sId = $this->_getSaveObjectId();
        $iLang = (int) $this->getEditLang();
        $iShopId = Registry::getConfig()->getShopId();
        $sParam = $this->processParam($this->getActCatId());

        $sQ = "select oxfixed from oxseo where
                   oxseo.oxobjectid = :oxobjectid and
                   oxseo.oxshopid = :oxshopid and oxseo.oxlang = :oxlang and oxparams = :oxparams";

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (bool) DatabaseProvider::getMaster()->getOne($sQ, [
            ':oxobjectid' => $sId,
            ':oxshopid' => $iShopId,
            ':oxlang' => $iLang,
            ':oxparams' => $sParam
        ]);
    }
}
