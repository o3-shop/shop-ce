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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Cache for storing module variables selected from database.
 *
 * @internal Do not make a module extension for this class.
 */
class SubShopSpecificFileCache extends \OxidEsales\Eshop\Core\FileCache
{
    /** @var ShopIdCalculator */
    private $shopIdCalculator;

    /**
     * @param ShopIdCalculator $shopIdCalculator
     */
    public function __construct($shopIdCalculator)
    {
        $this->shopIdCalculator = $shopIdCalculator;
    }

    /**
     * Returns shopId which should be used for cache file name generation.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getCacheFileName($key)
    {
        $name = strtolower(basename($key));
        $shopId = strtolower(basename($this->getShopIdCalculator()->getShopId()));

        return parent::CACHE_FILE_PREFIX . ".$shopId.$name.txt";
    }

    /**
     * @return ShopIdCalculator
     */
    protected function getShopIdCalculator()
    {
        return $this->shopIdCalculator;
    }
}
