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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\UpdateCheck;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckService;
use OxidEsales\TestingLibrary\helpers\ExceptionLogFileHelper;
use PHPUnit\Framework\TestCase;

class UpdateCheckServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clear log file to prevent ERROR entries from testCheckReturnsEmptyResultOnException
        // bleeding into the next test class (see issue #103)
        if (defined('OX_LOG_FILE') && is_file(OX_LOG_FILE)) {
            (new ExceptionLogFileHelper(OX_LOG_FILE))->clearExceptionLogFile();
        }
        parent::tearDown();
    }

    public function testBuildPayloadContainsRequiredKeys(): void
    {
        $moduleConfigA = $this->createModuleConfiguration('mod-a', '1.0.0');
        $moduleConfigB = $this->createModuleConfiguration('mod-b', '2.3.1');

        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration->method('getModuleConfigurations')
            ->willReturn([$moduleConfigA, $moduleConfigB]);

        $shopConfigBridge = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $shopConfigBridge->method('get')->willReturn($shopConfiguration);

        $moduleActivationBridge = $this->createMock(ModuleActivationBridgeInterface::class);

        $service = new UpdateCheckService($shopConfigBridge, $moduleActivationBridge);
        $payload = $service->buildPayload();

        $this->assertArrayHasKey('shop_version', $payload);
        $this->assertArrayHasKey('domain', $payload);
        $this->assertArrayHasKey('modules', $payload);
        $this->assertIsArray($payload['modules']);
        $this->assertCount(2, $payload['modules']);
        $this->assertSame('1.0.0', $payload['modules']['mod-a']);
        $this->assertSame('2.3.1', $payload['modules']['mod-b']);
    }

    public function testBuildPayloadOmitsModulesWithEmptyVersion(): void
    {
        // The OpenAPI contract for the o3-shop/update server requires every
        // module version to be a non-empty string (1-255 chars). OXID lets
        // modules ship without a version in metadata.php, in which case
        // ModuleConfiguration::getVersion() returns an empty string. Sending
        // those would make the server reject the entire payload with
        // HTTP 400 invalid_request and break the check for the whole shop.
        $versioned = $this->createModuleConfiguration('versioned-mod', '1.0.0');
        $unversioned = $this->createModuleConfiguration('unversioned-mod', '');

        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration->method('getModuleConfigurations')
            ->willReturn([$versioned, $unversioned]);

        $shopConfigBridge = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $shopConfigBridge->method('get')->willReturn($shopConfiguration);

        $moduleActivationBridge = $this->createMock(ModuleActivationBridgeInterface::class);

        $service = new UpdateCheckService($shopConfigBridge, $moduleActivationBridge);
        $payload = $service->buildPayload();

        $this->assertArrayHasKey('versioned-mod', $payload['modules']);
        $this->assertSame('1.0.0', $payload['modules']['versioned-mod']);
        $this->assertArrayNotHasKey(
            'unversioned-mod',
            $payload['modules'],
            'Modules without a declared version must be omitted from the payload to avoid the server returning HTTP 400 invalid_request.'
        );
    }

    public function testBuildPayloadStripsVPrefixFromShopVersion(): void
    {
        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration->method('getModuleConfigurations')->willReturn([]);

        $shopConfigBridge = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $shopConfigBridge->method('get')->willReturn($shopConfiguration);

        $moduleActivationBridge = $this->createMock(ModuleActivationBridgeInterface::class);

        $service = new UpdateCheckService($shopConfigBridge, $moduleActivationBridge);
        $payload = $service->buildPayload();

        $this->assertIsString($payload['shop_version']);
        $this->assertNotSame(
            '',
            $payload['shop_version'],
            'shop_version must always be present on the wire'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/^[vV]/',
            $payload['shop_version'],
            'shop_version must be sent without the leading `v` so it matches the OpenAPI contract '
            . '(`1.5.4` not `v1.5.4`) the o3-shop/update server enforces.'
        );
    }

    public function testBuildPayloadWithNoModules(): void
    {
        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration->method('getModuleConfigurations')->willReturn([]);

        $shopConfigBridge = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $shopConfigBridge->method('get')->willReturn($shopConfiguration);

        $moduleActivationBridge = $this->createMock(ModuleActivationBridgeInterface::class);

        $service = new UpdateCheckService($shopConfigBridge, $moduleActivationBridge);
        $payload = $service->buildPayload();

        $this->assertSame([], $payload['modules']);
        $this->assertNotEmpty($payload['shop_version']);
    }

    public function testCheckReturnsEmptyResultOnException(): void
    {
        $shopConfigBridge = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $shopConfigBridge->method('get')->willThrowException(new \RuntimeException('DB down'));

        $moduleActivationBridge = $this->createMock(ModuleActivationBridgeInterface::class);

        $service = new UpdateCheckService($shopConfigBridge, $moduleActivationBridge);
        $result = $service->check();

        $this->assertFalse($result->isCoreUpdateAvailable());
        $this->assertSame('', $result->getLatestCoreVersion());
        $this->assertSame([], $result->getOutdatedModules());
    }

    public function testCheckReportsProvidersUnreachableOnException(): void
    {
        $shopConfigBridge = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $shopConfigBridge->method('get')->willThrowException(new \RuntimeException('DB down'));

        $moduleActivationBridge = $this->createMock(ModuleActivationBridgeInterface::class);

        $service = new UpdateCheckService($shopConfigBridge, $moduleActivationBridge);
        $result = $service->check();

        $this->assertFalse(
            $result->areProvidersReachable(),
            'When the check throws and falls back to the catch-all, the result must mark providers as unreachable so the admin header hides the re-check icon.'
        );
    }

    public function testGetCachedResultReturnsNullWhenSessionEmpty(): void
    {
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable(UpdateCheckService::CACHE_SESSION_KEY);
        $service = $this->makeServiceWithEmptyShopConfig();
        $this->assertNull($service->getCachedResult());
    }

    public function testGetCachedResultReturnsNullForExpiredEntry(): void
    {
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable(
            UpdateCheckService::CACHE_SESSION_KEY,
            [
                // older than CACHE_TTL_SECONDS (86400)
                'timestamp' => time() - UpdateCheckService::CACHE_TTL_SECONDS - 1,
                'result'    => \OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckResult::empty(),
            ]
        );
        $service = $this->makeServiceWithEmptyShopConfig();
        $this->assertNull($service->getCachedResult());
    }

    public function testGetCachedResultReturnsNullWhenResultPayloadIsWrongType(): void
    {
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable(
            UpdateCheckService::CACHE_SESSION_KEY,
            [
                'timestamp' => time(),
                'result'    => 'not-a-result-object',
            ]
        );
        $service = $this->makeServiceWithEmptyShopConfig();
        $this->assertNull($service->getCachedResult());
    }

    public function testGetCachedResultReturnsCachedFreshResult(): void
    {
        $cached = \OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckResult::empty();
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable(
            UpdateCheckService::CACHE_SESSION_KEY,
            [
                'timestamp' => time(),
                'result'    => $cached,
            ]
        );
        $service = $this->makeServiceWithEmptyShopConfig();
        $this->assertSame($cached, $service->getCachedResult());
    }

    public function testCheckUsesCachedResultWhenAvailable(): void
    {
        $cached = \OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckResult::empty();
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable(
            UpdateCheckService::CACHE_SESSION_KEY,
            ['timestamp' => time(), 'result' => $cached]
        );
        $service = $this->makeServiceWithEmptyShopConfig();
        $this->assertSame($cached, $service->check(false));
    }

    public function testCheckBypassesCacheWhenForceRefreshIsTrue(): void
    {
        $cached = \OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckResult::empty();
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable(
            UpdateCheckService::CACHE_SESSION_KEY,
            ['timestamp' => time(), 'result' => $cached]
        );
        // forceRefresh skips cache → falls through to network calls. In the
        // unit-test environment those fail and the catch-all returns
        // unreachable(). Verify that a different (unreachable) result comes
        // back rather than the cached one.
        $service = $this->makeServiceWithEmptyShopConfig();
        $result = $service->check(true);
        // The returned object must NOT be the cached instance.
        $this->assertNotSame($cached, $result);
    }

    public function testCacheResultRoundTripsThroughSession(): void
    {
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable(UpdateCheckService::CACHE_SESSION_KEY);
        $service = $this->makeServiceWithEmptyShopConfig();

        $result = new \OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckResult(
            true,
            '1.6.0',
            'https://example.com/release'
        );
        $method = new \ReflectionMethod($service, 'cacheResult');
        $method->setAccessible(true);
        $method->invoke($service, $result);

        $cached = $service->getCachedResult();
        $this->assertNotNull($cached);
        $this->assertTrue($cached->isCoreUpdateAvailable());
        $this->assertSame('1.6.0', $cached->getLatestCoreVersion());
    }

    public function testParseEndpointResponseExtractsCoreVersionAndUpdateLink(): void
    {
        $service = $this->makeServiceWithEmptyShopConfig();
        $method = new \ReflectionMethod($service, 'parseEndpointResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'core_not_actual' => true,
            'actual_version'  => '1.6.0',
            'update_link'     => 'https://example.com/download',
            'plugins'         => [],
        ], []);

        $this->assertTrue($result->isCoreUpdateAvailable());
        $this->assertSame('1.6.0', $result->getLatestCoreVersion());
        $this->assertSame('https://example.com/download', $result->getUpdateLink());
        $this->assertSame([], $result->getOutdatedModules());
    }

    public function testParseEndpointResponseFiltersInactiveModulesFromOutdatedList(): void
    {
        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $bridge->method('isActive')->willReturnCallback(static fn ($id) => $id === 'mod-active');

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('getModuleConfigurations')->willReturn([]);
        $bridgeShop = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $bridgeShop->method('get')->willReturn($shopConfig);

        $service = new UpdateCheckService($bridgeShop, $bridge);
        $method = new \ReflectionMethod($service, 'parseEndpointResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'core_not_actual' => false,
            'plugins' => [
                ['code' => 'mod-active',   'version' => '2.0.0', 'url' => 'https://x/active'],
                ['code' => 'mod-inactive', 'version' => '3.0.0', 'url' => 'https://x/inactive'],
                ['code' => '',             'version' => '1.0.0'], // empty code → skip
            ],
        ], ['mod-active' => '1.0.0']);

        $outdated = $result->getOutdatedModules();
        $this->assertCount(1, $outdated, 'Inactive and empty-code modules must be filtered out.');
        $this->assertSame('mod-active', $outdated[0]['id']);
        $this->assertSame('1.0.0', $outdated[0]['installed_version']);
        $this->assertSame('2.0.0', $outdated[0]['latest_version']);
    }

    public function testParseEndpointResponseHandlesMissingPluginsKey(): void
    {
        $service = $this->makeServiceWithEmptyShopConfig();
        $method = new \ReflectionMethod($service, 'parseEndpointResponse');
        $method->setAccessible(true);

        $result = $method->invoke($service, ['core_not_actual' => false], []);
        $this->assertSame([], $result->getOutdatedModules());
    }

    public function testNormalizeVersionStripsLeadingV(): void
    {
        $reflection = new \ReflectionMethod(UpdateCheckService::class, 'normalizeVersion');
        $reflection->setAccessible(true);

        $this->assertSame('1.6.0', $reflection->invoke(null, 'v1.6.0'));
        $this->assertSame('1.6.0', $reflection->invoke(null, 'V1.6.0')); // capital V too
        $this->assertSame('1.6.0', $reflection->invoke(null, '1.6.0'));  // already bare
        $this->assertSame('', $reflection->invoke(null, ''));            // empty string passthrough
    }

    public function testEndpointAndConstantsHaveExpectedValues(): void
    {
        $this->assertSame('https://updates.o3-shop.com/check/v1', UpdateCheckService::ENDPOINT);
        $this->assertSame('updateCheckResult', UpdateCheckService::CACHE_SESSION_KEY);
        $this->assertSame(86400, UpdateCheckService::CACHE_TTL_SECONDS);
        $this->assertSame(5, UpdateCheckService::CURL_TIMEOUT);
    }

    private function makeServiceWithEmptyShopConfig(): UpdateCheckService
    {
        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('getModuleConfigurations')->willReturn([]);

        $bridgeShop = $this->createMock(ShopConfigurationDaoBridgeInterface::class);
        $bridgeShop->method('get')->willReturn($shopConfig);

        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);

        return new UpdateCheckService($bridgeShop, $bridge);
    }

    /**
     * @param string $id
     * @param string $version
     *
     * @return ModuleConfiguration
     */
    private function createModuleConfiguration(string $id, string $version): ModuleConfiguration
    {
        $config = new ModuleConfiguration();
        $config->setId($id);
        $config->setVersion($version);

        return $config;
    }
}
