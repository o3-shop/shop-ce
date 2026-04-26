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

namespace OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck;

class UpdateCheckResult
{
    /** @var bool */
    private $coreUpdateAvailable;

    /** @var string */
    private $latestCoreVersion;

    /** @var string */
    private $updateLink;

    /** @var array */
    private $outdatedModules;

    /**
     * @param bool   $coreUpdateAvailable
     * @param string $latestCoreVersion
     * @param string $updateLink
     * @param array  $outdatedModules Each item: ['id' => string, 'installed_version' => string, 'latest_version' => string, 'url' => string]
     */
    public function __construct(
        bool $coreUpdateAvailable = false,
        string $latestCoreVersion = '',
        string $updateLink = '',
        array $outdatedModules = []
    ) {
        $this->coreUpdateAvailable = $coreUpdateAvailable;
        $this->latestCoreVersion = $latestCoreVersion;
        $this->updateLink = $updateLink;
        $this->outdatedModules = $outdatedModules;
    }

    /**
     * @return self
     */
    public static function empty(): self
    {
        return new self();
    }

    public function isCoreUpdateAvailable(): bool
    {
        return $this->coreUpdateAvailable;
    }

    public function getLatestCoreVersion(): string
    {
        return $this->latestCoreVersion;
    }

    public function getUpdateLink(): string
    {
        return $this->updateLink;
    }

    /**
     * @return array Each item: ['id' => string, 'installed_version' => string, 'latest_version' => string, 'url' => string]
     */
    public function getOutdatedModules(): array
    {
        return $this->outdatedModules;
    }
}
