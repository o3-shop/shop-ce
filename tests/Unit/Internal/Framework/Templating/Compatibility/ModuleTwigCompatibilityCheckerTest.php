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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating\Compatibility;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Template;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\TemplateBlock;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Compatibility\ModuleTwigCompatibilityChecker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ModuleTwigCompatibilityCheckerTest extends TestCase
{
    /** @var string */
    private $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/twigcompat_' . uniqid();
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testModuleWithOnlyBlocksIsReportedAndLogged(): void
    {
        $this->file('smartyonly/views/blocks/b.tpl');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains("'smartyonly'"));

        $module = $this->module('smartyonly');
        $module->addTemplateBlock(new TemplateBlock('page/x.tpl', 'blk', 'views/blocks/b.tpl'));

        $this->assertSame(['smartyonly'], $this->checker([$module], ['smartyonly'], $logger)->getModulesWithoutTwigTemplates(1));
    }

    public function testModuleWithTwigSiblingsIsNotReported(): void
    {
        $this->file('both/views/blocks/b.tpl');
        $this->file('both/views/blocks/b.html.twig');
        $this->file('both/views/t.tpl');
        $this->file('both/views/t.html.twig');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $module = $this->module('both');
        $module->addTemplateBlock(new TemplateBlock('page/x.tpl', 'blk', 'views/blocks/b.tpl'));
        $module->addTemplate(new Template('t.tpl', 'views/t.tpl'));

        $this->assertSame([], $this->checker([$module], ['both'], $logger)->getModulesWithoutTwigTemplates(1));
    }

    public function testModuleWithViewsTwigDirectoryIsNotReported(): void
    {
        $this->file('twigdir/views/twig/extensions/themes/default/x.html.twig');
        $module = $this->module('twigdir');
        $module->addTemplateBlock(new TemplateBlock('page/x.tpl', 'blk', 'views/blocks/b.tpl'));

        $this->assertSame([], $this->checker([$module], ['twigdir'])->getModulesWithoutTwigTemplates(1));
    }

    public function testInactiveModuleIsIgnored(): void
    {
        $module = $this->module('inactive');
        $module->addTemplateBlock(new TemplateBlock('page/x.tpl', 'blk', 'views/blocks/b.tpl'));

        $this->assertSame([], $this->checker([$module], [])->getModulesWithoutTwigTemplates(1));
    }

    public function testAdminOnlyTemplatesAreIgnored(): void
    {
        $module = $this->module('adminonly');
        $module->addTemplate(new Template('a.tpl', 'views/admin/tpl/a.tpl'));
        $module->addTemplateBlock(new TemplateBlock('article_main.tpl', 'blk', 'views/admin/blocks/b.tpl'));

        $this->assertSame([], $this->checker([$module], ['adminonly'])->getModulesWithoutTwigTemplates(1));
    }

    private function file(string $relative): void
    {
        $path = $this->root . '/' . $relative;
        @mkdir(dirname($path), 0777, true);
        touch($path);
    }

    private function module(string $id): ModuleConfiguration
    {
        $module = new ModuleConfiguration();
        $module->setId($id);
        $module->setPath($id);

        return $module;
    }

    private function checker(array $modules, array $activeIds, ?LoggerInterface $logger = null): ModuleTwigCompatibilityChecker
    {
        $shop = new ShopConfiguration();
        foreach ($modules as $module) {
            $shop->addModuleConfiguration($module);
        }
        $shopDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopDao->method('get')->willReturn($shop);
        $setting = new ShopConfigurationSetting();
        $setting->setName(ShopConfigurationSetting::ACTIVE_MODULES)->setValue($activeIds)->setShopId(1);
        $settingDao = $this->createMock(ShopConfigurationSettingDaoInterface::class);
        $settingDao->method('get')->willReturn($setting);
        $resolver = $this->createMock(ModulePathResolverInterface::class);
        $resolver->method('getFullModulePathFromConfiguration')->willReturnCallback(
            function (string $id): string {
                return $this->root . '/' . $id;
            }
        );

        return new ModuleTwigCompatibilityChecker($shopDao, $settingDao, $resolver, $logger ?? $this->createMock(LoggerInterface::class));
    }
}
