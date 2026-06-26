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

namespace OxidEsales\EshopCommunity\Internal\Domain\Captcha;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Consent\CaptchaConsentInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\ConsentExemptCaptchaProviderInterface;

final class CaptchaService implements CaptchaServiceInterface
{
    /** @var CaptchaProviderLocator */
    private $locator;
    /** @var CaptchaConfigurationInterface */
    private $configuration;
    /** @var CaptchaConsentInterface */
    private $consent;
    /** @var bool */
    private $headScriptEmitted = false;

    public function __construct(
        CaptchaProviderLocator $locator,
        CaptchaConfigurationInterface $configuration,
        CaptchaConsentInterface $consent
    ) {
        $this->locator = $locator;
        $this->configuration = $configuration;
        $this->consent = $consent;
    }

    public function isEnabledForForm(string $formId): bool
    {
        return $this->activeProvider()->isConfigured() && $this->configuration->isFormEnabled($formId);
    }

    public function renderForForm(string $formId): string
    {
        if (!$this->isEnabledForForm($formId)) {
            return '';
        }
        $provider = $this->activeProvider();
        if (!($provider instanceof ConsentExemptCaptchaProviderInterface)
            && !$this->consent->isConsentGranted(Registry::getRequest())) {
            return '<div class="o3-captcha-consent-notice">'
                . htmlspecialchars((string) Registry::getLang()->translateString('O3_CAPTCHA_CONSENT_NOTICE'), ENT_QUOTES)
                . '</div>';
        }

        $html = '';
        if (!$this->headScriptEmitted) {
            $script = $provider->getHeadScript();
            if ($script !== null) {
                $html .= $script;
                $this->headScriptEmitted = true;
            }
        }
        return $html . $provider->renderWidget($formId);
    }

    public function verifyForForm(string $formId, Request $request): bool
    {
        if (!$this->isEnabledForForm($formId)) {
            return true;
        }
        $provider = $this->activeProvider();
        if (!($provider instanceof ConsentExemptCaptchaProviderInterface)
            && !$this->consent->isConsentGranted($request)) {
            return true;
        }
        return $provider->verify($request, $formId);
    }

    private function activeProvider(): CaptchaProviderInterface
    {
        return $this->locator->getById($this->configuration->getActiveProviderId());
    }
}
