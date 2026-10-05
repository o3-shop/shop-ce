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

use OxidEsales\Eshop\Application\Model\Object2Group;
use OxidEsales\EshopCommunity\Application\Controller\Admin\UserGroupMainAjax;

class UserGroupMainAjaxTest_StubObject2Group
{
    /** @var array<int,array{objectid:string,groupsid:string}> */
    public static array $saved = [];

    public ?\OxidEsales\Eshop\Core\Field $oxobject2group__oxobjectid = null;
    public ?\OxidEsales\Eshop\Core\Field $oxobject2group__oxgroupsid = null;

    public function save(): bool
    {
        self::$saved[] = [
            'objectid' => (string) ($this->oxobject2group__oxobjectid->value ?? ''),
            'groupsid' => (string) ($this->oxobject2group__oxgroupsid->value ?? ''),
        ];
        return true;
    }
}

class UserGroupMainAjaxTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UserGroupMainAjaxTest_StubObject2Group::$saved = [];
    }

    public function testGetQueryReturnsBareUserSelectWhenNoOxidGiven(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', true);

        $controller = oxNew(UserGroupMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertMatchesRegularExpression('/from\s+\S+\s+where\s+1\s*$/', trim($sql));
        $this->assertStringNotContainsString('oxobject2group', $sql);
    }

    public function testGetQueryFiltersByGroupOxidWhenSet(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', true);
        $this->setRequestParameter('oxid', 'group-staff');

        $controller = oxNew(UserGroupMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('oxobject2group', $sql);
        $this->assertStringContainsString("'group-staff'", $sql);
    }

    public function testGetQueryAppendsShopFilterWhenMallUsersDisabled(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', false);

        $controller = oxNew(UserGroupMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('oxshopid', $sql);
    }

    public function testGetQueryAppendsExclusionWhenSynchoxidDifferent(): void
    {
        $this->getConfig()->setConfigParam('blMallUsers', true);
        $this->setRequestParameter('oxid', 'group-staff');
        $this->setRequestParameter('synchoxid', 'group-admins');

        $controller = oxNew(UserGroupMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('not in', $sql);
        $this->assertStringContainsString("'group-admins'", $sql);
    }

    public function testAddUserToUGroupSavesOneRowPerSelectedUser(): void
    {
        \oxTestModules::addModuleObject(Object2Group::class, new UserGroupMainAjaxTest_StubObject2Group());

        $this->setRequestParameter('synchoxid', 'group-staff');

        $controller = $this->getMock(UserGroupMainAjax::class, ['_getActionIds']);
        $controller->expects($this->any())
            ->method('_getActionIds')
            ->will($this->returnValue(['user-1', 'user-2']));

        $controller->addUserToUGroup();

        $saved = UserGroupMainAjaxTest_StubObject2Group::$saved;
        $this->assertCount(2, $saved);
        $this->assertSame('user-1', $saved[0]['objectid']);
        $this->assertSame('group-staff', $saved[0]['groupsid']);
        $this->assertSame('user-2', $saved[1]['objectid']);
        $this->assertSame('group-staff', $saved[1]['groupsid']);
    }

    public function testAddUserToUGroupSkipsResetSentinelGroupOxid(): void
    {
        \oxTestModules::addModuleObject(Object2Group::class, new UserGroupMainAjaxTest_StubObject2Group());

        $this->setRequestParameter('synchoxid', '-1');

        $controller = $this->getMock(UserGroupMainAjax::class, ['_getActionIds']);
        $controller->expects($this->any())
            ->method('_getActionIds')
            ->will($this->returnValue(['user-1']));

        $controller->addUserToUGroup();

        $this->assertSame([], UserGroupMainAjaxTest_StubObject2Group::$saved);
    }
}
