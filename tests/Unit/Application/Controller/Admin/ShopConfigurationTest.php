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

use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidTestCase;

/**
 * Tests for ShopConfiguration class
 */
class ShopConfigurationTest extends OxidTestCase
{
    /**
     * Test _multilineToArray returns the expected array.
     */
    public function testMultilineToArray()
    {
        $oShopConf = new ShopConfiguration();
        $sMultiline = "line1\nline2\n\nline3 ";
        $aExpected = [0 => 'line1', 1 => 'line2', 3 => 'line3'];

        $aResult = $this->callVisibleProtectedMethod($oShopConf, '_multilineToArray', [$sMultiline]);

        $this->assertEquals($aExpected, $aResult);
    }

    /**
     * Test _aarrayToMultiline returns the expected string.
     */
    public function testAarrayToMultiline()
    {
        $oShopConf = new ShopConfiguration();
        $aInput = ['key1' => 'val1', 'key2' => 'val2'];
        $sExpected = "key1 => val1\nkey2 => val2";

        $sResult = $this->callVisibleProtectedMethod($oShopConf, '_aarrayToMultiline', [$aInput]);

        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * Helper to call protected methods for testing.
     *
     * @param object $oObject
     * @param string $sMethod
     * @param array  $aParams
     *
     * @return mixed
     */
    protected function callVisibleProtectedMethod($oObject, $sMethod, $aParams)
    {
        $oReflection = new \ReflectionClass(get_class($oObject));
        $oMethod = $oReflection->getMethod($sMethod);
        $oMethod->setAccessible(true);

        return $oMethod->invokeArgs($oObject, $aParams);
    }
}
