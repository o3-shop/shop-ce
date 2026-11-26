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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\FileSystem\FileSystem;
use OxidEsales\Eshop\Core\Theme;
use OxidEsales\EshopCommunity\Core\Module\ModuleTemplatePathCalculator;

class ModuleTemplatePathCalculatorCoverageTest extends \OxidTestCase
{
    private function makeTheme(array $activeThemes): Theme
    {
        $theme = $this->getMock(Theme::class, ['getActiveThemesList']);
        $theme->expects($this->any())
            ->method('getActiveThemesList')
            ->will($this->returnValue($activeThemes));
        return $theme;
    }

    private function makeFileSystem(bool $readable, string $combinedPath = '/tmp/o3-shop/module-template'): FileSystem
    {
        $fs = $this->getMock(FileSystem::class, ['combinePaths', 'isReadable']);
        $fs->expects($this->any())->method('combinePaths')->will($this->returnValue($combinedPath));
        $fs->expects($this->any())->method('isReadable')->will($this->returnValue($readable));
        return $fs;
    }

    public function testThrowsWhenModuleTemplatesConfigIsMissing(): void
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', null);
        $calculator = new ModuleTemplatePathCalculator(null, $this->makeTheme([]), $this->makeFileSystem(true));

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/Cannot find template/');
        $calculator->calculateModuleTemplatePath('foo.tpl');
    }

    public function testThrowsWhenTemplateNotFoundInAnyActiveModule(): void
    {
        // Module 'mymodule' is mapped, but its template config has no entry
        // for 'absent.tpl' → method walks the whole list and falls through.
        $this->getConfig()->setConfigParam('aModuleTemplates', [
            'mymodule' => ['existing.tpl' => 'mymodule/views/existing.tpl'],
        ]);
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);

        $calculator = new ModuleTemplatePathCalculator(null, $this->makeTheme(['wave']), $this->makeFileSystem(true));
        $calculator->setModulesPath('/var/www/html/modules');

        $this->expectException(\Throwable::class);
        $calculator->calculateModuleTemplatePath('absent.tpl');
    }

    public function testThrowsWhenTemplateFileIsNotReadable(): void
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', [
            'mymodule' => ['existing.tpl' => 'mymodule/views/existing.tpl'],
        ]);
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);

        $calculator = new ModuleTemplatePathCalculator(null, $this->makeTheme([]), $this->makeFileSystem(false));
        $calculator->setModulesPath('/var/www/html/modules');

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/Cannot find template file/');
        $calculator->calculateModuleTemplatePath('existing.tpl');
    }

    public function testReturnsModuleTemplatePathOnExactDefaultMatch(): void
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', [
            'mymodule' => ['existing.tpl' => 'mymodule/views/existing.tpl'],
        ]);
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);

        $expected = '/var/www/html/modules/mymodule/views/existing.tpl';
        $calculator = new ModuleTemplatePathCalculator(
            null,
            $this->makeTheme([]),
            $this->makeFileSystem(true, $expected)
        );
        $calculator->setModulesPath('/var/www/html/modules');

        $this->assertSame($expected, $calculator->calculateModuleTemplatePath('existing.tpl'));
    }

    public function testThemeSpecificTemplateOverridesDefault(): void
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', [
            'mymodule' => [
                'existing.tpl' => 'mymodule/views/existing.tpl',
                'wave'         => ['existing.tpl' => 'mymodule/views/wave/existing.tpl'],
            ],
        ]);
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);

        $expected = '/var/www/html/modules/mymodule/views/wave/existing.tpl';
        $calculator = new ModuleTemplatePathCalculator(
            null,
            $this->makeTheme(['wave']),
            $this->makeFileSystem(true, $expected)
        );
        $calculator->setModulesPath('/var/www/html/modules');

        $this->assertSame($expected, $calculator->calculateModuleTemplatePath('existing.tpl'));
    }

    public function testInactiveModuleIsSkipped(): void
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', [
            'inactive_module' => ['shared.tpl' => 'foo/shared.tpl'],
            'active_module'   => ['shared.tpl' => 'bar/shared.tpl'],
        ]);
        // Only active_module is in the active list.
        $this->getConfig()->setConfigParam('aModulePaths', ['active_module' => 'bar/']);

        $expected = '/var/www/html/modules/bar/shared.tpl';
        $calculator = new ModuleTemplatePathCalculator(
            null,
            $this->makeTheme([]),
            $this->makeFileSystem(true, $expected)
        );
        $calculator->setModulesPath('/var/www/html/modules');

        $this->assertSame($expected, $calculator->calculateModuleTemplatePath('shared.tpl'));
    }

    /** FileSystem mock joining paths and treating only $readable files as existing. */
    private function makeSiblingFileSystem(array $readable): FileSystem
    {
        $fs = $this->getMock(FileSystem::class, ['combinePaths', 'isReadable']);
        $fs->expects($this->any())->method('combinePaths')
            ->will($this->returnCallback(function ($a, $b) {
                return $a . '/' . $b;
            }));
        $fs->expects($this->any())->method('isReadable')
            ->will($this->returnCallback(function ($path) use ($readable) {
                return in_array($path, $readable, true);
            }));
        return $fs;
    }

    private function twigCalculator(array $templates, array $themes, array $readable): ModuleTemplatePathCalculator
    {
        $this->getConfig()->setConfigParam('aModuleTemplates', ['mymodule' => $templates]);
        $this->getConfig()->setConfigParam('aModulePaths', ['mymodule' => 'mymodule/']);
        $calculator = new ModuleTemplatePathCalculator(
            null,
            $this->makeTheme($themes),
            $this->makeSiblingFileSystem($readable)
        );
        $calculator->setModulesPath('/m');
        return $calculator;
    }

    public function testTwigNameFallsBackToTplMappingSibling(): void
    {
        $calculator = $this->twigCalculator(
            ['mymodule_page.tpl' => 'my/views/tpl/page.tpl'],
            [],
            ['/m/my/views/tpl/page.html.twig']
        );

        $this->assertSame('/m/my/views/tpl/page.html.twig', $calculator->calculateModuleTemplatePath('mymodule_page.html.twig'));
    }

    public function testTwigNameWithNestedPath(): void
    {
        $calculator = $this->twigCalculator(
            ['page/mymodule/page.tpl' => 'my/views/page.tpl'],
            [],
            ['/m/my/views/page.html.twig']
        );

        $this->assertSame('/m/my/views/page.html.twig', $calculator->calculateModuleTemplatePath('page/mymodule/page.html.twig'));
    }

    public function testTwigSiblingMissingThrows(): void
    {
        $calculator = $this->twigCalculator(
            ['mymodule_page.tpl' => 'my/views/tpl/page.tpl'],
            [],
            ['/m/my/views/tpl/page.tpl']
        );

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/Cannot find template/');
        $calculator->calculateModuleTemplatePath('mymodule_page.html.twig');
    }

    public function testThemeSpecificMappingSibling(): void
    {
        $calculator = $this->twigCalculator(
            [
                'page.tpl' => 'my/views/page.tpl',
                'wave'     => ['page.tpl' => 'my/views/wave/page.tpl'],
            ],
            ['wave'],
            ['/m/my/views/wave/page.html.twig', '/m/my/views/page.html.twig']
        );

        $this->assertSame('/m/my/views/wave/page.html.twig', $calculator->calculateModuleTemplatePath('page.html.twig'));
    }

    public function testExplicitTwigKeyWinsOverTplFallback(): void
    {
        $calculator = $this->twigCalculator(
            ['page.html.twig' => 'my/explicit.html.twig', 'page.tpl' => 'my/views/page.tpl'],
            [],
            ['/m/my/explicit.html.twig']
        );

        $this->assertSame('/m/my/explicit.html.twig', $calculator->calculateModuleTemplatePath('page.html.twig'));
    }
}
