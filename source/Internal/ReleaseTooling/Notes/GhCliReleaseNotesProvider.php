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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use Symfony\Component\Process\Process;

/**
 * Reference implementation: shells out to `gh api -X POST
 * /repos/<owner>/<repo>/releases/generate-notes` with tag_name and
 * previous_tag_name.
 *
 * Trusts the maintainer's local `gh` setup (already required by the
 * existing manual release flow). Returns a stub markdown on any
 * non-zero gh exit so the aggregated body still ships — the
 * maintainer can edit the draft GitHub release before publishing.
 */
class GhCliReleaseNotesProvider implements ReleaseNotesProvider
{
    public const GH_BIN = 'gh';

    public function notesFor(string $package, string $previousTag, string $newTag): string
    {
        $process = new Process([
            self::GH_BIN,
            'api',
            '-X', 'POST',
            sprintf('/repos/%s/releases/generate-notes', PackageRepoSlug::resolve($package)),
            '-f', 'tag_name=' . $newTag,
            '-f', 'previous_tag_name=' . $previousTag,
            '--jq', '.body',
        ]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            return $this->stubFailureNotes($package, $previousTag, $newTag, trim($process->getErrorOutput()));
        }

        $body = trim($process->getOutput());
        if ($body === '') {
            return $this->stubEmptyNotes($package, $previousTag, $newTag);
        }
        return $body;
    }

    private function stubFailureNotes(string $package, string $previousTag, string $newTag, string $stderr): string
    {
        return sprintf(
            '_Release-notes generation failed for %s (%s..%s). Edit this section manually before publishing. (%s)_',
            $package,
            $previousTag,
            $newTag,
            $stderr === '' ? 'no stderr captured' : $stderr
        );
    }

    private function stubEmptyNotes(string $package, string $previousTag, string $newTag): string
    {
        return sprintf(
            '_No PRs detected by GitHub between %s and %s for %s._',
            $previousTag,
            $newTag,
            $package
        );
    }
}
