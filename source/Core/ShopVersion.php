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

namespace OxidEsales\EshopCommunity\Core;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * Resolves the O3-Shop version at runtime via a 3-step chain:
 *   1. source/Core/version.generated.php — written by the composer
 *      post-install hook from the installed shop-ce version.
 *   2. Composer\InstalledVersions::getPrettyVersion('o3-shop/shop-ce') —
 *      composer's runtime API; works wherever the autoloader is loaded.
 *   3. The literal "dev" — fresh git clones with no composer install.
 *
 * The committed source carries no version literal: per-release commits
 * to this file stop with the introduction of the chain. Resolution never
 * forks a process (no git/shell_exec/proc_open/exec/passthru/backticks).
 */
class ShopVersion
{
    public const PACKAGE_NAME = 'o3-shop/shop-ce';
    public const GENERATED_VERSION_FILE = __DIR__ . '/version.generated.php';

    /**
     * @return string non-empty version string
     */
    public static function getVersion()
    {
        $resolved = static::tryGeneratedFile() ?? static::tryComposerRuntime();
        return ($resolved !== null && $resolved !== '') ? $resolved : 'dev';
    }

    /**
     * Step 1: read the file the post-install hook wrote.
     *
     * @param string|null $path override path; defaults to GENERATED_VERSION_FILE
     * @return string|null
     */
    public static function tryGeneratedFile($path = null)
    {
        $path = $path !== null ? $path : self::GENERATED_VERSION_FILE;
        if (!is_file($path)) {
            return null;
        }
        $version = include $path;
        return (is_string($version) && $version !== '') ? $version : null;
    }

    /**
     * Step 2: ask Composer's runtime API.
     *
     * @param string $packageName composer package name to query
     * @return string|null
     */
    public static function tryComposerRuntime($packageName = self::PACKAGE_NAME)
    {
        if (!class_exists(InstalledVersions::class)) {
            return null;
        }
        try {
            $version = InstalledVersions::getPrettyVersion($packageName);
        } catch (OutOfBoundsException $e) {
            return null;
        }
        return (is_string($version) && $version !== '') ? $version : null;
    }
}
