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

namespace OxidEsales\EshopCommunity\Internal\Framework\Event;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

interface ShopAwareInterface
{
    /**
     * This method is used by the DI container
     * to set an array of shop ids for which
     * this event subscriber should be executed.
     *
     * @param array $activeShops
     */
    public function setActiveShops(array $activeShops);

    /**
     * This is set by the DI container to provide
     * access to the current shop ID to determine
     * if the event should be executed or not.
     *
     * @param ContextInterface $context
     */
    public function setContext(ContextInterface $context);

    /**
     * This method is used by the event dispatcher to
     * determine, if the event should be executed for
     * the current shop or not.
     *
     * @return bool
     */
    public function isActive();
}
