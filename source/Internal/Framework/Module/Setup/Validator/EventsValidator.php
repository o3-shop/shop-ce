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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSettingNotValidException;

use function is_array;

class EventsValidator implements ModuleConfigurationValidatorInterface
{
    /** @var array $validEvents */
    private $validEvents = ['onActivate', 'onDeactivate'];
    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * @param ShopAdapterInterface $shopAdapter
     */
    public function __construct(ShopAdapterInterface $shopAdapter)
    {
        $this->shopAdapter = $shopAdapter;
    }

    /**
     * There is another service for syntax validation and we won't validate syntax in this method.
     *
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     *
     * @throws ModuleSettingNotValidException
     */
    public function validate(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasEvents()) {
            $events = [];

            foreach ($configuration->getEvents() as $event) {
                $events[$event->getAction()] = $event->getMethod();
            }
            foreach ($this->validEvents as $validEventName) {
                if (is_array($events) && array_key_exists($validEventName, $events)) {
                    $this->checkIfMethodIsCallable($events[$validEventName]);
                }
            }
        }
    }

    /**
     * @param string $method
     *
     * @throws ModuleSettingNotValidException
     */
    private function checkIfMethodIsCallable(string $method)
    {
        $this->isNamespacedClass($method);
        if (!is_callable($method) && $this->isNamespacedClass($method)) {
            throw new ModuleSettingNotValidException('The method ' . $method . ' is not callable.');
        }
    }

    /**
     * @deprecated 6.6 Will be removed completely
     *
     * This is needed only for the modules which has non namespaced classes.
     * This method MUST be removed when support for non namespaced modules will be dropped (metadata v1.*).
     *
     * @param string $method
     * @return bool
     */
    private function isNamespacedClass(string $method): bool
    {
        $className = explode('::', $method)[0];
        if ($this->shopAdapter->isNamespace($className)) {
            return true;
        }
        return false;
    }
}
