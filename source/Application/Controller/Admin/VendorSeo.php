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
use OxidEsales\Eshop\Application\Controller\Admin\ObjectSeo;
use OxidEsales\Eshop\Application\Model\SeoEncoderVendor;
use OxidEsales\Eshop\Application\Model\Vendor;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Vendor seo config class
 */
class VendorSeo extends ObjectSeo
{
    /**
     * Updating oxshowsuffix field
     *
     * @return null
     * @throws Exception
     */
    public function save()
    {
        $oVendor = oxNew(BaseModel::class);
        $oVendor->init('oxvendor');
        if ($oVendor->load($this->getEditObjectId())) {
            $sShowSuffixField = 'oxvendor__oxshowsuffix';
            $blShowSuffixParameter = Registry::getRequest()->getRequestEscapedParameter('blShowSuffix');
            $oVendor->$sShowSuffixField = new Field((int) $blShowSuffixParameter);
            $oVendor->save();
        }

        return parent::save();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderVendor
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEncoder" in next major
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::get(SeoEncoderVendor::class);
    }

    /**
     * This SEO object supports suffixes so return TRUE
     *
     * @return bool
     */
    public function isSuffixSupported()
    {
        return true;
    }

    /**
     * Returns true if SEO object id has suffix enabled
     *
     * @return bool|void
     */
    public function isEntrySuffixed()
    {
        $oVendor = oxNew(Vendor::class);
        if ($oVendor->load($this->getEditObjectId())) {
            return (bool) $oVendor->oxvendor__oxshowsuffix->value;
        }
    }

    /**
     * Returns url type
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getType" in next major
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'oxvendor';
    }

    /**
     * Returns seo uri
     *
     * @return string|void
     */
    public function getEntryUri()
    {
        $oVendor = oxNew(Vendor::class);
        if ($oVendor->load($this->getEditObjectId())) {
            return $this->_getEncoder()->getVendorUri($oVendor, $this->getEditLang());
        }
    }
}
