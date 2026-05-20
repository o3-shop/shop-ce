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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;

/**
 * Algorithm Step 3: per-candidate version resolution.
 *
 * Decision flow (first matching wins):
 *   - if !is_newer_than_from_pin AND !has_commits_beyond_latest_tag
 *       → CASE_UNCHANGED, reuse from_pin
 *   - if has_commits_beyond_latest_tag
 *       → CASE_NEEDS_NEW_TAG (Section 7 cuts)
 *   - if !stability_compatible(latest_tag, shop_to)
 *       → CASE_NEEDS_NEW_TAG (final shop release rejects pre-release dep)
 *   - otherwise (newer + stability OK + tag matches branch HEAD)
 *       → CASE_USABLE_TAG, use latest_tag
 *
 * See: openspec/.../release-graph-derivation/spec.md
 */
class CandidateVersionResolver
{
    /**
     * Matches the canonical o3-shop tag shape (vX.Y.Z, vX.Y.Z-RC1, etc.).
     * Tags that don't match are skipped from "highest semver tag" picking.
     */
    public const SEMVER_TAG_PATTERN = '/^v\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/';

    private RemoteRepoIntrospector $repos;
    private VersionParser $parser;

    public function __construct(RemoteRepoIntrospector $repos, ?VersionParser $parser = null)
    {
        $this->repos = $repos;
        $this->parser = $parser ?? new VersionParser();
    }

    /**
     * @param string $package    e.g. "o3-shop/shop-facts"
     * @param string $fromPin    constraint string from from_pin[] (exact tag or caret/tilde)
     * @param string $shopTo     the --to value (e.g. "v1.6.1-RC1") — drives stability check
     * @param string $packageRef the release branch on this package (e.g. "b-1.6", "main")
     */
    public function resolve(string $package, string $fromPin, string $shopTo, string $packageRef): VersionResolution
    {
        $tags = $this->repos->tags($package);
        $latestTag = $this->highestSemverTag(array_keys($tags));

        if ($latestTag === null) {
            return new VersionResolution(
                $package,
                VersionResolution::CASE_NEEDS_NEW_TAG,
                null,
                ['no semver tags found; Step 4 cuts the first tag'],
                null
            );
        }

        $branchSha = $this->repos->refCommit($package, $packageRef);
        $latestTagSha = $tags[$latestTag] ?? null;
        $hasCommitsBeyondLatestTag =
            $branchSha !== null
            && $latestTagSha !== null
            && $branchSha !== $latestTagSha;

        $isNewerThanFromPin = $this->isStrictlyNewer($latestTag, $fromPin);

        if (!$isNewerThanFromPin && !$hasCommitsBeyondLatestTag) {
            return new VersionResolution(
                $package,
                VersionResolution::CASE_UNCHANGED,
                $fromPin,
                ['latest_tag = ' . $latestTag . ', from_pin = ' . $fromPin . '; no new commits.'],
                $latestTag
            );
        }

        if ($hasCommitsBeyondLatestTag) {
            return new VersionResolution(
                $package,
                VersionResolution::CASE_NEEDS_NEW_TAG,
                null,
                [sprintf(
                    'commits exist on %s beyond %s; Step 4 cuts a new tag.',
                    $packageRef,
                    $latestTag
                )],
                $latestTag
            );
        }

        if (!self::stabilityCompatible($latestTag, $shopTo)) {
            return new VersionResolution(
                $package,
                VersionResolution::CASE_NEEDS_NEW_TAG,
                null,
                [sprintf(
                    'latest_tag %s is pre-release; --to %s is final. Step 4 cuts a final tag.',
                    $latestTag,
                    $shopTo
                )],
                $latestTag
            );
        }

        return new VersionResolution(
            $package,
            VersionResolution::CASE_USABLE_TAG,
            $latestTag,
            [sprintf(
                'latest_tag %s is newer than from_pin %s and stability matches %s.',
                $latestTag,
                $fromPin,
                $shopTo
            )],
            $latestTag
        );
    }

    /**
     * Returns true when $shopTo is final (no `-rc`/`-alpha`/`-beta`/...
     * pre-release segment) and $depTag is also final. RC `--to` accepts
     * either; only final `--to` rejects pre-release dep tags.
     */
    public static function stabilityCompatible(string $depTag, string $shopTo): bool
    {
        if (self::isPreRelease($shopTo)) {
            return true; // RC shop accepts either
        }
        return !self::isPreRelease($depTag); // final shop rejects pre-release dep
    }

    /**
     * Final tags are vX.Y.Z (no suffix) or vX.Y.Z-stable; everything
     * containing -rc / -alpha / -beta / -dev / -patch / -p / -pre is a
     * pre-release.
     */
    public static function isPreRelease(string $tag): bool
    {
        $stripped = ltrim($tag, 'v');
        return (new VersionParser())->parseStability($stripped) !== 'stable';
    }

    /**
     * @param array<int,string> $tags
     */
    private function highestSemverTag(array $tags): ?string
    {
        $semverTags = array_filter(
            $tags,
            static fn (string $t): bool => (bool) preg_match(self::SEMVER_TAG_PATTERN, $t)
        );
        if ($semverTags === []) {
            return null;
        }
        // Sort by semver, descending. Comparator handles RC vs final.
        usort($semverTags, function (string $a, string $b): int {
            return Comparator::greaterThan($a, $b) ? -1 : (Comparator::greaterThan($b, $a) ? 1 : 0);
        });
        return $semverTags[0];
    }

    /**
     * "Strictly newer" check that tolerates from_pin being a caret/tilde
     * constraint. Strips a leading `^` or `~` and uses the result as a
     * floor; latest_tag is "newer" when greater than the floor.
     *
     * Note: this loses subtlety for caret constraints whose actually-
     * installed version at --from time was newer than the floor. The
     * common case (exact pins) compares exactly; constraint cases over-
     * report "newer" rather than under-report, which only affects the
     * case-1-vs-case-2 split, never correctness of what gets shipped.
     */
    private function isStrictlyNewer(string $latestTag, string $fromPin): bool
    {
        $floor = ltrim($fromPin, '^~');
        // Bail out if floor isn't parseable; treat as "newer" so the
        // resolver errs on Case 2/3 rather than silently sticking with
        // an unparseable from_pin.
        try {
            return Comparator::greaterThan($latestTag, $floor);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
