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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Seo encoder for articles
 *
 */
class SeoEncoderArticle extends SeoEncoder
{
    /**
     * Product parent title cache
     *
     * @var array
     */
    protected static $_aTitleCache = [];

    /**
     * Returns target "extension" (.html)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getUrlExtension" in next major
     */
    protected function _getUrlExtension() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getUrlExtension();
    }

    /**
     * Returns target "extension" (.html)
     *
     * @return string
     */
    protected function getUrlExtension()
    {
        return '.html';
    }

    /**
     * Checks if current article is in same language as preferred (language id passed by param).
     * In case languages are not the same - reloads article object in different language
     *
     * @param Article $oArticle article to check language
     * @param int                                         $iLang    user defined language id
     *
     * @return Article
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductForLang" in next major
     */
    protected function _getProductForLang($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getProductForLang($oArticle, $iLang);
    }

    /**
     * Checks if current article is in same language as preferred (language id passed by param).
     * In case languages are not the same - reloads article object in different language
     *
     * @param Article $oArticle article to check language
     * @param int                                         $iLang    user defined language id
     *
     * @return Article
     */
    protected function getProductForLang($oArticle, $iLang)
    {
        if (isset($iLang) && $iLang != $oArticle->getLanguage()) {
            $sId = $oArticle->getId();
            $oArticle = oxNew(Article::class);
            $oArticle->setSkipAssign(true);
            $oArticle->loadInLang($iLang, $sId);
        }

        return $oArticle;
    }

    /**
     * Returns SEO uri for passed article and active tag
     *
     * @param Article $oArticle article object
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     */
    public function getArticleRecommUri($oArticle, $iLang)
    {
        $sSeoUri = null;
        if ($oRecomm = $this->_getRecomm($oArticle, $iLang)) {
            //load details link from DB
            if (!($sSeoUri = $this->_loadFromDb('oxarticle', $oArticle->getId(), $iLang, null, $oRecomm->getId(), true))) {
                $oArticle = $this->_getProductForLang($oArticle, $iLang);

                // create title part for uri
                $sTitle = $this->_prepareArticleTitle($oArticle);

                // create uri for all categories
                $sSeoUri = Registry::get(SeoEncoderRecomm::class)->getRecommUri($oRecomm, $iLang);
                $sSeoUri = $this->_processSeoUrl($sSeoUri . $sTitle, $oArticle->getId(), $iLang);

                $aStdParams = ['recommid' => $oRecomm->getId(), 'listtype' => $this->_getListType()];
                $this->_saveToDb(
                    'oxarticle',
                    $oArticle->getId(),
                    Registry::getUtilsUrl()->appendUrl(
                        $oArticle->getBaseStdLink($iLang),
                        $aStdParams
                    ),
                    $sSeoUri,
                    $iLang,
                    null,
                    0,
                    $oRecomm->getId()
                );
            }
        }

        return $sSeoUri;
    }

    /**
     * Returns active recommendation list object if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return RecommendationList | null
     */
    protected function _getRecomm($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oList = null;
        $oView = Registry::getConfig()->getActiveView();
        if ($oView instanceof FrontendController) {
            $oList = $oView->getActiveRecommList();
        }

        return $oList;
    }

    /**
     * Returns active list type
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getListType" in next major
     */
    protected function _getListType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getListType();
    }

    /**
     * Returns active list type
     *
     * @return string
     */
    protected function getListType()
    {
        return Registry::getConfig()->getActiveView()->getListType();
    }

    /**
     * create article uri for given category and save it
     *
     * @param Article $oArticle article object
     * @param Category $oCategory category object
     * @param int $iLang language to generate uri for
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "createArticleCategoryUri" in next major
     */
    protected function _createArticleCategoryUri($oArticle, $oCategory, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->createArticleCategoryUri($oArticle, $oCategory, $iLang);
    }

    /**
     * create article uri for given category and save it
     *
     * @param Article $oArticle article object
     * @param Category $oCategory category object
     * @param int $iLang language to generate uri for
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function createArticleCategoryUri($oArticle, $oCategory, $iLang)
    {
        startProfile(__FUNCTION__);
        $oArticle = $this->_getProductForLang($oArticle, $iLang);

        // create title part for uri
        $sTitle = $this->_prepareArticleTitle($oArticle);

        // writing category path
        $sSeoUri = $this->_processSeoUrl(
            Registry::get(SeoEncoderCategory::class)->getCategoryUri($oCategory, $iLang) . $sTitle,
            $oArticle->getId(),
            $iLang
        );
        $sCatId = $oCategory->getId();
        $this->_saveToDb(
            'oxarticle',
            $oArticle->getId(),
            Registry::getUtilsUrl()->appendUrl(
                $oArticle->getBaseStdLink($iLang),
                ['cnid' => $sCatId]
            ),
            $sSeoUri,
            $iLang,
            null,
            0,
            $sCatId
        );

        stopProfile(__FUNCTION__);

        return $sSeoUri;
    }

    /**
     * Returns SEO uri for passed article
     *
     * @param Article $oArticle article object
     * @param int $iLang language id
     * @param bool $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getArticleUri($oArticle, $iLang, $blRegenerate = false)
    {
        startProfile(__FUNCTION__);

        $sActCatId = '';

        $oActCat = $this->_getCategory($oArticle, $iLang);

        if ($oActCat instanceof Category) {
            $sActCatId = $oActCat->getId();
        } elseif ($oActCat = $this->_getMainCategory($oArticle)) {
            $sActCatId = $oActCat->getId();
        }

        //load details link from DB
        if ($blRegenerate || !($sSeoUri = $this->_loadFromDb('oxarticle', $oArticle->getId(), $iLang, null, $sActCatId, true))) {
            if ($oActCat) {
                $blInCat = $oActCat->isPriceCategory()
                    ? $oArticle->inPriceCategory($sActCatId)
                    : $oArticle->inCategory($sActCatId);

                if ($blInCat) {
                    $sSeoUri = $this->_createArticleCategoryUri($oArticle, $oActCat, $iLang);
                }
            }
        }

        stopProfile(__FUNCTION__);

        return $sSeoUri;
    }

    /**
     * Returns active category if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Category|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategory" in next major
     */
    protected function _getCategory($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getCategory($oArticle, $iLang);
    }

    /**
     * Returns active category if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Category|null
     */
    protected function getCategory($oArticle, $iLang)
    {
        $oCat = null;
        $oView = Registry::getConfig()->getActiveView();
        if ($oView instanceof FrontendController) {
            $oCat = $oView->getActiveCategory();
        } elseif ($oView instanceof BaseController) {
            $oCat = $oView->getActCategory();
        }

        return $oCat;
    }

    /**
     * Returns products main category id
     *
     * @param Article $oArticle product
     *
     * @return Category
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMainCategory" in next major
     */
    protected function _getMainCategory($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getMainCategory($oArticle);
    }

    /**
     * Returns products main category id
     *
     * @param Article $oArticle product
     *
     * @return Category
     * @throws DatabaseConnectionException
     */
    protected function getMainCategory($oArticle)
    {
        $oMainCat = null;

        // if variant parent id must be used
        $sArtId = $oArticle->getId();
        if (isset($oArticle->oxarticles__oxparentid->value) && $oArticle->oxarticles__oxparentid->value) {
            $sArtId = $oArticle->oxarticles__oxparentid->value;
        }

        $oDb = DatabaseProvider::getDb();
        $categoryViewName = Registry::get(TableViewNameGenerator::class)->getViewName("oxobject2category");

        // add main category caching;
        $sQ = "select oxcatnid from " . $categoryViewName . " where oxobjectid = :oxobjectid order by oxtime";
        $sIdent = md5($categoryViewName . $sArtId);

        if (($sMainCatId = $this->_loadFromCache($sIdent, "oxarticle")) === false) {
            $sMainCatId = $oDb->getOne($sQ, [
                ':oxobjectid' => $sArtId
            ]);
            // storing in cache
            $this->_saveInCache($sIdent, $sMainCatId, "oxarticle");
        }

        if ($sMainCatId) {
            $oMainCat = oxNew(Category::class);
            if (!$oMainCat->load($sMainCatId)) {
                $oMainCat = null;
            }
        }

        return $oMainCat;
    }

    /**
     * Returns SEO uri for passed article
     *
     * @param Article $oArticle article object
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getArticleMainUri($oArticle, $iLang)
    {
        startProfile(__FUNCTION__);

        $oMainCat = $this->_getMainCategory($oArticle);
        $sMainCatId = $oMainCat ? $oMainCat->getId() : '';

        //load default article url from DB
        if (!($sSeoUri = $this->_loadFromDb('oxarticle', $oArticle->getId(), $iLang, null, $sMainCatId, true))) {
            // save for main category
            if ($oMainCat) {
                $sSeoUri = $this->_createArticleCategoryUri($oArticle, $oMainCat, $iLang);
            } else {
                // get default article url
                $oArticle = $this->_getProductForLang($oArticle, $iLang);
                $sSeoUri = $this->_processSeoUrl($this->_prepareArticleTitle($oArticle), $oArticle->getId(), $iLang);

                // save default article url
                $this->_saveToDb(
                    'oxarticle',
                    $oArticle->getId(),
                    $oArticle->getBaseStdLink($iLang),
                    $sSeoUri,
                    $iLang,
                    null,
                    0,
                    ''
                );
            }
        }

        stopProfile(__FUNCTION__);

        return $sSeoUri;
    }

    /**
     * Returns seo title for current article (if oxTitle field is empty, oxArtnum is used).
     * Additionally - if oxVarSelect is set - title is appended with its value
     *
     * @param Article $oArticle article object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareArticleTitle" in next major
     */
    protected function _prepareArticleTitle($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->prepareArticleTitle($oArticle);
    }

    /**
     * Returns seo title for current article (if oxTitle field is empty, oxArtnum is used).
     * Additionally - if oxVarSelect is set - title is appended with its value
     *
     * @param Article $oArticle article object
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareArticleTitle($oArticle)
    {
        // create title part for uri
        if (!($sTitle = $oArticle->oxarticles__oxtitle->value)) {
            // taking parent article title
            if (($sParentId = $oArticle->oxarticles__oxparentid->value)) {
                // looking in cache ...
                if (!isset(self::$_aTitleCache[$sParentId])) {
                    $oDb = DatabaseProvider::getDb();
                    $sQ = "select oxtitle from " . $oArticle->getViewName() . " where oxid = :oxid";
                    self::$_aTitleCache[$sParentId] = $oDb->getOne($sQ, [
                        ':oxid' => $sParentId
                    ]);
                }
                $sTitle = self::$_aTitleCache[$sParentId];
            }
        }

        // variant has varselect value
        if ($oArticle->oxarticles__oxvarselect->value) {
            $sTitle .= ($sTitle ? ' ' : '') . $oArticle->oxarticles__oxvarselect->value . ' ';
        } elseif (!$sTitle || ($oArticle->oxarticles__oxparentid->value)) {
            // in case nothing was found - looking for number
            $sTitle .= ($sTitle ? ' ' : '') . $oArticle->oxarticles__oxartnum->value;
        }

        return $this->_prepareTitle($sTitle, false, $oArticle->getLanguage()) . $this->_getUrlExtension();
    }

    /**
     * Returns vendor seo uri for current article
     *
     * @param Article $oArticle article object
     * @param int $iLang language id
     * @param bool $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getArticleVendorUri($oArticle, $iLang, $blRegenerate = false)
    {
        startProfile(__FUNCTION__);

        $sSeoUri = null;
        if ($oVendor = $this->_getVendor($oArticle, $iLang)) {
            //load details link from DB
            if ($blRegenerate || !($sSeoUri = $this->_loadFromDb('oxarticle', $oArticle->getId(), $iLang, null, $oVendor->getId(), true))) {
                $oArticle = $this->_getProductForLang($oArticle, $iLang);

                // create title part for uri
                $sTitle = $this->_prepareArticleTitle($oArticle);

                // create uri for all categories
                $sSeoUri = Registry::get(SeoEncoderVendor::class)->getVendorUri($oVendor, $iLang);
                $sSeoUri = $this->_processSeoUrl($sSeoUri . $sTitle, $oArticle->getId(), $iLang);

                $aStdParams = ['cnid' => "v_" . $oVendor->getId(), 'listtype' => $this->_getListType()];
                $this->_saveToDb(
                    'oxarticle',
                    $oArticle->getId(),
                    Registry::getUtilsUrl()->appendUrl(
                        $oArticle->getBaseStdLink($iLang),
                        $aStdParams
                    ),
                    $sSeoUri,
                    $iLang,
                    null,
                    0,
                    $oVendor->getId()
                );
            }

            stopProfile(__FUNCTION__);
        }

        return $sSeoUri;
    }

    /**
     * Returns active vendor if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Vendor|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVendor" in next major
     */
    protected function _getVendor($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getVendor($oArticle, $iLang);
    }

    /**
     * Returns active vendor if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Vendor|null
     */
    protected function getVendor($oArticle, $iLang)
    {
        $oView = Registry::getConfig()->getActiveView();

        $oVendor = null;
        if ($sActVendorId = $oArticle->oxarticles__oxvendorid->value) {
            if ($oView instanceof FrontendController && ($oActVendor = $oView->getActVendor())) {
                $oVendor = $oActVendor;
            } else {
                $oVendor = oxNew(Vendor::class);
            }
            if ($oVendor->getId() !== $sActVendorId) {
                $oVendor = oxNew(Vendor::class);
                if (!$oVendor->loadInLang($iLang, $sActVendorId)) {
                    $oVendor = null;
                }
            }
        }

        return $oVendor;
    }

    /**
     * Returns manufacturer seo uri for current article
     *
     * @param Article $oArticle article object
     * @param int $iLang language id
     * @param bool $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getArticleManufacturerUri($oArticle, $iLang, $blRegenerate = false)
    {
        $sSeoUri = null;
        startProfile(__FUNCTION__);
        if ($oManufacturer = $this->_getManufacturer($oArticle, $iLang)) {
            //load details link from DB
            if ($blRegenerate || !($sSeoUri = $this->_loadFromDb('oxarticle', $oArticle->getId(), $iLang, null, $oManufacturer->getId(), true))) {
                $oArticle = $this->_getProductForLang($oArticle, $iLang);

                // create title part for uri
                $sTitle = $this->_prepareArticleTitle($oArticle);

                // create uri for all categories
                $sSeoUri = Registry::get(SeoEncoderManufacturer::class)->getManufacturerUri($oManufacturer, $iLang);
                $sSeoUri = $this->_processSeoUrl($sSeoUri . $sTitle, $oArticle->getId(), $iLang);

                $aStdParams = ['mnid' => $oManufacturer->getId(), 'listtype' => $this->_getListType()];
                $this->_saveToDb(
                    'oxarticle',
                    $oArticle->getId(),
                    Registry::getUtilsUrl()->appendUrl(
                        $oArticle->getBaseStdLink($iLang),
                        $aStdParams
                    ),
                    $sSeoUri,
                    $iLang,
                    null,
                    0,
                    $oManufacturer->getId()
                );
            }

            stopProfile(__FUNCTION__);
        }

        return $sSeoUri;
    }

    /**
     * Returns active manufacturer if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Manufacturer|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getManufacturer" in next major
     */
    protected function _getManufacturer($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getManufacturer($oArticle, $iLang);
    }

    /**
     * Returns active manufacturer if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Manufacturer|null
     */
    protected function getManufacturer($oArticle, $iLang)
    {
        $oManufacturer = null;
        if ($sActManufacturerId = $oArticle->oxarticles__oxmanufacturerid->value) {
            $oView = Registry::getConfig()->getActiveView();

            if ($oView instanceof FrontendController && ($oActManufacturer = $oView->getActManufacturer())) {
                $oManufacturer = $oActManufacturer;
            } else {
                $oManufacturer = oxNew(Manufacturer::class);
            }

            if ($oManufacturer->getId() !== $sActManufacturerId || $oManufacturer->getLanguage() != $iLang) {
                $oManufacturer = oxNew(Manufacturer::class);
                if (!$oManufacturer->loadInLang($iLang, $sActManufacturerId)) {
                    $oManufacturer = null;
                }
            }
        }

        return $oManufacturer;
    }

    /**
     * return article main url, with path of its default category
     *
     * @param Article $oArticle product
     * @param null $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getArticleMainUrl($oArticle, $iLang = null)
    {
        if (!isset($iLang)) {
            $iLang = $oArticle->getLanguage();
        }

        return $this->_getFullUrl($this->getArticleMainUri($oArticle, $iLang), $iLang);
    }

    /**
     * Encodes article URLs into SEO format
     *
     * @param Article $oArticle Article object
     * @param null $iLang language
     * @param int $iType type
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getArticleUrl($oArticle, $iLang = null, $iType = 0)
    {
        if (!isset($iLang)) {
            $iLang = $oArticle->getLanguage();
        }

        $sUri = null;
        switch ($iType) {
            case OXARTICLE_LINKTYPE_VENDOR:
                $sUri = $this->getArticleVendorUri($oArticle, $iLang);
                break;
            case OXARTICLE_LINKTYPE_MANUFACTURER:
                $sUri = $this->getArticleManufacturerUri($oArticle, $iLang);
                break;
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            case OXARTICLE_LINKTYPE_RECOMM:
                $sUri = $this->getArticleRecommUri($oArticle, $iLang);
                break;
            // END deprecated
            case OXARTICLE_LINKTYPE_PRICECATEGORY: // goes price category urls to default (category urls)
            default:
                $sUri = $this->getArticleUri($oArticle, $iLang);
                break;
        }

        // if was unable to fetch type uri - returning main
        if (!$sUri) {
            $sUri = $this->getArticleMainUri($oArticle, $iLang);
        }

        return $this->_getFullUrl($sUri, $iLang);
    }

    /**
     * deletes article seo entries
     *
     * @param Article $oArticle article to remove
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function onDeleteArticle($oArticle)
    {
        $oDb = DatabaseProvider::getDb();
        $oDb->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxarticle'", [
            ':oxobjectid' => $oArticle->getId()
        ]);
        $oDb->execute("delete from oxobject2seodata where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $oArticle->getId()
        ]);
        $oDb->execute("delete from oxseohistory where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $oArticle->getId()
        ]);
    }

    /**
     * Returns alternative uri used while updating seo
     *
     * @param string $sObjectId object id
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAltUri" in next major
     */
    protected function _getAltUri($sObjectId, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getAltUri($sObjectId, $iLang);
    }

    /**
     * Returns alternative uri used while updating seo
     *
     * @param string $sObjectId object id
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function getAltUri($sObjectId, $iLang)
    {
        $sSeoUrl = null;
        $oArticle = oxNew(Article::class);
        $oArticle->setSkipAssign(true);
        if ($oArticle->loadInLang($iLang, $sObjectId)) {
            // choosing URI type to generate
            switch ($this->_getListType()) {
                case 'vendor':
                    $sSeoUrl = $this->getArticleVendorUri($oArticle, $iLang, true);
                    break;
                case 'manufacturer':
                    $sSeoUrl = $this->getArticleManufacturerUri($oArticle, $iLang, true);
                    break;
                default:
                    $sSeoUrl = $this->getArticleUri($oArticle, $iLang, true);
                    break;
            }
        }

        return $sSeoUrl;
    }   
}
