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

use \oxRegistry;

class UtilsstringTest extends \OxidTestCase
{
    public function testPrepareCSVField()
    {
        $this->assertEquals('"blafoo;wurst;suppe"', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareCSVField("blafoo;wurst;suppe"));
        $this->assertEquals('"bl""afoo;wurst;suppe"', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareCSVField("bl\"afoo;wurst;suppe"));
        $this->assertEquals('"blafoo;wu"";rst;suppe"', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareCSVField("blafoo;wu\";rst;suppe"));
        $this->assertEquals('', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareCSVField(""));
        $this->assertEquals('""""', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareCSVField("\""));
        $this->assertEquals('";"', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareCSVField(";"));
    }

    public function testMinimizeTruncateString()
    {
        $sTest = "myfooblatest";
        $this->assertEquals("myf", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 3));
        $this->assertEquals("", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 0));
        $this->assertEquals("myfooblatest", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 99));

        $sTest = "        my,f,,     o  o bl at  ,,  ,,est,  ";
        $this->assertEquals("my", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 3));
        $this->assertEquals("my,f", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 4));
        $this->assertEquals("", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 0));
        $this->assertEquals("my,f,, o o bl at ,, ,,est", \OxidEsales\Eshop\Core\Registry::getUtilsString()->minimizeTruncateString($sTest, 99));
    }

    public function testPrepareStrForSearch()
    {
        $this->assertEquals(' &auml; &ouml; &uuml; &Auml; &Ouml; &Uuml; &szlig; &', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareStrForSearch(' ä ö ü Ä Ö Ü ß &amp;'));
        $this->assertEquals(' h&auml;user', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareStrForSearch(' häuser'));
        $this->assertEquals('', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareStrForSearch('qwertz'));
        $this->assertEquals('', \OxidEsales\Eshop\Core\Registry::getUtilsString()->prepareStrforSearch(''));
    }
}
