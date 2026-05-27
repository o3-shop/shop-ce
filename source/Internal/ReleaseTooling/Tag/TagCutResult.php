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

/**
 * Output of TagCutter::cut(): the new tag plus the bookkeeping
 * Section 11 needs to know about (delete .next-bump file? source
 * of the bump decision?).
 */
final class TagCutResult
{
    public const SOURCE_SHOP_VERBATIM = 'shop-version-verbatim';
    public const SOURCE_FLAG = 'flag';
    public const SOURCE_NEXT_BUMP_FILE = 'next-bump-file';
    public const SOURCE_DEFAULT_PATCH = 'default-patch';

    private string $newTag;
    private bool $deleteNextBumpFile;
    private string $source;
    /** @var array<int,string> */
    private array $notes;

    /**
     * @param array<int,string> $notes diagnostic strings (e.g. malformed-file warnings)
     */
    public function __construct(string $newTag, bool $deleteNextBumpFile, string $source, array $notes = [])
    {
        $this->newTag = $newTag;
        $this->deleteNextBumpFile = $deleteNextBumpFile;
        $this->source = $source;
        $this->notes = $notes;
    }

    public function newTag(): string
    {
        return $this->newTag;
    }

    public function deleteNextBumpFile(): bool
    {
        return $this->deleteNextBumpFile;
    }

    public function source(): string
    {
        return $this->source;
    }

    /** @return array<int,string> */
    public function notes(): array
    {
        return $this->notes;
    }
}
