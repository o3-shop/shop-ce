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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFileFetcher;

/**
 * Algorithm Step 4: decide what new tag to cut for a candidate.
 *
 *   shop-ce, shop-metapackage-ce → --to verbatim. Both ARE the shop
 *                          release — the code (shop-ce) and the
 *                          compilation (shop-metapackage-ce) move in
 *                          lockstep with the shop version.
 *   any other candidate  → bump latest_tag by level resolved with
 *                          precedence: --bump flag > .next-bump file > patch
 *
 * The .next-bump file is consumed on use: when it was the chosen
 * source, the result flags `deleteNextBumpFile = true` so Section 11
 * (per-repo flow) deletes it in the same commit the new tag is cut
 * from. The --bump flag never touches the file.
 *
 * See: openspec/.../release-graph-derivation/spec.md
 */
class TagCutter
{
    public const O3_SHOP_PREFIX = 'o3-shop/';
    public const SHOP_CE_PACKAGE = 'o3-shop/shop-ce';
    public const METAPACKAGE_PACKAGE = 'o3-shop/shop-metapackage-ce';
    public const NEXT_BUMP_PATH = '.next-bump';

    private RawRepoFileFetcher $fileFetcher;

    public function __construct(RawRepoFileFetcher $fileFetcher)
    {
        $this->fileFetcher = $fileFetcher;
    }

    /**
     * @param string                    $package     e.g. "o3-shop/shop-facts"
     * @param string|null               $latestTag   highest semver tag on the repo (null = no tags yet)
     * @param string                    $shopTo      --to value; used for the shop-ce / metapackage verbatim case
     * @param array<string,string>      $bumpFlags   short-slug => raw bump level (e.g. ["shop-facts" => "minor"])
     * @param string                    $packageRef  release branch on this repo (e.g. "b-1.6")
     */
    public function cut(
        string $package,
        ?string $latestTag,
        string $shopTo,
        array $bumpFlags,
        string $packageRef
    ): TagCutResult {
        // shop-ce and the metapackage ARE the shop release: tag both at the
        // --to version so the code and the compilation stay in lockstep.
        // Unconditional — this beats any --bump flag / .next-bump file.
        if ($package === self::SHOP_CE_PACKAGE || $package === self::METAPACKAGE_PACKAGE) {
            return new TagCutResult(
                $shopTo,
                false,
                TagCutResult::SOURCE_SHOP_VERBATIM
            );
        }

        $slug = $this->slug($package);
        $notes = [];

        // Precedence: flag > file > default patch.
        if (isset($bumpFlags[$slug])) {
            $level = BumpLevel::fromString($bumpFlags[$slug]);
            $newTag = $this->applyBump($latestTag, $level);
            return new TagCutResult($newTag, false, TagCutResult::SOURCE_FLAG);
        }

        $fileLevel = $this->readNextBumpFile($package, $packageRef, $notes);
        if ($fileLevel !== null) {
            $newTag = $this->applyBump($latestTag, $fileLevel);
            return new TagCutResult(
                $newTag,
                true,
                TagCutResult::SOURCE_NEXT_BUMP_FILE,
                $notes
            );
        }

        $level = BumpLevel::patch();
        $newTag = $this->applyBump($latestTag, $level);
        return new TagCutResult(
            $newTag,
            false,
            TagCutResult::SOURCE_DEFAULT_PATCH,
            $notes
        );
    }

    /**
     * Reads `.next-bump` from the release branch. Returns null when
     * absent or malformed. A malformed file is logged via $notes
     * (so the caller can warn) and treated as absent.
     *
     * @param array<int,string> $notes appended to with diagnostic strings
     */
    private function readNextBumpFile(string $package, string $ref, array &$notes): ?BumpLevel
    {
        $raw = $this->fileFetcher->fetchFile($package, $ref, self::NEXT_BUMP_PATH);
        if ($raw === null) {
            return null;
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            $notes[] = sprintf(
                'Warning: %s on %s@%s is empty; ignoring (falling through to patch default).',
                self::NEXT_BUMP_PATH,
                $package,
                $ref
            );
            return null;
        }
        try {
            return BumpLevel::fromString($trimmed);
        } catch (InvalidArgumentException $e) {
            $notes[] = sprintf(
                'Warning: %s on %s@%s contains invalid value %s; ignoring (falling through to patch default).',
                self::NEXT_BUMP_PATH,
                $package,
                $ref,
                self::quote($trimmed)
            );
            return null;
        }
    }

    private function applyBump(?string $latestTag, BumpLevel $level): string
    {
        if ($level->isExact()) {
            return (string) $level->exactVersion();
        }
        if ($latestTag === null) {
            throw new InvalidArgumentException(sprintf(
                "Cannot apply '%s' bump: no latest tag exists. Use an exact bump (--bump <repo>=v<semver>) for the first release.",
                $level->kind()
            ));
        }
        if (!preg_match('/^v(\d+)\.(\d+)\.(\d+)/', $latestTag, $m)) {
            throw new InvalidArgumentException(sprintf(
                "Cannot parse latest tag '%s' for bump.",
                $latestTag
            ));
        }
        $major = (int) $m[1];
        $minor = (int) $m[2];
        $patch = (int) $m[3];
        switch ($level->kind()) {
            case BumpLevel::KIND_MAJOR:
                return sprintf('v%d.0.0', $major + 1);
            case BumpLevel::KIND_MINOR:
                return sprintf('v%d.%d.0', $major, $minor + 1);
            case BumpLevel::KIND_PATCH:
            default:
                return sprintf('v%d.%d.%d', $major, $minor, $patch + 1);
        }
    }

    private function slug(string $package): string
    {
        if (strncmp($package, self::O3_SHOP_PREFIX, strlen(self::O3_SHOP_PREFIX)) === 0) {
            return substr($package, strlen(self::O3_SHOP_PREFIX));
        }
        return $package;
    }

    private static function quote(string $s): string
    {
        return "'" . $s . "'";
    }
}
