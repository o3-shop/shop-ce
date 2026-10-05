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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Smarty;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Bridge\SmartyEngineBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyEngine;
use PHPUnit\Framework\TestCase;
use Smarty;

class SmartyEngineTest extends TestCase
{
    private function makeEngineWithMocks(): array
    {
        $smarty = $this->createMock(Smarty::class);
        $bridge = $this->createMock(SmartyEngineBridgeInterface::class);
        return [new SmartyEngine($smarty, $bridge), $smarty, $bridge];
    }

    public function testRenderAssignsContextAndDelegatesToFetch(): void
    {
        [$engine, $smarty] = $this->makeEngineWithMocks();
        $smarty->expects($this->exactly(2))
            ->method('assign')
            ->withConsecutive(['key1', 'value1'], ['key2', 'value2']);
        $smarty->expects($this->once())
            ->method('fetch')
            ->with('foo.tpl')
            ->willReturn('rendered');

        $this->assertSame(
            'rendered',
            $engine->render('foo.tpl', ['key1' => 'value1', 'key2' => 'value2'])
        );
    }

    public function testRenderUsesCacheKeyWhenOxEngineTemplateIdIsPresent(): void
    {
        [$engine, $smarty] = $this->makeEngineWithMocks();
        $smarty->expects($this->any())->method('assign');
        $smarty->expects($this->once())
            ->method('fetch')
            ->with('foo.tpl', 'cache-id-7')
            ->willReturn('rendered');

        $engine->render('foo.tpl', ['oxEngineTemplateId' => 'cache-id-7']);
    }

    public function testRenderFragmentDelegatesToBridge(): void
    {
        [$engine, $smarty, $bridge] = $this->makeEngineWithMocks();
        $bridge->expects($this->once())
            ->method('renderFragment')
            ->with($smarty, '<%= title %>', 'frag-1', ['title' => 'hello'])
            ->willReturn('hello');

        $this->assertSame(
            'hello',
            $engine->renderFragment('<%= title %>', 'frag-1', ['title' => 'hello'])
        );
    }

    public function testExistsDelegatesToTemplateExists(): void
    {
        [$engine, $smarty] = $this->makeEngineWithMocks();
        $smarty->expects($this->once())
            ->method('template_exists')
            ->with('foo.tpl')
            ->willReturn(true);

        $this->assertTrue($engine->exists('foo.tpl'));
    }

    public function testAddGlobalStoresValueAndForwardsToSmarty(): void
    {
        [$engine, $smarty] = $this->makeEngineWithMocks();
        $smarty->expects($this->once())
            ->method('assign')
            ->with('logo', 'shop.png');

        $engine->addGlobal('logo', 'shop.png');
        $this->assertSame(['logo' => 'shop.png'], $engine->getGlobals());
    }

    public function testGetDefaultFileExtensionIsTpl(): void
    {
        [$engine] = $this->makeEngineWithMocks();
        $this->assertSame('tpl', $engine->getDefaultFileExtension());
    }

    public function testMagicSetForwardsToSmartyOnlyForExistingProperties(): void
    {
        $smarty = new Smarty();
        $bridge = $this->createMock(SmartyEngineBridgeInterface::class);
        $engine = new SmartyEngine($smarty, $bridge);

        // 'left_delimiter' is a real Smarty 2.x property → forwarded.
        $engine->left_delimiter = '[[';
        $this->assertSame('[[', $smarty->left_delimiter);

        // Unknown property → silently ignored (no exception thrown).
        $engine->totally_unknown_prop_for_o3_test = 'value';
        $this->assertFalse(property_exists($smarty, 'totally_unknown_prop_for_o3_test'));
    }

    public function testMagicGetReadsFromUnderlyingSmarty(): void
    {
        $smarty = new Smarty();
        $smarty->left_delimiter = '[[';
        $bridge = $this->createMock(SmartyEngineBridgeInterface::class);
        $engine = new SmartyEngine($smarty, $bridge);

        $this->assertSame('[[', $engine->left_delimiter);
    }
}
