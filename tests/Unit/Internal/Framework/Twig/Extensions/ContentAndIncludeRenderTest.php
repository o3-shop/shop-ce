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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IfContentExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IncludeExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IfContentLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IncludeDynamicLogic;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Renders the ifcontent tag and the include_dynamic tag end to end on Twig 3, with the logic classes mocked.
 */
class ContentAndIncludeRenderTest extends TestCase
{
    public function testIfContentRendersBodyWithLoadedContentByIdent(): void
    {
        $logic = $this->createMock(IfContentLogic::class);
        $logic->expects($this->once())->method('getContent')->with('oxagb', null)->willReturn((object) ['title' => 'AGB']);

        $this->assertSame('[AGB]', $this->render('[{% ifcontent ident "oxagb" %}{{ oCont.title }}{% endifcontent %}]', ['ifContent' => $logic]));
    }

    public function testIfContentLoadsByOxidAndAssignsToTheGivenVariable(): void
    {
        $logic = $this->createMock(IfContentLogic::class);
        $logic->expects($this->once())->method('getContent')->with(null, 'abc')->willReturn((object) ['title' => 'T']);

        $this->assertSame('T', $this->render('{% ifcontent oxid "abc" set page %}{{ page.title }}{% endifcontent %}', ['ifContent' => $logic]));
    }

    public function testIfContentRendersNothingWhenContentIsMissing(): void
    {
        $logic = $this->createMock(IfContentLogic::class);
        $logic->method('getContent')->willReturn(false);

        $this->assertSame('[]', $this->render('[{% ifcontent ident "missing" %}body{% endifcontent %}]', ['ifContent' => $logic]));
    }

    public function testIncludeDynamicWithVariablesIncludesTheTemplateWithPrefixedParameters(): void
    {
        $logic = $this->createMock(IncludeDynamicLogic::class);
        $logic->expects($this->once())->method('includeDynamicPrefix')->with(['greet' => 'hi'])
            ->willReturn(['greet' => 'hi', '__oxid_include_dynamic' => true]);

        $this->assertSame(
            'a|hi dynamic:yes|z',
            $this->render("a|{% include_dynamic 'inc.html.twig' with {greet: 'hi'} %}|z", ['include' => $logic], ['inc.html.twig' => '{{ greet }} dynamic:{{ __oxid_include_dynamic ? "yes" : "no" }}'])
        );
    }

    public function testIncludeDynamicOnlyDoesNotLeakTheContext(): void
    {
        $logic = $this->createMock(IncludeDynamicLogic::class);
        $logic->method('includeDynamicPrefix')->willReturnArgument(0);

        $this->assertSame(
            'p=1 s=',
            $this->render("{% include_dynamic 'inc.html.twig' with {p: 1} only %}", ['include' => $logic], ['inc.html.twig' => 'p={{ p }} s={{ secret }}'], ['secret' => 'x'])
        );
    }

    public function testIncludeDynamicRendersForCacheInsteadOfIncluding(): void
    {
        $logic = $this->createMock(IncludeDynamicLogic::class);
        $logic->expects($this->once())->method('renderForCache')->with(['greet' => 'hi'])->willReturn('<cache/>');
        $logic->expects($this->never())->method('includeDynamicPrefix');

        $this->assertSame(
            '<cache/>',
            $this->render("{% include_dynamic 'inc.html.twig' with {greet: 'hi'} %}", ['include' => $logic], ['inc.html.twig' => 'never'], ['_render4cache' => true])
        );
    }

    private function render(string $template, array $logics, array $templates = [], array $context = []): string
    {
        $twig = new Environment(new ArrayLoader(['main' => $template] + $templates), ['autoescape' => false, 'cache' => false]);
        if (isset($logics['ifContent'])) {
            $twig->addExtension(new IfContentExtension($logics['ifContent']));
        }
        if (isset($logics['include'])) {
            $twig->addExtension(new IncludeExtension($logics['include']));
        }

        return $twig->render('main', $context);
    }
}
