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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Application\Model\Vendor;
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
     * @deprecated Transitional during #107. Modules SHOULD override _getUrlExtension()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getUrlExtension() to the canonical override
      *             target and retires _getUrlExtension(); until then, _getUrlExtension() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getUrlExtension() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '.html';
    }

    /**
     * Returns target "extension" (.html)
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getUrlExtension(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getUrlExtension() the canonical override target.
     */
    protected function getUrlExtension()
    {
        return $this->_getUrlExtension();
    }

    /**
     * Checks if current article is in same language as preferred (language id passed by param).
     * In case languages are not the same - reloads article object in different language
     *
     * @param Article $oArticle article to check language
     * @param int                                         $iLang    user defined language id
     *
     * @return Article
     * @deprecated Transitional during #107. Modules SHOULD override _getProductForLang()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getProductForLang() to the canonical override
      *             target and retires _getProductForLang(); until then, _getProductForLang() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getProductForLang($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     * Checks if current article is in same language as preferred (language id passed by param).
     * In case languages are not the same - reloads article object in different language
     *
     * @param Article $oArticle article to check language
     * @param int                                         $iLang    user defined language id
     *
     * @return Article
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getProductForLang(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getProductForLang() the canonical override target.
     */
    protected function getProductForLang($oArticle, $iLang)
    {
        return $this->_getProductForLang($oArticle, $iLang);
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
     * @deprecated Transitional during #107. Modules SHOULD override _getListType()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getListType() to the canonical override
      *             target and retires _getListType(); until then, _getListType() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getListType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::getConfig()->getActiveView()->getListType();
    }

    /**
     * Returns active list type
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getListType(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getListType() the canonical override target.
     */
    protected function getListType()
    {
        return $this->_getListType();
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
     * @deprecated Transitional during #107. Modules SHOULD override _createArticleCategoryUri()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes createArticleCategoryUri() to the canonical override
      *             target and retires _createArticleCategoryUri(); until then, _createArticleCategoryUri() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _createArticleCategoryUri($oArticle, $oCategory, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     * create article uri for given category and save it
     *
     * @param Article $oArticle article object
     * @param Category $oCategory category object
     * @param int $iLang language to generate uri for
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _createArticleCategoryUri(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make createArticleCategoryUri() the canonical override target.
     */
    protected function createArticleCategoryUri($oArticle, $oCategory, $iLang)
    {
        return $this->_createArticleCategoryUri($oArticle, $oCategory, $iLang);
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
     * @deprecated Transitional during #107. Modules SHOULD override _getCategory()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getCategory() to the canonical override
      *             target and retires _getCategory(); until then, _getCategory() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getCategory($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     * Returns active category if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Category|null
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getCategory(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getCategory() the canonical override target.
     */
    protected function getCategory($oArticle, $iLang)
    {
        return $this->_getCategory($oArticle, $iLang);
    }

    /**
     * Returns products main category id
     *
     * @param Article $oArticle product
     *
     * @return Category
     * @throws DatabaseConnectionException
     * @deprecated Transitional during #107. Modules SHOULD override _getMainCategory()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getMainCategory() to the canonical override
      *             target and retires _getMainCategory(); until then, _getMainCategory() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getMainCategory($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oMainCat = null;

        // if variant parent id must be used
        $sArtId = $oArticle->getId();
        if (isset($oArticle->oxarticles__oxparentid->value) && $oArticle->oxarticles__oxparentid->value) {
            $sArtId = $oArticle->oxarticles__oxparentid->value;
        }

        $oDb = DatabaseProvider::getDb();
        $categoryViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');

        // add main category caching;
        $sQ = 'select oxcatnid from ' . $categoryViewName . ' where oxobjectid = :oxobjectid order by oxtime';
        $sIdent = md5($categoryViewName . $sArtId);

        if (($sMainCatId = $this->_loadFromCache($sIdent, 'oxarticle')) === false) {
            $sMainCatId = $oDb->getOne($sQ, [
                ':oxobjectid' => $sArtId,
            ]);
            // storing in cache
            $this->_saveInCache($sIdent, $sMainCatId, 'oxarticle');
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
     * Returns products main category id
     *
     * @param Article $oArticle product
     *
     * @return Category
     * @throws DatabaseConnectionException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getMainCategory(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getMainCategory() the canonical override target.
     */
    protected function getMainCategory($oArticle)
    {
        return $this->_getMainCategory($oArticle);
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
     * @deprecated Transitional during #107. Modules SHOULD override _prepareArticleTitle()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes prepareArticleTitle() to the canonical override
      *             target and retires _prepareArticleTitle(); until then, _prepareArticleTitle() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _prepareArticleTitle($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // create title part for uri
        if (!($sTitle = $oArticle->oxarticles__oxtitle->value)) {
            // taking parent article title
            if (($sParentId = $oArticle->oxarticles__oxparentid->value)) {
                // looking in cache ...
                if (!isset(self::$_aTitleCache[$sParentId])) {
                    $oDb = DatabaseProvider::getDb();
                    $sQ = 'select oxtitle from ' . $oArticle->getViewName() . ' where oxid = :oxid';
                    self::$_aTitleCache[$sParentId] = $oDb->getOne($sQ, [
                        ':oxid' => $sParentId,
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
     * Returns seo title for current article (if oxTitle field is empty, oxArtnum is used).
     * Additionally - if oxVarSelect is set - title is appended with its value
     *
     * @param Article $oArticle article object
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _prepareArticleTitle(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make prepareArticleTitle() the canonical override target.
     */
    protected function prepareArticleTitle($oArticle)
    {
        return $this->_prepareArticleTitle($oArticle);
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

                $aStdParams = ['cnid' => 'v_' . $oVendor->getId(), 'listtype' => $this->_getListType()];
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
     * @deprecated Transitional during #107. Modules SHOULD override _getVendor()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getVendor() to the canonical override
      *             target and retires _getVendor(); until then, _getVendor() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getVendor($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     * Returns active vendor if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Vendor|null
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getVendor(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getVendor() the canonical override target.
     */
    protected function getVendor($oArticle, $iLang)
    {
        return $this->_getVendor($oArticle, $iLang);
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
     * @deprecated Transitional during #107. Modules SHOULD override _getManufacturer()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getManufacturer() to the canonical override
      *             target and retires _getManufacturer(); until then, _getManufacturer() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getManufacturer($oArticle, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
     * Returns active manufacturer if available
     *
     * @param Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return Manufacturer|null
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getManufacturer(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getManufacturer() the canonical override target.
     */
    protected function getManufacturer($oArticle, $iLang)
    {
        return $this->_getManufacturer($oArticle, $iLang);
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
            ':oxobjectid' => $oArticle->getId(),
        ]);
        $oDb->execute('delete from oxobject2seodata where oxobjectid = :oxobjectid', [
            ':oxobjectid' => $oArticle->getId(),
        ]);
        $oDb->execute('delete from oxseohistory where oxobjectid = :oxobjectid', [
            ':oxobjectid' => $oArticle->getId(),
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
     * @deprecated Transitional during #107. Modules SHOULD override _getAltUri()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getAltUri() to the canonical override
      *             target and retires _getAltUri(); until then, _getAltUri() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getAltUri($sObjectId, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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

    /**
     * Returns alternative uri used while updating seo
     *
     * @param string $sObjectId object id
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getAltUri(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getAltUri() the canonical override target.
     */
    protected function getAltUri($sObjectId, $iLang)
    {
        return $this->_getAltUri($sObjectId, $iLang);
    }
}
