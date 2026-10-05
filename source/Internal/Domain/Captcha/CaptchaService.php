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
    /** @var bool */
    private $bootstrapEmitted = false;

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

        // #183: consent-exempt providers make no third-party calls → never gated.
        if ($provider instanceof ConsentExemptCaptchaProviderInterface) {
            return $this->renderWidgetWithHeadScript($provider, $formId);
        }

        // Cookie mode defers the load/consent decision to the browser so the markup
        // is cache-safe and the provider's script is never emitted up-front.
        if ($this->configuration->getConsentMode() === CaptchaConfigurationInterface::MODE_COOKIE) {
            return $this->renderDeferredGate($provider, $formId);
        }

        // always → consent granted → widget; gate → not granted → notice (#183).
        if (!$this->consent->isConsentGranted(Registry::getRequest())) {
            return $this->renderNotice();
        }

        return $this->renderWidgetWithHeadScript($provider, $formId);
    }

    public function verifyForForm(string $formId, Request $request): bool
    {
        if (!$this->isEnabledForForm($formId)) {
            return true;
        }
        $provider = $this->activeProvider();
        if (!($provider instanceof ConsentExemptCaptchaProviderInterface)
            && !$this->consent->isConsentGranted($request)) {
            // Fail closed: consent is required but not granted, so the captcha cannot
            // load or verify. Passing the submission through would let bots bypass the
            // captcha entirely, so the verification fails instead.
            Registry::getLogger()->info(
                __METHOD__ . " - Blocking submission for form '$formId': "
                . 'consent is required but not granted, so the captcha cannot be verified.'
            );
            return false;
        }
        return $provider->verify($request, $formId);
    }

    private function renderWidgetWithHeadScript(CaptchaProviderInterface $provider, string $formId): string
    {
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

    private function renderNotice(): string
    {
        return '<div class="o3-captcha-consent-notice">'
            . htmlspecialchars((string) Registry::getLang()->translateString('O3_CAPTCHA_CONSENT_NOTICE'), ENT_QUOTES)
            . '</div>';
    }

    private function renderDeferredGate(CaptchaProviderInterface $provider, string $formId): string
    {
        // The provider's head script is included in the FIRST gate's template
        // only; the bootstrap re-creates whatever <script> nodes it finds.
        $deferred = '';
        if (!$this->headScriptEmitted) {
            $script = $provider->getHeadScript();
            if ($script !== null) {
                $deferred .= $script;
                $this->headScriptEmitted = true;
            }
        }
        $deferred .= $provider->renderWidget($formId);

        $gate = '<div class="o3-captcha-gate">'
            . $this->renderNotice()
            . '<template class="o3-captcha-deferred">' . $deferred . '</template>'
            . '</div>';

        return $gate . $this->bootstrapScript();
    }

    private function bootstrapScript(): string
    {
        if ($this->bootstrapEmitted) {
            return '';
        }
        $this->bootstrapEmitted = true;

        $cookieName = json_encode($this->configuration->getConsentCookieName());
        $marker = json_encode($this->configuration->getConsentCookieMarker());

        return '<script>'
            . '(function(){'
            . 'var COOKIE=' . $cookieName . ',MARKER=' . $marker . ';'
            . 'function o3CaptchaConsentGate(){'
            . 'if(!COOKIE||!MARKER){return;}'
            . 'var parts=document.cookie?document.cookie.split(";"):[];var granted=false;'
            . 'for(var i=0;i<parts.length;i++){var c=parts[i].replace(/^\s+/,"");'
            . 'if(c.indexOf(COOKIE+"=")===0){'
            . 'if(decodeURIComponent(c.substring(COOKIE.length+1)).indexOf(MARKER)!==-1){granted=true;}break;}}'
            . 'if(!granted){return;}'
            . 'var gates=document.querySelectorAll(".o3-captcha-gate");'
            . 'for(var g=0;g<gates.length;g++){'
            . 'var tpl=gates[g].querySelector("template.o3-captcha-deferred");if(!tpl){continue;}'
            . 'var notice=gates[g].querySelector(".o3-captcha-consent-notice");if(notice){notice.style.display="none";}'
            . 'var frag=tpl.content.cloneNode(true);var old=frag.querySelectorAll("script");'
            . 'for(var s=0;s<old.length;s++){var n=document.createElement("script");'
            . 'for(var a=0;a<old[s].attributes.length;a++){n.setAttribute(old[s].attributes[a].name,old[s].attributes[a].value);}'
            . 'n.text=old[s].textContent;old[s].parentNode.replaceChild(n,old[s]);}'
            . 'gates[g].appendChild(frag);}'
            . '}'
            . 'if(document.readyState!=="loading"){o3CaptchaConsentGate();}'
            . 'else{document.addEventListener("DOMContentLoaded",o3CaptchaConsentGate);}'
            . '})();'
            . '</script>';
    }

    private function activeProvider(): CaptchaProviderInterface
    {
        return $this->locator->getById($this->configuration->getActiveProviderId());
    }
}
