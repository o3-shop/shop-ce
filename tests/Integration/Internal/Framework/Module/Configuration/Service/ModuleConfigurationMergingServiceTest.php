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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\{
    ModuleConfigurationMergingServiceInterface
};
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;

final class ModuleConfigurationMergingServiceTest extends TestCase
{
    use ContainerTrait;

    public function testMergeNewModuleConfiguration(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('newModule');

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(new ShopConfiguration(), $moduleConfiguration);

        $this->assertEquals(
            $moduleConfiguration,
            $shopConfiguration->getModuleConfiguration('newModule')
        );
    }

    public function testNewMergedModuleConfigurationIsCloned(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('newModule');

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(new ShopConfiguration(), $moduleConfiguration);

        $this->assertNotSame(
            $moduleConfiguration,
            $shopConfiguration->getModuleConfiguration('newModule')
        );
    }

    public function testExtensionClassAppendToChainAfterMergingNewModuleConfiguration(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('newModule');
        $moduleConfiguration->addClassExtension(
            new ClassExtension(
                'shopClass',
                'testModuleClassExtendsShopClass'
            )
        );

        $shopConfigurationWithChain = new ShopConfiguration();
        $chain = new ClassExtensionsChain();
        $chain->setChain([
            'shopClass'             => ['alreadyInstalledShopClass', 'anotherAlreadyInstalledShopClass'],
            'someAnotherShopClass'  => ['alreadyInstalledShopClass'],
        ]);

        $shopConfigurationWithChain->setClassExtensionsChain($chain);

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(
            $shopConfigurationWithChain,
            $moduleConfiguration
        );

        $this->assertSame(
            [
                'shopClass'             => [
                    'alreadyInstalledShopClass',
                    'anotherAlreadyInstalledShopClass',
                    'testModuleClassExtendsShopClass',
                ],
                'someAnotherShopClass'  => ['alreadyInstalledShopClass'],
            ],
            $shopConfiguration->getClassExtensionsChain()->getChain()
        );
        $this->assertEquals(
            $moduleConfiguration,
            $shopConfiguration->getModuleConfiguration('newModule')
        );
    }

    public function testMergeModuleConfigurationOfAlreadyInstalledModule(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('installedModule');

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(
            $this->getShopConfigurationWithAlreadyInstalledModule(),
            $moduleConfiguration
        );

        $this->assertEquals(
            $moduleConfiguration,
            $shopConfiguration->getModuleConfiguration('installedModule')
        );
    }

    public function testMergeSetsModuleConfigurationIfNoExistingModuleConfigurationInstalled(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('installedModule');

        $shopConfiguration = new ShopConfiguration();

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(
            $shopConfiguration,
            $moduleConfiguration
        );

        $this->assertEquals(
            $moduleConfiguration,
            $shopConfiguration->getModuleConfiguration('installedModule')
        );
    }

    public function testExtensionClassChainUpdatedAfterMergingAlreadyInstalledModule(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('installedModule');

        $classExtension = [
            'shopClass1' => 'extension1ToStayInNewModuleConfiguration',
            'shopClass2' => 'extension5',
            'shopClass5' => 'extension6'
        ];

        foreach ($classExtension as $namespace => $moduleExtension) {
            $moduleConfiguration->addClassExtension(
                new ClassExtension(
                    $namespace,
                    $moduleExtension
                )
            );
        }

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(
            $this->getShopConfigurationWithAlreadyInstalledModule(),
            $moduleConfiguration
        );

        $this->assertEquals(
            [
                'shopClass1' => ['someOtherExtension1', 'extension1ToStayInNewModuleConfiguration'],
                'shopClass2' => ['someOtherExtension2', 'extension5', 'someOtherExtension3'],
                'shopClass3' => ['someOtherExtension4'],
                'shopClass5' => ['extension6']
            ],
            $shopConfiguration->getClassExtensionsChain()->getChain()
        );
    }

    public function testSettingUpdatedAfterMergingAlreadyInstalledModule(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('installedModule');

        $moduleSettings = [
            [
                'name'     => 'existingValueIsTaken1',
                'group'    => 'oldGroup',
                'type'     => 'int',
                'position' => '100500'
            ],
            [
                'name'     => 'withTypeToChange',
                'type'     => 'bool',
                'position' => '100500',
                'value'    => 'true',
            ],
            [
                'name'     => 'existingValueIsTaken2',
                'type'     => 'str',
                'position' => '100500'
            ],
            [
                'name'        => 'existingValueIsTaken3',
                'type'        => 'select',
                'constraints' => ['1', '2', '3'],
                'position'    => '100500',
            ],
            [
                'name'        => 'existingValueNotTaken',
                'type'        => 'select',
                'constraints' => ['1', '2'],
                'position'    => '100500',
                'value'       => '2'
            ],
            [
                'name'     => 'completeNewOne',
                'type'     => 'string',
                'position' => '100500',
                'value'    => 'myValue'
            ]
        ];

        foreach ($moduleSettings as $settingData) {
            $setting = new Setting();

            $setting->setType($settingData['type']);
            $setting->setName($settingData['name']);

            if (isset($settingData['value'])) {
                $setting->setValue($settingData['value']);
            }

            if (isset($settingData['group'])) {
                $setting->setGroupName($settingData['group']);
            }

            if (isset($settingData['position'])) {
                $setting->setPositionInGroup((int)$settingData['position']);
            }

            if (isset($settingData['constraints'])) {
                $setting->setConstraints($settingData['constraints']);
            }

            $moduleConfiguration->addModuleSetting($setting);
        }

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(
            $this->getShopConfigurationWithAlreadyInstalledModule(),
            $moduleConfiguration
        );

        $mergedModuleConfiguration = $shopConfiguration->getModuleConfiguration('installedModule');

        $settings = [];

        foreach ($mergedModuleConfiguration->getModuleSettings() as $index => $setting) {
            if ($setting->getGroupName()) {
                $settings[$index]['group'] = $setting->getGroupName();
            }

            if ($setting->getName()) {
                $settings[$index]['name'] = $setting->getName();
            }

            if ($setting->getType()) {
                $settings[$index]['type'] = $setting->getType();
            }

            $settings[$index]['value'] = $setting->getValue();

            if ($setting->getPositionInGroup()) {
                $settings[$index]['position'] = $setting->getPositionInGroup();
            }

            if (!empty($setting->getConstraints())) {
                $settings[$index]['constraints'] = $setting->getConstraints();
            }
        }

        $this->assertEquals(
            [
                [
                    'name'     => 'existingValueIsTaken1',
                    'group'    => 'oldGroup',
                    'type'     => 'int',
                    'position' => '100500',
                    'value'    => '1'
                ],
                [
                    'name'     => 'withTypeToChange',
                    'type'     => 'bool',
                    'position' => '100500',
                    'value'    => 'true'
                ],
                [
                    'name'     => 'existingValueIsTaken2',
                    'type'     => 'str',
                    'position' => '100500',
                    'value'    => 'keep'
                ],
                [
                    'name'        => 'existingValueIsTaken3',
                    'type'        => 'select',
                    'constraints' => ['1', '2', '3'],
                    'position'    => '100500',
                    'value'       => '3',
                ],
                [
                    'name'        => 'existingValueNotTaken',
                    'type'        => 'select',
                    'constraints' => ['1', '2'],
                    'position'    => '100500',
                    'value'       => '2',
                ],
                [
                    'name'     => 'completeNewOne',
                    'type'     => 'string',
                    'position' => '100500',
                    'value'    => 'myValue'
                ]
            ],
            $settings
        );
    }

    public function testConfiguredOptionValueStaysTheSameAfterMerging(): void
    {
        $newModuleConfiguration = new ModuleConfiguration();
        $newModuleConfiguration->setId('test');
        $newModuleConfiguration->setConfigured(false);

        $oldModuleConfiguration = new ModuleConfiguration();
        $oldModuleConfiguration->setId('test');
        $oldModuleConfiguration->setConfigured(true);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($oldModuleConfiguration);

        $moduleConfigurationMergingService = $this->getMergingService();
        $shopConfiguration = $moduleConfigurationMergingService->merge(
            $shopConfiguration,
            $newModuleConfiguration
        );

        $this->assertTrue(
            $shopConfiguration->getModuleConfiguration('test')->isConfigured()
        );
    }

    private function getShopConfigurationWithAlreadyInstalledModule(): ShopConfiguration
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('installedModule');

        $classExtension = [
            'shopClass1'            => 'extension1ToStayInNewModuleConfiguration',
            'shopClass2'            => 'extension2ToBeChanged',
            'shopClass3'            => 'extension3ToBeDeleted',
            'shopClass4ToBeDeleted' => 'extension4ToBeDeleted'
        ];

        foreach ($classExtension as $namespace => $moduleExtension) {
            $moduleConfiguration->addClassExtension(
                new ClassExtension(
                    $namespace,
                    $moduleExtension
                )
            );
        }

        $moduleSettings =
            [
                [
                    'name'     => 'existingValueIsTaken1',
                    'group'    => 'oldGroup',
                    'type'     => 'int',
                    'position' => '100500',
                    'value'    => '1',
                ],
                [
                    'name'     => 'withTypeToChange',
                    'type'     => 'str',
                    'position' => '100500',
                    'value'    => 'toDelete',
                ],
                [
                    'name'     => 'existingValueIsTaken2',
                    'type'     => 'str',
                    'position' => '100500',
                    'value'    => 'keep',
                ],
                [
                    'name'        => 'existingValueIsTaken3',
                    'type'        => 'select',
                    'constraints' => ['1', '2', '3'],
                    'position'    => '100500',
                    'value'       => '3',
                ],
                [
                    'name'        => 'existingValueNotTaken',
                    'type'        => 'select',
                    'constraints' => ['1', '2', '3'],
                    'position'    => '100500',
                    'value'       => '3',
                ],
                [
                    'name'     => 'willBeDeleted',
                    'type'     => 'str',
                    'position' => '100500',
                    'value'    => 'myValue1',
                ]
            ];

        foreach ($moduleSettings as $settingData) {
            $setting = new Setting();

            $setting->setType($settingData['type']);
            $setting->setName($settingData['name']);
            $setting->setValue($settingData['value']);

            if (isset($settingData['group'])) {
                $setting->setGroupName($settingData['group']);
            }

            if (isset($settingData['position'])) {
                $setting->setPositionInGroup((int)$settingData['position']);
            }

            if (isset($settingData['constraints'])) {
                $setting->setConstraints($settingData['constraints']);
            }

            $moduleConfiguration->addModuleSetting($setting);
        }

        $chain = new ClassExtensionsChain();
        $chain->setChain([
            'shopClass1'            => ['someOtherExtension1', 'extension1ToStayInNewModuleConfiguration'],
            'shopClass2'            => ['someOtherExtension2', 'extension2ToBeChanged', 'someOtherExtension3'],
            'shopClass3'            => ['extension3ToBeDeleted', 'someOtherExtension4'],
            'shopClass4ToBeDeleted' => ['extension4ToBeDeleted']
        ]);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($moduleConfiguration);
        $shopConfiguration->setClassExtensionsChain($chain);

        return $shopConfiguration;
    }

    /**
     * @return ModuleConfigurationMergingServiceInterface
     */
    private function getMergingService(): ModuleConfigurationMergingServiceInterface
    {
        return $this->get(ModuleConfigurationMergingServiceInterface::class);
    }
}
