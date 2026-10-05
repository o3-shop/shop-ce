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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\MergeBackPolicy;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\MergeBackPrTitlePattern;
use PHPUnit\Framework\TestCase;

class MergeBackPolicyTest extends TestCase
{
    /** @dataProvider finalReleaseProvider */
    public function testFinalShopReleaseTriggersMergeBack(string $shopTo): void
    {
        $this->assertTrue(MergeBackPolicy::shouldOpenForShopTo($shopTo));
    }

    public function finalReleaseProvider(): array
    {
        return [
            ['v1.6.1'],
            ['v1.6.0'],
            ['v2.0.0'],
            ['v0.0.1'],
            ['v10.20.30'],
        ];
    }

    /** @dataProvider preReleaseProvider */
    public function testPreReleaseShopReleaseSuppressesMergeBack(string $shopTo): void
    {
        $this->assertFalse(MergeBackPolicy::shouldOpenForShopTo($shopTo));
    }

    public function preReleaseProvider(): array
    {
        return [
            'rc1'        => ['v1.6.1-RC1'],
            'rc lower'   => ['v1.6.1-rc1'],
            'rc dotted'  => ['v1.6.1-rc.1'],
            'alpha'      => ['v1.7.0-alpha'],
            'alpha 1'    => ['v1.7.0-alpha.1'],
            'beta'       => ['v1.7.0-beta'],
            'beta upper' => ['v1.7.0-BETA2'],
            'dev'        => ['v1.7.0-dev'],
        ];
    }

    /* ---------- title-pattern helper (paired tests) ---------- */

    public function testCanonicalTitleMatchesAcrossVersionShapes(): void
    {
        $this->assertTrue(MergeBackPrTitlePattern::matches('Merge v1.6.1 release into main'));
        $this->assertTrue(MergeBackPrTitlePattern::matches('Merge v1.6.1-RC1 release into main'));
        $this->assertTrue(MergeBackPrTitlePattern::matches('Merge v0.0.1 release into main'));
    }

    public function testTitleDoesNotMatchOffShape(): void
    {
        $this->assertFalse(MergeBackPrTitlePattern::matches('merge v1.6.1 release into main'));   // case-sensitive M
        $this->assertFalse(MergeBackPrTitlePattern::matches('Merge v1.6.1 release into b-1.6'));
        $this->assertFalse(MergeBackPrTitlePattern::matches('Merge release v1.6.1 into main'));
        $this->assertFalse(MergeBackPrTitlePattern::matches('feat: bump shop version'));
    }

    public function testExtractVersionRecoversTagFromMatch(): void
    {
        $this->assertSame(
            'v1.6.1-RC1',
            MergeBackPrTitlePattern::extractVersion('Merge v1.6.1-RC1 release into main')
        );
        $this->assertNull(MergeBackPrTitlePattern::extractVersion('not a merge-back title'));
    }

    public function testBuildTitleProducesPatternMatchingResult(): void
    {
        $title = MergeBackPrTitlePattern::buildTitle('v1.6.1');
        $this->assertSame('Merge v1.6.1 release into main', $title);
        $this->assertTrue(MergeBackPrTitlePattern::matches($title));
    }
}
