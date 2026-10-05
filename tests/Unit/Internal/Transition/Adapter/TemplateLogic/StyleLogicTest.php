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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\ViewHelper\StyleRegistrator;
use OxidEsales\Eshop\Core\ViewHelper\StyleRenderer;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\StyleLogic;

class StyleLogicTest_StubRegistrator
{
    public static array $calls = [];

    public function addFile($file, $if, $isDynamic)
    {
        self::$calls[] = [$file, $if, $isDynamic];
    }
}

class StyleLogicTest_StubRenderer
{
    public static array $calls = [];
    public static string $output = '<style>...</style>';

    public function render($widget, $forceRender, $isDynamic)
    {
        self::$calls[] = [$widget, $forceRender, $isDynamic];
        return self::$output;
    }
}

class StyleLogicTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        StyleLogicTest_StubRegistrator::$calls = [];
        StyleLogicTest_StubRenderer::$calls = [];
    }

    public function testIncludeBranchAddsFileViaRegistratorAndReturnsEmptyOutput(): void
    {
        \oxTestModules::addModuleObject(StyleRegistrator::class, new StyleLogicTest_StubRegistrator());

        $logic = new StyleLogic();
        $output = $logic->collectStyleSheets(['include' => '/path/to/file.css', 'if' => 'IE'], false);

        $this->assertSame('', $output);
        $this->assertCount(1, StyleLogicTest_StubRegistrator::$calls);
        $this->assertSame(['/path/to/file.css', 'IE', false], StyleLogicTest_StubRegistrator::$calls[0]);
    }

    public function testRenderBranchInvokesRendererWithDefaults(): void
    {
        \oxTestModules::addModuleObject(StyleRenderer::class, new StyleLogicTest_StubRenderer());
        StyleLogicTest_StubRenderer::$output = '<style>rendered</style>';

        $logic = new StyleLogic();
        $output = $logic->collectStyleSheets([], true);

        $this->assertSame('<style>rendered</style>', $output);
        $this->assertCount(1, StyleLogicTest_StubRenderer::$calls);
        // widget defaults to '', forceRender (inWidget) defaults to false.
        $this->assertSame(['', false, true], StyleLogicTest_StubRenderer::$calls[0]);
    }

    public function testRenderBranchPassesWidgetAndInWidgetParams(): void
    {
        \oxTestModules::addModuleObject(StyleRenderer::class, new StyleLogicTest_StubRenderer());

        $logic = new StyleLogic();
        $logic->collectStyleSheets(['widget' => 'mywidget', 'inWidget' => true], false);

        $this->assertSame(['mywidget', true, false], StyleLogicTest_StubRenderer::$calls[0]);
    }
}
