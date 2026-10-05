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

namespace OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator;

/**
 * One missing asset surfaced by the {@see RevocationTemplateValidator}.
 *
 * A small DTO — value-typed, immutable from the outside (the validator
 * returns these in a flat array; consumers iterate and present them).
 *
 * Asset types:
 *   - "page-template" — a Smarty template under the active theme
 *   - "email-template" — a Smarty mail template under the active theme
 *   - "translation-key" — a translation key with no value in a lang file
 *
 * `langId` is null for assets that aren't language-scoped (page templates).
 *
 * `remediationHint` is the short, user-facing instruction the admin
 * sees in the per-asset list when a save / activation / theme switch is
 * rejected by the gate (and the same line printed by `o3:check-templates`).
 */
final class MissingAsset
{
    public const TYPE_PAGE_TEMPLATE = 'page-template';
    public const TYPE_EMAIL_TEMPLATE = 'email-template';
    public const TYPE_TRANSLATION_KEY = 'translation-key';

    private string $assetType;
    private string $expectedPath;
    private ?int $langId;
    private string $remediationHint;

    public function __construct(
        string $assetType,
        string $expectedPath,
        ?int $langId,
        string $remediationHint
    ) {
        $this->assetType = $assetType;
        $this->expectedPath = $expectedPath;
        $this->langId = $langId;
        $this->remediationHint = $remediationHint;
    }

    public function getAssetType(): string
    {
        return $this->assetType;
    }

    public function getExpectedPath(): string
    {
        return $this->expectedPath;
    }

    public function getLangId(): ?int
    {
        return $this->langId;
    }

    public function getRemediationHint(): string
    {
        return $this->remediationHint;
    }
}
