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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

class FileCheckerResultTest extends \OxidTestCase
{

    /**
     * Test data constructor
     *
     * @param $sResult
     * @param $sFile file name
     * @param $sColor
     * @param $sMessage
     *
     * @return array
     */
    protected function _getResultArray($sResult, $sFile, $sColor, $sMessage)
    {
        return array(
            "result"  => $sResult,
            "ok"      => $sResult == "OK",
            "file"    => $sFile,
            "color"   => $sColor,
            "message" => $sMessage
        );
    }

    /**
     * Testing getter and setter of listAllFiles parameter
     * oxFileCheckerResult::setListAllFiles
     * oxFileCheckerResult::getListAllFiles
     */
    public function testGetListAllFiles()
    {
        $oCheckerResult = oxNew("oxFileCheckerResult");

        $oCheckerResult->setListAllFiles(true);
        $this->assertTrue($oCheckerResult->getListAllFiles());

        $oCheckerResult->setListAllFiles(false);
        $this->assertFalse($oCheckerResult->getListAllFiles());
    }

    /**
     * Testcase for oxFileCheckerResult::getResultSummary when all files ok
     */
    public function testAddResult_shopOk()
    {
        $oFileCheckerResult = oxNew('oxFileCheckerResult');

        $aResult1 = $this->_getResultArray("OK", "file1.php", "green", "File ok");
        $oFileCheckerResult->addResult($aResult1);

        $aResult2 = $this->_getResultArray("OK", "file2.php", "green", "File ok");
        $oFileCheckerResult->addResult($aResult2);

        $aResult3 = $this->_getResultArray("OK", "file3.php", "green", "File ok");
        $oFileCheckerResult->addResult($aResult3);

        $aResultSummary = $oFileCheckerResult->getResultSummary();

        $aExpectedResultSummary = array(
            'OK'              => 3,
            'VERSIONMISMATCH' => 0,
            'UNKNOWN'         => 0,
            'MODIFIED'        => 0,
            'FILES'           => 3,
            'SHOP_OK'         => true,
        );

        $this->assertEquals($aExpectedResultSummary, $aResultSummary);
    }

    /**
     * Testcase for oxFileCheckerResult::getResultSummary all file validity variations
     */
    public function testAddResult_shopNotOK()
    {
        $oFileCheckerResult = oxNew('oxFileCheckerResult');

        $aResult1 = $this->_getResultArray("OK", "file1.php", "green", "File ok");
        $oFileCheckerResult->addResult($aResult1);

        $aResult2 = $this->_getResultArray("VERSIONMISMATCH", "file2.php", "red", "File not ok");
        $oFileCheckerResult->addResult($aResult2);
        $oFileCheckerResult->addResult($aResult2);

        $aResult3 = $this->_getResultArray("UNKNOWN", "file3.php", "gray", "File not ok");
        $oFileCheckerResult->addResult($aResult3);

        $aResult4 = $this->_getResultArray("MODIFIED", "file4.php", "blue", "File not ok");
        $oFileCheckerResult->addResult($aResult4);
        $oFileCheckerResult->addResult($aResult4);

        $aResultSummary = $oFileCheckerResult->getResultSummary();

        $aExpectedResultSummary = array(
            'OK'              => 1,
            'VERSIONMISMATCH' => 2,
            'UNKNOWN'         => 1,
            'MODIFIED'        => 2,
            'FILES'           => 6,
            'SHOP_OK'         => false,
        );

        $this->assertEquals($aExpectedResultSummary, $aResultSummary);
    }

    /**
     * Test case for oxFileCheckerResult::getResult when listAllFiles is false
     */
    public function testAddResult_listAllFilesIsFalse_returnsFailedFiles()
    {
        $oFileCheckerResult = oxNew('oxFileCheckerResult');
        $oFileCheckerResult->setListAllFiles(false);
        $aExpectedResult = array();

        $aResult1 = $this->_getResultArray("OK", "file1.php", "green", "File ok");
        $oFileCheckerResult->addResult($aResult1);

        $aResult2 = $this->_getResultArray("VERSIONMISMATCH", "file2.php", "red", "File not ok");
        $oFileCheckerResult->addResult($aResult2);
        $oFileCheckerResult->addResult($aResult2);
        $aExpectedResult[] = $aResult2;
        $aExpectedResult[] = $aResult2;

        $aResult3 = $this->_getResultArray("UNKNOWN", "file3.php", "gray", "File not ok");
        $oFileCheckerResult->addResult($aResult3);
        $aExpectedResult[] = $aResult3;

        $aResult4 = $this->_getResultArray("MODIFIED", "file4.php", "blue", "File not ok");
        $oFileCheckerResult->addResult($aResult4);
        $oFileCheckerResult->addResult($aResult4);
        $aExpectedResult[] = $aResult4;
        $aExpectedResult[] = $aResult4;

        $aResultReturned = $oFileCheckerResult->getResult();

        $this->assertEquals($aExpectedResult, $aResultReturned);
    }

    /**
     * Test case for oxFileCheckerResult::getResult when listAllFiles is true
     */
    public function testAddResult_listAllFilesIsTrue_returnsAllFiles()
    {
        $oFileCheckerResult = oxNew('oxFileCheckerResult');
        $oFileCheckerResult->setListAllFiles(true);
        $aExpectedResult = array();

        $aResult1 = $this->_getResultArray("OK", "file1.php", "green", "File ok");
        $oFileCheckerResult->addResult($aResult1);
        $aExpectedResult[] = $aResult1;

        $aResult2 = $this->_getResultArray("VERSIONMISMATCH", "file2.php", "red", "File not ok");
        $oFileCheckerResult->addResult($aResult2);
        $oFileCheckerResult->addResult($aResult2);
        $aExpectedResult[] = $aResult2;
        $aExpectedResult[] = $aResult2;

        $aResult3 = $this->_getResultArray("UNKNOWN", "file3.php", "gray", "File not ok");
        $oFileCheckerResult->addResult($aResult3);
        $aExpectedResult[] = $aResult3;

        $aResult4 = $this->_getResultArray("MODIFIED", "file4.php", "blue", "File not ok");
        $oFileCheckerResult->addResult($aResult4);
        $oFileCheckerResult->addResult($aResult4);
        $aExpectedResult[] = $aResult4;
        $aExpectedResult[] = $aResult4;

        $aResultReturned = $oFileCheckerResult->getResult();

        $this->assertEquals($aExpectedResult, $aResultReturned);
    }
}
