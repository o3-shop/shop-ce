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

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\EshopCommunity\Core\Module\ModuleCache;

class ModuleCacheTest extends \OxidTestCase
{
    public function testGetModuleReturnsConstructorInjection(): void
    {
        $module = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->getMock();
        $cache = new ModuleCache($module);
        $this->assertSame($module, $cache->getModule());
    }

    public function testSetModuleReplacesInstance(): void
    {
        $original = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->getMock();
        $replacement = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->getMock();

        $cache = new ModuleCache($original);
        $cache->setModule($replacement);
        $this->assertSame($replacement, $cache->getModule());
    }

    public function testResetCacheClearsAllUtilCachesAndModuleVariables(): void
    {
        $module = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->getMock();
        $module->expects($this->once())
            ->method('getTemplates')
            ->willReturn(['tpl_a.tpl', 'tpl_b.tpl']);

        $utils = $this->getMock(Utils::class, ['resetTemplateCache', 'resetLanguageCache', 'resetMenuCache']);
        $utils->expects($this->once())->method('resetTemplateCache')->with(['tpl_a.tpl', 'tpl_b.tpl']);
        $utils->expects($this->once())->method('resetLanguageCache');
        $utils->expects($this->once())->method('resetMenuCache');
        Registry::set(Utils::class, $utils);

        (new ModuleCache($module))->resetCache();
    }
}
