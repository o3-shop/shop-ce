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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\UnsupportedMetaDataVersionException;

class MetaDataSchemataProvider implements MetaDataSchemataProviderInterface
{
    /**
     * @var array
     */
    private $metaDataSchemata;

    /**
     * MetaDataDefinition constructor.
     *
     * @param array $metaDataSchemata
     */
    public function __construct(array $metaDataSchemata)
    {
        $this->metaDataSchemata = $metaDataSchemata;
    }

    /**
     * @return array
     */
    public function getMetaDataSchemata(): array
    {
        return $this->metaDataSchemata;
    }

    /**
     * @param string $metaDataVersion
     *
     * @throws UnsupportedMetaDataVersionException
     *
     * @return array
     */
    public function getMetaDataSchemaForVersion(string $metaDataVersion): array
    {
        if (false === array_key_exists($metaDataVersion, $this->metaDataSchemata)) {
            throw new UnsupportedMetaDataVersionException("Metadata version $metaDataVersion is not supported");
        }

        return $this->metaDataSchemata[$metaDataVersion];
    }

    /**
     * @param string $metaDataVersion
     *
     * @throws UnsupportedMetaDataVersionException
     *
     * @return array
     */
    public function getFlippedMetaDataSchemaForVersion(string $metaDataVersion): array
    {
        if (false === array_key_exists($metaDataVersion, $this->metaDataSchemata)) {
            throw new UnsupportedMetaDataVersionException("Metadata version $metaDataVersion is not supported");
        }

        return $this->arrayFlipRecursive($this->metaDataSchemata[$metaDataVersion]);
    }

    /**
     * Recursively exchange keys and values for a given array
     *
     * @param array $metaDataVersion
     *
     * @return array
     */
    private function arrayFlipRecursive(array $metaDataVersion): array
    {
        $transposedArray = [];

        foreach ($metaDataVersion as $key => $item) {
            if (is_numeric($key) && \is_string($item)) {
                $transposedArray[$item] = $key;
            } elseif (\is_string($key) && \is_array($item)) {
                $transposedArray[$key] = $this->arrayFlipRecursive($item);
            }
        }

        return $transposedArray;
    }
}
