<?php

declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Smarty\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Bridge\SmartyTemplateRendererBridge;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Legacy\LegacySmartyEngineInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;

class SmartyTemplateRendererBridgeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTemplateRenderer()
    {
        $renderer = $this
            ->getMockBuilder(TemplateRendererInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bridge = new SmartyTemplateRendererBridge($renderer, $this->createMock(LegacySmartyEngineInterface::class));

        $this->assertSame($renderer, $bridge->getTemplateRenderer());
    }

    public function testSetEngineGoesToTheSmartyEngineOnly()
    {
        $smarty = new \Smarty();
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $renderer->expects($this->never())->method('getTemplateEngine');
        $smartyEngine = $this->createMock(LegacySmartyEngineInterface::class);
        $smartyEngine->expects($this->once())->method('setSmarty')->with($smarty);
        $smartyEngine->method('getSmarty')->willReturn($smarty);

        $bridge = new SmartyTemplateRendererBridge($renderer, $smartyEngine);
        $bridge->setEngine($smarty);

        $this->assertSame($smarty, $bridge->getEngine());
    }
}
