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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig\TemplateChain;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache\TemplateCacheServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;

/**
 * Modules extend a Twig theme template through the template chain, ported from twig-component 2.x:
 * the first active module is the rendered entry and extends the next one, the last one extends the theme.
 */
final class TemplateChainTest extends TestCase
{
    private const SHOP_ID = 1;
    private const OTHER_SHOP_ID = 2;
    private const TEMPLATE = 'page/start.html.twig';

    /** Installed in this order on purpose, so activation order differs from installation order. */
    private const MODULE_IDS = ['moduleb', 'modulea', 'inactivemodule'];

    private $container;

    /** @var array */
    private $originalThemes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = ContainerFactory::getInstance()->getContainer();
        $this->container
            ->get('oxid_esales.module.install.service.launched_shop_project_configuration_generator')
            ->generate();

        foreach (self::MODULE_IDS as $moduleId) {
            $package = new OxidEshopPackage($moduleId, __DIR__ . '/Fixtures/modules/' . $moduleId);
            $package->setTargetDirectory('oeTest/' . $moduleId);
            $this->container->get(ModuleInstallerInterface::class)->install($package);
        }

        $config = Registry::getConfig();
        $this->originalThemes = [$config->getConfigParam('sTheme'), $config->getConfigParam('sCustomTheme')];

        $this->container->get(TemplateCacheServiceInterface::class)->invalidateTemplateCache();
    }

    protected function tearDown(): void
    {
        $moduleStateService = $this->container->get(ModuleStateServiceInterface::class);
        foreach ([self::SHOP_ID, self::OTHER_SHOP_ID] as $shopId) {
            foreach (self::MODULE_IDS as $moduleId) {
                if ($moduleStateService->isActive($moduleId, $shopId)) {
                    $moduleStateService->setDeactivated($moduleId, $shopId);
                }
            }
        }

        $this->container->get('oxid_esales.symfony.file_system')
            ->remove($this->container->get(ContextInterface::class)->getModulesPath() . '/oeTest/');
        $this->container
            ->get('oxid_esales.module.install.service.launched_shop_project_configuration_generator')
            ->generate();

        $config = Registry::getConfig();
        $config->setConfigParam('sTheme', $this->originalThemes[0]);
        $config->setConfigParam('sCustomTheme', $this->originalThemes[1]);
        $this->container->get(TemplateCacheServiceInterface::class)->invalidateTemplateCache();

        parent::tearDown();
    }

    public function testNoModuleRendersThemeTemplate(): void
    {
        $this->assertSame('[theme]', $this->render(self::TEMPLATE));
    }

    public function testOneModuleOverridesBlock(): void
    {
        $this->activate('modulea');

        $this->assertSame('[<modulea-twigtheme>theme</modulea-twigtheme>]', $this->render(self::TEMPLATE));
    }

    public function testTwoModulesChainInActivationOrder(): void
    {
        $this->activate('modulea');
        $this->activate('moduleb');

        $this->assertSame(
            '[<modulea-twigtheme><moduleb-default>theme</moduleb-default></modulea-twigtheme>]',
            $this->render(self::TEMPLATE)
        );
    }

    public function testInactiveModuleIgnored(): void
    {
        $this->activate('modulea');

        $this->assertStringNotContainsString('inactivemodule', $this->render(self::TEMPLATE));
    }

    public function testModuleOfOtherShopIgnored(): void
    {
        $this->container->get(ModuleStateServiceInterface::class)->setActive('moduleb', self::OTHER_SHOP_ID);

        $this->assertSame('[theme]', $this->render(self::TEMPLATE));
    }

    public function testThemeSpecificBeatsDefault(): void
    {
        $this->activate('modulea');
        $engine = $this->createTwigEngine();

        $this->assertSame(
            '[<modulea-twigtheme>theme</modulea-twigtheme>]',
            trim($engine->render(self::TEMPLATE))
        );

        // Same engine and process: another active theme falls back to the module's "default" extension.
        Registry::getConfig()->setConfigParam('sTheme', 'othertheme');

        $this->assertSame(
            '[<modulea-default>theme</modulea-default>]',
            trim($engine->render(self::TEMPLATE))
        );
    }

    public function testChildThemeUsesParentThemeExtension(): void
    {
        $this->activate('modulea');

        $this->assertSame(
            '[<modulea-twigtheme>theme</modulea-twigtheme>]',
            trim($this->createTwigEngine('childtheme')->render(self::TEMPLATE))
        );
    }

    public function testLegacyTplNameIsChainedThroughItsTwigSibling(): void
    {
        $this->activate('modulea');

        $this->assertSame('[<modulea-twigtheme>theme</modulea-twigtheme>]', $this->render('page/start.tpl'));
    }

    public function testThemePageExtendsLayoutThroughItsChain(): void
    {
        $this->activate('modulea');

        $this->assertSame(
            '<layout><modulea-layout>head</modulea-layout>details</layout>',
            $this->render('page/details.html.twig')
        );
    }

    public function testThemePageIncludesPartialThroughItsChain(): void
    {
        $this->activate('modulea');

        $box = '<modulea-box>box</modulea-box>';
        $this->assertSame("$box|$box|$box", $this->render('page/list.html.twig'));
    }

    public function testModuleOwnTemplateNamespace(): void
    {
        $this->activate('modulea');

        $this->assertSame('<modulea-own>', $this->render('@modulea/page/own.html.twig'));
    }

    private function activate(string $moduleId): void
    {
        $this->container->get(ModuleActivationBridgeInterface::class)->activate($moduleId, self::SHOP_ID);
    }

    private function render(string $template): string
    {
        return trim($this->createTwigEngine()->render($template));
    }

    /**
     * A freshly compiled container, so the engine sees the current module state; the theme
     * templates come from the fixture directory and the active theme is "twigtheme" (or a child theme of it).
     */
    private function createTwigEngine(string $customTheme = ''): TemplateEngineInterface
    {
        // Set here: module activation reloads the shop config.
        Registry::getConfig()->setConfigParam('sTheme', 'twigtheme');
        Registry::getConfig()->setConfigParam('sCustomTheme', $customTheme);

        $builder = (new ContainerBuilderFactory())->create()->getContainer();
        $builder->getDefinition('twig.loader.filesystem')->replaceArgument(0, [__DIR__ . '/Fixtures/theme']);
        $builder->compile();

        return $builder->get('oxid_esales.templating.twig_engine');
    }
}
