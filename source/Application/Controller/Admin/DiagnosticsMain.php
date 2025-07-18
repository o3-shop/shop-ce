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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Diagnostics;
use OxidEsales\Eshop\Application\Model\DiagnosticsOutput;
use OxidEsales\Eshop\Application\Model\FileChecker;
use OxidEsales\Eshop\Application\Model\FileCheckerResult;
use OxidEsales\Eshop\Application\Model\FileCollector;
use OxidEsales\Eshop\Application\Model\SmartyRenderer;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\Facts\Facts;

/**
 * Checks Version of System files.
 * Admin Menu: Service -> Version Checker -> Main.
 */
class DiagnosticsMain extends AdminDetailsController
{
    /**
     * error tag
     *
     * @var boolean
     */
    protected $_blError = false;

    /**
     * error message
     *
     * @var string
     */
    protected $_sErrorMessage = null;

    /**
     * Diagnostic check object
     *
     * @var mixed
     */
    protected $_oDiagnostics = null;

    /**
     * Smarty renderer
     *
     * @var mixed
     */
    protected $_oRenderer = null;

    /**
     * Result output object
     *
     * @var mixed
     */
    protected $_oOutput = null;

    /**
     * Variable for storing shop root directory
     *
     * @var mixed|string
     */
    protected $_sShopDir = '';

    /**
     * Error status getter
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "hasError" in next major
     */
    protected function _hasError() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->hasError();
    }

    /**
     * Error status getter
     *
     * @return bool
     */
    protected function hasError()
    {
        return $this->_blError;
    }

    /**
     * Error status getter
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getErrorMessage" in next major
     */
    protected function _getErrorMessage() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getErrorMessage();
    }

    /**
     * Error status getter
     *
     * @return string
     */
    protected function getErrorMessage()
    {
        return $this->_sErrorMessage;
    }

    /**
     * Calls parent constructor and initializes checker object
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->_sShopDir = Registry::getConfig()->getConfigParam('sShopDir');
        $this->_oOutput = oxNew(DiagnosticsOutput::class);
        $this->_oRenderer = oxNew(SmartyRenderer::class);
    }

    /**
     * Loads version-check class.
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        if ($this->hasError()) {
            $this->_aViewData['sErrorMessage'] = $this->getErrorMessage();
        }

        return "diagnostics_form.tpl";
    }

    /**
     * Gets list of files to be checked
     *
     * @return array list of shop files to be checked
     * @throws Exception
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     */
    protected function _getFilesToCheck() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDiagnostics = oxNew(Diagnostics::class);
        $aFilePathList = $oDiagnostics->getFileCheckerPathList();
        $aFileExtensionList = $oDiagnostics->getFileCheckerExtensionList();

        $oFileCollector = oxNew(FileCollector::class);
        $oFileCollector->setBaseDirectory($this->_sShopDir);

        foreach ($aFilePathList as $sPath) {
            if (is_file($this->_sShopDir . $sPath)) {
                $oFileCollector->addFile($sPath);
            } elseif (is_dir($this->_sShopDir . $sPath)) {
                $oFileCollector->addDirectoryFiles($sPath, $aFileExtensionList, true);
            }
        }

        return $oFileCollector->getFiles();
    }

    /**
     * Checks versions for list of oxid files
     *
     * @param array $aFileList array list of files to be checked
     *
     * @return null|FileCheckerResult
     * @throws Exception
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     */
    protected function _checkOxidFiles($aFileList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oFileChecker = oxNew(FileChecker::class);
        $oFileChecker->setBaseDirectory($this->_sShopDir);
        $oFileChecker->setVersion(Registry::getConfig()->getVersion());
        $oFileChecker->setEdition((new Facts())->getEdition());
        $oFileChecker->setRevision(Registry::getConfig()->getRevision());

        if (!$oFileChecker->init()) {
            $this->_blError = true;
            $this->_sErrorMessage = $oFileChecker->getErrorMessage();

            return null;
        }

        $oFileCheckerResult = oxNew(FileCheckerResult::class);

        $blListAllFiles = ($this->getParam('listAllFiles') == 'listAllFiles');
        $oFileCheckerResult->setListAllFiles($blListAllFiles);

        foreach ($aFileList as $sFile) {
            $aCheckResult = $oFileChecker->checkFile($sFile);
            $oFileCheckerResult->addResult($aCheckResult);
        }

        return $oFileCheckerResult;
    }

    /**
     * Returns body of file check report
     *
     * @param FileCheckerResult $oFileCheckerResult mixed file checker result object
     *
     * @return string body of report
     * @throws Exception
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     */
    protected function _getFileCheckReport($oFileCheckerResult) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aViewData = [
            "sVersion"       => Registry::getConfig()->getVersion(),
            "sEdition"       => (new Facts())->getEdition(),
            "sRevision"      => Registry::getConfig()->getRevision(),
            "aResultSummary" => $oFileCheckerResult->getResultSummary(),
            "aResultOutput"  => $oFileCheckerResult->getResult(),
        ];

        return $this->_oRenderer->renderTemplate("version_checker_result.tpl", $aViewData);
    }

    /**
     * Checks system file versions
     *
     * @return void
     * @throws Exception
     */
    public function startDiagnostics()
    {
        $sReport = "";

        $aDiagnosticsResult = $this->runBasicDiagnostics();
        $sReport .= $this->_oRenderer->renderTemplate("diagnostics_main.tpl", $aDiagnosticsResult);

        /**
         * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
         */
        if ($this->getParam('oxdiag_frm_chkvers')) {
            $aFileList = $this->_getFilesToCheck();
            $oFileCheckerResult = $this->_checkOxidFiles($aFileList);

            if ($this->hasError()) {
                return;
            }

            $sReport .= $this->_getFileCheckReport($oFileCheckerResult);
        }

        $this->_oOutput->storeResult($sReport);

        $sResult = $this->_oOutput->readResultFile();
        $this->_aViewData['sResult'] = $sResult;
    }

    /**
     * Performs main system diagnostic.
     * Shop and module details, database health, php parameters, server information
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "runBasicDiagnostics" in next major
     */
    protected function _runBasicDiagnostics() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->runBasicDiagnostics();
    }

    /**
     * Performs main system diagnostic.
     * Shop and module details, database health, php parameters, server information
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function runBasicDiagnostics()
    {
        $aViewData = [];
        $oDiagnostics = oxNew(Diagnostics::class);

        $oDiagnostics->setShopLink(Registry::getConfig()->getConfigParam('sShopURL'));
        $oDiagnostics->setEdition(Registry::getConfig()->getFullEdition());
        $oDiagnostics->setVersion(Registry::getConfig()->getVersion());
        $oDiagnostics->setRevision(Registry::getConfig()->getRevision());

        /**
         * Shop
         */
        if ($this->getParam('runAnalysis')) {
            $aViewData['runAnalysis'] = true;
            $aViewData['aShopDetails'] = $oDiagnostics->getShopDetails();
        }

        /**
         * Modules
         */
        if ($this->getParam('oxdiag_frm_modules')) {
            $aViewData['oxdiag_frm_modules'] = true;
            $aViewData['mylist'] = $this->getInstalledModules();
        }

        /**
         * Health
         */
        if ($this->getParam('oxdiag_frm_health')) {
            $oSysReq = oxNew(\OxidEsales\Eshop\Core\SystemRequirements::class);
            $aViewData['oxdiag_frm_health'] = true;
            $aViewData['aInfo'] = $oSysReq->getSystemInfo();
            $aViewData['aCollations'] = $oSysReq->checkCollation();
        }

        /**
         * PHP info
         * Fetches a handful of php configuration parameters and collects their values.
         */
        if ($this->getParam('oxdiag_frm_php')) {
            $aViewData['oxdiag_frm_php'] = true;
            $aViewData['aPhpConfigparams'] = $oDiagnostics->getPhpSelection();
            $aViewData['sPhpDecoder'] = $oDiagnostics->getPhpDecoder();
        }

        /**
         * Server info
         */
        if ($this->getParam('oxdiag_frm_server')) {
            $aViewData['isExecAllowed'] = $oDiagnostics->isExecAllowed();
            $aViewData['oxdiag_frm_server'] = true;
            $aViewData['aServerInfo'] = $oDiagnostics->getServerInfo();
        }

        /**
         * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
         */
        if ($this->getParam('oxdiag_frm_chkvers')) {
            $aViewData['oxdiag_frm_chkvers'] = true;
        }

        return $aViewData;
    }
    
    /**
     * Downloads result of system file check
     */
    public function downloadResultFile()
    {
        $this->_oOutput->downloadResultFile();
        exit(0);
    }

    /**
     * Checks system file versions
     *
     * @return string
     */
    public function getSupportContactForm()
    {
        $aLinks = [
            "de" => "https://community.o3-shop.com/",
            "en" => "https://community.o3-shop.com/"
        ];

        $oLang = Registry::getLang();
        $aLanguages = $oLang->getLanguageArray();
        $iLangId = $oLang->getTplLanguage();
        $sLangCode = $aLanguages[$iLangId]->abbr;

        if (!array_key_exists($sLangCode, $aLinks)) {
            $sLangCode = "de";
        }

        return $aLinks[$sLangCode];
    }

    /**
     * Request parameter getter
     *
     * @param string $sParam
     *
     * @return string
     */
    public function getParam($sParam)
    {
        return Registry::getRequest()->getRequestEscapedParameter($sParam);
    }

    /**
     * @return array
     */
    private function getInstalledModules(): array
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $shopConfiguration = $container->get(ShopConfigurationDaoBridgeInterface::class)->get();

        $modules = [];

        foreach ($shopConfiguration->getModuleConfigurations() as $moduleConfiguration) {
            $module = oxNew(Module::class);
            $module->load($moduleConfiguration->getId());
            $modules[$moduleConfiguration->getId()] = $module;
        }

        return $modules;
    }
}
