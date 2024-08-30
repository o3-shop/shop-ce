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

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article file link manager.
 *
 */
class OrderFile extends BaseModel
{
    /**
     * Object core table name
     *
     * @var string
     */
    protected $_sCoreTable = 'oxorderfiles';

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxorderfile';


    /**
     * Initialises the instance
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxorderfiles');
    }

    /**
     * reset order files downloadcount and / or expration times
     */
    public function reset()
    {
        $oArticleFile = oxNew(File::class);
        $oArticleFile->load($this->oxorderfiles__oxfileid->value);
        if (file_exists($oArticleFile->getStoreLocation())) {
            $this->oxorderfiles__oxdownloadcount = new Field(0);
            $this->oxorderfiles__oxfirstdownload = new Field('0000-00-00 00:00:00');
            $this->oxorderfiles__oxlastdownload = new Field('0000-00-00 00:00:00');
            $iExpirationTime = $this->oxorderfiles__oxlinkexpirationtime->value * 3600;
            $sNow = Registry::getUtilsDate()->getTime();
            $sDate = date('Y-m-d H:i:s', $sNow + $iExpirationTime);
            $this->oxorderfiles__oxvaliduntil = new Field($sDate);
            $this->oxorderfiles__oxresetcount = new Field($this->oxorderfiles__oxresetcount->value + 1);
        }
    }

    /**
     * set order id
     *
     * @param string $sOrderId - order id
     */
    public function setOrderId($sOrderId)
    {
        $this->oxorderfiles__oxorderid = new Field($sOrderId);
    }

    /**
     * set order article id
     *
     * @param string $sOrderArticleId - order article id
     */
    public function setOrderArticleId($sOrderArticleId)
    {
        $this->oxorderfiles__oxorderarticleid = new Field($sOrderArticleId);
    }

    /**
     * set shop id
     *
     * @param string $sShopId - shop id
     */
    public function setShopId($sShopId)
    {
        $this->oxorderfiles__oxshopid = new Field($sShopId);
    }

    /**
     * Set file and download options
     *
     * @param string $sFileName               file name
     * @param string $sFileId                 file id
     * @param int    $iMaxDownloadCounts      max download count
     * @param int    $iExpirationTime         main download time after order in times
     * @param int    $iExpirationDownloadTime download time after first download in hours
     */
    public function setFile($sFileName, $sFileId, $iMaxDownloadCounts, $iExpirationTime, $iExpirationDownloadTime)
    {
        $sNow = Registry::getUtilsDate()->getTime();
        $sDate = date('Y-m-d G:i', $sNow + $iExpirationTime * 3600);

        $this->oxorderfiles__oxfileid = new Field($sFileId);
        $this->oxorderfiles__oxfilename = new Field($sFileName);
        $this->oxorderfiles__oxmaxdownloadcount = new Field($iMaxDownloadCounts);
        $this->oxorderfiles__oxlinkexpirationtime = new Field($iExpirationTime);
        $this->oxorderfiles__oxdownloadexpirationtime = new Field($iExpirationDownloadTime);
        $this->oxorderfiles__oxvaliduntil = new Field($sDate);
    }

    /**
     * Returns downloadable file size in bytes.
     *
     * @return int
     */
    public function getFileSize()
    {
        $oFile = oxNew(File::class);
        $oFile->load($this->oxorderfiles__oxfileid->value);

        return $oFile->getSize();
    }

    /**
     * returns long name
     *
     * @param string $sFieldName - field name
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFieldLongName" in next major
     */
    protected function _getFieldLongName($sFieldName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aFieldNames = [
            'oxorderfiles__oxarticletitle',
            'oxorderfiles__oxarticleartnum',
            'oxorderfiles__oxordernr',
            'oxorderfiles__oxorderdate',
            'oxorderfiles__oxispaid',
            'oxorderfiles__oxpurchasedonly'
        ];

        if (in_array($sFieldName, $aFieldNames)) {
            return $sFieldName;
        }

        return parent::_getFieldLongName($sFieldName);
    }

    /**
     * Checks if order file is still available to download
     *
     * @return bool
     */
    public function isValid()
    {
        if (!$this->oxorderfiles__oxmaxdownloadcount->value || ($this->oxorderfiles__oxdownloadcount->value < $this->oxorderfiles__oxmaxdownloadcount->value)) {
            if (!$this->oxorderfiles__oxlinkexpirationtime->value && !$this->oxorderfiles__oxdownloadxpirationtime->value) {
                return true;
            } else {
                $sNow = Registry::getUtilsDate()->getTime();
                $iTimestamp = strtotime($this->oxorderfiles__oxvaliduntil->value);
                if (!$iTimestamp || ($iTimestamp > $sNow)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * returns state payed or not the order
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->oxorderfiles__oxispaid->value;
    }

    /**
     * returns date ant time
     *
     * @return bool
     */
    public function getValidUntil()
    {
        return substr($this->oxorderfiles__oxvaliduntil->value, 0, 16);
    }

    /**
     * returns date ant time
     *
     * @return bool
     */
    public function getLeftDownloadCount()
    {
        $iLeft = $this->oxorderfiles__oxmaxdownloadcount->value - $this->oxorderfiles__oxdownloadcount->value;
        if ($iLeft < 0) {
            $iLeft = 0;
        }

        return $iLeft;
    }

    /**
     * Checks if download link is valid, changes count, if first download changes valid until
     *
     */
    public function processOrderFile()
    {
        if ($this->isValid()) {
            //first download
            if (!$this->oxorderfiles__oxdownloadcount->value) {
                $this->oxorderfiles__oxdownloadcount = new Field(1);

                $iExpirationTime = $this->oxorderfiles__oxdownloadexpirationtime->value * 3600;
                $iTime = Registry::getUtilsDate()->getTime();
                $this->oxorderfiles__oxvaliduntil = new Field(date('Y-m-d H:i:s', $iTime + $iExpirationTime));

                $this->oxorderfiles__oxfirstdownload = new Field(date('Y-m-d H:i:s', $iTime));
                $this->oxorderfiles__oxlastdownload = new Field(date('Y-m-d H:i:s', $iTime));
            } else {
                $this->oxorderfiles__oxdownloadcount = new Field($this->oxorderfiles__oxdownloadcount->value + 1);

                $iTime = Registry::getUtilsDate()->getTime();
                $this->oxorderfiles__oxlastdownload = new Field(date('Y-m-d H:i:s', $iTime));
            }
            $this->save();

            return $this->oxorderfiles__oxfileid->value;
        }

        return false;
    }

    /**
     * Gets field id.
     *
     * @return mixed
     */
    public function getFileId()
    {
        return $this->oxorderfiles__oxfileid->value;
    }
}
