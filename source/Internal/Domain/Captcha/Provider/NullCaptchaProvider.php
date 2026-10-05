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

namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider;

use OxidEsales\Eshop\Core\Request;

final class NullCaptchaProvider implements CaptchaProviderInterface
{
    public function getId(): string
    {
        return '';
    }

    public function getTitle(): string
    {
        return 'O3_CAPTCHA_PROVIDER_NONE';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function getHeadScript(): ?string
    {
        return null;
    }

    public function renderWidget(string $formId): string
    {
        return '';
    }

    public function verify(Request $request, string $formId): bool
    {
        return true;
    }
}
