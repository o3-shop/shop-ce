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
 *
 */
class SeoEncoderVendor extends \OxidEsales\Eshop\Core\SeoEncoder
{
    /**
     * Root vendor uri cache
     *
     * @var string
     */
    protected $_aRootVendorUri = null;

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
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor           Vendor object
     * @param int                                        $languageId       Language id
     * @param bool                                       $shouldRegenerate If TRUE - forces seo url regeneration
     *
     * @return string
     */
    public function getVendorUri($vendor, $languageId = null, $shouldRegenerate = false)
    {
        if (!isset($languageId)) {
            $languageId = $vendor->getLanguage();
        }
        // load from db
        if ($shouldRegenerate || !($seoUrl = $this->_loadFromDb('oxvendor', $vendor->getId(), $languageId))) {
            if ($languageId != $vendor->getLanguage()) {
                $vendorId = $vendor->getId();
                $vendor = oxNew(\OxidEsales\Eshop\Application\Model\Vendor::class);
                $vendor->loadInLang($languageId, $vendorId);
            }

            $seoUrl = '';
            if ($vendor->getId() != 'root') {
                if (!isset($this->_aRootVendorUri[$languageId])) {
                    $rootVendor = oxNew(\OxidEsales\Eshop\Application\Model\Vendor::class);
                    $rootVendor->loadInLang($languageId, 'root');
                    $this->_aRootVendorUri[$languageId] = $this->getVendorUri($rootVendor, $languageId);
                }
                $seoUrl .= $this->_aRootVendorUri[$languageId];
            }

            $seoUrl .= $this->_prepareTitle($vendor->oxvendor__oxtitle->value, false, $vendor->getLanguage()) . '/';
            $seoUrl = $this->_processSeoUrl($seoUrl, $vendor->getId(), $languageId);

            // save to db
            $this->_saveToDb('oxvendor', $vendor->getId(), $vendor->getBaseStdLink($languageId), $seoUrl, $languageId);
        }

        return $seoUrl;
    }

    /**
     * Returns vendor SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor     Vendor object.
     * @param int                                        $pageNumber Number of the page which should be prepared.
     * @param int                                        $languageId Language id.
     * @param bool                                       $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    public function getVendorPageUrl($vendor, $pageNumber, $languageId = null, $isFixed = null)
    {
        if (!isset($languageId)) {
            $languageId = $vendor->getLanguage();
        }
        $stdUrl = $vendor->getBaseStdLink($languageId);
        $parameters = null;

        $stdUrl = $this->_trimUrl($stdUrl, $languageId);
        $seoUrl = $this->getVendorUri($vendor, $languageId);

        if ($isFixed === null) {
            $isFixed = $this->_isFixed('oxvendor', $vendor->getId(), $languageId);
        }

        return $this->assembleFullPageUrl($vendor, 'oxvendor', $stdUrl, $seoUrl, $pageNumber, $parameters, $languageId, $isFixed);
    }

    /**
     * Encodes vendor category URLs into SEO format.
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor     Vendor object
     * @param int                                        $languageId Language id
     *
     * @return null
     */
    public function getVendorUrl($vendor, $languageId = null)
    {
        if (!isset($languageId)) {
            $languageId = $vendor->getLanguage();
        }

        return $this->_getFullUrl($this->getVendorUri($vendor, $languageId), $languageId);
    }

    /**
     * Deletes Vendor seo entry
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor Vendor object
     */
    public function onDeleteVendor($vendor)
    {
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $vendorId = $vendor->getId();
        $database->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxvendor'", [
            ':oxobjectid' => $vendorId
        ]);
        $database->execute("delete from oxobject2seodata where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $vendorId
        ]);
        $database->execute("delete from oxseohistory where oxobjectid = :oxobjectid", [
            ':oxobjectid' => $vendorId
        ]);
    }

    /**
     * Returns alternative uri used while updating seo.
     *
     * @param string $vendorId   Vendor id
     * @param int    $languageId Language id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAltUri" in next major
     */
    protected function _getAltUri($vendorId, $languageId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $seoUrl = null;
        $vendor = oxNew(\OxidEsales\Eshop\Application\Model\Vendor::class);
        if ($vendor->loadInLang($languageId, $vendorId)) {
            $seoUrl = $this->getVendorUri($vendor, $languageId, true);
        }

        return $seoUrl;
    }
}
