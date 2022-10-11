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

use oxDb;

/**
 * Seo encoder base
 */
class SeoEncoderManufacturer extends \OxidEsales\Eshop\Core\SeoEncoder
{
    /**
     * Root manufacturer uri cache
     *
     * @var array
     */
    protected $_aRootManufacturerUri = null;

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
     * Returns part of SEO url excluding path
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer manufacturer object
     * @param int                                              $iLang         language
     * @param bool                                             $blRegenerate  if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function getManufacturerUri($oManufacturer, $iLang = null, $blRegenerate = false)
    {
        if (!isset($iLang)) {
            $iLang = $oManufacturer->getLanguage();
        }
        // load from db
        if ($blRegenerate || !($sSeoUrl = $this->_loadFromDb('oxmanufacturer', $oManufacturer->getId(), $iLang))) {
            if ($iLang != $oManufacturer->getLanguage()) {
                $sId = $oManufacturer->getId();
                $oManufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
                $oManufacturer->loadInLang($iLang, $sId);
            }

            $sSeoUrl = '';
            if ($oManufacturer->getId() != 'root') {
                if (!isset($this->_aRootManufacturerUri[$iLang])) {
                    $oRootManufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
                    $oRootManufacturer->loadInLang($iLang, 'root');
                    $this->_aRootManufacturerUri[$iLang] = $this->getManufacturerUri($oRootManufacturer, $iLang);
                }
                $sSeoUrl .= $this->_aRootManufacturerUri[$iLang];
            }

            $sSeoUrl .= $this->_prepareTitle($oManufacturer->oxmanufacturers__oxtitle->value, false, $oManufacturer->getLanguage()) . '/';
            $sSeoUrl = $this->_processSeoUrl($sSeoUrl, $oManufacturer->getId(), $iLang);

            // save to db
            $this->_saveToDb('oxmanufacturer', $oManufacturer->getId(), $oManufacturer->getBaseStdLink($iLang), $sSeoUrl, $iLang);
        }

        return $sSeoUrl;
    }

    /**
     * Returns Manufacturer SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $manufacturer Manufacturer object
     * @param int                                              $pageNumber   Number of the page which should be prepared.
     * @param int                                              $languageId   Language id.
     * @param bool                                             $isFixed      Fixed url marker (default is null).
     *
     * @return string
     */
    public function getManufacturerPageUrl($manufacturer, $pageNumber, $languageId = null, $isFixed = null)
    {
        if (!isset($languageId)) {
            $languageId = $manufacturer->getLanguage();
        }
        $stdUrl = $manufacturer->getBaseStdLink($languageId);
        $parameters = null;

        $stdUrl = $this->_trimUrl($stdUrl, $languageId);
        $seoUrl = $this->getManufacturerUri($manufacturer, $languageId);

        if ($isFixed === null) {
            $isFixed = $this->_isFixed('oxmanufacturer', $manufacturer->getId(), $languageId);
        }

        return $this->assembleFullPageUrl($manufacturer, 'oxmanufacturer', $stdUrl, $seoUrl, $pageNumber, $parameters, $languageId, $isFixed);
    }

    /**
     * Encodes manufacturer category URLs into SEO format
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer Manufacturer object
     * @param int                                              $iLang         language
     *
     * @return string
     */
    public function getManufacturerUrl($oManufacturer, $iLang = null)
    {
        if (!isset($iLang)) {
            $iLang = $oManufacturer->getLanguage();
        }

        return $this->_getFullUrl($this->getManufacturerUri($oManufacturer, $iLang), $iLang);
    }

    /**
     * Deletes manufacturer seo entry
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer Manufacturer object
     */
    public function onDeleteManufacturer($oManufacturer)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oDb->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxmanufacturer'", [
            ':oxobjectid' => $oManufacturer->getId()
        ]);
        $oDb->execute("delete from oxobject2seodata where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $oManufacturer->getId()
        ]);
        $oDb->execute("delete from oxseohistory where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $oManufacturer->getId()
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
        $oManufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        if ($oManufacturer->loadInLang($iLang, $sObjectId)) {
            $sSeoUrl = $this->getManufacturerUri($oManufacturer, $iLang, true);
        }

        return $sSeoUrl;
    }
}
