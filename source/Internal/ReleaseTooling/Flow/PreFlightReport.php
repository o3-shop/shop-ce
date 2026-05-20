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

/**
 * Combined verdict from running all pre-flight gates against one repo.
 */
final class PreFlightReport
{
    /** @var array<int,GateOutcome> */
    private array $outcomes;

    /**
     * @param array<int,GateOutcome> $outcomes
     */
    public function __construct(array $outcomes)
    {
        $this->outcomes = $outcomes;
    }

    /** @return array<int,GateOutcome> */
    public function outcomes(): array
    {
        return $this->outcomes;
    }

    public function shouldAbort(): bool
    {
        foreach ($this->outcomes as $outcome) {
            if ($outcome->aborts()) {
                return true;
            }
        }
        return false;
    }

    public function hasWarnings(): bool
    {
        foreach ($this->outcomes as $outcome) {
            if ($outcome->status() === GateOutcome::STATUS_WARNING) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,string> */
    public function allMessages(): array
    {
        $lines = [];
        foreach ($this->outcomes as $outcome) {
            foreach ($outcome->messages() as $message) {
                $lines[] = sprintf('[%s] %s', $outcome->gateName(), $message);
            }
        }
        return $lines;
    }
}
