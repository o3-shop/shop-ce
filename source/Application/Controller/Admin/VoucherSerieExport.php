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

use OxidEsales\Eshop\Application\Controller\Admin\VoucherSerieMain;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;

/**
 * General export class.
 */
class VoucherSerieExport extends VoucherSerieMain
{
    /**
     * Export class name
     *
     * @var string
     */
    public $sClassDo = "voucherserie_export";

    /**
     * Export file extension
     *
     * @var string
     */
    public $sExportFileType = "csv";

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = "voucherserie_export.tpl";

    /**
     * Number of records to export per tick
     *
     * @var int
     */
    public $iExportPerTick = 1000;

    /**
     * Calls parent constructor and initializes $this->_sFilePath parameter
     */
    public function __construct()
    {
        parent::__construct();

        // export file name
        $this->sExportFileName = $this->_getExportFileName();

        // set generic frame template
        $this->_sFilePath = $this->_getExportFilePath();
    }

    /**
     * Returns export file download url
     *
     * @return string
     */
    public function getDownloadUrl()
    {
        $myConfig = Registry::getConfig();

        // override cause of admin dir
        $sUrl = $myConfig->getConfigParam('sShopURL') . $myConfig->getConfigParam('sAdminDir');
        if ($myConfig->getConfigParam('sAdminSSLURL')) {
            $sUrl = $myConfig->getConfigParam('sAdminSSLURL');
        }

        $sUrl = Registry::getUtilsUrl()->processUrl($sUrl . '/index.php');

        return $sUrl . '&amp;cl=' . $this->sClassDo . '&amp;fnc=download';
    }

    /**
     * Return export file name
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getExportFileName" in next major
     */
    protected function _getExportFileName() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSessionFileName = Registry::getSession()->getVariable("sExportFileName");
        if (!$sSessionFileName) {
            $sSessionFileName = md5($this->getSession()->getId() . Registry::getUtilsObject()->generateUId());
            Registry::getSession()->setVariable("sExportFileName", $sSessionFileName);
        }

        return $sSessionFileName;
    }

    /**
     * Return export file path
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getExportFilePath" in next major
     */
    protected function _getExportFilePath() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::getConfig()->getConfigParam('sShopDir') . "/export/" . $this->_getExportFileName();
    }

    /**
     * Performs Voucherserie export to export file.
     */
    public function download()
    {
        $oUtils = Registry::getUtils();
        $oUtils->setHeader("Pragma: public");
        $oUtils->setHeader("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        $oUtils->setHeader("Expires: 0");
        $oUtils->setHeader("Content-Disposition: attachment; filename=vouchers.csv");
        $oUtils->setHeader("Content-Type: application/csv");
        $sFile = $this->_getExportFilePath();
        if (file_exists($sFile) && is_readable($sFile)) {
            readfile($sFile);
        }
        $oUtils->showMessageAndExit("");
    }

    /**
     * Does Export
     */
    public function run()
    {
        $blContinue = true;

        $this->fpFile = @fopen($this->_sFilePath, "a");
        if (!isset($this->fpFile) || !$this->fpFile) {
            // we do have an error !
            $this->stop(ERR_FILEIO);
        } else {
            // file is open
            $iStart = Registry::getRequest()->getRequestEscapedParameter('iStart');
            if (!$iStart) {
                ftruncate($this->fpFile, 0);
            }

            if (($iExportedItems = $this->exportVouchers($iStart)) === false) {
                // end reached
                $this->stop(ERR_SUCCESS);
                $blContinue = false;
            }

            if ($blContinue) {
                // make ticker continue
                $this->_aViewData['refresh'] = 0;
                $this->_aViewData['iStart'] = $iStart + $iExportedItems;
                $this->_aViewData['iExpItems'] = $iStart + $iExportedItems;
            }
            fclose($this->fpFile);
        }
    }

    /**
     * Writes voucher number information to export file and returns number of written records info
     *
     * @param int $iStart start exporting from
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function exportVouchers($iStart)
    {
        $iExported = false;

        if ($oSerie = $this->_getVoucherSerie()) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            $sSelect = "select oxvouchernr from oxvouchers where oxvoucherserieid = :oxvoucherserieid";
            $rs = $oDb->selectLimit($sSelect, $this->iExportPerTick, $iStart, [
                ':oxvoucherserieid' => $oSerie->getId()
            ]);

            if (!$rs->EOF) {
                $iExported = 0;

                // writing header text
                if ($iStart == 0) {
                    $this->write(
                        Registry::getLang()->translateString("VOUCHERSERIE_MAIN_VOUCHERSTATISTICS", Registry::getLang()->getTplLanguage(), true));
                }
            }

            // writing vouchers..
            while (!$rs->EOF) {
                $this->write(current($rs->fields));
                $iExported++;
                $rs->fetchRow();
            }
        }

        return $iExported;
    }

    /**
     * writes one line into open export file
     *
     * @param string $sLine exported line
     */
    public function write($sLine)
    {
        if ($sLine) {
            fwrite($this->fpFile, $sLine . "\n");
        }
    }
}
