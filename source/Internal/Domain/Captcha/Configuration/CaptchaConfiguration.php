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
    public const MODE_KEY = 'sCaptchaConsentMode';
    public const COOKIE_NAME_KEY = 'sCaptchaConsentCookieName';
    public const COOKIE_MARKER_KEY = 'sCaptchaConsentCookieMarker';

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

    public function getConsentMode(): string
    {
        $mode = (string) Registry::getConfig()->getConfigParam(self::MODE_KEY, '');

        if ($mode === '') {
            // Backward-compat: derive from the legacy boolean so existing
            // installs never silently flip to an insecure (always-on) state.
            $legacy = Registry::getConfig()->getConfigParam(self::CONSENT_KEY, true);
            return $legacy ? self::MODE_GATE : self::MODE_ALWAYS;
        }

        if (!in_array($mode, [self::MODE_ALWAYS, self::MODE_GATE, self::MODE_COOKIE], true)) {
            return self::MODE_GATE;
        }

        return $mode;
    }

    public function getConsentCookieName(): string
    {
        return (string) Registry::getConfig()->getConfigParam(self::COOKIE_NAME_KEY, '');
    }

    public function getConsentCookieMarker(): string
    {
        return (string) Registry::getConfig()->getConfigParam(self::COOKIE_MARKER_KEY, '');
    }

    public function getProviderSetting(string $providerId, string $key, $default = null)
    {
        return Registry::getConfig()->getConfigParam(self::PROVIDER_SETTING_PREFIX . $providerId . '_' . $key, $default);
    }
}
