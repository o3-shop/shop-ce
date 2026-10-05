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
use OxidEsales\EshopCommunity\Application\Controller\Admin\UserMainAjax;

/**
 * Object2Group stub: every save() snapshots the (objectid, groupsid)
 * pair so the test can verify which user/group rows the controller would
 * have inserted, without persisting anything.
 *
 * UtilsObject::setClassInstance hands back the same instance from
 * oxNew(Object2Group::class) — that's the controller's loop iteration
 * pattern, so we snapshot per call.
 */
class UserMainAjaxTest_StubObject2Group
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

class UserMainAjaxTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UserMainAjaxTest_StubObject2Group::$saved = [];
    }

    public function testGetQueryReturnsBareGroupSelectWhenNoOxidGiven(): void
    {
        $controller = oxNew(UserMainAjax::class);
        $sql = $controller->UNITgetQuery();

        // No "oxid" parameter → simple "from $sGroupTable where 1" form,
        // with no join against oxobject2group.
        $this->assertMatchesRegularExpression('/from\s+\S+\s+where\s+1\s*$/', trim($sql));
        $this->assertStringNotContainsString('oxobject2group', $sql);
    }

    public function testGetQueryJoinsOnObject2GroupWhenOxidGiven(): void
    {
        $this->setRequestParameter('oxid', 'user-42');

        $controller = oxNew(UserMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('left join oxobject2group', $sql);
        $this->assertStringContainsString("'user-42'", $sql);
    }

    public function testGetQueryAppendsExclusionWhenSynchoxidIsDifferent(): void
    {
        $this->setRequestParameter('oxid', 'user-42');
        $this->setRequestParameter('synchoxid', 'user-99');

        $controller = oxNew(UserMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringContainsString('not in', $sql);
        $this->assertStringContainsString("'user-99'", $sql);
    }

    public function testGetQueryDoesNotAppendExclusionWhenSynchoxidEqualsOxid(): void
    {
        $this->setRequestParameter('oxid', 'user-42');
        $this->setRequestParameter('synchoxid', 'user-42');

        $controller = oxNew(UserMainAjax::class);
        $sql = $controller->UNITgetQuery();

        $this->assertStringNotContainsString('not in', $sql);
    }

    public function testAddUserToGroupSavesOneObject2GroupPerSelectedGroupIfSynchoxidGiven(): void
    {
        \oxTestModules::addModuleObject(Object2Group::class, new UserMainAjaxTest_StubObject2Group());

        $this->setRequestParameter('synchoxid', 'user-42');

        $controller = $this->getMock(UserMainAjax::class, ['_getActionIds']);
        $controller->expects($this->any())
            ->method('_getActionIds')
            ->will($this->returnValue(['group-staff', 'group-managers']));

        $controller->addUserToGroup();

        $saved = UserMainAjaxTest_StubObject2Group::$saved;
        $this->assertCount(2, $saved);
        $this->assertSame('user-42', $saved[0]['objectid']);
        $this->assertSame('group-staff', $saved[0]['groupsid']);
        $this->assertSame('user-42', $saved[1]['objectid']);
        $this->assertSame('group-managers', $saved[1]['groupsid']);
    }

    public function testAddUserToGroupSavesNothingForResetSentinelOxid(): void
    {
        \oxTestModules::addModuleObject(Object2Group::class, new UserMainAjaxTest_StubObject2Group());

        $this->setRequestParameter('synchoxid', '-1');

        $controller = $this->getMock(UserMainAjax::class, ['_getActionIds']);
        $controller->expects($this->any())
            ->method('_getActionIds')
            ->will($this->returnValue(['group-staff']));

        $controller->addUserToGroup();

        $this->assertSame([], UserMainAjaxTest_StubObject2Group::$saved);
    }

    public function testAddUserToGroupSavesNothingForEmptySynchoxid(): void
    {
        \oxTestModules::addModuleObject(Object2Group::class, new UserMainAjaxTest_StubObject2Group());

        // No 'synchoxid' parameter → controller skips the foreach.

        $controller = $this->getMock(UserMainAjax::class, ['_getActionIds']);
        $controller->expects($this->any())
            ->method('_getActionIds')
            ->will($this->returnValue(['group-staff']));

        $controller->addUserToGroup();

        $this->assertSame([], UserMainAjaxTest_StubObject2Group::$saved);
    }
}
