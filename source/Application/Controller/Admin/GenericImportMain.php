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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\NoJsValidator;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;

/**
 * Admin general export manager.
 */
class GenericImportMain extends AdminDetailsController
{
    /**
     * Export class name
     *
     * @var string
     */
    public $sClassDo = 'genImport_do';

    /**
     * Export ui class name
     *
     * @var string
     */
    public $sClassMain = 'genImport_main';

    /**
     * Csv file path
     *
     * @var string
     */
    protected $_sCsvFilePath = null;

    /**
     * Csv file field terminator
     *
     * @var string
     */
    protected $_sStringTerminator = null;

    /**
     * Csv file field encloser
     *
     * @var string
     */
    protected $_sStringEncloser = null;

    /**
     * Default Csv file field terminator
     *
     * @var string
     */
    protected $_sDefaultStringTerminator = ';';

    /**
     * Default Csv file field encloser
     *
     * @var string
     */
    protected $_sDefaultStringEncloser = '"';

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'genimport_main.tpl';

    /**
     * Creates shop object, passes shop data to Smarty engine and returns name of
     * template file
     *
     * @return string
     */
    public function render()
    {
        $config = Registry::getConfig();
        $oRequest = Registry::getRequest();

        $genericImport = oxNew(\OxidEsales\Eshop\Core\GenericImport\GenericImport::class);
        $this->_sCsvFilePath = null;

        $navigationStep = $oRequest->getRequestEscapedParameter('sNavStep');

        if (!$navigationStep) {
            $navigationStep = 1;
        } else {
            $navigationStep++;
        }

        $navigationStep = $this->checkErrors($navigationStep);

        if ($navigationStep == 1) {
            $this->_aViewData['sGiCsvFieldTerminator'] = Str::getStr()->htmlentities($this->getCsvFieldsTerminator());
            $this->_aViewData['sGiCsvFieldEncloser'] = Str::getStr()->htmlentities($this->getCsvFieldsEncloser());
        }

        if ($navigationStep == 2) {
            $noJsValidator = oxNew(NoJsValidator::class);
            //saving csv field terminator and encloser to config
            $terminator = $oRequest->getRequestEscapedParameter('sGiCsvFieldTerminator');
            if ($terminator && !$noJsValidator->isValid($terminator)) {
                $this->setErrorToView($terminator);
            } else {
                $this->_sStringTerminator = $terminator;
                $config->saveShopConfVar('str', 'sGiCsvFieldTerminator', $terminator);
            }

            $encloser = $oRequest->getRequestEscapedParameter('sGiCsvFieldEncloser');
            if ($encloser && !$noJsValidator->isValid($encloser)) {
                $this->setErrorToView($encloser);
            } else {
                $this->_sStringEncloser = $encloser;
                $config->saveShopConfVar('str', 'sGiCsvFieldEncloser', $encloser);
            }

            $type = $oRequest->getRequestEscapedParameter('sType');
            $importObject = $genericImport->getImportObject($type);
            $this->_aViewData['sType'] = $type;
            $this->_aViewData['sImportTable'] = $importObject->getBaseTableName();
            $this->_aViewData['aCsvFieldsList'] = $this->getCsvFieldsNames();
            $this->_aViewData['aDbFieldsList'] = $importObject->getFieldList();
        }

        if ($navigationStep == 3) {
            $csvFields = $oRequest->getRequestEscapedParameter('aCsvFields');
            $type = $oRequest->getRequestEscapedParameter('sType');

            $genericImport = oxNew(\OxidEsales\Eshop\Core\GenericImport\GenericImport::class);
            $genericImport->setImportType($type);
            $genericImport->setCsvFileFieldsOrder($csvFields);
            $genericImport->setCsvContainsHeader(Registry::getSession()->getVariable('blCsvContainsHeader'));

            $genericImport->importFile($this->getUploadedCsvFilePath());
            $this->_aViewData['iTotalRows'] = $genericImport->getImportedRowCount();

            //checking if errors occurred during import
            $this->checkImportErrors($genericImport);

            //deleting uploaded csv file from temp dir
            $this->deleteCsvFile();

            //check if repeating import - then forcing first step
            if ($oRequest->getRequestEscapedParameter('iRepeatImport')) {
                $this->_aViewData['iRepeatImport'] = 1;
                $navigationStep = 1;
            }
        }

        if ($navigationStep == 1) {
            $this->_aViewData['aImportTables'] = $genericImport->getImportObjectsList();
            asort($this->_aViewData['aImportTables']);
            $this->resetUploadedCsvData();
        }

        $this->_aViewData['sNavStep'] = $navigationStep;

        return parent::render();
    }

    /**
     * Deletes uploaded csv file from temp directory
     * @deprecated Use deleteCsvFile() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _deleteCsvFile().
     */
    protected function _deleteCsvFile() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPath = $this->getUploadedCsvFilePath();
        if (is_file($sPath)) {
            @unlink($sPath);
        }
    }

    /**
     * Deletes uploaded csv file from temp directory
     *
     * @internal If your override does not fully replace the behavior, call parent::deleteCsvFile()
     *           (not the deprecated _deleteCsvFile()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function deleteCsvFile()
    {
        $this->_deleteCsvFile();
    }

    /**
     * Get columns names from CSV file header. If file has no header
     * returns default columns names Column 1, Column 2 ...
     *
     * @return array
     * @deprecated Use getCsvFieldsNames() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getCsvFieldsNames().
     */
    protected function _getCsvFieldsNames() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blCsvContainsHeader = Registry::getRequest()->getRequestEscapedParameter('blContainsHeader');
        Registry::getSession()->setVariable('blCsvContainsHeader', $blCsvContainsHeader);
        $this->getUploadedCsvFilePath();

        $aFirstRow = $this->getCsvFirstRow();
        $aCsvFields = [];

        if (!$blCsvContainsHeader) {
            $iIndex = 1;
            foreach ($aFirstRow as $sValue) {
                $aCsvFields[$iIndex] = 'Column ' . $iIndex++;
            }
        } else {
            foreach ($aFirstRow as $sKey => $sValue) {
                $aFirstRow[$sKey] = Str::getStr()->htmlentities($sValue);
            }

            $aCsvFields = $aFirstRow;
        }

        return $aCsvFields;
    }

    /**
     * Get columns names from CSV file header. If file has no header
     * returns default columns names Column 1, Column 2 ...
     *
     * @return array
     *
     * @internal If your override does not fully replace the behavior, call parent::getCsvFieldsNames()
     *           (not the deprecated _getCsvFieldsNames()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getCsvFieldsNames()
    {
        return $this->_getCsvFieldsNames();
    }

    /**
     * Get first row from uploaded CSV file
     *
     * @return array
     * @deprecated Use getCsvFirstRow() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getCsvFirstRow().
     */
    protected function _getCsvFirstRow() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPath = $this->getUploadedCsvFilePath();
        $iMaxLineLength = 8192;
        $aRow = [];

        //getting first row
        if (($rFile = @fopen($sPath, 'r')) !== false) {
            $aRow = fgetcsv($rFile, $iMaxLineLength, $this->getCsvFieldsTerminator(), $this->getCsvFieldsEncloser());
            fclose($rFile);
        }

        return $aRow;
    }

    /**
     * Get first row from uploaded CSV file
     *
     * @return array
     *
     * @internal If your override does not fully replace the behavior, call parent::getCsvFirstRow()
     *           (not the deprecated _getCsvFirstRow()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getCsvFirstRow()
    {
        return $this->_getCsvFirstRow();
    }

    /**
     * Resets CSV parameters stored in session
     * @deprecated Use resetUploadedCsvData() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _resetUploadedCsvData().
     */
    protected function _resetUploadedCsvData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_sCsvFilePath = null;
        Registry::getSession()->setVariable('sCsvFilePath', null);
        Registry::getSession()->setVariable('blCsvContainsHeader', null);
    }

    /**
     * Resets CSV parameters stored in session
     *
     * @internal If your override does not fully replace the behavior, call parent::resetUploadedCsvData()
     *           (not the deprecated _resetUploadedCsvData()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function resetUploadedCsvData()
    {
        $this->_resetUploadedCsvData();
    }

    /**
     * Checks current import navigation step errors.
     * Returns step id in which error occurred.
     *
     * @param int $iNavStep Navigation step id
     *
     * @return int
     * @deprecated Use checkErrors() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _checkErrors().
     */
    protected function _checkErrors($iNavStep) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($iNavStep == 2) {
            if (!$this->getUploadedCsvFilePath()) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('GENIMPORT_ERRORUPLOADINGFILE');
                Registry::getUtilsView()->addErrorToDisplay($oEx, false, true, 'genimport');

                return 1;
            }
        }

        if ($iNavStep == 3) {
            $blIsEmpty = true;
            $aCsvFields = Registry::getRequest()->getRequestEscapedParameter('aCsvFields');
            foreach ($aCsvFields as $sValue) {
                if ($sValue) {
                    $blIsEmpty = false;
                    break;
                }
            }

            if ($blIsEmpty) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('GENIMPORT_ERRORASSIGNINGFIELDS');
                Registry::getUtilsView()->addErrorToDisplay($oEx, false, true, 'genimport');

                return 2;
            }
        }

        return $iNavStep;
    }

    /**
     * Checks current import navigation step errors.
     * Returns step id in which error occurred.
     *
     * @param int $iNavStep Navigation step id
     *
     * @return int
     *
     * @internal If your override does not fully replace the behavior, call parent::checkErrors()
     *           (not the deprecated _checkErrors()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function checkErrors($iNavStep)
    {
        return $this->_checkErrors($iNavStep);
    }

    /**
     * Checks if CSV file was uploaded. If uploaded - moves it to temp dir
     * and stores path to file in session. Return path to uploaded file.
     *
     * @return string|null
     * @deprecated Use getUploadedCsvFilePath() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getUploadedCsvFilePath().
     */
    protected function _getUploadedCsvFilePath() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        //try to get uploaded csv file path
        if ($this->_sCsvFilePath !== null) {
            return $this->_sCsvFilePath;
        } elseif ($this->_sCsvFilePath = Registry::getSession()->getVariable('sCsvFilePath')) {
            return $this->_sCsvFilePath;
        }

        $oConfig = Registry::getConfig();
        $aFile = $oConfig->getUploadedFile('csvfile');
        if (isset($aFile['name']) && $aFile['name']) {
            $this->_sCsvFilePath = $oConfig->getConfigParam('sCompileDir') . basename($aFile['tmp_name']);
            move_uploaded_file($aFile['tmp_name'], $this->_sCsvFilePath);
            Registry::getSession()->setVariable('sCsvFilePath', $this->_sCsvFilePath);

            return $this->_sCsvFilePath;
        }
    }

    /**
     * Checks if CSV file was uploaded. If uploaded - moves it to temp dir
     * and stores path to file in session. Return path to uploaded file.
     *
     * @return string|null|void
     *
     * @internal If your override does not fully replace the behavior, call parent::getUploadedCsvFilePath()
     *           (not the deprecated _getUploadedCsvFilePath()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getUploadedCsvFilePath()
    {
        return $this->_getUploadedCsvFilePath();
    }

    /**
     * Checks if any error occurred during import and displays them
     *
     * @param object $oErpImport Import object
     * @deprecated Use checkImportErrors() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _checkImportErrors().
     */
    protected function _checkImportErrors($oErpImport) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        foreach ($oErpImport->getStatistics() as $aValue) {
            if (!$aValue ['r']) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage($aValue ['m']);
                Registry::getUtilsView()->addErrorToDisplay($oEx, false, true, 'genimport');
            }
        }
    }

    /**
     * Checks if any error occurred during import and displays them
     *
     * @param object $oErpImport Import object
     *
     * @internal If your override does not fully replace the behavior, call parent::checkImportErrors()
     *           (not the deprecated _checkImportErrors()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function checkImportErrors($oErpImport)
    {
        $this->_checkImportErrors($oErpImport);
    }

    /**
     * Get csv field terminator symbol
     *
     * @return string
     * @deprecated Use getCsvFieldsTerminator() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getCsvFieldsTerminator().
     */
    protected function _getCsvFieldsTerminator() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_sStringTerminator === null) {
            $this->_sStringTerminator = $this->_sDefaultStringTerminator;
            if ($char = Registry::getConfig()->getConfigParam('sGiCsvFieldTerminator')) {
                $this->_sStringTerminator = $char;
            }
        }

        return $this->_sStringTerminator;
    }

    /**
     * Get csv field terminator symbol
     *
     * @return string
     *
     * @internal If your override does not fully replace the behavior, call parent::getCsvFieldsTerminator()
     *           (not the deprecated _getCsvFieldsTerminator()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getCsvFieldsTerminator()
    {
        return $this->_getCsvFieldsTerminator();
    }

    /**
     * Get csv field encloser symbol
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCsvFieldsEncloser" in next major
     */
    protected function _getCsvFieldsEncolser() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getCsvFieldsEncloser();
    }

    /**
     * Get csv field encloser symbol
     *
     * @return string
     */
    protected function getCsvFieldsEncloser()
    {
        if ($this->_sStringEncloser === null) {
            $this->_sStringEncloser = $this->_sDefaultStringEncloser;
            if ($char = Registry::getConfig()->getConfigParam('sGiCsvFieldEncloser')) {
                $this->_sStringEncloser = $char;
            }
        }

        return $this->_sStringEncloser;
    }

    /**
     * @param string $invalidData
     */
    private function setErrorToView($invalidData)
    {
        $error = oxNew(DisplayError::class);
        $error->setFormatParameters([htmlspecialchars($invalidData)]);
        $error->setMessage('SHOP_CONFIG_ERROR_INVALID_VALUE');
        Registry::getUtilsView()->addErrorToDisplay($error);
    }
}
