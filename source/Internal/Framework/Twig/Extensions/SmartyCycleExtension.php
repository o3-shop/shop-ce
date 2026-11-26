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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions;

use Twig\Error\Error;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class SmartyCycleExtension
 */
class SmartyCycleExtension extends AbstractExtension
{
    /**
     * @var array
     */
    private $cycleVars;

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('smarty_cycle', [$this, 'smartyCycle']),
        ];
    }

    /**
     * @param array $values
     * @param array $parameters
     *
     * @return mixed|null
     */
    public function smartyCycle(array $values = [], array $parameters = [])
    {
        $name = (empty($parameters['name'])) ? 'default' : $parameters['name'];
        $advance = (isset($parameters['advance'])) ? (bool) $parameters['advance'] : true;
        $reset = (isset($parameters['reset'])) ? (bool) $parameters['reset'] : false;
        $return = (isset($parameters['print'])) ? (bool) $parameters['print'] : true;

        if (empty($values)) {
            if (!isset($this->cycleVars[$name]['values'])) {
                throw new Error("static_cycle: missing 'values' parameter");
            }
        } else {
            if (isset($this->cycleVars[$name]['values'])
                && $this->cycleVars[$name]['values'] != $values) {
                $this->cycleVars[$name]['index'] = 0;
            }
            $this->cycleVars[$name]['values'] = $values;
        }

        if (isset($parameters['delimiter'])) {
            $this->cycleVars[$name]['delimiter'] = $parameters['delimiter'];
        } elseif (!isset($this->cycleVars[$name]['delimiter'])) {
            $this->cycleVars[$name]['delimiter'] = ',';
        }

        if (is_array($this->cycleVars[$name]['values'])) {
            $cycleArray = $this->cycleVars[$name]['values'];
        } else {
            $cycleArray = explode($this->cycleVars[$name]['delimiter'], $this->cycleVars[$name]['values']);
        }

        if (!isset($this->cycleVars[$name]['index']) || $reset) {
            $this->cycleVars[$name]['index'] = 0;
        }

        $value = $cycleArray[$this->cycleVars[$name]['index']];

        if ($advance) {
            if ($this->cycleVars[$name]['index'] >= count($cycleArray) - 1) {
                $this->cycleVars[$name]['index'] = 0;
            } else {
                $this->cycleVars[$name]['index']++;
            }
        }

        return $return ? $value : null;
    }
}
