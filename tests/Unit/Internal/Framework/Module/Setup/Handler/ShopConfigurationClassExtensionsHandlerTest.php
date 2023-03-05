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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\{
    ModuleConfiguration,
    ModuleConfiguration\ClassExtension
};
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\ShopConfigurationClassExtensionsHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/** @internal */
final class ShopConfigurationClassExtensionsHandlerTest extends TestCase
{
    public function testHandleOnModuleActivationWithInvalidConfigWillSkipExecution(): void
    {
        $shopId = 1;
        $daoMock = $this->prophesize(ShopConfigurationSettingDaoInterface::class);
        $emptyModuleConfig = new ModuleConfiguration();

        (new ShopConfigurationClassExtensionsHandler(
            $daoMock->reveal()
        ))->handleOnModuleActivation($emptyModuleConfig, $shopId);

        $daoMock->get(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS, $shopId)->shouldNotHaveBeenCalled();
        $daoMock->save(Argument::type(ShopConfigurationSetting::class))->shouldNotHaveBeenCalled();
    }

    public function testHandleOnModuleActivationWithSettingNotFoundWillCallSave(): void
    {
        $shopId = 1;
        $moduleId = 'some-module-id';
        $shopClass = 'some-shop-class';
        $moduleClass = 'some-module-class';
        $expectedConfig = [
            $moduleId => [$moduleClass],
        ];
        $daoMock = $this->prophesize(ShopConfigurationSettingDaoInterface::class);
        $daoMock->get(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS, $shopId)
            ->willThrow(EntryDoesNotExistDaoException::class);
        $shopConfig = $this->createEmptyShopConfig($shopId);
        $shopConfig->setValue($expectedConfig);
        $moduleConfig = (new ModuleConfiguration())
            ->setId($moduleId)
            ->addClassExtension(new ClassExtension($shopClass, $moduleClass));

        (new ShopConfigurationClassExtensionsHandler(
            $daoMock->reveal()
        ))->handleOnModuleActivation($moduleConfig, $shopId);

        $daoMock->save($shopConfig)->shouldHaveBeenCalledOnce();
    }

    public function testHandleOnModuleActivationWillSaveMergedConfig(): void
    {
        $shopId = 1;
        $moduleId = 'some-module-id';
        $shopClass1 = 'some-shop-class-1';
        $moduleClass1 = 'some-module-class-1';
        $initialConfig = ['some-key' => 'some-value'];
        $expectedConfig = [
            'some-key' => 'some-value',
            $moduleId => [$moduleClass1],
        ];
        $shopConfig = (new ShopConfigurationSetting())->setValue($initialConfig);
        $daoMock = $this->prophesize(ShopConfigurationSettingDaoInterface::class);
        $daoMock->get(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS, $shopId)->willReturn($shopConfig);

        $moduleConfig = (new ModuleConfiguration())
            ->setId($moduleId)
            ->addClassExtension(new ClassExtension($shopClass1, $moduleClass1));

        (new ShopConfigurationClassExtensionsHandler(
            $daoMock->reveal()
        ))->handleOnModuleActivation($moduleConfig, $shopId);

        $this->assertSame($expectedConfig, $shopConfig->getValue());
        $daoMock->save($shopConfig)->shouldHaveBeenCalledOnce();
    }

    public function testHandleOnModuleDeactivationWithInvalidConfigWillSkipExecution(): void
    {
        $shopId = 1;
        $configId = 'some-config-id';
        $daoMock = $this->prophesize(ShopConfigurationSettingDaoInterface::class);
        $moduleConfig = new ModuleConfiguration();

        (new ShopConfigurationClassExtensionsHandler(
            $daoMock->reveal()
        ))->handleOnModuleDeactivation($moduleConfig, $shopId);

        $daoMock->get($configId, $shopId)->shouldNotHaveBeenCalled();
        $daoMock->save(Argument::type(ShopConfigurationSetting::class))->shouldNotHaveBeenCalled();
    }

    public function testHandleOnModuleDeactivationWithSettingNotFoundWillCallSave(): void
    {
        $shopId = 1;
        $daoMock = $this->prophesize(ShopConfigurationSettingDaoInterface::class);
        $daoMock->get(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS, $shopId)
            ->willThrow(EntryDoesNotExistDaoException::class);
        $moduleConfig = (new ModuleConfiguration())
            ->setId('some-module-id')
            ->addClassExtension(new ClassExtension('some-shop-class', 'some-module-class'));

        (new ShopConfigurationClassExtensionsHandler(
            $daoMock->reveal()
        ))->handleOnModuleDeactivation($moduleConfig, $shopId);

        $daoMock->save($this->createEmptyShopConfig($shopId))->shouldHaveBeenCalledOnce();
    }

    public function testHandleOnModuleDeactivationWillSaveCleanedConfig(): void
    {
        $shopId = 1;
        $moduleId = 'some-module-id';
        $initialConfig = [
            'some-key' => 'some-value',
            $moduleId => ['anything'],
            'another-key' => 'another-value',
        ];
        $expectedConfig = [
            'some-key' => 'some-value',
            'another-key' => 'another-value',
        ];
        $shopConfig = (new ShopConfigurationSetting())->setValue($initialConfig);
        $daoMock = $this->prophesize(ShopConfigurationSettingDaoInterface::class);
        $daoMock->get(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS, $shopId)->willReturn($shopConfig);
        $moduleConfig = (new ModuleConfiguration())
            ->setId($moduleId)
            ->addClassExtension(new ClassExtension('some-shop-class', 'some-module-class'));

        (new ShopConfigurationClassExtensionsHandler(
            $daoMock->reveal()
        ))->handleOnModuleDeactivation($moduleConfig, $shopId);

        $this->assertSame($expectedConfig, $shopConfig->getValue());
        $daoMock->save($shopConfig)->shouldHaveBeenCalledOnce();
    }

    private function createEmptyShopConfig(int $shopId): ShopConfigurationSetting
    {
        return (new ShopConfigurationSetting())
            ->setShopId($shopId)
            ->setName(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS)
            ->setType(ShopSettingType::ARRAY)
            ->setValue([]);
    }
}
