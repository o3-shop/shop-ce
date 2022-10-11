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

/**
 * Simple variant list.
 *
 */
class SimpleVariantList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * Parent article for list variants
     */
    protected $_oParent = null;

    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxsimplevariant';

    /**
     * Sets parent variant
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oParent Parent article
     */
    public function setParent($oParent)
    {
        $this->_oParent = $oParent;
    }

    /**
     * Sets parent for variant. This method is invoked for each element in oxList::assign() loop.
     *
     * @param oxSimleVariant $oListObject Simple variant
     * @param array          $aDbFields   Array of available
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignElement" in next major
     */
    protected function _assignElement($oListObject, $aDbFields) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oListObject->setParent($this->_oParent);
        parent::_assignElement($oListObject, $aDbFields);
    }
}
