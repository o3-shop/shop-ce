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
}
