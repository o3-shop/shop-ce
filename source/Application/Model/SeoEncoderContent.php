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

use oxRegistry;
use oxDb;

/**
 * Seo encoder base
 */
class SeoEncoderContent extends \OxidEsales\Eshop\Core\SeoEncoder
{
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
     * Returns SEO uri for content object. Includes parent category path info if
     * content is assigned to it
     *
     * @param \OxidEsales\Eshop\Application\Model\Content $oCont        content category object
     * @param int                                         $iLang        language
     * @param bool                                        $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function getContentUri($oCont, $iLang = null, $blRegenerate = false)
    {
        if (!isset($iLang)) {
            $iLang = $oCont->getLanguage();
        }
        //load details link from DB
        if ($blRegenerate || !($sSeoUrl = $this->_loadFromDb('oxContent', $oCont->getId(), $iLang))) {
            if ($iLang != $oCont->getLanguage()) {
                $sId = $oCont->getId();
                $oCont = oxNew(\OxidEsales\Eshop\Application\Model\Content::class);
                $oCont->loadInLang($iLang, $sId);
            }

            $sSeoUrl = '';
            if ($oCont->getCategoryId() && $oCont->getType() === 2) {
                $oCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
                if ($oCat->loadInLang($iLang, $oCont->oxcontents__oxcatid->value)) {
                    $sParentId = $oCat->oxcategories__oxparentid->value;
                    if ($sParentId && $sParentId != 'oxrootid') {
                        $oParentCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
                        if ($oParentCat->loadInLang($iLang, $oCat->oxcategories__oxparentid->value)) {
                            $sSeoUrl .= \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Application\Model\SeoEncoderCategory::class)->getCategoryUri($oParentCat);
                        }
                    }
                }
            }

            $sSeoUrl .= $this->_prepareTitle($oCont->oxcontents__oxtitle->value, false, $oCont->getLanguage()) . '/';
            $sSeoUrl = $this->_processSeoUrl($sSeoUrl, $oCont->getId(), $iLang);

            $this->_saveToDb('oxcontent', $oCont->getId(), $oCont->getBaseStdLink($iLang), $sSeoUrl, $iLang);
        }

        return $sSeoUrl;
    }

    /**
     * encodeContentUrl encodes content link
     *
     * @param \OxidEsales\Eshop\Application\Model\Content $oCont category object
     * @param int                                         $iLang language
     *
     * @return string|bool
     */
    public function getContentUrl($oCont, $iLang = null)
    {
        if (!isset($iLang)) {
            $iLang = $oCont->getLanguage();
        }

        return $this->_getFullUrl($this->getContentUri($oCont, $iLang), $iLang);
    }

    /**
     * deletes content seo entries
     *
     * @param string $sId content ids
     */
    public function onDeleteContent($sId)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oDb->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxcontent'", [
            ':oxobjectid' => $sId
        ]);
        $oDb->execute("delete from oxobject2seodata where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $sId
        ]);
        $oDb->execute("delete from oxseohistory where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $sId
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
        $oCont = oxNew(\OxidEsales\Eshop\Application\Model\Content::class);
        if ($oCont->loadInLang($iLang, $sObjectId)) {
            $sSeoUrl = $this->getContentUri($oCont, $iLang, true);
        }

        return $sSeoUrl;
    }
}
