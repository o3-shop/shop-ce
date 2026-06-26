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

namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration;

use OxidEsales\Eshop\Core\Registry;

final class CaptchaConfiguration implements CaptchaConfigurationInterface
{
    public const PROVIDER_KEY = 'sCaptchaProvider';
    public const CONSENT_KEY = 'blCaptchaRequireConsent';
    public const FORM_PREFIX = 'blCaptchaForm_';
    public const PROVIDER_SETTING_PREFIX = 'sCaptcha_';

    public function getActiveProviderId(): string
    {
        return (string) Registry::getConfig()->getConfigParam(self::PROVIDER_KEY, '');
    }

    public function isFormEnabled(string $formId): bool
    {
        return (bool) Registry::getConfig()->getConfigParam(self::FORM_PREFIX . $formId, false);
    }

    public function isConsentRequired(): bool
    {
        $value = Registry::getConfig()->getConfigParam(self::CONSENT_KEY, true);
        return (bool) $value;
    }

    public function getProviderSetting(string $providerId, string $key, $default = null)
    {
        return Registry::getConfig()->getConfigParam(self::PROVIDER_SETTING_PREFIX . $providerId . '_' . $key, $default);
    }
}
