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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer;

/**
 * Maps composer package names to their GitHub repo slugs. The
 * default is identity (`o3-shop/<name>`), but a few o3-shop repos
 * use mixed-case GitHub names while their composer package names
 * are all-lowercase. raw.githubusercontent.com URLs and
 * `git ls-remote` are both case-sensitive, so every place that
 * builds a GitHub URL from a package name resolves through this.
 */
final class PackageRepoSlug
{
    /** @var array<string,string> composer package => GitHub owner/repo */
    public const RENAMES = [
        'o3-shop/mink-selenium-driver' => 'o3-shop/MinkSeleniumDriver',
        'o3-shop/php-selenium' => 'o3-shop/PHP-Selenium',
        'o3-shop/o3-theme' => 'o3-shop/o3-Theme',
    ];

    public static function resolve(string $packageName): string
    {
        return self::RENAMES[$packageName] ?? $packageName;
    }
}
