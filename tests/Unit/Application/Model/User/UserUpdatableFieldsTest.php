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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model\User;

use OxidEsales\EshopCommunity\Application\Model\User\UserShippingAddressUpdatableFields;
use OxidEsales\EshopCommunity\Application\Model\User\UserUpdatableFields;

class UserUpdatableFieldsTest extends \OxidTestCase
{
    public function testUserUpdatableFieldsTableName(): void
    {
        $fields = new UserUpdatableFields();
        $this->assertSame('oxuser', $fields->getTableName());
    }

    public function testUserUpdatableFieldsListIncludesIdentityAndAddressColumns(): void
    {
        $list = (new UserUpdatableFields())->getUpdatableFields();
        $this->assertContains('OXUSERNAME', $list);
        $this->assertContains('OXPASSWORD', $list);
        $this->assertContains('OXFNAME', $list);
        $this->assertContains('OXLNAME', $list);
        $this->assertContains('OXBIRTHDATE', $list);
    }

    public function testUserUpdatableFieldsAreAllUppercaseColumnNames(): void
    {
        // Field cleaner compares case-sensitively, so any lowercase entry
        // in this list would silently strip user-submitted data.
        $list = (new UserUpdatableFields())->getUpdatableFields();
        foreach ($list as $field) {
            $this->assertSame(
                strtoupper($field),
                $field,
                "Updatable field '$field' must be uppercase."
            );
        }
    }

    public function testShippingUpdatableFieldsTableName(): void
    {
        $fields = new UserShippingAddressUpdatableFields();
        $this->assertSame('oxaddress', $fields->getTableName());
    }

    public function testShippingUpdatableFieldsListIncludesAddressColumns(): void
    {
        $list = (new UserShippingAddressUpdatableFields())->getUpdatableFields();
        $this->assertContains('OXFNAME', $list);
        $this->assertContains('OXSTREET', $list);
        $this->assertContains('OXCITY', $list);
        $this->assertContains('OXCOUNTRYID', $list);
        $this->assertContains('OXZIP', $list);
    }

    public function testShippingUpdatableFieldsAreAllUppercase(): void
    {
        $list = (new UserShippingAddressUpdatableFields())->getUpdatableFields();
        foreach ($list as $field) {
            $this->assertSame(strtoupper($field), $field, "Field '$field' must be uppercase.");
        }
    }

    public function testShippingFieldsExcludesUserIdentityColumns(): void
    {
        // OXUSERNAME / OXPASSWORD must NOT be in the shipping-address
        // allowlist — only the user's own form should accept those.
        $list = (new UserShippingAddressUpdatableFields())->getUpdatableFields();
        $this->assertNotContains('OXUSERNAME', $list);
        $this->assertNotContains('OXPASSWORD', $list);
        $this->assertNotContains('OXSHOPID', $list);
    }
}
