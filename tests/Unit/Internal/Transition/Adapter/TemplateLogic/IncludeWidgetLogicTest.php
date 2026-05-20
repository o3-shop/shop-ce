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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\WidgetControl;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IncludeWidgetLogic;

class IncludeWidgetLogicTest extends \OxidTestCase
{
    public function testRenderWidgetExtractsClassAndPassesParentViews(): void
    {
        $widgetControl = $this->getMockBuilder(WidgetControl::class)
            ->onlyMethods(['start'])
            ->getMock();
        $widgetControl->expects($this->once())
            ->method('start')
            ->with(
                $this->equalTo('mywidget'),                  // class lower-cased
                $this->isNull(),                              // function = null
                $this->equalTo(['arg' => 'value']),           // remaining params
                $this->equalTo(['parentA', 'parentB'])        // parent views split on '|'
            )
            ->willReturn('rendered-output');
        Registry::set(WidgetControl::class, $widgetControl);

        $logic = new IncludeWidgetLogic();
        $result = $logic->renderWidget([
            'cl' => 'MyWidget',                               // becomes 'mywidget'
            '_parent' => 'parentA|parentB',
            'arg' => 'value',
        ]);
        $this->assertSame('rendered-output', $result);
    }

    public function testRenderWidgetTreatsMissingClassAsEmptyString(): void
    {
        $widgetControl = $this->getMockBuilder(WidgetControl::class)
            ->onlyMethods(['start'])
            ->getMock();
        $widgetControl->expects($this->once())
            ->method('start')
            ->with('', $this->isNull(), [], $this->isNull())
            ->willReturn('');
        Registry::set(WidgetControl::class, $widgetControl);

        $logic = new IncludeWidgetLogic();
        $logic->renderWidget([]);
    }
}
