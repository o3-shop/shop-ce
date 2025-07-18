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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article files manager.
 *
 */
class File extends BaseModel
{
    /**
     * No active user exception code.
     */
    const NO_USER = 2;

    /**
     * Object core table name
     *
     * @var string
     */
    protected $_sCoreTable = 'oxfiles';

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxfile';

    /**
     * Stores relative oxFile path from configs 'sDownloadsDir'
     *
     * @var string
     */
    protected $_sRelativeFilePath = null;

    /**
     * Paid order indicator
     *
     * @var bool
     */
    protected $_blIsPaid = null;

    /**
     * Full URL where article could be downloaded from.
     * Is set to false in case download is not available for current user
     *
     * @var string|bool
     */
    protected $_sDownloadLink = null;

    /**
     * Has valid downloads indicator
     *
     * @var bool
     */
    protected $_blHasValidDownloads = null;

    /**
     * Default manual upload dir located within general file dir
     *
     * @var string
     */
    protected $_sManualUploadDir = "uploads";

    /**
     * Initialises the instance
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Sets oxfile__oxstorehash with file hash.
     * Moves file to desired location and change its access rights.
     *
     * @param int $sFileIndex File index
     *
     * @throws StandardException Throws exception if file wasn't moved or if rights wasn't changed.
     */
    public function processFile($sFileIndex)
    {
        $aFileInfo = Registry::getConfig()->getUploadedFile($sFileIndex);

        $this->_checkArticleFile($aFileInfo);

        $sFileHash = $this->_getFileHash($aFileInfo['tmp_name']);
        $this->oxfiles__oxstorehash = new Field($sFileHash, Field::T_RAW);
        $sUploadTo = $this->getStoreLocation();

        if (!$this->_uploadFile($aFileInfo['tmp_name'], $sUploadTo)) {
            throw new StandardException('EXCEPTION_COULDNOTWRITETOFILE');
        }
    }

    /**
     * Checks if given file is valid upload file
     *
     * @param array $aFileInfo File info array
     *
     * @throws StandardException
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkArticleFile" in next major
     */
    protected function _checkArticleFile($aFileInfo) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        //checking params
        if (!isset($aFileInfo['name']) || !isset($aFileInfo['tmp_name'])) {
            throw new StandardException('EXCEPTION_NOFILE');
        }

        // error uploading file ?
        if (isset($aFileInfo['error']) && $aFileInfo['error']) {
            throw new StandardException('EXCEPTION_FILEUPLOADERROR_' . ((int) $aFileInfo['error']));
        }
    }

    /**
     * Return full path of root dir where download files are stored
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getBaseDownloadDirPath" in next major
     */
    protected function _getBaseDownloadDirPath() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sConfigValue = Registry::getConfig()->getConfigParam('sDownloadsDir');

        //Unix full path is set
        if ($sConfigValue && $sConfigValue[0] == DIRECTORY_SEPARATOR) {
            return $sConfigValue;
        }

        //relative path is set
        if ($sConfigValue) {
            $sPath = getShopBasePath() . DIRECTORY_SEPARATOR . $sConfigValue;

            return $sPath;
        }

        //no path is set
        $sPath = getShopBasePath() . "/out/downloads/";

        return $sPath;
    }

    /**
     * Returns full filesystem path where files are stored.
     * Make sure that object oxfiles__oxstorehash or oxfiles__oxfilename
     * attribute is set before calling this method
     *
     * @return string
     */
    public function getStoreLocation()
    {
        $sPath = $this->_getBaseDownloadDirPath();
        $sPath .= DIRECTORY_SEPARATOR . $this->_getFileLocation();

        return $sPath;
    }

    /**
     * Return true if file is under download folder.
     * Return false if file is above download folder or if file does not exist.
     *
     * @return bool
     */
    public function isUnderDownloadFolder()
    {
        $storageLocation = realpath($this->getStoreLocation());

        if ($storageLocation === false) {
            return false;
        }

        $downloadFolder = realpath($this->_getBaseDownloadDirPath());

        return strpos($storageLocation, $downloadFolder) !== false;
    }

    /**
     * Returns relative file path from oxConfig 'sDownloadsDir' variable.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFileLocation" in next major
     */
    protected function _getFileLocation() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_sRelativeFilePath = '';
        $sFileHash = $this->oxfiles__oxstorehash->value;
        $sFileName = $this->oxfiles__oxfilename->value;

        //security check for demo shops
        if (Registry::getConfig()->isDemoShop()) {
            $sFileName = basename($sFileName);
        }

        if ($this->isUploaded()) {
            $this->_sRelativeFilePath = $this->_getHashedFileDir($sFileHash);
            $this->_sRelativeFilePath .= DIRECTORY_SEPARATOR . $sFileHash;
        } else {
            $this->_sRelativeFilePath = DIRECTORY_SEPARATOR . $this->_sManualUploadDir . DIRECTORY_SEPARATOR . $sFileName;
        }

        return $this->_sRelativeFilePath;
    }

    /**
     * Returns relative sub dir of Config 'sDownloadsDir' of
     * required file from supplied $sFileHash parameter.
     * Creates dir in case it does not exist.
     *
     * @param string $sFileHash File hash value
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getHashedFileDir" in next major
     */
    protected function _getHashedFileDir($sFileHash) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sDir = substr($sFileHash, 0, 2);
        $sAbsDir = $this->_getBaseDownloadDirPath() . DIRECTORY_SEPARATOR . $sDir;

        if (!is_dir($sAbsDir)) {
            mkdir($sAbsDir, 0755);
        }

        return $sDir;
    }

    /**
     * Calculates file hash.
     * Currently, MD5 is used.
     *
     * @param string $sFileName File name values
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFileHash" in next major
     */
    protected function _getFileHash($sFileName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return md5_file($sFileName);
    }

    /**
     * Moves file from source to target and changes file mode.
     * Returns true on success.
     *
     * @param string $sSource Source filename
     * @param string $sTarget Target filename
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "uploadFile" in next major
     */
    protected function _uploadFile($sSource, $sTarget) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blDone = move_uploaded_file($sSource, $sTarget);

        if ($blDone) {
            $blDone = @chmod($sTarget, 0644);
        }

        return $blDone;
    }

    /**
     * Checks whether the file has been uploaded over admin area.
     * Returns true in case file is uploaded (and hashed) over admin area.
     * Returns false in case file is placed manually (ftp) to "out/downloads/uploads" dir.
     * It's similar so don't get confused here.
     *
     * @return bool
     */
    public function isUploaded()
    {
        $blHashed = false;
        if ($this->oxfiles__oxstorehash->value) {
            $blHashed = true;
        }

        return $blHashed;
    }

    /**
     * Deletes oxFile record from DB, removes orphan files.
     *
     * @param null $sOxId default null
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function delete($sOxId = null)
    {
        $sOxId = $sOxId ? $sOxId : $this->getId();

        $this->load($sOxId);
        // if record cannot be delete, abort deletion
        if ($blDeleted = parent::delete($sOxId)) {
            $this->_deleteFile();
        }

        return $blDeleted;
    }

    /**
     * Checks if file is not used for  other objects.
     * If not used, unlink the file.
     *
     * @return bool|void
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteFile" in next major
     */
    protected function _deleteFile() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$this->isUploaded()) {
            return false;
        }
        $oDb = DatabaseProvider::getDb();
        $iCount = $oDb->getOne(
            'SELECT COUNT(*) FROM `oxfiles` WHERE `OXSTOREHASH` = :oxstorehash',
            [
                ':oxstorehash' => $this->oxfiles__oxstorehash->value
            ]
        );
        if (!$iCount) {
            $sPath = $this->getStoreLocation();
            unlink($sPath);
        }
    }

    /**
     * returns oxfile__oxfilename for URL usage
     * converts spec symbols to %xx combination
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilenameForUrl" in next major
     */
    protected function _getFilenameForUrl() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return rawurlencode($this->oxfiles__oxfilename->value);
    }

    /**
     * Supplies the downloadable file for client and exits
     */
    public function download()
    {
        $oUtils = Registry::getUtils();
        $sFileName = $this->_getFilenameForUrl();
        $sFileLocations = $this->getStoreLocation();

        if (!$this->exist() || !$this->isUnderDownloadFolder()) {
            throw new StandardException('EXCEPTION_NOFILE');
        }

        $oUtils->setHeader("Pragma: public");
        $oUtils->setHeader("Expires: 0");
        $oUtils->setHeader("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
        $oUtils->setHeader('Content-Disposition: attachment;filename=' . $sFileName);
        $oUtils->setHeader("Content-Type: application/octet-stream");
        if ($iFileSize = $this->getSize()) {
            $oUtils->setHeader("Content-Length: " . $iFileSize);
        }
        readfile($sFileLocations);
        $oUtils->showMessageAndExit(null);
    }

    /**
     * Check if file exist
     *
     * @return bool
     */
    public function exist()
    {
        return file_exists($this->getStoreLocation());
    }

    /**
     * Checks if this file has valid ordered downloads
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function hasValidDownloads()
    {
        if ($this->_blHasValidDownloads == null) {
            $this->_blHasValidDownloads = false;

            $oDb = DatabaseProvider::getDb();

            $sSql = "SELECT
                        `oxorderfiles`.`oxid`
                     FROM `oxorderfiles`
                        LEFT JOIN `oxorderarticles` ON `oxorderarticles`.`oxid` = `oxorderfiles`.`oxorderarticleid`
                        LEFT JOIN `oxorder` ON `oxorder`.`oxid` = `oxorderfiles`.`oxorderid`
                     WHERE `oxorderfiles`.`oxfileid` = :oxfileid
                        AND ( ! `oxorderfiles`.`oxmaxdownloadcount` OR `oxorderfiles`.`oxmaxdownloadcount` > `oxorderfiles`.`oxdownloadcount`)
                        AND ( `oxorderfiles`.`oxvaliduntil` = '0000-00-00 00:00:00' OR `oxorderfiles`.`oxvaliduntil` > :oxvaliduntil )
                        AND `oxorder`.`oxstorno` = 0
                        AND `oxorderarticles`.`oxstorno` = 0";
            $params = [
                ':oxfileid' => $this->getId(),
                ':oxvaliduntil' => date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime())
            ];

            if ($oDb->getOne($sSql, $params)) {
                $this->_blHasValidDownloads = true;
            }
        }

        return $this->_blHasValidDownloads;
    }

    /**
     * Returns max download count of file
     *
     * @return int
     */
    public function getMaxDownloadsCount()
    {
        $iMaxCount = $this->oxfiles__oxmaxdownloads->value;
        //if value is -1, takes global options
        if ($iMaxCount < 0) {
            $iMaxCount = Registry::getConfig()->getConfigParam("iMaxDownloadsCount");
        }

        return $iMaxCount;
    }

    /**
     * Returns max download count of file, if user is not registered
     *
     * @return int
     */
    public function getMaxUnregisteredDownloadsCount()
    {
        $iMaxCount = $this->oxfiles__oxmaxunregdownloads->value;
        //if value is -1, takes global options
        if ($iMaxCount < 0) {
            $iMaxCount = Registry::getConfig()->getConfigParam("iMaxDownloadsCountUnregistered");
        }

        return $iMaxCount;
    }

    /**
     * Returns ordered file link expiration time in hours
     *
     * @return int
     */
    public function getLinkExpirationTime()
    {
        $iExpTime = $this->oxfiles__oxlinkexptime->value;
        //if value is -1, takes global options
        if ($iExpTime < 0) {
            $iExpTime = Registry::getConfig()->getConfigParam("iLinkExpirationTime");
        }

        return $iExpTime;
    }

    /**
     * Returns download link expiration time in hours, after the first download
     *
     * @return int
     */
    public function getDownloadExpirationTime()
    {
        $iExpTime = $this->oxfiles__oxdownloadexptime->value;
        //if value is -1, takes global options
        if ($iExpTime < 0) {
            $iExpTime = Registry::getConfig()->getConfigParam("iDownloadExpirationTime");
        }

        return $iExpTime;
    }

    /**
     * Returns file size in bytes
     *
     * @return int
     */
    public function getSize()
    {
        $iSize = 0;
        if ($this->exist()) {
            $iSize = filesize($this->getStoreLocation());
        }

        return $iSize;
    }
}
