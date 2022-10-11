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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Cache;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache\TemplateCacheServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;

class ShopModuleCacheService implements ModuleCacheServiceInterface
{
    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * @var TemplateCacheServiceInterface
     */
    private $templateCacheService;

    /**
     * ShopModuleCacheService constructor.
     *
     * @param ShopAdapterInterface          $shopAdapter
     * @param TemplateCacheServiceInterface $templateCacheService
     */
    public function __construct(ShopAdapterInterface $shopAdapter, TemplateCacheServiceInterface $templateCacheService)
    {
        $this->shopAdapter = $shopAdapter;
        $this->templateCacheService = $templateCacheService;
    }

    /**
     * Invalidate all module related cache items for a given module and a given shop
     *
     * @param string $moduleId
     * @param int    $shopId
     */
    public function invalidateModuleCache(string $moduleId, int $shopId)
    {
        $this->templateCacheService->invalidateTemplateCache();
        $this->shopAdapter->invalidateModuleCache($moduleId);
    }
}
