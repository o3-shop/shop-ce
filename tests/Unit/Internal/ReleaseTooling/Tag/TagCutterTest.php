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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Tag;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFileFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutResult;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutter;
use PHPUnit\Framework\TestCase;

class TagCutterTest extends TestCase
{
    private function cutter(array $files = []): TagCutter
    {
        return new TagCutter(new InMemoryFileFetcher($files));
    }

    /* ---------- 7.1 — shop-ce uses --to verbatim ---------- */

    public function testShopCeAlwaysGetsToVerbatim(): void
    {
        $result = $this->cutter()->cut(
            'o3-shop/shop-ce',
            'v1.6.0',
            'v1.6.1-RC1',
            ['shop-ce' => 'major'], // ignored: shop-ce special case beats flag
            'b-1.6'
        );
        $this->assertSame('v1.6.1-RC1', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_SHOP_VERBATIM, $result->source());
        $this->assertFalse($result->deleteNextBumpFile());
    }

    /* ---------- shop-metapackage-ce uses --to verbatim (lockstep) ---------- */

    public function testMetapackageAlwaysGetsToVerbatim(): void
    {
        // The metapackage is the compilation; it ships at the shop version.
        // --to verbatim beats any --bump flag, just like shop-ce.
        $result = $this->cutter()->cut(
            'o3-shop/shop-metapackage-ce',
            'v1.6.1-RC5',
            'v1.6.1-RC8',
            ['shop-metapackage-ce' => 'major'], // ignored: verbatim beats flag
            'b-1.6'
        );
        $this->assertSame('v1.6.1-RC8', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_SHOP_VERBATIM, $result->source());
        $this->assertFalse($result->deleteNextBumpFile());
    }

    /* ---------- 7.2 + 7.7 — default patch ---------- */

    public function testDefaultPatchBumpsLatestPatchByOne(): void
    {
        $result = $this->cutter()->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            [],
            'b-1.6'
        );
        $this->assertSame('v1.0.5', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_DEFAULT_PATCH, $result->source());
        $this->assertFalse($result->deleteNextBumpFile());
    }

    /* ---------- 7.2 + 7.7 — flag honored ---------- */

    public function testFlagBumpHonoredAcrossKinds(): void
    {
        $minor = $this->cutter()->cut(
            'o3-shop/testing-library',
            'v1.2.5',
            'v1.6.1-RC1',
            ['testing-library' => 'minor'],
            'b-1.6'
        );
        $this->assertSame('v1.3.0', $minor->newTag());
        $this->assertSame(TagCutResult::SOURCE_FLAG, $minor->source());

        $major = $this->cutter()->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            ['shop-facts' => 'major'],
            'b-1.6'
        );
        $this->assertSame('v2.0.0', $major->newTag());
    }

    public function testFlagAcceptsExactVersion(): void
    {
        $result = $this->cutter()->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            ['shop-facts' => 'v2.0.0'],
            'b-1.6'
        );
        $this->assertSame('v2.0.0', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_FLAG, $result->source());
        $this->assertFalse($result->deleteNextBumpFile());
    }

    /* ---------- 7.3 + 7.7 — .next-bump file honored ---------- */

    public function testNextBumpFileHonoredWhenPresentAndValid(): void
    {
        $cutter = $this->cutter([
            'o3-shop/gdpr-optin-module|b-1.0|.next-bump' => "minor\n",
        ]);
        $result = $cutter->cut(
            'o3-shop/gdpr-optin-module',
            'v1.0.1',
            'v1.6.1-RC1',
            [],
            'b-1.0'
        );
        $this->assertSame('v1.1.0', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_NEXT_BUMP_FILE, $result->source());
        $this->assertTrue($result->deleteNextBumpFile()); // 7.5
    }

    public function testNextBumpFileWhitespaceIsTrimmed(): void
    {
        $cutter = $this->cutter([
            'o3-shop/gdpr-optin-module|b-1.0|.next-bump' => "   major   \n\n",
        ]);
        $result = $cutter->cut(
            'o3-shop/gdpr-optin-module',
            'v1.0.1',
            'v1.6.1-RC1',
            [],
            'b-1.0'
        );
        $this->assertSame('v2.0.0', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_NEXT_BUMP_FILE, $result->source());
    }

    public function testNextBumpFileWithExactVersion(): void
    {
        $cutter = $this->cutter([
            'o3-shop/gdpr-optin-module|b-1.0|.next-bump' => 'v1.5.0',
        ]);
        $result = $cutter->cut(
            'o3-shop/gdpr-optin-module',
            'v1.0.1',
            'v1.6.1-RC1',
            [],
            'b-1.0'
        );
        $this->assertSame('v1.5.0', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_NEXT_BUMP_FILE, $result->source());
    }

    /* ---------- 7.2 + 7.6 — flag overrides .next-bump file ---------- */

    public function testFlagOverridesNextBumpFileAndLeavesFileUntouched(): void
    {
        $cutter = $this->cutter([
            'o3-shop/shop-facts|b-1.6|.next-bump' => "minor\n",
        ]);
        $result = $cutter->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            ['shop-facts' => 'major'],
            'b-1.6'
        );
        $this->assertSame('v2.0.0', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_FLAG, $result->source());
        $this->assertFalse($result->deleteNextBumpFile());
    }

    /* ---------- 7.5 — file consumed on use ---------- */

    public function testFileConsumedOnUseSignalsDelete(): void
    {
        $cutter = $this->cutter([
            'o3-shop/shop-facts|b-1.6|.next-bump' => 'patch',
        ]);
        $result = $cutter->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            [],
            'b-1.6'
        );
        $this->assertTrue($result->deleteNextBumpFile());
    }

    /* ---------- 7.7 — invalid .next-bump value ---------- */

    public function testInvalidNextBumpValueIsIgnoredWithWarning(): void
    {
        $cutter = $this->cutter([
            'o3-shop/shop-facts|b-1.6|.next-bump' => 'huge',
        ]);
        $result = $cutter->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            [],
            'b-1.6'
        );
        $this->assertSame('v1.0.5', $result->newTag()); // fell through to patch default
        $this->assertSame(TagCutResult::SOURCE_DEFAULT_PATCH, $result->source());
        $this->assertFalse($result->deleteNextBumpFile()); // file was NOT consumed
        $this->assertNotEmpty($result->notes());
        $this->assertStringContainsString("'huge'", $result->notes()[0]);
    }

    public function testEmptyNextBumpFileIsIgnoredWithWarning(): void
    {
        $cutter = $this->cutter([
            'o3-shop/shop-facts|b-1.6|.next-bump' => "   \n",
        ]);
        $result = $cutter->cut(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.6.1-RC1',
            [],
            'b-1.6'
        );
        $this->assertSame('v1.0.5', $result->newTag());
        $this->assertSame(TagCutResult::SOURCE_DEFAULT_PATCH, $result->source());
        $this->assertNotEmpty($result->notes());
    }

    /* ---------- bump arithmetic ---------- */

    public function testPatchBumpClearsOnlyTheLastSegment(): void
    {
        $r = $this->cutter()->cut('o3-shop/x', 'v3.7.42', 'v1.6.1-RC1', ['x' => 'patch'], 'main');
        $this->assertSame('v3.7.43', $r->newTag());
    }

    public function testMinorBumpResetsPatchSegment(): void
    {
        $r = $this->cutter()->cut('o3-shop/x', 'v3.7.42', 'v1.6.1-RC1', ['x' => 'minor'], 'main');
        $this->assertSame('v3.8.0', $r->newTag());
    }

    public function testMajorBumpResetsMinorAndPatch(): void
    {
        $r = $this->cutter()->cut('o3-shop/x', 'v3.7.42', 'v1.6.1-RC1', ['x' => 'major'], 'main');
        $this->assertSame('v4.0.0', $r->newTag());
    }

    public function testBumpFromPreReleaseTagDropsPreReleaseSuffix(): void
    {
        // latest_tag = v1.0.0-RC1, patch bump -> v1.0.1 (pre-release suffix drops on bump)
        $r = $this->cutter()->cut('o3-shop/x', 'v1.0.0-RC1', 'v1.6.1', ['x' => 'patch'], 'main');
        $this->assertSame('v1.0.1', $r->newTag());
    }

    public function testApplyingPatchWithNoLatestTagThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cutter()->cut('o3-shop/x', null, 'v1.6.1-RC1', [], 'main');
    }

    public function testApplyingExactWithNoLatestTagSucceeds(): void
    {
        $r = $this->cutter()->cut(
            'o3-shop/x',
            null,
            'v1.6.1-RC1',
            ['x' => 'v1.0.0'],
            'main'
        );
        $this->assertSame('v1.0.0', $r->newTag());
    }
}

/**
 * In-memory test double for RawRepoFileFetcher. Fixture key:
 * "<package>|<ref>|<path>" (composite to disambiguate same-path
 * across refs).
 */
final class InMemoryFileFetcher implements RawRepoFileFetcher
{
    /** @var array<string,string> */
    private array $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function fetchFile(string $packageName, string $ref, string $path): ?string
    {
        $key = $packageName . '|' . $ref . '|' . $path;
        return $this->files[$key] ?? null;
    }
}
