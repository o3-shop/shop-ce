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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\PasswordHasher;
use \oxPasswordHasher;

class PasswordHasherTest extends \OxidTestCase
{
    public function testHash()
    {
        $sPassword = 'password';
        $sSalt = 'salt';

        $oHasher = $this->getMock('oxSha512Hasher');
        $oHasher->expects($this->once())->method('hash')->with($this->equalTo($sPassword . $sSalt));

        $oPasswordHasher = new PasswordHasher($oHasher);

        $oPasswordHasher->hash($sPassword, $sSalt);
    }
}
