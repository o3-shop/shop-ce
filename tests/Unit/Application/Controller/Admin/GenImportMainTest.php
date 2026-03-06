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

use Exception;
use oxRegistry;
use oxTestModules;

/**
 * Tests for GenImport_Main class
 */
class GenImportMainTest extends \OxidTestCase
{
    /**
     * GenImport_Main::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('GenImport_Main');
        $this->assertEquals('genimport_main.tpl', $oView->render());
    }

    /**
     * Checks if values was converted to HTML entities.
     *
     * @return array
     */
    public function providerRenderIfConvertedViewData()
    {
        return [
            ['sGiCsvFieldTerminator', "'<b>", '&#039;&lt;b&gt;'],
            ['sGiCsvFieldEncloser', "'<b>", '&#039;&lt;b&gt;'],
        ];
    }

    /**
     * @param $sParameter
     * @param $sValue
     * @param $sResult
     *
     * @dataProvider providerRenderIfConvertedViewData
     */
    public function testRenderIfConvertedViewData($sParameter, $sValue, $sResult)
    {
        $oView = oxNew('GenImport_Main');
        $this->getConfig()->setConfigParam($sParameter, $sValue);
        $oView->render();
        $aData = $oView->getViewData();

        $this->assertSame($sResult, $aData[$sParameter]);
    }

    /**
     * GenImport_Main::DeleteCsvFile() test case
     *
     * @return null
     */
    public function testDeleteCsvFile()
    {
        // creating file for test
        $sFilePath = $this->getConfig()->getConfigParam('sCompileDir') . md5(time());
        $rFile = fopen($sFilePath, 'w');
        fclose($rFile);

        $this->assertTrue(file_exists($sFilePath));

        // testing..
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\GenericImportMain::class, ['getUploadedCsvFilePath']);
        $oView->expects($this->once())->method('getUploadedCsvFilePath')->will($this->returnValue($sFilePath));
        $oView->UNITdeleteCsvFile();

        $this->assertFalse(file_exists($sFilePath));
    }

    /**
     * GenImport_Main::GetCsvFieldsNames() test case
     *
     * @return null
     */
    public function testGetCsvFieldsNamesContainsNoHeader()
    {
        $this->setRequestParameter('blContainsHeader', false);

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\GenericImportMain::class, ['getUploadedCsvFilePath', 'getCsvFirstRow']);
        $oView->expects($this->once())->method('getUploadedCsvFilePath')->will($this->returnValue(false));
        $oView->expects($this->once())->method('getCsvFirstRow')->will($this->returnValue([1, 2, 3]));
        $this->assertEquals([2 => 'Column 1', 3 => 'Column 2', 4 => 'Column 3'], $oView->UNITgetCsvFieldsNames());
    }

    /**
     * GenImport_Main::GetCsvFieldsNames() test case
     *
     * @return null
     */
    public function testGetCsvFieldsNamesContainsHeader()
    {
        $this->setRequestParameter('blContainsHeader', true);

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\GenericImportMain::class, ['getUploadedCsvFilePath', 'getCsvFirstRow']);
        $oView->expects($this->once())->method('getUploadedCsvFilePath')->will($this->returnValue(false));
        $oView->expects($this->once())->method('getCsvFirstRow')->will($this->returnValue([1, 2, 3]));
        $this->assertEquals([1, 2, 3], $oView->UNITgetCsvFieldsNames());
    }

    /**
     * GenImport_Main::GetCsvFirstRow() test case
     *
     * @return null
     */
    public function testGetCsvFirstRow()
    {
        // creating file for test
        $sFilePath = $this->getConfig()->getConfigParam('sCompileDir') . md5(time());
        $rFile = fopen($sFilePath, 'w');
        fwrite($rFile, '"test1";"test2";"test3"');
        fclose($rFile);

        // testing..
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\GenericImportMain::class, ['getCsvFieldsTerminator', 'getCsvFieldsEncloser', 'getUploadedCsvFilePath']);
        $oView->expects($this->once())->method('getCsvFieldsTerminator')->will($this->returnValue(';'));
        $oView->expects($this->once())->method('getCsvFieldsEncloser')->will($this->returnValue('"'));
        $oView->expects($this->once())->method('getUploadedCsvFilePath')->will($this->returnValue($sFilePath));
        $this->assertEquals(['test1', 'test2', 'test3'], $oView->UNITgetCsvFirstRow());
    }

    /**
     * GenImport_Main::ResetUploadedCsvData() test case
     *
     * @return null
     */
    public function testResetUploadedCsvData()
    {
        $this->getSession()->setVariable('sCsvFilePath', 'sCsvFilePath');
        $this->getSession()->setVariable('blCsvContainsHeader', 'blCsvContainsHeader');

        $oView = $this->getProxyClass('GenImport_Main');
        $oView->setNonPublicVar('_sCsvFilePath', 'testPath');
        $oView->UNITresetUploadedCsvData();

        $this->assertNull(oxRegistry::getSession()->getVariable('sCsvFilePath'));
        $this->assertNull(oxRegistry::getSession()->getVariable('blCsvContainsHeader'));
        $this->assertNull($oView->getNonPublicVar('_sCsvFilePath'));
    }

    /**
     * GenImport_Main::CheckErrors() test case
     *
     * @return null
     */
    public function testCheckErrorsStep2()
    {
        oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{}');

        // defining parameters
        $iNavStep = 2;

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\GenericImportMain::class, ['getUploadedCsvFilePath']);
        $oView->expects($this->once())->method('getUploadedCsvFilePath')->will($this->returnValue(false));
        $this->assertEquals(1, $oView->UNITcheckErrors($iNavStep));
    }

    /**
     * GenImport_Main::CheckErrors() test case
     *
     * @return null
     */
    public function testCheckErrorsStep3EmptyCsvFields()
    {
        $this->setRequestParameter('aCsvFields', []);

        // defining parameters
        $iNavStep = 3;

        $oView = oxNew('GenImport_Main');
        $this->assertEquals(2, $oView->UNITcheckErrors($iNavStep));
    }

    /**
     * GenImport_Main::CheckErrors() test case
     *
     * @return null
     */
    public function testCheckErrorsStep3()
    {
        oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{}');
        $this->setRequestParameter('aCsvFields', ['sTestField']);

        // defining parameters
        $iNavStep = 3;

        $oView = oxNew('GenImport_Main');
        $this->assertEquals($iNavStep, $oView->UNITcheckErrors($iNavStep));
    }

    /**
     * GenImport_Main::GetUploadedCsvFilePath() test case
     *
     * @return null
     */
    public function testGetUploadedCsvFilePathDefinedAsClassParam()
    {
        $this->getSession()->setVariable('sCsvFilePath', null);

        // testing..
        $oView = $this->getProxyClass('GenImport_Main');
        $oView->setNonPublicVar('_sCsvFilePath', '_sCsvFilePath');
        $this->assertEquals('_sCsvFilePath', $oView->UNITgetUploadedCsvFilePath());
    }

    /**
     * GenImport_Main::GetUploadedCsvFilePath() test case
     *
     * @return null
     */
    public function testGetUploadedCsvFilePathDefinedAsSessionParam()
    {
        $this->getSession()->setVariable('sCsvFilePath', 'sCsvFilePath');

        // testing..
        $oView = $this->getProxyClass('GenImport_Main');
        $this->assertEquals('sCsvFilePath', $oView->UNITgetUploadedCsvFilePath());
    }

    /**
     * GenImport_Main::GetUploadedCsvFilePath() test case
     *
     * @return null
     */
    public function testGetUploadedCsvFilePath()
    {
        $this->markTestSkipped(
            'Cannot unit-test file upload path: production code uses Registry::getConfig() (not $this->getConfig()) '
            . 'and move_uploaded_file() which requires a real HTTP upload. Needs integration test.'
        );
    }

    /**
     * GenImport_Main::CheckImportErrors() test case
     *
     * @return null
     */
    public function testCheckImportErrors()
    {
        oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{ throw new Exception( "addErrorToDisplay" );}');

        // defining parameters
        $oErpImport = $this->getMock('oxErpGenImport', ['getStatistics']);
        $oErpImport->expects($this->once())->method('getStatistics')->will($this->returnValue([['r' => false, 'm' => true]]));

        try {
            $oView = oxNew('GenImport_Main');
            $oView->UNITcheckImportErrors($oErpImport);
        } catch (Exception $oExcp) {
            $this->assertEquals('addErrorToDisplay', $oExcp->getMessage(), 'Error in GenImport_Main::_checkImportErrors()');

            return;
        }
        $this->fail('Error in GenImport_Main::_checkImportErrors()');
    }

    /**
     * GenImport_Main::GetCsvFieldsTerminator() test case
     *
     * @return null
     */
    public function testGetCsvFieldsTerminator()
    {
        $this->getConfig()->setConfigParam('sGiCsvFieldTerminator', ';');

        // testing..
        $oView = oxNew('GenImport_Main');
        $this->assertEquals($this->getConfig()->getConfigParam('sGiCsvFieldTerminator'), $oView->UNITgetCsvFieldsTerminator());
    }

    /**
     * GenImport_Main::GetCsvFieldsEncolser() test case
     *
     * @return null
     */
    public function testGetCsvFieldsEncolser()
    {
        $this->getConfig()->setConfigParam('sGiCsvFieldEncloser', '"');

        // testing..
        $oView = oxNew('GenImport_Main');
        $this->assertEquals($this->getConfig()->getConfigParam('sGiCsvFieldEncloser'), $oView->UNITgetCsvFieldsEncolser());
    }
}
