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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
class SettingChangedEvent extends Event
{
    const NAME = self::class;

    /**
     * @var string
     */
    private $settingName;

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var string
     */
    private $moduleId;

    public function __construct(string $settingName, int $shopId, string $moduleId)
    {
        $this->settingName = $settingName;
        $this->shopId = $shopId;
        $this->moduleId = $moduleId;
    }

    public function getSettingName(): string
    {
        return $this->settingName;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getModuleId(): string
    {
        return $this->moduleId;
    }
}
