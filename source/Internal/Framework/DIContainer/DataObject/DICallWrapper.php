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

class DICallWrapper
{
    const METHOD_KEY = 'method';
    const PARAMETER_KEY = 'arguments';

    private $callArray;

    /**
     * DICallWrapper constructor.
     *
     * @param array $callArray
     */
    public function __construct(array $callArray = [])
    {
        if (!$callArray) {
            $this->callArray = ['method' => '', 'arguments' => []];
        } else {
            $this->callArray = $callArray;
        }
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->callArray[$this::METHOD_KEY];
    }

    /**
     * @param string $methodName
     */
    public function setMethodName(string $methodName)
    {
        $this->callArray[$this::METHOD_KEY] = $methodName;
    }

    /**
     * @param int   $index
     * @param mixed $parameter
     */
    public function setParameter(int $index, $parameter)
    {
        $this->callArray[$this::PARAMETER_KEY][$index] = $parameter;
    }

    /**
     * @param int $index
     *
     * @return mixed
     */
    public function getParameter(int $index)
    {
        return $this->callArray[$this::PARAMETER_KEY][$index];
    }

    /**
     * @return array
     */
    public function getCallAsArray(): array
    {
        return $this->callArray;
    }
}
