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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow;

use Composer\Semver\Comparator;
use RuntimeException;

/**
 * Rewrites a theme repo's `theme.php` `'version' => '…'` line to match
 * the tag bin/release is about to cut. Keeps the admin → Theme list's
 * displayed version in sync with the tag at release time — without
 * adding any runtime dependency on composer's package metadata, so the
 * "developer uploads their own theme into an existing installation"
 * workflow keeps working unchanged (their own theme.php remains the
 * source of truth).
 *
 * Edits theme.php as text (not via `include` + `var_export`) to
 * preserve key order, indentation, and any inline comments — these
 * files are hand-authored and we don't want a release commit to also
 * reflow them.
 *
 *   - Non-theme repos (no `theme.php` at the repo root) → returns []
 *     (no work, no exception). `bin/release` walks many non-theme
 *     packages.
 *   - Theme repos whose value is already current → returns [] (no
 *     edit needed, no commit churn).
 *   - Theme repos that need a bump → rewrites the line and returns
 *     `['theme.php']` so the orchestrator stages it alongside any
 *     `composer.json` edits.
 *   - Theme repos whose `theme.php` is missing the `'version'` line
 *     entirely → throws. The contract is broken; better to fail loud
 *     than silently skip.
 *   - Theme repos whose `theme.php` already declares a version NEWER
 *     than the tag being cut → throws. A hand-bumped theme.php ahead of
 *     the computed tag is the signature of a forgotten `--bump` /
 *     `.next-bump` (the v1.3.5 release silently downgraded a 1.4.0
 *     theme.php this way); better to abort than ship a downgrade.
 */
final class ThemeFileVersionWriter
{
    /**
     * @param string $repoPath   absolute path to the repo's working tree
     * @param string $newVersion the tag being cut (e.g. "v1.3.2"); a
     *                           leading "v"/"V" is stripped before writing
     *                           since theme.php stores bare semver
     *
     * @return array<int,string> repo-relative paths staged: ['theme.php']
     *                            when the line was rewritten, [] otherwise
     *
     * @throws RuntimeException when theme.php exists but the expected
     *         `'version' => '…'` pattern is missing, or when filesystem
     *         I/O fails.
     */
    public function apply(string $repoPath, string $newVersion): array
    {
        $themeFilePath = rtrim($repoPath, '/') . '/theme.php';
        if (!is_file($themeFilePath)) {
            return [];
        }
        $bareVersion = ltrim($newVersion, 'vV');

        $contents = file_get_contents($themeFilePath);
        if ($contents === false) {
            throw new RuntimeException(sprintf(
                'failed to read theme.php: %s',
                $themeFilePath
            ));
        }

        $pattern = "/(['\"])version\\1(\\s*=>\\s*)(['\"])([^'\"]*)\\3/";
        if (!preg_match($pattern, $contents, $m)) {
            throw new RuntimeException(sprintf(
                "theme.php missing expected `'version' => '…'` line: %s",
                $themeFilePath
            ));
        }
        if ($m[4] === $bareVersion) {
            return [];
        }
        if ($this->isDowngrade($m[4], $bareVersion)) {
            throw new RuntimeException(sprintf(
                "theme.php at %s already declares version '%s', which is newer than the '%s' tag about to be cut. "
                . 'This usually means a --bump flag or .next-bump file was forgotten. '
                . 'Refusing to downgrade — re-run with the intended bump level.',
                $themeFilePath,
                $m[4],
                $bareVersion
            ));
        }

        $rewritten = preg_replace_callback(
            $pattern,
            static function (array $match) use ($bareVersion): string {
                return $match[1] . 'version' . $match[1]
                    . $match[2]
                    . $match[3] . $bareVersion . $match[3];
            },
            $contents,
            1
        );
        if ($rewritten === null || $rewritten === $contents) {
            throw new RuntimeException(sprintf(
                'regex rewrite produced no change in %s',
                $themeFilePath
            ));
        }
        if (file_put_contents($themeFilePath, $rewritten) === false) {
            throw new RuntimeException(sprintf(
                'failed to write theme.php: %s',
                $themeFilePath
            ));
        }
        return ['theme.php'];
    }

    /**
     * True when the current theme.php version is strictly newer than the
     * version about to be written. Unparseable current values (themes
     * with non-semver placeholders) skip the guard — the rewrite is then
     * a correction, not a downgrade.
     */
    private function isDowngrade(string $currentVersion, string $newVersion): bool
    {
        try {
            return Comparator::greaterThan($currentVersion, $newVersion);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
