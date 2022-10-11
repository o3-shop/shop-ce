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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Templating;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRenderer;

class TemplateRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderTemplate()
    {
        $response = 'rendered template';
        $engine = $this->getEngineMock();
        $engine->expects($this->once())
            ->method('render')
            ->with('template')
            ->will($this->returnValue($response));

        $renderer = new TemplateRenderer($engine);

        $this->assertSame($response, $renderer->renderTemplate('template', []));
    }

    public function testRenderFragment()
    {
        $response = 'rendered template';
        $engine = $this->getEngineMock();
        $engine->expects($this->once())
            ->method('renderFragment')
            ->with('template')
            ->will($this->returnValue($response));

        $renderer = new TemplateRenderer($engine);

        $this->assertSame($response, $renderer->renderFragment('template', 'testId', []));
    }

    public function testGetExistingEngine()
    {
        $engine = $this->getEngineMock();

        $renderer= new TemplateRenderer($engine);

        $this->assertSame($engine, $renderer->getTemplateEngine());
    }

    public function testExists()
    {
        $engine = $this->getEngineMock();
        $engine->expects($this->once())
            ->method('exists')
            ->with('template')
            ->will($this->returnValue(true));

        $renderer = new TemplateRenderer($engine);

        $this->assertTrue($renderer->exists('template'));
    }

    /**
     * @return \OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface
     */
    private function getEngineMock()
    {
        $engine = $this
            ->getMockBuilder('OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface')
            ->getMock();

        return $engine;
    }
}
