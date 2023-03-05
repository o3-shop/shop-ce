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

use oxRegistry;
use oxField;

/**
 * Manufacturer seo config class
 */
class ManufacturerSeo extends \OxidEsales\Eshop\Application\Controller\Admin\ObjectSeo
{
    /**
     * Updating showsuffix field
     *
     * @return null
     */
    public function save()
    {
        $oManufacturer = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $oManufacturer->init('oxmanufacturers');
        if ($oManufacturer->load($this->getEditObjectId())) {
            $sShowSuffixField = 'oxmanufacturers__oxshowsuffix';
            $blShowSuffixParameter = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('blShowSuffix');
            $oManufacturer->$sShowSuffixField = new \OxidEsales\Eshop\Core\Field((int) $blShowSuffixParameter);
            $oManufacturer->save();
        }

        return parent::save();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return oxSeoEncoderManufacturer
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEncoder" in next major
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Application\Model\SeoEncoderManufacturer::class);
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
     * Returns url type
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getType" in next major
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'oxmanufacturer';
    }

    /**
     * Returns true if SEO object id has suffix enabled
     *
     * @return bool
     */
    public function isEntrySuffixed()
    {
        $oManufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        if ($oManufacturer->load($this->getEditObjectId())) {
            return (bool) $oManufacturer->oxmanufacturers__oxshowsuffix->value;
        }
    }

    /**
     * Returns seo uri
     *
     * @return string
     */
    public function getEntryUri()
    {
        $oManufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        if ($oManufacturer->load($this->getEditObjectId())) {
            return $this->_getEncoder()->getManufacturerUri($oManufacturer, $this->getEditLang());
        }
    }
}
