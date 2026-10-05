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

use OxidEsales\EshopCommunity\Application\Controller\Admin\SelectListMainAjax;

class SelectListMainAjaxTest extends \OxidTestCase
{
    public function testGetQueryReturnsBareArticleSelectWhenNoSelOxidGiven(): void
    {
        $controller = oxNew(SelectListMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('from ', $sql);
        $this->assertStringContainsString('where 1', $sql);
        // Without blVariantsSelection, parent filter is appended.
        $this->assertStringContainsString("oxparentid = ''", $sql);
    }

    public function testGetQueryWithVariantsSelectionDoesNotFilterParentId(): void
    {
        $this->getConfig()->setConfigParam('blVariantsSelection', true);
        $controller = oxNew(SelectListMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringNotContainsString('oxparentid', $sql);
    }

    public function testGetQueryJoinsObject2SelectListWhenSelOxidEqualsSynchoxid(): void
    {
        $this->setRequestParameter('oxid', 'sel-1');
        $this->setRequestParameter('synchoxid', 'sel-1');

        $controller = oxNew(SelectListMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('left join oxobject2selectlist', $sql);
        $this->assertStringContainsString("'sel-1'", $sql);
    }

    public function testGetQueryJoinsObject2CategoryWhenDifferentSynchoxidGiven(): void
    {
        $this->setRequestParameter('oxid', 'cat-7');
        $this->setRequestParameter('synchoxid', 'sel-1');

        $controller = oxNew(SelectListMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('oxobject2category', $sql);
        $this->assertStringContainsString("'cat-7'", $sql);
        // Exclusion clause must reference the synchoxid value.
        $this->assertStringContainsString('not in', $sql);
        $this->assertStringContainsString("'sel-1'", $sql);
    }

    public function testColumnsHaveAllThreeContainers(): void
    {
        $controller = $this->getProxyClass(SelectListMainAjax::class);
        $columns = $controller->getNonPublicVar('_aColumns');
        $this->assertSame(['container1', 'container2', 'container3'], array_keys($columns));
    }

    public function testAllowExtColumnsIsTrue(): void
    {
        $controller = $this->getProxyClass(SelectListMainAjax::class);
        $this->assertTrue((bool) $controller->getNonPublicVar('_blAllowExtColumns'));
    }

    public function testAddArtToSelDoesNothingForResetSentinelOxid(): void
    {
        $this->setRequestParameter('synchoxid', '-1');
        $controller = $this->getMock(SelectListMainAjax::class, ['_getActionIds']);
        $controller->expects($this->any())
            ->method('_getActionIds')
            ->will($this->returnValue(['art-1', 'art-2']));

        // Must return without throwing — no DB save attempted.
        $controller->addArtToSel();
        $this->assertTrue(true);
    }
}
