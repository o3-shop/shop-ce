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

use \oxPasswordSaltGenerator;

class PasswordSaltGeneratorTest extends \OxidTestCase
{
    public function providerOpenSslRandomBytesGeneratorAvailability()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @dataProvider providerOpenSslRandomBytesGeneratorAvailability
     */
    public function testSaltLength($blIsOpenSslRandomBytesGeneratorAvailable)
    {
        $oOpenSSLFunctionalityChecker = $this->_getOpenSSLFunctionalityChecker($blIsOpenSslRandomBytesGeneratorAvailable);
        $oGenerator = new oxPasswordSaltGenerator($oOpenSSLFunctionalityChecker);
        $this->assertSame(32, strlen($oGenerator->generate()));
    }

    /**
     * @dataProvider providerOpenSslRandomBytesGeneratorAvailability
     */
    public function testGeneratedSaltShouldBeUnique($blIsOpenSslRandomBytesGeneratorAvailable)
    {
        $oOpenSSLFunctionalityChecker = $this->_getOpenSSLFunctionalityChecker($blIsOpenSslRandomBytesGeneratorAvailable);
        $oGenerator = new oxPasswordSaltGenerator($oOpenSSLFunctionalityChecker);
        $aSalts = array();

        for ($i = 1; $i <= 100; $i++) {
            $aSalts[] = $oGenerator->generate();
        }

        $this->assertSame(100, count(array_unique($aSalts)));
    }

    /**
     * Returns oxOpenSSLFunctionalityChecker object dependent on condition. It can return mocked object or not.
     * This is needed because of environment. For example on php 5.2 there is no such function like openssl_random_pseudo_bytes
     * so in that case we don't want to mock checker.
     *
     * @param $blIsOpenSslRandomBytesGeneratorAvailable
     *
     * @return oxOpenSSLFunctionalityChecker
     */
    private function _getOpenSSLFunctionalityChecker($blIsOpenSslRandomBytesGeneratorAvailable)
    {
        if ($blIsOpenSslRandomBytesGeneratorAvailable) {
            $oOpenSSLFunctionalityChecker = oxNew('oxOpenSSLFunctionalityChecker');
        } else {
            /** @var oxOpenSSLFunctionalityChecker $oOpenSSLFunctionalityChecker */
            $oOpenSSLFunctionalityChecker = $this->getMock(\OxidEsales\Eshop\Core\OpenSSLFunctionalityChecker::class, array('isOpenSslRandomBytesGeneratorAvailable'));
            $oOpenSSLFunctionalityChecker->expects($this->any())->method('isOpenSslRandomBytesGeneratorAvailable')->will($this->returnValue($blIsOpenSslRandomBytesGeneratorAvailable));
        }


        return $oOpenSSLFunctionalityChecker;
    }
}
