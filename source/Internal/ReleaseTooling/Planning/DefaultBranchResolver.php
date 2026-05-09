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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning;

/**
 * Maps each o3-shop package to the branch its release line cuts
 * from. The map matches the per-repo branch decisions made during
 * Section 1.5 (archive.exclude rollout).
 *
 * Repos not in the map fall back to `main`.
 */
final class DefaultBranchResolver
{
    /** @var array<string,string> package => branch */
    public const PACKAGE_TO_BRANCH = [
        'o3-shop/o3-shop' => 'b-1.6',
        'o3-shop/shop-ce' => 'b-1.6',
        'o3-shop/testing-library' => 'b-1.6',
        'o3-shop/shop-facts' => 'b-1.6',
        'o3-shop/shop-ide-helper' => 'b-1.6',
        'o3-shop/shop-unified-namespace-generator' => 'b-1.6',
        'o3-shop/shop-doctrine-migration-wrapper' => 'b-1.6',
        'o3-shop/shop-demodata-installer' => 'b-1.6',
        'o3-shop/gdpr-optin-module' => 'b-1.0',
        'o3-shop/usercentrics' => 'b-1.0',
        'o3-shop/codeception-modules' => 'b-1.0',
        'o3-shop/php-selenium' => 'b-1.0',
        'o3-shop/o3-theme' => 'main',
        'o3-shop/wave-theme' => 'main',
        'o3-shop/shop-demodata-ce' => 'main',
        'o3-shop/tinymce-editor' => 'main',
        'o3-shop/shop-composer-plugin' => 'main',
        'o3-shop/shop-db-views-generator' => 'main',
        'o3-shop/smarty' => 'support/2.6',
        'o3-shop/mink-selenium-driver' => 'b-7.0.x',
        'o3-shop/developer-tools' => 'b-7.0.x',
        'o3-shop/codeception-page-objects' => 'b-6.5.x',
    ];

    public function __invoke(string $package): string
    {
        return self::PACKAGE_TO_BRANCH[$package] ?? 'main';
    }
}
