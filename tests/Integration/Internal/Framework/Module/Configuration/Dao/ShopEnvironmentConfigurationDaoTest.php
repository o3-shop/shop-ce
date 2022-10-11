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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfiguration\ModuleSettingsDataMapper;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

final class ShopEnvironmentConfigurationDaoTest extends TestCase
{
    use ContainerTrait;

    public function testGet(): void
    {
        $this->prepareTestEnvironmentShopConfigurationFile();
        $environmentConfiguration = $this->get(ShopEnvironmentConfigurationDaoInterface::class)->get(1);
        $expectedEnvironmentConfiguration = [
            'modules' => [
                'testModuleId' => [
                    ModuleSettingsDataMapper::MAPPING_KEY => [
                        'settingToOverwrite' => [
                            'value' => 'overwrittenValue',
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedEnvironmentConfiguration, $environmentConfiguration);
    }

    public function testRemove(): void
    {
        $this->prepareTestEnvironmentShopConfigurationFile();

        $this->get(ShopEnvironmentConfigurationDaoInterface::class)->remove(1);

        $environmentConfiguration = $this->get(ShopEnvironmentConfigurationDaoInterface::class)->get(1);

        $this->assertEquals([], $environmentConfiguration);
    }

    public function testRemoveOverwriteAlreadyBackupEnvironmentFile(): void
    {
        $this->prepareTestEnvironmentShopConfigurationFile();
        $this->get(ShopEnvironmentConfigurationDaoInterface::class)->remove(1);

        $this->prepareTestEnvironmentShopConfigurationFile();
        $this->get(ShopEnvironmentConfigurationDaoInterface::class)->remove(1);
    }

    public function testRemoveWithNonExistingEnvironmentFile(): void
    {
        $this->get(ShopEnvironmentConfigurationDaoInterface::class)->remove(1);
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
                'testModuleId'=> [
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
