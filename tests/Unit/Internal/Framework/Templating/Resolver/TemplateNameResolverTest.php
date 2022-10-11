<?php declare(strict_types=1);
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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating\Resolver;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Resolver\TemplateNameResolver;

class TemplateNameResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider resolveSmartyDataProvider
     */
    public function testResolveSmartyTemplate($templateName, $response): void
    {
        $resolver = new TemplateNameResolver($this->getTemplateEngineMock('tpl'));

        $this->assertSame($response, $resolver->resolve($templateName));
    }

    public function resolveSmartyDataProvider(): array
    {
        return [
            [
                'template',
                'template.tpl'
            ],
            [
                'some/path/template',
                'some/path/template.tpl'
            ],
            [
                'some/path/template_name',
                'some/path/template_name.tpl'
            ],
            [
                'some/path/template.name',
                'some/path/template.name.tpl'
            ],
            [
                '',
                ''
            ]
        ];
    }

    /**
     * @dataProvider resolveTwigDataProvider
     */
    public function testResolveTwigTemplate($response, $templateName): void
    {
        $resolver = new TemplateNameResolver($this->getTemplateEngineMock('html.twig'));

        $this->assertSame($response, $resolver->resolve($templateName));
    }

    public function resolveTwigDataProvider(): array
    {
        return [
            [
                'template.html.twig',
                'template'
            ],
            [
                'some/path/template_name.html.twig',
                'some/path/template_name'
            ],
            [
                'some/path/template.name.html.twig',
                'some/path/template.name'
            ],
            [
                '',
                ''
            ]
        ];
    }

    /**
     * @param string $extension
     *
     * @return TemplateEngineInterface
     */
    private function getTemplateEngineMock($extension): TemplateEngineInterface
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
