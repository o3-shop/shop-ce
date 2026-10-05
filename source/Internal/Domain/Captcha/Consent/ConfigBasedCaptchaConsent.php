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

namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;

final class ConfigBasedCaptchaConsent implements CaptchaConsentInterface
{
    /** @var CaptchaConfigurationInterface */
    private $configuration;

    public function __construct(CaptchaConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isConsentGranted(Request $request): bool
    {
        switch ($this->configuration->getConsentMode()) {
            case CaptchaConfigurationInterface::MODE_ALWAYS:
                return true;
            case CaptchaConfigurationInterface::MODE_COOKIE:
                return $this->cookieMarkerPresent();
            case CaptchaConfigurationInterface::MODE_GATE:
            default:
                return false;
        }
    }

    private function cookieMarkerPresent(): bool
    {
        $name = $this->configuration->getConsentCookieName();
        $marker = $this->configuration->getConsentCookieMarker();

        if ($name === '' || $marker === '') {
            return false;
        }

        $value = (string) Registry::getUtilsServer()->getOxCookie($name);

        return $value !== '' && strpos($value, $marker) !== false;
    }
}
