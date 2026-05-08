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

/**
 * The four bump kinds accepted by `--bump <repo>=<level>` and
 * `.next-bump`. Used by Section 7 (Algorithm Step 4).
 */
final class BumpLevel
{
    public const KIND_PATCH = 'patch';
    public const KIND_MINOR = 'minor';
    public const KIND_MAJOR = 'major';
    public const KIND_EXACT = 'exact';

    public const EXACT_PATTERN = '/^v\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/';

    private string $kind;
    private ?string $exactVersion;

    private function __construct(string $kind, ?string $exactVersion = null)
    {
        $this->kind = $kind;
        $this->exactVersion = $exactVersion;
    }

    public static function patch(): self
    {
        return new self(self::KIND_PATCH);
    }

    public static function minor(): self
    {
        return new self(self::KIND_MINOR);
    }

    public static function major(): self
    {
        return new self(self::KIND_MAJOR);
    }

    public static function exact(string $version): self
    {
        if (!preg_match(self::EXACT_PATTERN, $version)) {
            throw new InvalidArgumentException(
                "Not a valid exact version: '{$version}'. Expected v<major>.<minor>.<patch> with optional pre-release suffix."
            );
        }
        return new self(self::KIND_EXACT, $version);
    }

    /**
     * Parses the user-facing forms accepted by `--bump <repo>=<level>`
     * and `.next-bump`.
     *
     * @throws InvalidArgumentException on malformed input
     */
    public static function fromString(string $raw): self
    {
        switch ($raw) {
            case self::KIND_PATCH:
                return self::patch();
            case self::KIND_MINOR:
                return self::minor();
            case self::KIND_MAJOR:
                return self::major();
            default:
                if (preg_match(self::EXACT_PATTERN, $raw)) {
                    return self::exact($raw);
                }
                throw new InvalidArgumentException(
                    "Invalid bump level '{$raw}'. Expected patch | minor | major | v<semver>."
                );
        }
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function exactVersion(): ?string
    {
        return $this->exactVersion;
    }

    public function isExact(): bool
    {
        return $this->kind === self::KIND_EXACT;
    }
}
