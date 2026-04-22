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
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\SeoEncoderCategory;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Category seo config class
 */
class CategorySeo extends ObjectSeo
{
    /**
     * Updating oxshowsuffix field
     *
     * @return null
     * @throws Exception
     */
    public function save()
    {
        $sOxid = $this->getEditObjectId();
        $oCategory = oxNew(Category::class);
        if ($oCategory->load($sOxid)) {
            $blShowSuffixParameter = Registry::getRequest()->getRequestEscapedParameter('blShowSuffix');
            $sShowSuffixField = 'oxcategories__oxshowsuffix';
            $oCategory->$sShowSuffixField = new Field((int) $blShowSuffixParameter);
            $oCategory->save();

            $this->getEncoder()->markRelatedAsExpired($oCategory);
        }

        return parent::save();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderCategory
     * @deprecated Use getEncoder() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getEncoder().
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::get(SeoEncoderCategory::class);
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderCategory
     *
     * @internal If your override does not fully replace the behavior, call parent::getEncoder()
     *           (not the deprecated _getEncoder()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getEncoder()
    {
        return $this->_getEncoder();
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
     * @deprecated Use getType() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getType().
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'oxcategory';
    }

    /**
     * Returns url type
     *
     * @return string
     *
     * @internal If your override does not fully replace the behavior, call parent::getType()
     *           (not the deprecated _getType()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getType()
    {
        return $this->_getType();
    }

    /**
     * Returns true if SEO object id has suffix enabled
     *
     * @return bool|void
     * @throws DatabaseConnectionException
     */
    public function isEntrySuffixed()
    {
        $oCategory = oxNew(Category::class);
        if ($oCategory->load($this->getEditObjectId())) {
            return (bool) $oCategory->oxcategories__oxshowsuffix->value;
        }
    }

    /**
     * Returns seo uri
     *
     * @return string|void
     * @throws DatabaseConnectionException
     */
    public function getEntryUri()
    {
        $oCategory = oxNew(Category::class);
        if ($oCategory->load($this->getEditObjectId())) {
            return $this->getEncoder()->getCategoryUri($oCategory, $this->getEditLang());
        }
    }
}
