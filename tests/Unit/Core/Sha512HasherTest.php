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

class Sha512HasherTest extends \OxidTestCase
{
    public function testEncrypt()
    {
        $sHash = 'b32e441399b4601e11846563bea5c6597b7fbeeb8d443a05cdaf0c5615f6bd9c168eac63856945c2b188f933db330f8202bbd4a2a4abadef0ed96f6247970622';
        $oHasher = oxNew('oxSha512Hasher');

        $this->assertSame($sHash, $oHasher->hash('somestring05853e9aba10b9c25a3b8af5618ec9fa'));
    }
}
