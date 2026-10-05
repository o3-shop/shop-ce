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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfiguration;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Form\CaptchaFormRegistry;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use Psr\Container\ContainerInterface;

/**
 * CAPTCHA feature — admin configuration page.
 *
 * Owns the operator-facing `oxconfig` settings of the core CAPTCHA feature:
 *   - `sCaptchaProvider`             (str) — id of the active provider ('' = none)
 *   - `sCaptchaConsentMode`          (str) — consent mode: '', 'gate', or 'cookie'
 *   - `sCaptchaConsentCookieName`    (str) — cookie name to inspect in cookie mode
 *   - `sCaptchaConsentCookieMarker`  (str) — marker value expected in that cookie
 *   - `blCaptchaForm_<formId>`       (bool) — per-form on/off toggle
 *   - `sCaptcha_<providerId>_<key>`  (str)  — per-provider credential / setting
 *
 * All values are persisted under the `module:captcha` config section so the
 * settings stay grouped together and can be cleared as a unit.
 *
 * Lives as a dedicated admin page (mirrors {@see RevocationConfigController})
 * so the provider/field plumbing stays scoped to CAPTCHA logic. The template
 * reads the active provider's declared config fields and the registered form
 * ids through the accessors below.
 */
class CaptchaConfigController extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'captcha_config.tpl';

    /** @var ContainerInterface|null lazy-resolved; settable for tests */
    private $container = null;

    /**
     * @return string admin template name to render
     */
    public function render()
    {
        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * Selectable CAPTCHA providers, keyed by id.
     *
     * The Null provider (id '') is intentionally excluded — "no CAPTCHA" is
     * represented by an empty `sCaptchaProvider` value, not a list entry.
     *
     * @return array<string,string> id => title language ident
     */
    public function getCaptchaProviders(): array
    {
        $out = [];
        foreach ($this->locator()->getAll() as $id => $provider) {
            if ($id === '') {
                continue;
            }
            $out[$id] = $provider->getTitle();
        }

        return $out;
    }

    public function getActiveProviderId(): string
    {
        return $this->configuration()->getActiveProviderId();
    }

    /**
     * Config fields for every selectable provider, keyed by provider id.
     *
     * The template renders one (hidden) field group per provider and reveals
     * the selected provider's group client-side, so the operator can switch
     * provider and fill in its credentials in a single save — no intermediate
     * "save to reveal the fields" step.
     *
     * @return array<string,CaptchaConfigField[]> providerId => declared config fields
     */
    public function getAllProviderConfigFields(): array
    {
        $out = [];
        foreach ($this->locator()->getAll() as $id => $provider) {
            if ($id === '') {
                continue;
            }
            $out[$id] = $provider->getConfigFields();
        }

        return $out;
    }

    /**
     * @return string[] ids of the forms that can be CAPTCHA-protected
     */
    public function getCaptchaFormIds(): array
    {
        return $this->container()->get(CaptchaFormRegistry::class)->getFormIds();
    }

    public function getProviderSettingValueFor(string $providerId, string $key): string
    {
        return (string) $this->configuration()->getProviderSetting($providerId, $key, '');
    }

    public function isFormEnabled(string $formId): bool
    {
        return $this->configuration()->isFormEnabled($formId);
    }

    public function getConsentMode(): string
    {
        return $this->configuration()->getConsentMode();
    }

    public function getConsentCookieName(): string
    {
        return $this->configuration()->getConsentCookieName();
    }

    public function getConsentCookieMarker(): string
    {
        return $this->configuration()->getConsentCookieMarker();
    }

    /**
     * Persist the submitted CAPTCHA configuration.
     *
     * @return void
     */
    public function save()
    {
        $config = Registry::getConfig();
        $request = Registry::getRequest();
        $shopId = $config->getShopId();

        $provider = (string) $request->getRequestEscapedParameter(CaptchaConfiguration::PROVIDER_KEY);
        $config->saveShopConfVar('str', CaptchaConfiguration::PROVIDER_KEY, $provider, $shopId, 'module:captcha');
        $config->saveShopConfVar(
            'str',
            CaptchaConfiguration::MODE_KEY,
            (string) $request->getRequestEscapedParameter(CaptchaConfiguration::MODE_KEY),
            $shopId,
            'module:captcha'
        );
        $config->saveShopConfVar(
            'str',
            CaptchaConfiguration::COOKIE_NAME_KEY,
            (string) $request->getRequestEscapedParameter(CaptchaConfiguration::COOKIE_NAME_KEY),
            $shopId,
            'module:captcha'
        );
        $config->saveShopConfVar(
            'str',
            CaptchaConfiguration::COOKIE_MARKER_KEY,
            (string) $request->getRequestEscapedParameter(CaptchaConfiguration::COOKIE_MARKER_KEY),
            $shopId,
            'module:captcha'
        );

        foreach ($this->getCaptchaFormIds() as $formId) {
            $config->saveShopConfVar(
                'bool',
                CaptchaConfiguration::FORM_PREFIX . $formId,
                (bool) $request->getRequestEscapedParameter(CaptchaConfiguration::FORM_PREFIX . $formId) ? '1' : '',
                $shopId,
                'module:captcha'
            );
        }

        if ($provider !== '') {
            foreach ($this->locator()->getById($provider)->getConfigFields() as $field) {
                $name = CaptchaConfiguration::PROVIDER_SETTING_PREFIX . $provider . '_' . $field->getKey();
                $config->saveShopConfVar(
                    'str',
                    $name,
                    (string) $request->getRequestEscapedParameter(
                        'providerField_' . $provider . '_' . $field->getKey()
                    ),
                    $shopId,
                    'module:captcha'
                );
            }
        }

        parent::save();
    }

    /**
     * Test seam — inject a container without touching the DI factory.
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    private function container(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->getContainer();
        }

        return $this->container;
    }

    private function locator(): CaptchaProviderLocator
    {
        return $this->container()->get(CaptchaProviderLocator::class);
    }

    private function configuration(): CaptchaConfigurationInterface
    {
        return $this->container()->get(CaptchaConfigurationInterface::class);
    }
}
