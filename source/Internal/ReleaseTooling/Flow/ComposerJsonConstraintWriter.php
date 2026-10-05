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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ConstraintEditPlan;
use RuntimeException;

/**
 * Applies the constraint-edit plans produced by §8 to a local clone's
 * composer.json. Operates on the file as text (not via json_decode →
 * json_encode) so existing key ordering, indentation, and trailing
 * whitespace stay intact — composer.json files in this org are
 * hand-curated and we don't want a release commit to also reflow them.
 */
final class ComposerJsonConstraintWriter
{
    /**
     * @param string                         $composerJsonPath absolute path
     * @param array<int,ConstraintEditPlan>  $edits            for this file only
     * @return array<int,string>                                repo-relative paths staged (always ['composer.json'] when edits applied)
     *
     * @throws RuntimeException when the file is unreadable, unwritable,
     *         or an expected pattern is missing (i.e. someone hand-edited
     *         since the dry-run plan was computed).
     */
    public function apply(string $composerJsonPath, array $edits): array
    {
        if ($edits === []) {
            return [];
        }
        if (!is_file($composerJsonPath) || !is_readable($composerJsonPath)) {
            throw new RuntimeException(sprintf('composer.json not readable: %s', $composerJsonPath));
        }
        $contents = file_get_contents($composerJsonPath);
        if ($contents === false) {
            throw new RuntimeException(sprintf('failed to read composer.json: %s', $composerJsonPath));
        }

        foreach ($edits as $edit) {
            $contents = $this->applyOne($contents, $edit, $composerJsonPath);
        }

        if (file_put_contents($composerJsonPath, $contents) === false) {
            throw new RuntimeException(sprintf('failed to write composer.json: %s', $composerJsonPath));
        }
        return ['composer.json'];
    }

    private function applyOne(string $contents, ConstraintEditPlan $edit, string $path): string
    {
        $dep = $edit->depPackage();
        $oldConstraint = $edit->update()->oldConstraint();
        $newConstraint = $edit->update()->newConstraint();

        // Match `"<dep>": "<oldConstraint>"` allowing any whitespace
        // between key and value so we tolerate a tab/double-space style.
        $pattern = sprintf(
            '/(["\'])%s\1(\s*:\s*)(["\'])%s\3/',
            preg_quote($dep, '/'),
            preg_quote($oldConstraint, '/')
        );
        // Braced backreferences are mandatory here: with `$3%s`, a new
        // constraint starting with a digit (e.g. `1.6.1-RC11` passed without
        // the `v` prefix) turns `$3` + `1` into the nonexistent group `$31`,
        // which PCRE expands to an empty string — silently eating the
        // opening quote and the constraint's first character.
        $replacement = sprintf(
            '${1}%s${1}${2}${3}%s${3}',
            $dep,
            $newConstraint
        );
        $count = 0;
        $result = preg_replace($pattern, $replacement, $contents, -1, $count);
        if ($result === null) {
            throw new RuntimeException(sprintf(
                'regex error while updating %s in %s',
                $dep,
                $path
            ));
        }
        if ($count === 0) {
            throw new RuntimeException(sprintf(
                'expected pattern not found in %s — looking for "%s": "%s" '
                . '(constraint may have been hand-edited since the dry-run plan)',
                $path,
                $dep,
                $oldConstraint
            ));
        }
        return $result;
    }
}
