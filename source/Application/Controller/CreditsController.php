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

namespace OxidEsales\EshopCommunity\Application\Controller;

/**
 * Special page for Credits
 */
class CreditsController extends \OxidEsales\Eshop\Application\Controller\ContentController
{
    /**
     * Content id.
     *
     * @var string
     */
    protected $_sContentId = "oxcredits";

    /**
     * Returns active content id to load its seo meta info
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoObjectId" in next major
     */
    protected function _getSeoObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getContentId();
    }

    /**
     * Template variable getter. Returns active content
     *
     * @return object
     */
    public function getContent()
    {
        if ($this->_oContent === null) {
            $this->_oContent = false;
            $oContent = oxNew(\OxidEsales\Eshop\Application\Model\Content::class);
            if ($oContent->loadByIdent($this->getContentId())) {
                $this->_oContent = $oContent;
            }
        }

        return $this->_oContent;
    }
}
