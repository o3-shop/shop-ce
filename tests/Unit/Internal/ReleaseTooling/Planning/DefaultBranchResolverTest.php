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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Planning;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DefaultBranchResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\ReleaseLine;
use PHPUnit\Framework\TestCase;

class DefaultBranchResolverTest extends TestCase
{
    public function testPaypalCheckoutIsKeyedByItsPackageName(): void
    {
        // The repo is o3-shop/paypal-module, but the planner looks up the
        // composer package name; a repo-name key falls through to main.
        $this->assertSame('b-1.7', (new DefaultBranchResolver())('o3-shop/paypal-checkout'));
        $this->assertArrayNotHasKey('o3-shop/paypal-module', DefaultBranchResolver::PACKAGE_TO_BRANCH);
    }

    public function testNoKeyIsARenamedGitHubRepoName(): void
    {
        // A repo-name key never matches a package and falls back to main
        // (the paypal-module bug); keys must be composer package names.
        foreach (PackageRepoSlug::RENAMES as $package => $repo) {
            $this->assertArrayNotHasKey($repo, DefaultBranchResolver::PACKAGE_TO_BRANCH, $repo . ' is a GitHub repo name; key it as ' . $package);
        }
    }

    public function testEveryLineVersionedPackageIsMappedToALineBranch(): void
    {
        foreach (ReleaseLine::LINE_VERSIONED_PACKAGES as $package) {
            $this->assertArrayHasKey($package, DefaultBranchResolver::PACKAGE_TO_BRANCH);
            $this->assertMatchesRegularExpression('/^b-\\d+\\.\\d+$/', DefaultBranchResolver::PACKAGE_TO_BRANCH[$package], $package);
        }
    }

    public function testPaypalClientReleasesFromItsLineNotTheStaleMain(): void
    {
        $this->assertSame('b-7.0.x', (new DefaultBranchResolver())('o3-shop/paypal-client'));
    }
}
