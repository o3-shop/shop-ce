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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig;

/**
 * Class TemplateNameParser
 *
 * Parses following strings:
 * loaderName::key::value?param1=value1&param2=value2
 */
class TemplateLoaderNameParser
{
    /**
     * Checks whether string is valid template name
     *
     * @param string $name
     *
     * @return bool
     */
    public function isValidName(string $name): bool
    {
        return count(explode('::', $name)) == 3;
    }

    /**
     * loaderName::key::value?param1=value1&param2=value2
     * ----------
     *
     * @param string $name
     *
     * @return string
     */
    public function getLoaderName(string $name): string
    {
        return $this->getNamePart($name, 0);
    }

    /**
     * loaderName::key::value?param1=value1&param2=value2
     *             ---
     *
     * @param string $name
     *
     * @return string
     */
    public function getKey(string $name): string
    {
        return $this->getNamePart($name, 1);
    }

    /**
     * loaderName::key::value?param1=value1&param2=value2
     *                  -----
     *
     * @param string $name
     *
     * @return string
     */
    public function getValue(string $name): string
    {
        return $this->getNamePart($name, 2);
    }

    /**
     * loaderName::key::value?param1=value1&param2=value2
     *                        ---------------------------
     *
     * @param string $name
     *
     * @return array
     */
    public function getParameters(string $name): array
    {
        $parameters = [];

        if (count(explode('?', $name)) >= 2) {
            $parametersString = explode('?', $name)[1];

            parse_str($parametersString, $parameters);
        }

        return $parameters;
    }

    /**
     * loaderName::key::value?param1=value1&param2=value2
     *     [0]     [1]   [2]
     *
     * @param string $name
     * @param int    $index
     *
     * @return string
     */
    private function getNamePart(string $name, int $index): string
    {
        if (!$this->isValidName($name)) {
            throw new \Exception('Invalid template name.');
        }

        $nameParts = explode('?', $name)[0];

        return explode('::', $nameParts)[$index];
    }
}
