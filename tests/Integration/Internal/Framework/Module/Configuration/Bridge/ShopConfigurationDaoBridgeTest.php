<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Configuration\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfiguration\ModuleSettingsDataMapper;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class ShopConfigurationDaoBridgeTest extends TestCase
{
    use ContainerTrait;
    /**
     * @var string
     */
    private $testModuleId = 'testModuleId';

    public function testSaving(): void
    {
        $shopConfigurationDaoBridge = $this->get(ShopConfigurationDaoBridgeInterface::class);

        $someModule = new ModuleConfiguration();
        $someModule
            ->setId('someId')
            ->setPath('somePath');

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($someModule);

        $shopConfigurationDaoBridge->save($shopConfiguration);

        $this->assertEquals(
            $shopConfiguration,
            $shopConfigurationDaoBridge->get()
        );
    }

    public function testSavingRemoveEnvironmentFile(): void
    {
        $shopConfigurationDaoBridge = $this->get(ShopConfigurationDaoBridgeInterface::class);

        $originalSetting = new Setting();
        $originalSetting
            ->setName('settingToOverwrite')
            ->setValue('originalValue')
            ->setType('int');

        $module = new ModuleConfiguration();
        $module
            ->setId($this->testModuleId)
            ->setPath('test')
            ->addModuleSetting($originalSetting);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($module);
        $shopConfigurationDaoBridge->save($shopConfiguration);

        $this->prepareTestEnvironmentShopConfigurationFile();

        $this->assertSame(
            'overwrittenValue',
            $shopConfigurationDaoBridge
                ->get()
                ->getModuleConfiguration($this->testModuleId)
                ->getModuleSetting('settingToOverwrite')
                ->getValue()
        );

        $shopConfigurationDaoBridge->save($shopConfiguration);

        $this->assertSame(
            'originalValue',
            $shopConfigurationDaoBridge
                ->get()
                ->getModuleConfiguration($this->testModuleId)
                ->getModuleSetting('settingToOverwrite')
                ->getValue()
        );
    }

    private function prepareTestEnvironmentShopConfigurationFile(): void
    {
        $fileStorageFactory = $this->get(FileStorageFactoryInterface::class);
        $storage = $fileStorageFactory->create(
            $this->get(ContextInterface::class)
                ->getProjectConfigurationDirectory() . 'environment/1.yaml'
        );

        $storage->save([
            'modules' => [
                $this->testModuleId => [
                    ModuleSettingsDataMapper::MAPPING_KEY => [
                        'settingToOverwrite' => [
                            'value' => 'overwrittenValue',
                        ]
                    ]
                ]
            ]
        ]);
    }
}
