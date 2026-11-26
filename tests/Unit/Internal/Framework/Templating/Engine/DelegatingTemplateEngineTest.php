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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating\Engine;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\DelegatingTemplateEngine;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\TemplateEngineName;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\TemplateEngineResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Resolver\LegacyTemplateNameResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Resolver\TemplateNameResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class DelegatingTemplateEngineTest extends TestCase
{
    /** @var TemplateEngineInterface */
    private $smarty;

    /** @var TemplateEngineInterface */
    private $twig;

    /** @var TemplateEngineInterface */
    private $active;

    protected function setUp(): void
    {
        $this->smarty = $this->createMock(TemplateEngineInterface::class);
        $this->smarty->method('getDefaultFileExtension')->willReturn('tpl');
        $this->twig = $this->createMock(TemplateEngineInterface::class);
        $this->twig->method('getDefaultFileExtension')->willReturn('html.twig');
        $this->active = $this->smarty;
    }

    public function testDelegatesExtension(): void
    {
        $engine = $this->engine();

        $this->active = $this->twig;
        $this->assertSame('html.twig', $engine->getDefaultFileExtension());

        $this->active = $this->smarty;
        $this->assertSame('tpl', $engine->getDefaultFileExtension());
    }

    public function testNestedNameKeepsPath(): void
    {
        $nameResolver = new LegacyTemplateNameResolver(new TemplateNameResolver($this->engine()));

        $this->active = $this->twig;
        $this->assertSame('page/checkout/basket.html.twig', $nameResolver->resolve('page/checkout/basket.tpl'));

        $this->active = $this->smarty;
        $this->assertSame('page/checkout/basket.tpl', $nameResolver->resolve('page/checkout/basket.tpl'));
    }

    public function testDelegatesRenderingToTheActiveEngine(): void
    {
        $this->active = $this->twig;
        $this->twig->method('render')->with('a.html.twig', ['x' => 1])->willReturn('twig-out');
        $this->twig->method('renderFragment')->with('{{ x }}', 'id', ['x' => 1])->willReturn('fragment-out');
        $this->twig->method('exists')->with('a.html.twig')->willReturn(true);
        $this->twig->method('getGlobals')->willReturn(['g' => 1]);
        $this->smarty->expects($this->never())->method('render');

        $engine = $this->engine();

        $this->assertSame('twig-out', $engine->render('a.html.twig', ['x' => 1]));
        $this->assertSame('fragment-out', $engine->renderFragment('{{ x }}', 'id', ['x' => 1]));
        $this->assertTrue($engine->exists('a.html.twig'));
        $this->assertSame(['g' => 1], $engine->getGlobals());
    }

    public function testAddGlobalReachesBothEngines(): void
    {
        $this->smarty->expects($this->once())->method('addGlobal')->with('oView', 'view');
        $this->twig->expects($this->once())->method('addGlobal')->with('oView', 'view');

        $this->engine()->addGlobal('oView', 'view');
    }

    private function engine(): DelegatingTemplateEngine
    {
        $resolver = $this->createMock(TemplateEngineResolverInterface::class);
        $resolver->method('getEngine')->willReturnCallback(function () {
            return $this->active;
        });

        return new DelegatingTemplateEngine(
            $resolver,
            new ServiceLocator([
                TemplateEngineName::SMARTY => function () {
                    return $this->smarty;
                },
                TemplateEngineName::TWIG => function () {
                    return $this->twig;
                },
            ])
        );
    }
}
