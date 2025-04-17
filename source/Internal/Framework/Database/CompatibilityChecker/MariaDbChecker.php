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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Database\CompatibilityChecker;

class MariaDbChecker implements MariaDbCheckerInterface, DatabaseCheckerInterface
{
    protected string $versionString;
    protected string $version;

    /**
     * @param string $version
     *
     * @return void
     */
    public function setVersion(string $version): void
    {
        $this->versionString = $version;
        $this->version = substr($this->versionString, 0, strpos($this->versionString, '-'));
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return
            version_compare($this->version, '10.0', '>=') &&
            version_compare($this->version, '10.11', '<');
    }
}