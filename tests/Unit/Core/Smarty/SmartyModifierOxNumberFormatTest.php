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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Smarty;

use \oxRegistry;

$filePath = oxRegistry::getConfig()->getConfigParam('sShopDir') . 'Core/Smarty/Plugin/modifier.oxnumberformat.php';
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    require_once dirname(__FILE__) . '/../../../../source/Core/Smarty/Plugin/modifier.oxnumberformat.php';
}

class SmartyModifierOxNumberFormatTest extends \OxidTestCase
{

    /**
     * Provides number format, number and expected value
     */
    public function Provider()
    {
        return array(
            array("EUR@ 1.00@ ,@ .@ EUR@ 2", 25000, '25.000,00'),
            array("EUR@ 1.00@ ,@ .@ EUR@ 2", 25000.1584, '25.000,16'),
            array("EUR@ 1.00@ ,@ .@ EUR@ 3", 25000.1584, '25.000,158'),
            array("EUR@ 1.00@ ,@ .@ EUR@ 0", 25000000.5584, '25.000.001'),
            array("EUR@ 1.00@ .@ ,@ EUR@ 2", 25000000.5584, '25,000,000.56'),
        );
    }

    /**
     * Tests how oxnumberformat modifier works
     *
     * @dataProvider Provider
     */
    public function testNumberFormatDefaultFormat($sFormat, $mValue, $sExpected)
    {
        $this->assertEquals($sExpected, smarty_modifier_oxnumberformat($sFormat, $mValue));
    }
}
