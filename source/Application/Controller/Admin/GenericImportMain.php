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

        $navigationStep = $this->_checkErrors($navigationStep);

        if ($navigationStep == 1) {
            $this->_aViewData['sGiCsvFieldTerminator'] = Str::getStr()->htmlentities($this->_getCsvFieldsTerminator());
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
            $this->_aViewData['aCsvFieldsList'] = $this->_getCsvFieldsNames();
            $this->_aViewData['aDbFieldsList'] = $importObject->getFieldList();
        }

        if ($navigationStep == 3) {
            $csvFields = $oRequest->getRequestEscapedParameter('aCsvFields');
            $type = $oRequest->getRequestEscapedParameter('sType');

            $genericImport = oxNew(\OxidEsales\Eshop\Core\GenericImport\GenericImport::class);
            $genericImport->setImportType($type);
            $genericImport->setCsvFileFieldsOrder($csvFields);
            $genericImport->setCsvContainsHeader(Registry::getSession()->getVariable('blCsvContainsHeader'));

            $genericImport->importFile($this->_getUploadedCsvFilePath());
            $this->_aViewData['iTotalRows'] = $genericImport->getImportedRowCount();

            //checking if errors occurred during import
            $this->_checkImportErrors($genericImport);

            //deleting uploaded csv file from temp dir
            $this->_deleteCsvFile();

            //check if repeating import - then forcing first step
            if ($oRequest->getRequestEscapedParameter('iRepeatImport')) {
                $this->_aViewData['iRepeatImport'] = 1;
                $navigationStep = 1;
            }
        }

        if ($navigationStep == 1) {
            $this->_aViewData['aImportTables'] = $genericImport->getImportObjectsList();
            asort($this->_aViewData['aImportTables']);
            $this->_resetUploadedCsvData();
        }

        $this->_aViewData['sNavStep'] = $navigationStep;

        return parent::render();
    }

    /**
     * Deletes uploaded csv file from temp directory
     * @deprecated Transitional during #107. Modules SHOULD override _deleteCsvFile()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes deleteCsvFile() to the canonical override
      *             target and retires _deleteCsvFile(); until then, _deleteCsvFile() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _deleteCsvFile() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPath = $this->_getUploadedCsvFilePath();
        if (is_file($sPath)) {
            @unlink($sPath);
        }
    }

    /**
     * Deletes uploaded csv file from temp directory
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _deleteCsvFile(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make deleteCsvFile() the canonical override target.
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
     * @deprecated Transitional during #107. Modules SHOULD override _getCsvFieldsNames()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getCsvFieldsNames() to the canonical override
      *             target and retires _getCsvFieldsNames(); until then, _getCsvFieldsNames() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getCsvFieldsNames() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blCsvContainsHeader = Registry::getRequest()->getRequestEscapedParameter('blContainsHeader');
        Registry::getSession()->setVariable('blCsvContainsHeader', $blCsvContainsHeader);
        $this->_getUploadedCsvFilePath();

        $aFirstRow = $this->_getCsvFirstRow();
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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getCsvFieldsNames(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getCsvFieldsNames() the canonical override target.
     */
    protected function getCsvFieldsNames()
    {
        return $this->_getCsvFieldsNames();
    }

    /**
     * Get first row from uploaded CSV file
     *
     * @return array
     * @deprecated Transitional during #107. Modules SHOULD override _getCsvFirstRow()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getCsvFirstRow() to the canonical override
      *             target and retires _getCsvFirstRow(); until then, _getCsvFirstRow() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getCsvFirstRow() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sPath = $this->_getUploadedCsvFilePath();
        $iMaxLineLength = 8192;
        $aRow = [];

        //getting first row
        if (($rFile = @fopen($sPath, 'r')) !== false) {
            $aRow = fgetcsv($rFile, $iMaxLineLength, $this->_getCsvFieldsTerminator(), $this->getCsvFieldsEncloser());
            fclose($rFile);
        }

        return $aRow;
    }

    /**
     * Get first row from uploaded CSV file
     *
     * @return array
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getCsvFirstRow(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getCsvFirstRow() the canonical override target.
     */
    protected function getCsvFirstRow()
    {
        return $this->_getCsvFirstRow();
    }

    /**
     * Resets CSV parameters stored in session
     * @deprecated Transitional during #107. Modules SHOULD override _resetUploadedCsvData()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes resetUploadedCsvData() to the canonical override
      *             target and retires _resetUploadedCsvData(); until then, _resetUploadedCsvData() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _resetUploadedCsvData(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make resetUploadedCsvData() the canonical override target.
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
     * @deprecated Transitional during #107. Modules SHOULD override _checkErrors()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes checkErrors() to the canonical override
      *             target and retires _checkErrors(); until then, _checkErrors() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _checkErrors($iNavStep) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($iNavStep == 2) {
            if (!$this->_getUploadedCsvFilePath()) {
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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _checkErrors(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make checkErrors() the canonical override target.
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
     * @deprecated Transitional during #107. Modules SHOULD override _getUploadedCsvFilePath()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getUploadedCsvFilePath() to the canonical override
      *             target and retires _getUploadedCsvFilePath(); until then, _getUploadedCsvFilePath() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getUploadedCsvFilePath(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getUploadedCsvFilePath() the canonical override target.
     */
    protected function getUploadedCsvFilePath()
    {
        return $this->_getUploadedCsvFilePath();
    }

    /**
     * Checks if any error occurred during import and displays them
     *
     * @param object $oErpImport Import object
     * @deprecated Transitional during #107. Modules SHOULD override _checkImportErrors()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes checkImportErrors() to the canonical override
      *             target and retires _checkImportErrors(); until then, _checkImportErrors() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _checkImportErrors(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make checkImportErrors() the canonical override target.
     */
    protected function checkImportErrors($oErpImport)
    {
        $this->_checkImportErrors($oErpImport);
    }

    /**
     * Get csv field terminator symbol
     *
     * @return string
     * @deprecated Transitional during #107. Modules SHOULD override _getCsvFieldsTerminator()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getCsvFieldsTerminator() to the canonical override
      *             target and retires _getCsvFieldsTerminator(); until then, _getCsvFieldsTerminator() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
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
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getCsvFieldsTerminator(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getCsvFieldsTerminator() the canonical override target.
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
