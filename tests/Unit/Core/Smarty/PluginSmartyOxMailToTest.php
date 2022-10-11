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

use \Smarty;
use \oxRegistry;

$filePath = oxRegistry::getConfig()->getConfigParam('sShopDir') . 'Core/Smarty/Plugin/function.oxmailto.php';
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    require_once dirname(__FILE__) . '/../../../../source/Core/Smarty/Plugin/function.oxmailto.php';
}

class PluginSmartyOxMailToTest extends \OxidTestCase
{
    public function testSmartyFunctionOxMailTo()
    {
        $aParams = array();
        $aParams['encode'] = 'javascript';
        $aParams['address'] = 'admin@my-o3-shop.com';
        $aParams['cc'] = 'cc@my-o3-shop.com';
        $aParams['bcc'] = 'bcc@my-o3-shop.com';
        $aParams['followupto'] = 'followupto@my-o3-shop.com';
        $aParams['subject'] = 'subject';
        $aParams['newsgroups'] = 'newsgroups';
        $aParams['extra'] = 'extra';
        $aParams['text'] = 'text';

        $oSmarty = new Smarty();

        $sMailTo = "admin@my-o3-shop.com?cc=cc@my-o3-shop.com&bcc=bcc@my-o3-shop.com&followupto=followupto@my-o3-shop.com";
        $sMailTo .= "&subject=subject&newsgroups=newsgroups";

        $sString = 'document.write(\'<a href="mailto:' . $sMailTo . '" extra>text</a>\');';
        $sEncodedString = "%" . wordwrap(current(unpack("H*", $sString)), 2, "%", true);
        $sExpected = '<script type="text/javascript">eval(decodeURIComponent(\'' . $sEncodedString . '\'))</script>';

        $this->assertEquals($sExpected, smarty_function_oxmailto($aParams, $oSmarty));
    }
}
