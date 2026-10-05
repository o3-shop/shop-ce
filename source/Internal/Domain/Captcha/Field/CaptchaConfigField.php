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

namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field;

final class CaptchaConfigField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CHECKBOX = 'checkbox';

    /** @var string */
    private $key;
    /** @var string */
    private $labelIdent;
    /** @var string */
    private $type;
    /** @var string */
    private $default;

    public function __construct(string $key, string $labelIdent, string $type, string $default = '')
    {
        $this->key = $key;
        $this->labelIdent = $labelIdent;
        $this->type = $type;
        $this->default = $default;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabelIdent(): string
    {
        return $this->labelIdent;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefault(): string
    {
        return $this->default;
    }
}
