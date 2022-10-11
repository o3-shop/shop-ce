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

/**
 * Diagnostic tool result outputer
 * Performs OutputKey check of shop files and generates report file.
 *
 */
class DiagnosticsOutput
{
    /**
     * result key
     *
     * @var string
     */
    protected $_sOutputKey = "diagnostic_tool_result";


    /**
     * Result file path
     *
     * @var string
     */
    protected $_sOutputFileName = "diagnostic_tool_result.html";

    /**
     * Utils object
     *
     * @var mixed
     */
    protected $_oUtils = null;

    /**
     * Object constructor
     */
    public function __construct()
    {
        $this->_oUtils = \OxidEsales\Eshop\Core\Registry::getUtils();
    }

    /**
     * OutputKey setter
     *
     * @param string $sOutputKey Output key.
     */
    public function setOutputKey($sOutputKey)
    {
        if (!empty($sOutputKey)) {
            $this->_sOutputKey = $sOutputKey;
        }
    }

    /**
     * OutputKey getter
     *
     * @return string
     */
    public function getOutputKey()
    {
        return $this->_sOutputKey;
    }

    /**
     * OutputFileName setter
     *
     * @param string $sOutputFileName Output file name.
     */
    public function setOutputFileName($sOutputFileName)
    {
        if (!empty($sOutputFileName)) {
            $this->_sOutputFileName = $sOutputFileName;
        }
    }

    /**
     * OutputKey getter
     *
     * @return string
     */
    public function getOutputFileName()
    {
        return $this->_sOutputFileName;
    }

    /**
     * Stores result file in file cache
     *
     * @param string $sResult Result.
     */
    public function storeResult($sResult)
    {
        $this->_oUtils->toFileCache($this->_sOutputKey, $sResult);
    }

    /**
     * Reads exported result file contents
     *
     * @param string $sOutputKey Output key.
     *
     * @return string
     */
    public function readResultFile($sOutputKey = null)
    {
        $sCurrentKey = (empty($sOutputKey)) ? $this->_sOutputKey : $sOutputKey;

        return $this->_oUtils->fromFileCache($sCurrentKey);
    }

    /**
     * Sends generated file for download
     *
     * @param string $sOutputKey Output key.
     */
    public function downloadResultFile($sOutputKey = null)
    {
        $sCurrentKey = (empty($sOutputKey)) ? $this->_sOutputKey : $sOutputKey;

        $this->_oUtils = \OxidEsales\Eshop\Core\Registry::getUtils();
        $iFileSize = filesize($this->_oUtils->getCacheFilePath($sCurrentKey));

        $this->_oUtils->setHeader("Pragma: public");
        $this->_oUtils->setHeader("Expires: 0");
        $this->_oUtils->setHeader("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
        $this->_oUtils->setHeader('Content-Disposition: attachment;filename=' . $this->_sOutputFileName);
        $this->_oUtils->setHeader("Content-Type:text/html;charset=utf-8");
        if ($iFileSize) {
            $this->_oUtils->setHeader("Content-Length: " . $iFileSize);
        }
        echo $this->_oUtils->fromFileCache($sCurrentKey);
    }
}
