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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Component\Widget;

use OxidEsales\EshopCommunity\Application\Model\ArticleList;

/**
 * Tests for oxwAction class
 */
class ActionsTest extends \OxidTestCase
{

    /**
     * Fixture tearDown
     */
    protected function tearDown(): void
    {
        $query = "UPDATE oxactions2article set OXSORT = 0 WHERE OXACTIONID = 'oxtop5' AND OXSORT = 666";
        \oxDb::getDb()->execute($query);

        parent::tearDown();
    }

    /**
     * Testing oxwAction::render()
     */
    public function testRender()
    {
        $action = oxNew('oxwActions');
        $this->assertSame('widget/product/action.tpl', $action->render());
    }

    /**
     * Testing oxwAction::getAction()
     */
    public function testGetAction()
    {
        $query = "UPDATE oxactions2article set OXSORT = 666 WHERE OXACTIONID = 'oxtop5' AND OXSORT = 0 AND OXARTID not in (2028, 2080)";
        \oxDb::getDb()->execute($query);

        $topProductCount = $this->getTestConfig()->getShopEdition() == 'EE' ? 6 : 4;
        $topProductId = $this->getTestConfig()->getShopEdition() == 'EE' ? '2028' : '2080';

        $this->getConfig()->setConfigParam('bl_perfLoadAktion', 1);

        $action = oxNew('oxwActions');
        $action->setViewParameters(array('action' => 'oxtop5'));
        $aList = $action->getAction();
        $this->assertTrue($aList instanceof ArticleList);
        $this->assertSame($topProductCount, $aList->count());
        $this->assertSame($topProductId, $aList->current()->getId());
    }

    /**
     * Testing oxwAction::getActionName()
     */
    public function testGetActionName()
    {
        $action = oxNew('oxwActions');
        $action->setViewParameters(array('action' => 'oxbargain'));
        $this->assertSame('Angebot der Woche', $action->getActionName());
    }

    /**
     * Testing oxwAction::getListType()
     */
    public function testGetListType()
    {
        $action = oxNew('oxwActions');
        $action->setViewParameters(array('listtype' => 'grid'));
        $this->assertSame('grid', $action->getListType());
    }
}
