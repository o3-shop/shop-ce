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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\SettingsMergingService;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use PHPUnit\Framework\TestCase;

class SettingsMergingServiceTest extends TestCase
{
    private function makeSetting(string $name, string $type, $value, ?array $constraints = null): Setting
    {
        $setting = new Setting();
        $setting->setName($name);
        $setting->setType($type);
        $setting->setValue($value);
        if ($constraints !== null) {
            $setting->setConstraints($constraints);
        }
        return $setting;
    }

    public function testReturnsIncomingConfigurationVerbatimWhenShopHasNoExistingModule(): void
    {
        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(false);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting($this->makeSetting('color', 'str', 'red'));

        $service = new SettingsMergingService();
        $merged = $service->merge($shopConfig, $incoming);

        $this->assertSame($incoming, $merged);
        $this->assertSame('red', $merged->getModuleSettings()[0]->getValue());
    }

    public function testReturnsIncomingUntouchedWhenExistingHasNoSettings(): void
    {
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting($this->makeSetting('color', 'str', 'blue'));

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        $this->assertSame('blue', $merged->getModuleSettings()[0]->getValue());
    }

    public function testReturnsIncomingUntouchedWhenIncomingHasNoSettings(): void
    {
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');
        $existing->addModuleSetting($this->makeSetting('color', 'str', 'red'));

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        $this->assertSame([], $merged->getModuleSettings());
    }

    public function testMergesValueFromExistingWhenSameNameAndType(): void
    {
        // The shop already had a value for `color` — when the module is
        // being installed/updated and the incoming `color` setting still
        // ships its default, the merge keeps the user's customised value.
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');
        $existing->addModuleSetting($this->makeSetting('color', 'str', 'red'));

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting($this->makeSetting('color', 'str', 'default'));

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        $this->assertSame('red', $merged->getModuleSettings()[0]->getValue());
    }

    public function testDoesNotMergeWhenTypeDiffers(): void
    {
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');
        // existing was 'str', new schema declares it as 'bool' → don't merge,
        // keep the new default.
        $existing->addModuleSetting($this->makeSetting('show_banner', 'str', 'red'));

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting($this->makeSetting('show_banner', 'bool', false));

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        $this->assertFalse($merged->getModuleSettings()[0]->getValue());
    }

    public function testSkipsMergeWhenExistingValueIsNull(): void
    {
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');
        $existing->addModuleSetting($this->makeSetting('color', 'str', null));

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting($this->makeSetting('color', 'str', 'default'));

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        // null shouldn't overwrite — incoming default survives.
        $this->assertSame('default', $merged->getModuleSettings()[0]->getValue());
    }

    public function testSelectTypeMergesOnlyWhenValueIsStillInTheConstraintList(): void
    {
        // Existing user choice 'red' is in the new schema's allowed list → merge.
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');
        $existing->addModuleSetting($this->makeSetting('color', 'select', 'red'));

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting(
            $this->makeSetting('color', 'select', 'blue', ['red', 'blue', 'green'])
        );

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        $this->assertSame('red', $merged->getModuleSettings()[0]->getValue());
    }

    public function testSelectTypeDoesNotMergeWhenValueWasRemovedFromConstraints(): void
    {
        // 'orange' was a valid pick before — but the new schema dropped it.
        // Don't merge: keep the new default so the stored value is always valid.
        $existing = new ModuleConfiguration();
        $existing->setId('mymodule');
        $existing->addModuleSetting($this->makeSetting('color', 'select', 'orange'));

        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);
        $shopConfig->method('getModuleConfiguration')->willReturn($existing);

        $incoming = new ModuleConfiguration();
        $incoming->setId('mymodule');
        $incoming->addModuleSetting(
            $this->makeSetting('color', 'select', 'blue', ['red', 'blue', 'green'])
        );

        $merged = (new SettingsMergingService())->merge($shopConfig, $incoming);
        $this->assertSame('blue', $merged->getModuleSettings()[0]->getValue());
    }
}
