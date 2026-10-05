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

use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\EshopCommunity\Application\Controller\Admin\SelectListOrderAjax;

class SelectListOrderAjaxTest extends \OxidTestCase
{
    public function testGetQueryJoinsObject2SelectListByObjectId(): void
    {
        $this->setRequestParameter('oxid', 'article-7');

        $controller = oxNew(SelectListOrderAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('left join oxobject2selectlist', $sql);
        $this->assertStringContainsString("'article-7'", $sql);
    }

    public function testGetSortingReturnsOxsortClause(): void
    {
        $controller = oxNew(SelectListOrderAjax::class);
        $this->assertSame('order by oxobject2selectlist.oxsort ', $controller->UNITgetSorting());
    }

    /**
     * setSorting() shape: when the underlying list is empty (no rows),
     * the controller must still finish the request by emitting a response,
     * not throw. We mock _outputResponse to capture the result envelope.
     */
    public function testSetSortingTolaratesEmptyListAndCallsOutputResponse(): void
    {
        $list = $this->getMock(ListModel::class, ['init', 'selectString']);
        $list->expects($this->any())->method('init');
        $list->expects($this->once())->method('selectString');
        \oxTestModules::addModuleObject(ListModel::class, $list);

        $captured = null;
        $controller = $this->getMock(
            SelectListOrderAjax::class,
            ['_getData', '_outputResponse', '_getQueryCols']
        );
        $controller->expects($this->any())->method('_getQueryCols')->will($this->returnValue('* '));
        $controller->expects($this->any())->method('_getData')->will($this->returnValue(['count' => 0]));
        $controller->expects($this->once())
            ->method('_outputResponse')
            ->willReturnCallback(function ($payload) use (&$captured) {
                $captured = $payload;
            });

        $this->setRequestParameter('oxid', 'article-empty');
        $controller->setSorting();

        $this->assertSame(['count' => 0], $captured);
    }
}
