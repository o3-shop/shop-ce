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

use OxidEsales\Eshop\Application\Controller\Admin\ObjectSeo;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Application\Model\SeoEncoderContent;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Content seo config class
 */
class ContentSeo extends ObjectSeo
{
    /**
     * Returns url type
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getType" in next major
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getType();
    }

    /**
     * Returns url type
     *
     * @return string
     */
    protected function getType()
    {
        return 'oxcontent';
    }
    
    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderContent
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEncoder" in next major
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getEncoder();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderContent
     */
    protected function getEncoder()
    {
        return Registry::get(SeoEncoderContent::class);
    }

    /**
     * Returns seo uri
     *
     * @return string|void
     * @throws DatabaseConnectionException
     */
    public function getEntryUri()
    {
        $oContent = oxNew(Content::class);
        if ($oContent->load($this->getEditObjectId())) {
            return $this->getEncoder()->getContentUri($oContent, $this->getEditLang());
        }
    }
}
