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

/**
 * Algorithm Step 6: stitch a single cross-repo markdown body
 * out of per-repo `generate-notes` outputs plus a summary of repos
 * whose version did not change.
 *
 * Output shape:
 *
 *   ## o3-shop/<changed-repo-A>
 *
 *   <body returned by GitHub generate-notes>
 *
 *   ## o3-shop/<changed-repo-B>
 *
 *   <body returned by GitHub generate-notes>
 *
 *   ## Unchanged in this release
 *
 *   - `o3-shop/<unchanged-repo-X>` continues at `<from-pin>`
 *   - `o3-shop/<unchanged-repo-Y>` continues at `<from-pin>`
 *
 * The aggregated body becomes the body of the o3-shop draft GitHub
 * release. Per-repo draft releases retain their own GitHub-generated
 * notes; this aggregation is additive.
 *
 * See: openspec/.../release-notes-aggregation/spec.md
 */
class ReleaseNotesAggregator
{
    public const UNCHANGED_HEADING = '## Unchanged in this release';

    private ReleaseNotesProvider $provider;

    public function __construct(ReleaseNotesProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param array<int,CandidateState> $candidates
     */
    public function aggregate(array $candidates): string
    {
        $changed = [];
        $unchanged = [];
        foreach ($candidates as $state) {
            if ($state->isChanged()) {
                $changed[] = $state;
            } else {
                $unchanged[] = $state;
            }
        }

        $sections = [];

        foreach ($changed as $state) {
            $body = $this->provider->notesFor(
                $state->package(),
                $state->fromPin(),
                $state->chosenVersion()
            );
            $sections[] = sprintf("## %s\n\n%s", $state->package(), trim($body));
        }

        if ($unchanged !== []) {
            $lines = [self::UNCHANGED_HEADING, ''];
            foreach ($unchanged as $state) {
                $lines[] = sprintf(
                    '- `%s` continues at `%s`',
                    $state->package(),
                    $state->fromPin()
                );
            }
            $sections[] = implode("\n", $lines);
        }

        if ($sections === []) {
            return '';
        }

        return implode("\n\n", $sections) . "\n";
    }
}
