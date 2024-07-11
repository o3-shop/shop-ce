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

namespace OxidEsales\EshopCommunity\Application\Component\Widget;

use OxidEsales\Eshop\Application\Model\ContentList;

/**
 * List of additional shop information links widget.
 * Forms info link list.
 */
class Information extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Current class template name
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/footer/info.tpl';

    /**
     * @var ContentList
     */
    protected $_oContentList;

    /**
     * Returns service keys.
     *
     * @return array
     */
    public function getServicesKeys()
    {
        $oContentList = $this->getContentList();

        return $oContentList->getServiceKeys();
    }

    /**
     * Get services content list
     *
     * @return array
     */
    public function getServicesList()
    {
        $oContentList = $this->getContentList();
        $oContentList->loadServices();

        return $oContentList;
    }

    /**
     * Returns content list object.
     *
     * @return object|ContentList
     * @deprecated underscore prefix violates PSR12, will be renamed to "getContentList" in next major
     */
    protected function _getContentList() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getContentList();
    }
    
    /**
     * Returns content list object.
     *
     * @return object|ContentList
     */
    protected function getContentList()
    {
        if (!$this->_oContentList) {
            $this->_oContentList = oxNew(ContentList::class);
        }

        return $this->_oContentList;
    }
}
