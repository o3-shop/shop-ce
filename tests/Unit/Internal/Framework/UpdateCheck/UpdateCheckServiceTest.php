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
