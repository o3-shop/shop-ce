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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

final class UtilsServerTest extends TestCase
{
    use ContainerTrait;

    protected function tearDown(): void
    {
        $utils = oxNew('oxutilsserver');
        if ($utils->getUserCookie()) {
            $utils->deleteUserCookie();
        }
        parent::tearDown();
    }

    public function testGetSetAndDeleteUserCookie(): void
    {
        $utils = oxNew('oxutilsserver');

        $this->assertNull($utils->getUserCookie());

        $utils->setUserCookie('admin', 'admin', null, 31536000, User::USER_COOKIE_SALT);

        $aData = explode('@@@', $utils->getUserCookie());

        $this->assertTrue(
            $this->get(PasswordServiceBridgeInterface::class)->verifyPassword('admin' . User::USER_COOKIE_SALT, $aData[1])
        );

        $utils->deleteUserCookie();
        $this->assertNull($utils->getUserCookie());
    }
}
