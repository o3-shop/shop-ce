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
     * @deprecated Transitional during #107. Modules SHOULD override _getType()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getType() to the canonical override
      *             target and retires _getType(); until then, _getType() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 'oxcontent';
    }

    /**
     * Returns url type
     *
     * @return string
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getType(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getType() the canonical override target.
     */
    protected function getType()
    {
        return $this->_getType();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderContent
     * @deprecated Transitional during #107. Modules SHOULD override _getEncoder()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getEncoder() to the canonical override
      *             target and retires _getEncoder(); until then, _getEncoder() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::get(SeoEncoderContent::class);
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return SeoEncoderContent
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getEncoder(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getEncoder() the canonical override target.
     */
    protected function getEncoder()
    {
        return $this->_getEncoder();
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
