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

use OxidEsales\Eshop\Application\Model\Object2Role;
use OxidEsales\EshopCommunity\Application\Controller\Admin\AdminRightsMainAjax;

class AdminRightsMainAjaxTest_StubObject2Role
{
    /** @var array<int,array<string,mixed>> */
    public static array $assigned = [];
    public static array $saved = [];

    public function assign($values): void
    {
        self::$assigned[] = is_array($values) ? $values : iterator_to_array($values);
    }

    public function save(): bool
    {
        self::$saved[] = end(self::$assigned);
        return true;
    }
}

class AdminRightsMainAjaxTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AdminRightsMainAjaxTest_StubObject2Role::$assigned = [];
        AdminRightsMainAjaxTest_StubObject2Role::$saved = [];
    }

    public function testGetQueryReturnsBareUserSelectWhenNoRoleGiven(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', true);

        $controller = oxNew(AdminRightsMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('where 1', $sql);
        $this->assertStringNotContainsString('o3object2role', $sql);
    }

    public function testGetQueryFiltersByRoleIdWhenSet(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', true);
        $this->setRequestParameter('oxid', 'role-admins');

        $controller = oxNew(AdminRightsMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('o3object2role', $sql);
        $this->assertStringContainsString("'role-admins'", $sql);
    }

    public function testGetQueryAppendsShopFilterWhenMallUsersDisabled(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', false);

        $controller = oxNew(AdminRightsMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('oxshopid', $sql);
    }

    public function testGetQueryAppendsExclusionWhenSynchoxidDifferent(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', true);
        $this->setRequestParameter('oxid', 'role-admins');
        $this->setRequestParameter('synchoxid', 'role-staff');

        $controller = oxNew(AdminRightsMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('not in', $sql);
        $this->assertStringContainsString("'role-staff'", $sql);
    }

    public function testAddUserToRoleSavesOneAssignmentPerSelectedUser(): void
    {
        \oxTestModules::addModuleObject(Object2Role::class, new AdminRightsMainAjaxTest_StubObject2Role());

        $this->setRequestParameter('synchoxid', 'role-admins');

        $controller = $this->getMock(AdminRightsMainAjax::class, ['getActionIds']);
        $controller->expects($this->any())
            ->method('getActionIds')
            ->will($this->returnValue(['user-1', 'user-2']));

        $controller->addusertorole();

        $saved = AdminRightsMainAjaxTest_StubObject2Role::$saved;
        $this->assertCount(2, $saved);
        $this->assertSame('user-1', $saved[0]['objectid']);
        $this->assertSame('role-admins', $saved[0]['roleid']);
        $this->assertSame('user-2', $saved[1]['objectid']);
    }

    public function testAddUserToRoleSavesNothingForResetSentinelOxid(): void
    {
        \oxTestModules::addModuleObject(Object2Role::class, new AdminRightsMainAjaxTest_StubObject2Role());

        $this->setRequestParameter('synchoxid', '-1');

        $controller = $this->getMock(AdminRightsMainAjax::class, ['getActionIds']);
        $controller->expects($this->any())
            ->method('getActionIds')
            ->will($this->returnValue(['user-1']));

        $controller->addusertorole();

        $this->assertSame([], AdminRightsMainAjaxTest_StubObject2Role::$saved);
    }
}
