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

namespace OxidEsales\EshopCommunity\Internal\Transition\ShopEvents;

use Symfony\Component\EventDispatcher\Event;

class BasketChangedEvent extends Event
{
    const NAME = self::class;

    /**
     * Url the shop wants to redirect to after product is put to basket.
     *
     * @var \OxidEsales\Eshop\Application\Component\BasketComponent
     */
    private $basketComponent;

    /**
     * BasketChangedEvent constructor.
     *
     * @param \OxidEsales\Eshop\Application\Component\BasketComponent $basketComponent Basket component
     */
    public function __construct(\OxidEsales\Eshop\Application\Component\BasketComponent $basketComponent)
    {
        $this->basketComponent = $basketComponent;
    }

    /**
     * Getter for basket component object.
     *
     * @return \OxidEsales\Eshop\Application\Component\BasketComponent
     */
    public function getBasket(): \OxidEsales\Eshop\Application\Component\BasketComponent
    {
        return $this->basketComponent;
    }
}
