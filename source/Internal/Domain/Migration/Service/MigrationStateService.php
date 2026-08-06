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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Migration\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Repository\ExecutedMigrationsRepositoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

class MigrationStateService implements MigrationStateServiceInterface
{
    private ExecutedMigrationsRepositoryInterface $executedMigrationsRepository;
    private BasicContextInterface $context;

    public function __construct(
        ExecutedMigrationsRepositoryInterface $executedMigrationsRepository,
        BasicContextInterface $context
    ) {
        $this->executedMigrationsRepository = $executedMigrationsRepository;
        $this->context = $context;
    }

    public function isTracked(): bool
    {
        return $this->executedMigrationsRepository->tableExists();
    }

    public function getAvailableVersions(): array
    {
        $versions = [];
        foreach (glob($this->getMigrationDataPath() . '/Version*.php') ?: [] as $file) {
            if (preg_match('/^Version(\d+)\.php$/', basename($file), $matches) === 1) {
                $versions[] = $matches[1];
            }
        }
        sort($versions);

        return $versions;
    }

    public function getExecutedVersions(): array
    {
        $versions = $this->executedMigrationsRepository->getExecutedVersions();
        sort($versions);

        return $versions;
    }

    public function getPendingVersions(): array
    {
        return array_values(
            array_diff($this->getAvailableVersions(), $this->getExecutedVersions())
        );
    }

    public function getUnknownVersions(): array
    {
        return array_values(
            array_diff($this->getExecutedVersions(), $this->getAvailableVersions())
        );
    }

    public function getCurrentVersion(): ?string
    {
        $executed = $this->getExecutedVersions();

        return $executed === [] ? null : (string) end($executed);
    }

    public function getLatestAvailableVersion(): ?string
    {
        $available = $this->getAvailableVersions();

        return $available === [] ? null : (string) end($available);
    }

    private function getMigrationDataPath(): string
    {
        return $this->context->getSourcePath() . '/migration/data';
    }
}
