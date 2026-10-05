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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint;

use Composer\Semver\Semver;

/**
 * Algorithm Step 5: decide whether and how to rewrite a composer.json
 * constraint when a candidate's chosen version arrives.
 *
 * Parsimony: only edit when the existing constraint does NOT already
 * satisfy the chosen version. Caret/tilde constraints typically still
 * cover patch and minor bumps, so most pin locations stay untouched.
 *
 * When an edit IS needed:
 *   - exact pin (e.g. `v1.5.4`)        → replace with chosen verbatim
 *   - caret    (e.g. `^v1.3.0`)        → re-anchor at chosen
 *   - tilde    (e.g. `~v1.3.0`)        → re-anchor at chosen
 *   - everything else (ranges, ORs, …) → replace with chosen verbatim
 *
 * The Section 11 per-repo flow does the actual file write; this
 * class is pure decision logic.
 *
 * See: openspec/.../release-graph-derivation/spec.md
 */
class ConstraintUpdater
{
    public function update(string $existingConstraint, string $chosenVersion): ConstraintUpdate
    {
        $existing = trim($existingConstraint);
        $chosen = trim($chosenVersion);

        if ($this->satisfies($chosen, $existing)) {
            return new ConstraintUpdate(
                $existingConstraint,
                $existingConstraint,
                ConstraintUpdate::SHAPE_UNCHANGED
            );
        }

        if ($existing !== '' && $existing[0] === '^') {
            return new ConstraintUpdate(
                $existingConstraint,
                '^' . $chosen,
                ConstraintUpdate::SHAPE_CARET_WIDENED
            );
        }

        if ($existing !== '' && $existing[0] === '~') {
            return new ConstraintUpdate(
                $existingConstraint,
                '~' . $chosen,
                ConstraintUpdate::SHAPE_TILDE_WIDENED
            );
        }

        if ($this->looksLikeExactVersion($existing)) {
            return new ConstraintUpdate(
                $existingConstraint,
                $chosen,
                ConstraintUpdate::SHAPE_EXACT_REPLACED
            );
        }

        return new ConstraintUpdate(
            $existingConstraint,
            $chosen,
            ConstraintUpdate::SHAPE_FALLBACK_REPLACED
        );
    }

    /**
     * Exact-pin shape: optional `v` followed by N.N.N with optional
     * pre-release suffix. Matches the o3-shop tag convention.
     */
    private function looksLikeExactVersion(string $constraint): bool
    {
        return (bool) preg_match('/^v?\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/', $constraint);
    }

    /**
     * Wraps `Composer\Semver\Semver::satisfies` so we can isolate the
     * library quirk where it throws on syntactically invalid
     * constraints; an unparseable constraint is treated as
     * "doesn't satisfy" so the caller falls into the rewrite branch.
     */
    private function satisfies(string $version, string $constraint): bool
    {
        try {
            return Semver::satisfies($version, $constraint);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
