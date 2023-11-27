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

use \oxConfigFile;

class OxConfigFileTest extends \OxidTestCase
{

    /**
     * Test for OxConfigFile::getVar() method
     */
    public function testGetVar()
    {
        $filePath = $this->createFile('config.inc.php', '<?php $this->testVar = "testValue";');
        $oConfigFile = new oxConfigFile($filePath);

        $sVar = $oConfigFile->getVar("testVar");
        $this->assertSame("testValue", $sVar);
    }

    /**
     * Test for OxConfigFile::setVar() method
     */
    public function testSetVar()
    {
        $filePath = $this->createFile('config.inc.php', '<?php ');
        $oConfigFile = new oxConfigFile($filePath);

        $oConfigFile->setVar("testVar", 'testValue2');

        $sVar = $oConfigFile->getVar("testVar");
        $this->assertSame('testValue2', $sVar);
    }

    /**
     * Tests OxConfigFile::isVarSet() method
     */
    public function testIsVarSet()
    {
        $filePath = $this->createFile('config.inc.php', '<?php $this->testVar = "testValue";');
        $oConfigFile = new oxConfigFile($filePath);

        $this->assertTrue($oConfigFile->isVarSet("testVar"), "Variable is supposed to be set");
        $this->assertFalse($oConfigFile->isVarSet("nonExistingVar"), "Variable is not supposed to be set");
    }

    /**
     * Test for OxConfigFile::getVars() method
     */
    public function testGetVars()
    {
        $this->markTestSkipped('Review with D.S. Bug or feature.?');
//        Result: Array
//        (
//            [dynamicProperties] => Array
//            (
//                [testVar] => testValue
//                [testVar2] => testValue2
//        )

        $filePath = $this->createFile('config.inc.php', '<?php $this->testVar = "testValue"; $this->testVar2 = "testValue2";');
        $oConfigFile = new oxConfigFile($filePath);

        $aVars = $oConfigFile->getVars();
        $expectedArray = array(
            'testVar' => 'testValue',
            'testVar2' => 'testValue2',
        );

        $this->assertSame($expectedArray, $aVars);
    }

    /**
     * Tests that file is loaded only once
     */
    public function testFileIsLoadedOnlyOnce()
    {
        $filePath = $this->createFile('config.inc.php', '<?php $this->testVar = "testValue";');
        $oConfigFile = new oxConfigFile($filePath);

        $sVar = $oConfigFile->getVar("testVar");
        $this->assertSame("testValue", $sVar);

        $oConfigFile->setVar("testVar", 'testValue2');

        $this->assertSame("testValue2", $oConfigFile->getVar("testVar"));
    }

    /**
     * Tests that custom config is being set and variables from it are reachable
     */
    public function testSetFile()
    {
        $filePath = $this->createFile('config.inc.php', '<?php $this->testVar = "testValue";');
        $oConfigFile = new oxConfigFile($filePath);

        $customConfigInc = $this->createFile('config.inc.php', '<?php $this->testVar2 = "testValue2";');
        $oConfigFile->setFile($customConfigInc);

        $this->assertSame("testValue2", $oConfigFile->getVar("testVar2"));
    }
}
