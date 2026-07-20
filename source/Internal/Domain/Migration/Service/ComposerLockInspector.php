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

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

class ComposerLockInspector implements ComposerLockInspectorInterface
{
    private BasicContextInterface $context;

    public function __construct(BasicContextInterface $context)
    {
        $this->context = $context;
    }

    public function exists(): bool
    {
        return is_readable($this->getComposerLockPath());
    }

    public function findInstalledPackagesByPrefix(string $prefix): array
    {
        $matches = [];
        foreach ($this->getInstalledPackageNames() as $name) {
            if (strpos($name, $prefix) === 0) {
                $matches[] = $name;
            }
        }
        sort($matches);

        return $matches;
    }

    /**
     * @return string[]
     */
    private function getInstalledPackageNames(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $contents = file_get_contents($this->getComposerLockPath());
        if ($contents === false) {
            return [];
        }

        $lock = json_decode($contents, true);
        if (!is_array($lock)) {
            return [];
        }

        $names = [];
        foreach (['packages', 'packages-dev'] as $section) {
            foreach ($lock[$section] ?? [] as $package) {
                if (isset($package['name'])) {
                    $names[] = (string) $package['name'];
                }
            }
        }

        return $names;
    }

    private function getComposerLockPath(): string
    {
        return $this->context->getShopRootPath() . '/composer.lock';
    }
}
