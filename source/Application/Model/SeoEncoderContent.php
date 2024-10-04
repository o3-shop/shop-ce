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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;

/**
 * Seo encoder base
 */
class SeoEncoderContent extends SeoEncoder
{
    /**
     * Returns target "extension" (/)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getUrlExtension" in next major
     */
    protected function _getUrlExtension() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getUrlExtension();
    }

    /**
     * Returns target "extension" (/)
     *
     * @return string
     */
    protected function getUrlExtension()
    {
        return '/';
    }

    /**
     * Returns SEO uri for content object. Includes parent category path info if
     * content is assigned to it
     *
     * @param Content $oCont content category object
     * @param null $iLang language
     * @param bool $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     * @throws DatabaseConnectionException
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
                $oCont = oxNew(Content::class);
                $oCont->loadInLang($iLang, $sId);
            }

            $sSeoUrl = '';
            if ($oCont->getCategoryId() && $oCont->getType() === 2) {
                $oCat = oxNew(Category::class);
                if ($oCat->loadInLang($iLang, $oCont->oxcontents__oxcatid->value)) {
                    $sParentId = $oCat->oxcategories__oxparentid->value;
                    if ($sParentId && $sParentId != 'oxrootid') {
                        $oParentCat = oxNew(Category::class);
                        if ($oParentCat->loadInLang($iLang, $oCat->oxcategories__oxparentid->value)) {
                            $sSeoUrl .= Registry::get(SeoEncoderCategory::class)->getCategoryUri($oParentCat);
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
     * @param Content $oCont category object
     * @param null $iLang language
     *
     * @return string|bool
     * @throws DatabaseConnectionException
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function onDeleteContent($sId)
    {
        $oDb = DatabaseProvider::getDb();
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
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
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
     */
    protected function getAltUri($sObjectId, $iLang)
    {
        $sSeoUrl = null;
        $oCont = oxNew(Content::class);
        if ($oCont->loadInLang($iLang, $sObjectId)) {
            $sSeoUrl = $this->getContentUri($oCont, $iLang, true);
        }

        return $sSeoUrl;
    }
}
