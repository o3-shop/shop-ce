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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\GenericExport;
use OxidEsales\Eshop\Application\Controller\Admin\NavigationTree;

/**
 * Tests for GenericExport admin controller.
 */
class GenericExportTest extends \OxidTestCase
{
    public function testExportClassNamesAreSet(): void
    {
        $controller = oxNew(GenericExport::class);

        $this->assertSame('genexport_do', $controller->sClassDo);
        $this->assertSame('genexport_main', $controller->sClassMain);
    }

    /**
     * getViewId() must bypass DynamicExportBaseController's hard-coded
     * 'dyn_interface' and instead return the navigation-derived class id
     * from AdminController.
     */
    public function testGetViewIdBypassesDynamicExportBase(): void
    {
        $navigation = $this->getMock(NavigationTree::class, ['getClassId']);
        $navigation->expects($this->once())
            ->method('getClassId')
            ->with('genexport')
            ->will($this->returnValue('genexport_main'));

        $controller = $this->getMock(GenericExport::class, ['getNavigation']);
        $controller->expects($this->once())
            ->method('getNavigation')
            ->will($this->returnValue($navigation));

        $this->assertSame('genexport_main', $controller->getViewId());
    }
}
