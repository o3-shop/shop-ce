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

$filePath = oxRegistry::getConfig()->getConfigParam('sShopDir') . 'Core/Smarty/Plugin/function.oxscript.php';
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    require_once dirname(__FILE__) . '/../../../../source/Core/Smarty/Plugin/function.oxscript.php';
}

class PluginSmartyOxScriptTest extends \OxidTestCase
{

    /**
     * Check for error if not existing file for include given.
     */
    public function testSmartyFunctionOxScript_includeNotExist()
    {
        $this->getConfig()->setConfigParam("iDebug", -1);

        $this->expectWarning();
        $oSmarty = new Smarty();
        $this->assertEquals('', smarty_function_oxscript(array('include' => 'somescript.js'), $oSmarty));
    }

    /**
     * Check oxscript include
     */
    public function testSmartyFunctionOxScript_includeExist()
    {
        $oSmarty = new Smarty();
        $this->assertEquals('', smarty_function_oxscript(array('include' => 'http://someurl/src/js/libs/jquery.min.js'), $oSmarty));

        $sOutput = '<script type="text/javascript" src="http://someurl/src/js/libs/jquery.min.js"></script>';

        $this->assertEquals($sOutput, smarty_function_oxscript(array('inWidget' => false), $oSmarty));
    }

    /**
     * Check oxscript inclusion in widget
     */
    public function testSmartyFunctionOxScript_widget_include()
    {
        $oSmarty = new Smarty();
        $this->assertEquals('', smarty_function_oxscript(array('include' => 'http://someurl/src/js/libs/jquery.min.js'), $oSmarty));

        $sOutput = <<<JS
<script type='text/javascript'>
    window.addEventListener('load', function() {
        WidgetsHandler.registerFile('http://someurl/src/js/libs/jquery.min.js', 'somewidget');
    }, false)
</script>
JS;

        $this->assertEquals($sOutput, smarty_function_oxscript(array('widget' => 'somewidget', 'inWidget' => true), $oSmarty));
    }

    /**
     * Check for oxscript add method
     */
    public function testSmartyFunctionOxScript_add()
    {
        $oSmarty = new Smarty();
        $this->assertEquals('', smarty_function_oxscript(array('add' => 'oxidadd'), $oSmarty));

        $sOutput = "<script type='text/javascript'>oxidadd</script>";

        $this->assertEquals($sOutput, smarty_function_oxscript(array(), $oSmarty));
    }

    /**
     * Provides data for oxscript add function
     */
    public function addProvider()
    {
        return array(
            array('oxidadd', 'oxidadd'),
            array('"oxidadd"', '"oxidadd"'),
            array("'oxidadd'", "\\'oxidadd\\'"),
            array("oxid\r\nadd", 'oxid\nadd'),
            array("oxid\nadd", 'oxid\nadd'),
        );
    }

    /**
     * Check for oxscript add method in widget
     *
     * @dataProvider addProvider
     */
    public function testSmartyFunctionOxScript_widget_add($sScript, $sScriptOutput)
    {
        $oSmarty = new Smarty();
        $this->assertEquals('', smarty_function_oxscript(array('add' => $sScript), $oSmarty));

        $sOutput = "<script type='text/javascript'>window.addEventListener('load', function() { WidgetsHandler.registerFunction('$sScriptOutput', 'somewidget'); }, false )</script>";
        $this->assertEquals($sOutput, smarty_function_oxscript(array('widget' => 'somewidget', 'inWidget' => true), $oSmarty));
    }
}
