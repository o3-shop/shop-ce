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

use OxidEsales\Eshop\Application\Model\RightsRoles;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\EshopCommunity\Application\Controller\Admin\AdminRightsList;

/**
 * Stub list model returned to the controller's render() loop so it can
 * iterate without touching the database. Behaves enough like ListModel
 * for the foreach + getBaseObject() flow.
 */
class AdminRightsListTest_StubList extends \ArrayObject
{
    public bool $baseObjectLoaded = false;

    public function getBaseObject(): void
    {
        $this->baseObjectLoaded = true;
    }
}

/**
 * Stub model whose load() return value can be steered per-test, so we
 * can verify that AdminRightsList::deleteEntry() only delegates to the
 * parent when the entry actually exists.
 */
class AdminRightsListTest_StubRole extends BaseModel
{
    /** @var bool steered from the test via the static toggle */
    public static bool $loadReturns = true;

    /** @var string[] oxids that were passed to load() */
    public array $loadedOxids = [];

    public function isDerived()
    {
        return true; // short-circuit AdminListController::deleteEntry
    }

    public function load($oxId)
    {
        $this->loadedOxids[] = $oxId;
        return self::$loadReturns;
    }
}

class AdminRightsListTest extends \OxidTestCase
{
    public function testListDefaults(): void
    {
        $controller = $this->getProxyClass(AdminRightsList::class);
        $this->assertSame('adminrights_list.tpl', $controller->getNonPublicVar('_sThisTemplate'));
        $this->assertSame(RightsRoles::class, $controller->getNonPublicVar('_sListClass'));
        $this->assertSame(ListModel::class, $controller->getNonPublicVar('_sListType'));
    }

    public function testRenderReturnsTemplateWhenListIsEmpty(): void
    {
        $controller = $this->getMock(AdminRightsList::class, ['getItemList']);
        $controller->expects($this->any())
            ->method('getItemList')
            ->will($this->returnValue(null));

        $this->assertSame('adminrights_list.tpl', $controller->render());
    }

    public function testRenderIteratesListAndCallsGetBaseObject(): void
    {
        $stubArticle = oxNew(BaseModel::class);
        $list = new AdminRightsListTest_StubList(['key1' => $stubArticle]);

        $controller = $this->getMock(AdminRightsList::class, ['getItemList']);
        $controller->expects($this->any())
            ->method('getItemList')
            ->will($this->returnValue($list));

        $this->assertSame('adminrights_list.tpl', $controller->render());
        $this->assertTrue($list->baseObjectLoaded, 'render() must call $oList->getBaseObject() on a non-empty list.');
    }

    public function testDeleteEntrySkipsWhenOxidIsEmpty(): void
    {
        $controller = $this->getMock(AdminRightsList::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue(''));

        // Must not raise; parent::deleteEntry must not be reached.
        $this->bindStubRole($controller, true);
        $controller->deleteEntry();
        $this->assertTrue(true);
    }

    public function testDeleteEntrySkipsWhenLoadFails(): void
    {
        $stubRole = new AdminRightsListTest_StubRole();
        AdminRightsListTest_StubRole::$loadReturns = false;
        \oxTestModules::addModuleObject(RightsRoles::class, $stubRole);

        $controller = $this->getMock(AdminRightsList::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rights-oxid-123'));

        $this->bindStubRole($controller, false);
        $controller->deleteEntry();

        // load() was attempted, but parent::deleteEntry must not have run.
        $this->assertSame(['rights-oxid-123'], $stubRole->loadedOxids);
    }

    public function testDeleteEntryDelegatesWhenLoadSucceeds(): void
    {
        $stubRole = new AdminRightsListTest_StubRole();
        AdminRightsListTest_StubRole::$loadReturns = true;
        \oxTestModules::addModuleObject(RightsRoles::class, $stubRole);

        $controller = $this->getMock(AdminRightsList::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('rights-oxid-123'));

        $this->bindStubRole($controller, true);
        $controller->deleteEntry();

        $this->assertSame(['rights-oxid-123'], $stubRole->loadedOxids);
    }

    /**
     * Swap the controller's $_sListClass with a stub whose isDerived()===true,
     * so AdminListController::deleteEntry() exits early and the test stays
     * isolated from DB.
     */
    private function bindStubRole($controller, bool $loadResult): void
    {
        AdminRightsListTest_StubRole::$loadReturns = $loadResult;

        $reflection = new \ReflectionClass(AdminRightsList::class);
        $listClassProp = $reflection->getProperty('_sListClass');
        $listClassProp->setAccessible(true);
        $listClassProp->setValue($controller, AdminRightsListTest_StubRole::class);
    }
}
