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

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Event\ShopAwareInterface;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\MissingUpdateCallException;

class DIServiceWrapper
{
    const CALLS_SECTION = 'calls';

    const SET_ACTIVE_SHOPS_METHOD = 'setActiveShops';
    const SET_CONTEXT_METHOD = 'setContext';
    const SET_CONTEXT_PARAMETER = '@OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface';

    /** @var  string $key */
    private $key;

    /** @var  array $serviceArray */
    private $serviceArray;

    /** @var  string $class */
    private $class;

    /**
     * DIServiceWrapper constructor.
     *
     * @param string $key
     * @param array  $serviceArray
     */
    public function __construct(string $key, array $serviceArray)
    {
        $this->key = $key;
        $this->serviceArray = $serviceArray;

        if (isset($serviceArray['class'])) {
            $this->class = $serviceArray['class'];
        } elseif (class_exists($this->key)) {
            $this->class = $this->key;
        }
    }

    /**
     * @return array
     */
    public function getServiceAsArray(): array
    {
        return $this->serviceArray;
    }

    /**
     * @return bool
     */
    public function isShopAware(): bool
    {
        if (!$this->hasClass()) {
            return false;
        }

        $class = $this->getClass();
        $interfaces = class_implements($class);

        return in_array(ShopAwareInterface::class, $interfaces, true);
    }

    /**
     * @param array $shops
     * @return array
     */
    public function addActiveShops(array $shops)
    {
        $this->addShopAwareCallsIfMissing();
        $setActiveShopsCall = $this->getCall($this::SET_ACTIVE_SHOPS_METHOD);
        $currentlyActiveShops = $setActiveShopsCall->getParameter(0);
        $newActiveShops = array_merge($currentlyActiveShops, $shops);
        $setActiveShopsCall->setParameter(0, $newActiveShops);
        $this->updateCall($setActiveShopsCall);
        return $newActiveShops;
    }

    /**
     * @param array $shops
     * @return array
     */
    public function removeActiveShops(array $shops)
    {
        $setActiveShopsCall = $this->getCall($this::SET_ACTIVE_SHOPS_METHOD);
        $currentlyActiveShops = $setActiveShopsCall->getParameter(0);
        $newActiveShops = [];
        foreach ($currentlyActiveShops as $shopId) {
            if (array_search($shopId, $shops) === false) {
                $newActiveShops[] = $shopId;
            }
        }
        $setActiveShopsCall->setParameter(0, $newActiveShops);
        $this->updateCall($setActiveShopsCall);

        return $newActiveShops;
    }

    /**
     * @return bool
     */
    public function hasActiveShops()
    {
        $this->addShopAwareCallsIfMissing();
        $setActiveShopsCall = $this->getCall($this::SET_ACTIVE_SHOPS_METHOD);
        $currentlyActiveShops = $setActiveShopsCall->getParameter(0);
        return count($currentlyActiveShops) > 0;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Check if the class for the service definition exists.
     * If no class is defined, it also returns true.
     *
     * @return bool
     */
    public function checkClassExists()
    {
        if (! $this->hasClass()) {
            return true;
        }
        return class_exists($this->getClass());
    }

    private function addShopAwareCallsIfMissing()
    {
        if (!$this->hasCall($this::SET_ACTIVE_SHOPS_METHOD)) {
            $setActiveShopCall = new DICallWrapper();
            $setActiveShopCall->setMethodName($this::SET_ACTIVE_SHOPS_METHOD);
            $setActiveShopCall->setParameter(0, []);
            $this->addCall($setActiveShopCall);
        }
        if (!$this->hasCall($this::SET_CONTEXT_METHOD)) {
            $setContextCall = new DICallWrapper();
            $setContextCall->setMethodName($this::SET_CONTEXT_METHOD);
            $setContextCall->setParameter(0, $this::SET_CONTEXT_PARAMETER);
            $this->addCall($setContextCall);
        }
    }

    /**
     * @return array
     */
    private function getCalls(): array
    {
        if (!array_key_exists($this::CALLS_SECTION, $this->serviceArray)) {
            return [];
        }
        $calls = [];
        foreach ($this->serviceArray[$this::CALLS_SECTION] as $callArray) {
            $calls[] = new DICallWrapper($callArray);
        }

        return $calls;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    private function hasCall(string $methodName)
    {
        foreach ($this->getCalls() as $call) {
            if ($call->getMethodName() === $methodName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DICallWrapper $call
     */
    private function addCall(DICallWrapper $call)
    {
        if (!array_key_exists($this::CALLS_SECTION, $this->serviceArray)) {
            $this->serviceArray[$this::CALLS_SECTION] = [];
        }
        $this->serviceArray[$this::CALLS_SECTION][] = $call->getCallAsArray();
    }

    /**
     * @param DICallWrapper $call
     *
     * @throws MissingUpdateCallException
     * @return void
     */
    private function updateCall(DICallWrapper $call)
    {
        $callsCount = count($this->serviceArray[$this::CALLS_SECTION]);

        for ($i = 0; $i < $callsCount; $i++) {
            $existingCall = new DICallWrapper($this->serviceArray[$this::CALLS_SECTION][$i]);
            if ($existingCall->getMethodName() === $call->getMethodName()) {
                $this->serviceArray[$this::CALLS_SECTION][$i] = $call->getCallAsArray();
                return;
            }
        }
        throw new MissingUpdateCallException();
    }


    /**
     * @param string $methodName
     *
     * @return DICallWrapper
     * @throws MissingUpdateCallException
     */
    private function getCall(string $methodName): DICallWrapper
    {
        if (array_key_exists($this::CALLS_SECTION, $this->serviceArray)) {
            foreach ($this->serviceArray[$this::CALLS_SECTION] as $callArray) {
                $call = new DICallWrapper($callArray);
                if ($call->getMethodName() === $methodName) {
                    return $call;
                }
            }
        }
        throw new MissingUpdateCallException();
    }

    /**
     * @return string
     */
    private function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return bool
     */
    private function hasClass(): bool
    {
        return isset($this->class);
    }
}
