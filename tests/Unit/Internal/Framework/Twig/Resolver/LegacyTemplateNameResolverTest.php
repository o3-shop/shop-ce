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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Resolver;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\Resolver\TemplateNameResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\TemplateNameResolver as TwigTemplateNameResolver;
use PHPUnit\Framework\TestCase;

class LegacyTemplateNameResolverTest extends TestCase
{
    /**
     * @param string $templateName
     * @param string $response
     *
     * @dataProvider resolveSmartyDataProvider
     */
    public function testResolveSmartyTemplate(string $templateName, string $response): void
    {
        $resolver = new TwigTemplateNameResolver(
            new TemplateNameResolver(
                $this->getTemplateEngineMock('tpl')
            )
        );

        $this->assertSame($response, $resolver->resolve($templateName));
    }

    /**
     * @return array
     */
    public function resolveSmartyDataProvider(): array
    {
        return [
            [
                'template.tpl',
                'template.tpl',
            ],
            [
                'some/path/template.tpl',
                'some/path/template.tpl',
            ],
            [
                'some/path/template_name.tpl',
                'some/path/template_name.tpl',
            ],
            [
                'some/path/template.name.tpl',
                'some/path/template.name.tpl',
            ],
            [
                'template.html.twig',
                'template.tpl',
            ],
            [
                'some/path/template.html.twig',
                'some/path/template.tpl',
            ],
            [
                'some/path/template_name.html.twig',
                'some/path/template_name.tpl',
            ],
            [
                'some/path/template.name.html.twig',
                'some/path/template.name.tpl',
            ],
            [
                '',
                '',
            ],
        ];
    }

    /**
     * @param string $templateName
     * @param string $response
     *
     * @dataProvider resolveTwigDataProvider
     */
    public function testResolveTwigTemplate(string $templateName, string $response): void
    {
        $resolver = new TwigTemplateNameResolver(
            new TemplateNameResolver(
                $this->getTemplateEngineMock('html.twig')
            )
        );

        $this->assertSame($response, $resolver->resolve($templateName));
    }

    /**
     * @return array
     */
    public function resolveTwigDataProvider(): array
    {
        return [
            [
                'template.tpl',
                'template.html.twig',
            ],
            [
                'some/path/template_name.tpl',
                'some/path/template_name.html.twig',
            ],
            [
                'some/path/template.name.tpl',
                'some/path/template.name.html.twig',
            ],
            [
                'template.html.twig',
                'template.html.twig',
            ],
            [
                'some/path/template_name.html.twig',
                'some/path/template_name.html.twig',
            ],
            [
                'some/path/template.name.html.twig',
                'some/path/template.name.html.twig',
            ],
            [
                '',
                '',
            ],
        ];
    }

    /**
     * @param string $extension
     *
     * @return TemplateEngineInterface
     */
    private function getTemplateEngineMock(string $extension): TemplateEngineInterface
    {
        $engine = $this
            ->getMockBuilder('OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $engine->expects($this->any())
            ->method('getDefaultFileExtension')
            ->will($this->returnValue($extension));

        return $engine;
    }
}
