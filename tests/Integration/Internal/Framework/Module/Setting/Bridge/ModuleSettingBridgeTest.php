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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDaoInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

final class ModuleSettingBridgeTest extends TestCase
{
    use ContainerTrait;

    public function setup(): void
    {
        $modulePath = realpath(__DIR__ . '/../../TestData/TestModule/');

        $configurationInstaller = $this->get(ModuleConfigurationInstallerInterface::class);
        $configurationInstaller->install($modulePath, 'targetPath');

        parent::setUp();
    }

    public function testSave(): void
    {
        $bridge = $this->get(ModuleSettingBridgeInterface::class);
        $newValue = ['some new setting'];

        $bridge->save('test-setting', $newValue, 'test-module');

        $configurationDao = $this->get(ModuleConfigurationDaoInterface::class);
        $configuration = $configurationDao->get('test-module', 1);
        $this->assertSame($newValue, $configuration->getModuleSetting('test-setting')->getValue());

        $settingsDao = $this->get(SettingDaoInterface::class);
        $this->assertSame($newValue, $settingsDao->get('test-setting', 'test-module', 1)->getValue());
    }

    public function testGet(): void
    {
        $defaultModuleSettingValue = ['Preis', 'Hersteller'];
        $bridge = $this->get(ModuleSettingBridgeInterface::class);
        $this->assertSame($defaultModuleSettingValue, $bridge->get('test-setting', 'test-module'));
    }
}
