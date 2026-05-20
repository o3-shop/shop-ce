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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIConfigWrapper;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIServiceWrapper;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\ServicesYamlConfigurationErrorEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleServicesActivationService;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleServicesActivationServiceTest extends TestCase
{
    private string $tmpDir = '';
    private string $modulePath = '';
    private string $servicesYaml = '';
    private string $generatedServicesPath = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/o3-msas-' . uniqid();
        $this->modulePath = $this->tmpDir . '/modules/mymodule';
        $this->generatedServicesPath = $this->tmpDir . '/var/generated/services.yaml';
        mkdir($this->modulePath, 0777, true);
        mkdir(dirname($this->generatedServicesPath), 0777, true);
        $this->servicesYaml = $this->modulePath . '/services.yaml';
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpDir);
    }

    private function makeContext(array $allShops = [1]): ContextInterface
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getAllShopIds')->willReturn($allShops);
        $context->method('getGeneratedServicesFilePath')->willReturn($this->generatedServicesPath);
        return $context;
    }

    private function makePathResolver(): ModulePathResolverInterface
    {
        $resolver = $this->createMock(ModulePathResolverInterface::class);
        $resolver->method('getFullModulePathFromConfiguration')->willReturn($this->modulePath);
        return $resolver;
    }

    public function testActivateIsNoopWhenServicesYamlMissing(): void
    {
        // No services.yaml on disk → NoServiceYamlException → silently return.
        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->expects($this->never())->method('saveProjectConfigFile');

        $service = new ModuleServicesActivationService(
            $dao,
            $this->createMock(EventDispatcherInterface::class),
            $this->makePathResolver(),
            $this->createMock(ModuleStateServiceInterface::class),
            $this->makeContext()
        );
        $service->activateModuleServices('mymodule', 1);
    }

    public function testActivateAddsImportAndShopIdToShopAwareServices(): void
    {
        // Create services.yaml on disk so the module-side load succeeds.
        file_put_contents($this->servicesYaml, "services: {}\n");

        $shopAwareService = $this->makeShopAwareServiceMock('my.service', true);
        $regularService = $this->makeShopAwareServiceMock('my.regular', false);

        $moduleConfig = $this->makeConfigWrapper([$shopAwareService, $regularService]);
        $moduleConfig->method('checkServiceClassesCanBeLoaded')->willReturn(true);

        $projectConfig = $this->makeConfigWrapper([]);
        $projectConfig->expects($this->once())
            ->method('addImport');
        $projectConfig->method('hasService')->willReturn(false);
        $projectConfig->expects($this->once())
            ->method('addOrUpdateService')
            ->with($shopAwareService);

        $shopAwareService->expects($this->once())
            ->method('addActiveShops')
            ->with([7]);

        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->method('loadDIConfigFile')->willReturn($moduleConfig);
        $dao->method('loadProjectConfigFile')->willReturn($projectConfig);
        $dao->expects($this->once())
            ->method('saveProjectConfigFile')
            ->with($projectConfig);

        $service = new ModuleServicesActivationService(
            $dao,
            $this->createMock(EventDispatcherInterface::class),
            $this->makePathResolver(),
            $this->createMock(ModuleStateServiceInterface::class),
            $this->makeContext()
        );
        $service->activateModuleServices('mymodule', 7);
    }

    public function testActivateDispatchesErrorEventAndPropagatesWhenServiceClassesCannotBeLoaded(): void
    {
        file_put_contents($this->servicesYaml, "services: {}\n");

        $moduleConfig = $this->makeConfigWrapper([]);
        $moduleConfig->method('checkServiceClassesCanBeLoaded')->willReturn(false);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ServicesYamlConfigurationErrorEvent::NAME,
                $this->isInstanceOf(ServicesYamlConfigurationErrorEvent::class)
            );

        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->method('loadDIConfigFile')->willReturn($moduleConfig);
        // Save must not be called when the module is in error state.
        $dao->expects($this->never())->method('saveProjectConfigFile');

        $service = new ModuleServicesActivationService(
            $dao,
            $dispatcher,
            $this->makePathResolver(),
            $this->createMock(ModuleStateServiceInterface::class),
            $this->makeContext()
        );

        // Activate: ServicesYamlConfigurationError must propagate so the
        // caller can fail-fast (deactivate is forgiving and swallows it).
        $this->expectException(\OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ServicesYamlConfigurationError::class);
        $service->activateModuleServices('mymodule', 1);
    }

    public function testDeactivateIsNoopWhenServicesYamlMissing(): void
    {
        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->expects($this->never())->method('saveProjectConfigFile');

        $service = new ModuleServicesActivationService(
            $dao,
            $this->createMock(EventDispatcherInterface::class),
            $this->makePathResolver(),
            $this->createMock(ModuleStateServiceInterface::class),
            $this->makeContext()
        );
        $service->deactivateModuleServices('mymodule', 1);
    }

    public function testDeactivateRemovesShopIdFromShopAwareServiceAndImportWhenLastShop(): void
    {
        file_put_contents($this->servicesYaml, "services: {}\n");

        $shopAwareService = $this->makeShopAwareServiceMock('my.service', true);
        $shopAwareService->expects($this->once())
            ->method('removeActiveShops')
            ->with([1]);

        $moduleConfig = $this->makeConfigWrapper([$shopAwareService]);
        $moduleConfig->method('checkServiceClassesCanBeLoaded')->willReturn(true);

        $projectConfig = $this->makeConfigWrapper([]);
        $projectConfig->method('hasService')->willReturn(true);
        $projectConfig->method('getService')->willReturn($shopAwareService);
        $projectConfig->expects($this->once())
            ->method('addOrUpdateService')
            ->with($shopAwareService);
        // Last active shop → import is removed.
        $projectConfig->expects($this->once())->method('removeImport');

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(false); // no other active shops

        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->method('loadDIConfigFile')->willReturn($moduleConfig);
        $dao->method('loadProjectConfigFile')->willReturn($projectConfig);
        $dao->expects($this->once())->method('saveProjectConfigFile');

        $service = new ModuleServicesActivationService(
            $dao,
            $this->createMock(EventDispatcherInterface::class),
            $this->makePathResolver(),
            $stateService,
            $this->makeContext([1, 2])
        );
        $service->deactivateModuleServices('mymodule', 1);
    }

    public function testDeactivateKeepsImportWhenAnotherShopStillUsesTheModule(): void
    {
        file_put_contents($this->servicesYaml, "services: {}\n");

        $moduleConfig = $this->makeConfigWrapper([]);
        $moduleConfig->method('checkServiceClassesCanBeLoaded')->willReturn(true);

        $projectConfig = $this->makeConfigWrapper([]);
        // Module is still active on another shop → import stays.
        $projectConfig->expects($this->never())->method('removeImport');

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(true);

        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->method('loadDIConfigFile')->willReturn($moduleConfig);
        $dao->method('loadProjectConfigFile')->willReturn($projectConfig);

        $service = new ModuleServicesActivationService(
            $dao,
            $this->createMock(EventDispatcherInterface::class),
            $this->makePathResolver(),
            $stateService,
            $this->makeContext([1, 2])
        );
        $service->deactivateModuleServices('mymodule', 1);
    }

    public function testDeactivateSwallowsServicesYamlConfigurationError(): void
    {
        // services.yaml exists but its classes can't be loaded → still no-op
        // (spec'd as "if it could never have been activated, there's nothing to deactivate").
        file_put_contents($this->servicesYaml, "services: {}\n");

        $moduleConfig = $this->makeConfigWrapper([]);
        $moduleConfig->method('checkServiceClassesCanBeLoaded')->willReturn(false);

        $dao = $this->createMock(ProjectYamlDaoInterface::class);
        $dao->method('loadDIConfigFile')->willReturn($moduleConfig);
        $dao->expects($this->never())->method('saveProjectConfigFile');

        $service = new ModuleServicesActivationService(
            $dao,
            $this->createMock(EventDispatcherInterface::class),
            $this->makePathResolver(),
            $this->createMock(ModuleStateServiceInterface::class),
            $this->makeContext()
        );
        $service->deactivateModuleServices('mymodule', 1);
    }

    private function makeConfigWrapper(array $services): DIConfigWrapper
    {
        $wrapper = $this->createMock(DIConfigWrapper::class);
        $wrapper->method('getServices')->willReturn($services);
        return $wrapper;
    }

    private function makeShopAwareServiceMock(string $key, bool $shopAware): DIServiceWrapper
    {
        $service = $this->getMockBuilder(DIServiceWrapper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isShopAware', 'getKey', 'addActiveShops', 'removeActiveShops'])
            ->getMock();
        $service->method('isShopAware')->willReturn($shopAware);
        $service->method('getKey')->willReturn($key);
        return $service;
    }

    private function rmrf(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->rmrf($path . '/' . $entry);
        }
        rmdir($path);
    }
}
