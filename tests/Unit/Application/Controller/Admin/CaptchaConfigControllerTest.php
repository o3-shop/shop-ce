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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Application\Controller\Admin\CaptchaConfigController;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfiguration;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Configuration\CaptchaConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Field\CaptchaConfigField;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Form\CaptchaFormRegistry;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\CaptchaProviderLocator;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\Provider\NullCaptchaProvider;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for {@see CaptchaConfigController}.
 *
 * Covers the template-facing accessors (provider list, active-provider
 * fields, form ids, value getters) and the all-config-rows save behaviour,
 * without bootstrapping the admin session. A hand-built PSR container holds
 * the real CAPTCHA services so the accessors exercise production wiring; the
 * active-provider id is supplied through a mocked
 * {@see CaptchaConfigurationInterface} so no DB / oxconfig is needed.
 */
class CaptchaConfigControllerTest extends UnitTestCase
{
    /**
     * Id of the stub provider injected into the locator. Core no longer ships
     * any real provider (Google reCAPTCHA moved to a separate module), so the
     * tests register a fake one through the same tagged-services seam a module
     * would use.
     */
    private const FAKE_PROVIDER_ID = 'fake';

    /** @var array<string,mixed> */
    private array $requestParams = [];

    /** @var array<int,array{string,string,mixed,mixed,string}> tally of saveShopConfVar() calls */
    private array $savedConfVars = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestParams = [];
        $this->savedConfVars = [];
        Registry::set('logger', new NullLogger());
    }

    public function testGetCaptchaProvidersListsSelectableProvidersWithoutTheNullProvider(): void
    {
        $controller = $this->makeController('');

        $providers = $controller->getCaptchaProviders();

        $this->assertArrayHasKey(self::FAKE_PROVIDER_ID, $providers);
        $this->assertArrayNotHasKey('', $providers, 'The Null provider must not be offered as a selectable option.');
    }

    public function testGetAllProviderConfigFieldsAreKeyedByProviderId(): void
    {
        // Fields for every selectable provider are rendered (hidden) so the
        // operator can switch provider and fill credentials in one save.
        $controller = $this->makeController('');

        $all = $controller->getAllProviderConfigFields();

        $this->assertArrayHasKey(self::FAKE_PROVIDER_ID, $all);
        $this->assertArrayNotHasKey('', $all, 'The Null provider exposes no selectable config fields.');

        $keys = array_map(static fn ($field) => $field->getKey(), $all[self::FAKE_PROVIDER_ID]);
        $this->assertContains('scoreThreshold', $keys, 'Each provider surfaces its declared config fields.');
    }

    public function testGetProviderSettingValueForDelegatesPerProvider(): void
    {
        $controller = $this->makeController(self::FAKE_PROVIDER_ID);

        // The mocked configuration returns '' for any provider/key lookup.
        $this->assertSame('', $controller->getProviderSettingValueFor(self::FAKE_PROVIDER_ID, 'scoreThreshold'));
    }

    public function testGetCaptchaFormIdsReturnsAllRegisteredForms(): void
    {
        $controller = $this->makeController('');

        $this->assertCount(7, $controller->getCaptchaFormIds());
    }

    public function testSavePersistsProviderConsentAndPerFormFlags(): void
    {
        $this->requestParams = [
            CaptchaConfiguration::PROVIDER_KEY => self::FAKE_PROVIDER_ID,
            CaptchaConfiguration::CONSENT_KEY => '1',
            CaptchaConfiguration::FORM_PREFIX . 'contact' => '1',
            'providerField_fake_scoreThreshold' => '0.7',
        ];
        $this->mockConfigAndRequest();

        $controller = $this->makeController(self::FAKE_PROVIDER_ID);
        $controller->save();

        $byName = $this->indexBy($this->savedConfVars, 1);

        // Provider id + consent flag.
        $this->assertSame(self::FAKE_PROVIDER_ID, $byName[CaptchaConfiguration::PROVIDER_KEY][2]);
        $this->assertSame('1', $byName[CaptchaConfiguration::CONSENT_KEY][2]);

        // All seven per-form flags get written; the checked one is '1'.
        $this->assertSame('1', $byName[CaptchaConfiguration::FORM_PREFIX . 'contact'][2]);
        $this->assertSame('', $byName[CaptchaConfiguration::FORM_PREFIX . 'newsletter'][2]);

        // Per-provider settings are namespaced by provider id.
        $threshold = CaptchaConfiguration::PROVIDER_SETTING_PREFIX . self::FAKE_PROVIDER_ID . '_scoreThreshold';
        $this->assertSame('0.7', $byName[$threshold][2]);

        // Everything is written under the dedicated config section.
        foreach ($this->savedConfVars as $call) {
            $this->assertSame('module:captcha', $call[4]);
        }
    }

    public function testSaveSkipsProviderSettingsWhenNoProviderIsSelected(): void
    {
        $this->requestParams = [
            CaptchaConfiguration::PROVIDER_KEY => '',
        ];
        $this->mockConfigAndRequest();

        $controller = $this->makeController('');
        $controller->save();

        $byName = $this->indexBy($this->savedConfVars, 1);
        $this->assertSame('', $byName[CaptchaConfiguration::PROVIDER_KEY][2]);
        // No `sCaptcha_*` provider-setting rows when there is no active provider.
        $settingRows = array_filter(
            $this->savedConfVars,
            static fn ($call) => strpos($call[1], CaptchaConfiguration::PROVIDER_SETTING_PREFIX) === 0
        );
        $this->assertSame([], $settingRows);
    }

    /**
     * Build a controller whose container holds the real CAPTCHA services and
     * whose active-provider id is fixed via a mocked configuration.
     */
    private function makeController(string $activeProviderId): CaptchaConfigController
    {
        $configuration = $this->createMock(CaptchaConfigurationInterface::class);
        $configuration->method('getActiveProviderId')->willReturn($activeProviderId);
        $configuration->method('isFormEnabled')->willReturn(false);
        $configuration->method('isConsentRequired')->willReturn(true);
        $configuration->method('getProviderSetting')->willReturn('');

        $locator = new CaptchaProviderLocator(
            [$this->fakeProvider()],
            new NullCaptchaProvider()
        );

        $services = [
            CaptchaProviderLocator::class => $locator,
            CaptchaConfigurationInterface::class => $configuration,
            CaptchaFormRegistry::class => new CaptchaFormRegistry(),
        ];

        $container = new class ($services) implements ContainerInterface {
            /** @var array<string,object> */
            private array $services;

            public function __construct(array $services)
            {
                $this->services = $services;
            }

            public function get($id)
            {
                return $this->services[$id];
            }

            public function has($id): bool
            {
                return isset($this->services[$id]);
            }
        };

        $controller = (new \ReflectionClass(CaptchaConfigController::class))->newInstanceWithoutConstructor();
        $controller->setContainer($container);

        return $controller;
    }

    /**
     * A minimal stand-in for a CAPTCHA provider that a module would register.
     * Exposes a single `scoreThreshold` config field so the active-provider
     * field accessor has something to surface.
     */
    private function fakeProvider(): CaptchaProviderInterface
    {
        return new class () implements CaptchaProviderInterface {
            public function getId(): string
            {
                // Must match CaptchaConfigControllerTest::FAKE_PROVIDER_ID; a
                // nested anonymous class cannot read the outer private constant.
                return 'fake';
            }

            public function getTitle(): string
            {
                return 'Fake provider';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function getConfigFields(): array
            {
                return [new CaptchaConfigField('scoreThreshold', 'O3_CAPTCHA_SCORE_THRESHOLD', CaptchaConfigField::TYPE_NUMBER, '0.5')];
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
        };
    }

    private function mockConfigAndRequest(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRequestEscapedParameter')->willReturnCallback(
            fn ($name, $default = null) => $this->requestParams[$name] ?? $default
        );
        Registry::set(Request::class, $request);

        $config = $this->createMock(Config::class);
        $config->method('getShopId')->willReturn(1);
        $config->method('saveShopConfVar')->willReturnCallback(
            function ($type, $name, $value, $shopId = null, $module = '') {
                $this->savedConfVars[] = [(string) $type, (string) $name, $value, $shopId, (string) $module];
            }
        );
        Registry::set(Config::class, $config);
    }

    /**
     * @param array<int,array> $rows
     * @return array<string,array>
     */
    private function indexBy(array $rows, int $columnIndex): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row[$columnIndex]] = $row;
        }

        return $indexed;
    }
}
