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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ModuleSortList;
use OxidEsales\Eshop\Application\Model\Article;

/**
 * Tests for Shop_Config class
 */
class ModuleSortListTest extends \OxidTestCase
{
    public function testRender()
    {
        $oView = oxNew(ModuleSortList::class);
        $this->assertEquals('module_sortlist.tpl', $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['aExtClasses']));
        $this->assertTrue(isset($aViewData['aDisabledModules']));
    }

    public function testSave()
    {
        $this->setAdminMode(true);

        $chain = [
            Article::class => [
                'dir1/module1',
                'dir2/module2',
            ]
        ];

        $this->setRequestParameter('aModules', json_encode($chain));

        $moduleSortList = oxNew(ModuleSortList::class);
        $moduleSortList->save();

        $moduleSortList->render();

        $viewData = $moduleSortList->getViewData();
        $this->assertSame(
            [
                'OxidEsales---Eshop---Application---Model---Article' => [
                    'dir1/module1',
                    'dir2/module2',
                ]
            ],
            $viewData['aExtClasses']
        );
    }

    /**
     * Module_SortList::remove()
     *
     * @return null
     */
    public function testRemove()
    {
        $this->setRequestParameter("noButton", true);
        $oView = oxNew('Module_SortList');
        $oView->remove();
        $this->assertTrue($this->getSession()->getVariable("blSkipDeletedExtChecking"));
    }
}
