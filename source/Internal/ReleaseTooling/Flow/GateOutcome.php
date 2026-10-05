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
 * Per-gate verdict. Three shapes:
 *   STATUS_PASSED  the gate's check is satisfied
 *   STATUS_WARNING failed but does not abort the release (e.g. incoming PR)
 *   STATUS_ABORT   failed and the release run MUST stop
 *
 * Messages travel separately from the status so the runner can print
 * a single combined report at the end.
 */
final class GateOutcome
{
    public const STATUS_PASSED = 'passed';
    public const STATUS_WARNING = 'warning';
    public const STATUS_ABORT = 'abort';

    private string $gateName;
    private string $status;
    /** @var array<int,string> */
    private array $messages;

    /**
     * @param array<int,string> $messages
     */
    private function __construct(string $gateName, string $status, array $messages)
    {
        $this->gateName = $gateName;
        $this->status = $status;
        $this->messages = $messages;
    }

    public static function passed(string $gateName): self
    {
        return new self($gateName, self::STATUS_PASSED, []);
    }

    /**
     * @param array<int,string> $messages
     */
    public static function warning(string $gateName, array $messages): self
    {
        return new self($gateName, self::STATUS_WARNING, $messages);
    }

    /**
     * @param array<int,string> $messages
     */
    public static function abort(string $gateName, array $messages): self
    {
        return new self($gateName, self::STATUS_ABORT, $messages);
    }

    public function gateName(): string
    {
        return $this->gateName;
    }

    public function status(): string
    {
        return $this->status;
    }

    /** @return array<int,string> */
    public function messages(): array
    {
        return $this->messages;
    }

    public function isPassed(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    public function aborts(): bool
    {
        return $this->status === self::STATUS_ABORT;
    }
}
