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

use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Seo encoder category
 */
class SeoEncoderCategory extends \OxidEsales\Eshop\Core\SeoEncoder
{
    /** @var array _aCatCache cache for categories. */
    protected $_aCatCache = [];

    /**
     * Returns target "extension" (/)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getUrlExtension" in next major
     */
    protected function _getUrlExtension() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '/';
    }

    /**
     * _categoryUrlLoader loads category from db
     * returns false if cat needs to be encoded (load failed)
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat  category object
     * @param int                                          $iLang active language id
     *
     * @access protected
     *
     * @return boolean
     * @deprecated underscore prefix violates PSR12, will be renamed to "categoryUrlLoader" in next major
     */
    protected function _categoryUrlLoader($oCat, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCacheId = $this->_getCategoryCacheId($oCat, $iLang);
        if (isset($this->_aCatCache[$sCacheId])) {
            $sSeoUrl = $this->_aCatCache[$sCacheId];
        } elseif (($sSeoUrl = $this->_loadFromDb('oxcategory', $oCat->getId(), $iLang))) {
            // caching
            $this->_aCatCache[$sCacheId] = $sSeoUrl;
        }

        return $sSeoUrl;
    }

    /**
     * _getCatecgoryCacheId return string for isntance cache id
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat  category object
     * @param int                                          $iLang active language
     *
     * @access private
     *
     * @return string
     */
    private function _getCategoryCacheId($oCat, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $oCat->getId() . '_' . ((int) $iLang);
    }

    /**
     * Returns SEO uri for passed category
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat         category object
     * @param int                                          $iLang        language
     * @param bool                                         $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function getCategoryUri($oCat, $iLang = null, $blRegenerate = false)
    {
        startProfile(__FUNCTION__);
        $sCatId = $oCat->getId();

        // skipping external category URLs
        if ($oCat->oxcategories__oxextlink->value) {
            $sSeoUrl = null;
        } else {
            // not found in cache, process it from the top
            if (!isset($iLang)) {
                $iLang = $oCat->getLanguage();
            }

            $aCacheMap = [];
            $aStdLinks = [];

            while ($oCat && !($sSeoUrl = $this->_categoryUrlLoader($oCat, $iLang))) {
                if ($iLang != $oCat->getLanguage()) {
                    $sId = $oCat->getId();
                    $oCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
                    $oCat->loadInLang($iLang, $sId);
                }

                // prepare oCat title part
                $sTitle = $this->_prepareTitle($oCat->oxcategories__oxtitle->value, false, $oCat->getLanguage());

                foreach (array_keys($aCacheMap) as $id) {
                    $aCacheMap[$id] = $sTitle . '/' . $aCacheMap[$id];
                }

                $aCacheMap[$oCat->getId()] = $sTitle;
                $aStdLinks[$oCat->getId()] = $oCat->getBaseStdLink($iLang);

                // load parent
                $oCat = $oCat->getParentCategory();
            }

            foreach ($aCacheMap as $sId => $sUri) {
                $this->_aCatCache[$sId . '_' . $iLang] = $this->_processSeoUrl($sSeoUrl . $sUri . '/', $sId, $iLang);
                $this->_saveToDb('oxcategory', $sId, $aStdLinks[$sId], $this->_aCatCache[$sId . '_' . $iLang], $iLang);
            }

            $sSeoUrl = $this->_aCatCache[$sCatId . '_' . $iLang];
        }

        stopProfile(__FUNCTION__);

        return $sSeoUrl;
    }

    /**
     * Returns category SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $category   Category object.
     * @param int                                          $pageNumber Number of the page which should be prepared.
     * @param int                                          $languageId Language id.
     * @param bool                                         $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    public function getCategoryPageUrl($category, $pageNumber, $languageId = null, $isFixed = null)
    {
        if (!isset($languageId)) {
            $languageId = $category->getLanguage();
        }
        $stdUrl = $category->getBaseStdLink($languageId);
        $parameters = null;

        $stdUrl = $this->_trimUrl($stdUrl, $languageId);
        $seoUrl = $this->getCategoryUri($category, $languageId);

        if ($isFixed === null) {
            $isFixed = $this->_isFixed('oxcategory', $category->getId(), $languageId);
        }

        return $this->assembleFullPageUrl($category, 'oxcategory', $stdUrl, $seoUrl, $pageNumber, $parameters, $languageId, $isFixed);
    }

    /**
     * Category URL encoder. If category has external URLs, skip encoding
     * for this category. If SEO id is not set, generates and saves SEO id
     * for category (\OxidEsales\Eshop\Core\SeoEncoder::_getSeoId()).
     * If category has subcategories, it iterates through them.
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory Category object
     * @param int                                          $iLang     Language
     *
     * @return string
     */
    public function getCategoryUrl($oCategory, $iLang = null)
    {
        $sUrl = '';
        if (!isset($iLang)) {
            $iLang = $oCategory->getLanguage();
        }
        // category may have specified url
        if (($sSeoUrl = $this->getCategoryUri($oCategory, $iLang))) {
            $sUrl = $this->_getFullUrl($sSeoUrl, $iLang);
        }

        return $sUrl;
    }

    /**
     * Marks related to category objects as expired
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory Category object
     */
    public function markRelatedAsExpired($oCategory)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        // select it from table instead of using object carrying value
        // this is because this method is usually called inside update,
        // where object may already be carrying changed id
        $aCatInfo = $oDb->getAll("select oxrootid, oxleft, oxright from oxcategories where oxid = :oxid limit 1", [
            ':oxid' => $oCategory->getId()
        ]);

        // update sub cats
        $sQ = "update oxseo as seo1, (select oxid from oxcategories 
            where oxrootid = :oxrootid 
            and oxleft > :oxleft 
            and oxright < :oxright ) as seo2 
                set seo1.oxexpired = '1' where seo1.oxtype = 'oxcategory' and seo1.oxobjectid = seo2.oxid";
        $oDb->execute($sQ, [
            ':oxrootid' => $aCatInfo[0][0],
            ':oxleft' => (int) $aCatInfo[0][1],
            ':oxright' => (int) $aCatInfo[0][2]
        ]);

        // update subarticles
        $sQ = "update oxseo as seo1, (select distinct o2c.oxobjectid as id from oxcategories as cat left join oxobject2category "
              . "as o2c on o2c.oxcatnid=cat.oxid where cat.oxrootid = :oxrootid and cat.oxleft >= :oxleft "
              . "and cat.oxright <= :oxright) as seo2 "
              . "set seo1.oxexpired = '1' where seo1.oxtype = 'oxarticle' and seo1.oxobjectid = seo2.id "
              . "and seo1.oxfixed = 0";
        $oDb->execute($sQ, [
            ':oxrootid' => $aCatInfo[0][0],
            ':oxleft' => (int) $aCatInfo[0][1],
            ':oxright' => (int) $aCatInfo[0][2]
        ]);
    }

    /**
     * @param Category $category
     */
    public function onDeleteCategory($category)
    {
        $this->setRelatedToCategorySeoUrlsAsExpired($category);

        $database = DatabaseProvider::getDb();

        $database->execute("delete from oxseo where oxseo.oxtype = 'oxarticle' and oxseo.oxparams = :oxparams", [
            ':oxparams' => $category->getId()
        ]);
        $database->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxcategory'", [
            ':oxobjectid' => $category->getId()
        ]);
        $database->execute("delete from oxobject2seodata where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $category->getId()
        ]);
        $database->execute("delete from oxseohistory where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $category->getId()
        ]);
    }

    /**
     * Returns alternative uri used while updating seo
     *
     * @param string $sObjectId object id
     * @param int    $iLang     language id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAltUri" in next major
     */
    protected function _getAltUri($sObjectId, $iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSeoUrl = null;
        $oCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
        if ($oCat->loadInLang($iLang, $sObjectId)) {
            $sSeoUrl = $this->getCategoryUri($oCat, $iLang);
        }

        return $sSeoUrl;
    }

    private function setRelatedToCategorySeoUrlsAsExpired(Category $category): void
    {
        $sql = "
            select oxident
            from oxseo
            where oxseo.oxseourl like concat((select oxseourl from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxcategory'), '%') 
              and (oxtype = 'oxcategory' or oxtype = 'oxarticle')
          ";

        $result = DatabaseProvider::getDb()->select($sql, [':oxobjectid' => $category->getId()]);

        $urlIdents = [];
        foreach ($result->fetchAll() as $row)
        {
            $urlIdents[] = $row[0];
        }

        DatabaseProvider::getDb()->execute("update oxseo set oxseo.oxexpired=1 where oxseo.oxident in ('" . implode("','", $urlIdents) . "')");
    }
}
