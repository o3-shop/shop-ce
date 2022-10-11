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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\{
    ModuleConfiguration\ModuleSettingsDataMapper
};
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ShopConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Webmozart\PathUtil\Path;

final class ShopConfigurationDaoTest extends TestCase
{
    use ContainerTrait;

    private $testModuleId = 'testModuleId';
    private $testedSetting = 'settingToOverwrite';
    private $originalValue = 'some-original-value';
    private $newValue = 'some-new-value';

    public function testSave(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);

        $module = new ModuleConfiguration();
        $module
            ->setId('test')
            ->setPath('test');

        $shopConfigurationWithModule = new ShopConfiguration();
        $shopConfigurationWithModule->addModuleConfiguration($module);
        $shopConfigurationDao->save($shopConfigurationWithModule, 1);

        $shopConfiguration = new ShopConfiguration();
        $shopConfigurationDao->save($shopConfiguration, 2);

        $this->assertEquals(
            $shopConfigurationWithModule,
            $shopConfigurationDao->get(1)
        );

        $this->assertEquals(
            $shopConfiguration,
            $shopConfigurationDao->get(2)
        );
    }

    public function testEnvironmentConfigurationOverwritesShopConfiguration(): void
    {
        $this->configureModuleInShopFile();
        $this->configureModuleInEnvironmentFile();

        $this->assertSame(
            $this->newValue,
            $this->get(ShopConfigurationDaoInterface::class)
                ->get(1)
                ->getModuleConfiguration($this->testModuleId)
                ->getModuleSetting($this->testedSetting)
                ->getValue()
        );
    }

    public function testGetAlwaysReturnsTheSameObjectIfConfigurationWasNotChanged(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);

        $shopConfiguration = $shopConfigurationDao->get(1);

        $this->assertSame(
            $shopConfiguration,
            $shopConfigurationDao->get(1)
        );
    }

    public function testGetAll(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);

        $this->assertEquals(
            new ShopConfiguration(),
            $shopConfigurationDao->get(1)
        );

        $shopConfigurationDao->save(new ShopConfiguration(), 3);

        $this->assertEquals(
            [
                1 => new ShopConfiguration(),
                3 => new ShopConfiguration(),
            ],
            $shopConfigurationDao->getAll()
        );
    }

    public function testWithIncorrectNode(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);

        $yamlStorage = $this->get(FileStorageFactoryInterface::class)->create(
            Path::join(
                $this->get(BasicContextInterface::class)->getProjectConfigurationDirectory(),
                'shops/1.yaml'
            )
        );

        $yamlStorage->save(['incorrectKey']);

        $this->expectException(InvalidConfigurationException::class);
        $shopConfigurationDao->get(1);
    }

    public function testGetIncorrectShopId(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);
        $shopConfigurationDao->save(new ShopConfiguration(), 2);
        $shopConfigurationDao->save(new ShopConfiguration(), 3);

        $this->expectException(ShopConfigurationNotFoundException::class);
        $shopConfigurationDao->get(99);
    }

    public function testGetCorrectShopId(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);

        $shopConfiguration = $shopConfigurationDao->get(1);

        $this->assertSame(
            $shopConfiguration,
            $shopConfigurationDao->get(1)
        );
    }

    public function testBadShopConfigurationFile(): void
    {
        $fileStorageFactory = $this->get(FileStorageFactoryInterface::class);
        $storage = $fileStorageFactory->create(
            $this->get(BasicContextInterface::class)->getProjectConfigurationDirectory() . '/shops/1.yaml'
        );
        $storage->save(["test" => "test"]);

        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);

        $this->expectException(InvalidConfigurationException::class);
        $shopConfigurationDao->get(1);
    }

    public function testBadEnvironmentConfigurationFile(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);

        $fileStorageFactory = $this->get(FileStorageFactoryInterface::class);
        $storage = $fileStorageFactory->create(
            $this->get(BasicContextInterface::class)->getProjectConfigurationDirectory() . '/environment/1.yaml'
        );
        $storage->save(["test" => "test"]);

        $this->expectException(InvalidConfigurationException::class);
        $shopConfigurationDao->get(1);
    }

    public function testDeleteAll(): void
    {
        $shopConfigurationDao = $this->get(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao->save(new ShopConfiguration(), 1);
        $shopConfigurationDao->save(new ShopConfiguration(), 2);
        $shopConfigurationDao->save(new ShopConfiguration(), 3);

        $shopConfigurationDao->deleteAll();

        $this->expectException(ShopConfigurationNotFoundException::class);
        $this->assertEquals(
            [],
            $shopConfigurationDao->get(1)
        );
    }

    private function configureModuleInEnvironmentFile(): void
    {
        $storage = $this->get(FileStorageFactoryInterface::class)
            ->create(
                $this->get(ContextInterface::class)
                    ->getProjectConfigurationDirectory() . 'environment/1.yaml'
            );

        $storage->save([
            'modules' => [
                $this->testModuleId => [
                    ModuleSettingsDataMapper::MAPPING_KEY => [
                        $this->testedSetting => ['value' => $this->newValue],
                    ]
                ]
            ]
        ]);
    }

    private function configureModuleInShopFile(): void
    {
        $originalModuleSetting = new Setting();
        $originalModuleSetting
            ->setName($this->testedSetting)
            ->setValue($this->originalValue)
            ->setType('int');
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId($this->testModuleId)
            ->setPath('test')
            ->addModuleSetting($originalModuleSetting);
        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($moduleConfiguration);
        $this->get(ShopConfigurationDaoInterface::class)->save($shopConfiguration, 1);
    }
}
