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
 * Diagnostic tool model
 * Stores configuration and public diagnostic methods for shop diagnostics
 *
 */
class Diagnostics
{
    /**
     * Edition of THIS O3-Shop
     *
     * @var string
     */
    protected $_sEdition = "";

    /**
     * Version of THIS O3-Shop
     *
     * @var string
     */
    protected $_sVersion = "";

    /**
     * Revision of THIS O3-Shop
     *
     * @deprecated since v6.0.0 (2017-12-04); This functionality will be removed completely
     *
     * @var string
     */
    protected $_sRevision = "";

    /**
     * Revision of THIS O3-Shop
     *
     * @var string
     */
    protected $_sShopLink = "";

    /**
     * Array of all files and folders in shop root folder which are to be checked
     *
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     * @var array
     */
    protected $_aFileCheckerPathList = [
        'bootstrap.php',
        'index.php',
        'oxid.php',
        'oxseo.php',
        'admin/',
        'Application/',
        'bin/',
        'Core/',
        'modules/',
    ];

    /**
     * Array of file extensions which are to be checked
     *
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     * @var array
     */
    protected $_aFileCheckerExtensionList = ['php', 'tpl'];

    /**
     * Setter for list of files and folders to check
     *
     * @param array $aPathList Path list.
     *
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     */
    public function setFileCheckerPathList($aPathList)
    {
        $this->_aFileCheckerPathList = $aPathList;
    }

    /**
     * getter for list of files and folders to check
     *
     * @return array
     *
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     */
    public function getFileCheckerPathList()
    {
        return $this->_aFileCheckerPathList;
    }

    /**
     * Setter for extensions of files to check
     *
     * @param array $aExtList List of extensions.
     *
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     */
    public function setFileCheckerExtensionList($aExtList)
    {
        $this->_aFileCheckerExtensionList = $aExtList;
    }

    /**
     * getter for extensions of files to check
     *
     * @deprecated since v6.3 (2018-06-04); This functionality will be removed completely.
     *
     * @return array
     */
    public function getFileCheckerExtensionList()
    {
        return $this->_aFileCheckerExtensionList;
    }


    /**
     * Version setter
     *
     * @param string $sVersion Version.
     */
    public function setVersion($sVersion)
    {
        if (!empty($sVersion)) {
            $this->_sVersion = $sVersion;
        }
    }

    /**
     * Version getter
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_sVersion;
    }

    /**
     * Edition setter
     *
     * @param string $sEdition Edition
     */
    public function setEdition($sEdition)
    {
        if (!empty($sEdition)) {
            $this->_sEdition = $sEdition;
        }
    }

    /**
     * Edition getter
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->_sEdition;
    }

    /**
     * Revision setter
     *
     * @deprecated since v6.0.0 (2017-12-04); This functionality will be removed completely
     *
     * @param string $sRevision revision.
     */
    public function setRevision($sRevision)
    {
        if (!empty($sRevision)) {
            $this->_sRevision = $sRevision;
        }
    }

    /**
     * Revision getter
     *
     * @deprecated since v6.0.0 (2017-12-04); This functionality will be removed completely
     *
     * @return bool|string
     */
    public function getRevision()
    {
        return $this->_sRevision;
    }


    /**
     * ShopLink setter
     *
     * @param string $sShopLink Shop link.
     */
    public function setShopLink($sShopLink)
    {
        if (!empty($sShopLink)) {
            $this->_sShopLink = $sShopLink;
        }
    }

    /**
     * ShopLink getter
     *
     * @return string
     */
    public function getShopLink()
    {
        return $this->_sShopLink;
    }

    /**
     * Collects information on the shop, like amount of categories, articles, users
     *
     * @return array
     */
    public function getShopDetails()
    {
        $aShopDetails = [
            'Date'                => date(\OxidEsales\Eshop\Core\Registry::getLang()->translateString('fullDateFormat'), time()),
            'URL'                 => $this->getShopLink(),
            'Edition'             => $this->getEdition(),
            'Version'             => $this->getVersion(),
            'Revision'            => $this->getRevision(),
            'Subshops (Total)'    => $this->_countRows('oxshops', true),
            'Subshops (Active)'   => $this->_countRows('oxshops', false),
            'Categories (Total)'  => $this->_countRows('oxcategories', true),
            'Categories (Active)' => $this->_countRows('oxcategories', false),
            'Articles (Total)'    => $this->_countRows('oxarticles', true),
            'Articles (Active)'   => $this->_countRows('oxarticles', false),
            'Users (Total)'       => $this->_countRows('oxuser', true),
        ];

        return $aShopDetails;
    }

    /**
     * counts result Rows
     *
     * @param string  $sTable table
     * @param boolean $blMode mode
     *
     * @return integer
     * @deprecated underscore prefix violates PSR12, will be renamed to "countRows" in next major
     */
    protected function _countRows($sTable, $blMode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sRequest = 'SELECT COUNT(*) FROM ' . $sTable;

        if ($blMode == false) {
            $sRequest .= ' WHERE oxactive = 1';
        }

        $aRes = $oDb->select($sRequest)->fields[0];

        return $aRes;
    }


    /**
     * Picks some pre-selected PHP configuration settings and returns them.
     *
     * @return array
     */
    public function getPhpSelection()
    {
        $aPhpIniParams = [
            'allow_url_fopen',
            'display_errors',
            'file_uploads',
            'max_execution_time',
            'memory_limit',
            'post_max_size',
            'register_globals',
            'upload_max_filesize',
        ];

        $aPhpIniConf = [];

        foreach ($aPhpIniParams as $sParam) {
            $sValue = ini_get($sParam);
            $aPhpIniConf[$sParam] = $sValue;
        }

        return $aPhpIniConf;
    }


    /**
     * Returns the installed PHP devoder (like Zend Optimizer, Guard Loader)
     *
     * @return string
     */
    public function getPhpDecoder()
    {
        $sReturn = 'Zend ';

        if (function_exists('zend_optimizer_version')) {
            $sReturn .= 'Optimizer';
        }

        if (function_exists('zend_loader_enabled')) {
            $sReturn .= 'Guard Loader';
        }

        return $sReturn;
    }


    /**
     * General server information
     * We will use the exec command here several times. In order tro prevent stop on failure, use $this->isExecAllowed().
     *
     * @return array
     */
    public function getServerInfo()
    {
        // init empty variables (can be filled if exec is allowed)
        $iMemTotal = $iMemFree = $sCpuModelName = $sCpuModel = $sCpuFreq = $iCpuCores = null;

        // fill, if exec is allowed
        if ($this->isExecAllowed()) {
            $iCpuAmnt = $this->_getCpuAmount();
            $iCpuMhz = $this->_getCpuMhz();
            $iBogo = $this->_getBogoMips();
            $iMemTotal = $this->_getMemoryTotal();
            $iMemFree = $this->_getMemoryFree();
            $sCpuModelName = $this->_getCpuModel();
            $sCpuModel = $iCpuAmnt . 'x ' . $sCpuModelName;
            $sCpuFreq = $iCpuMhz . ' MHz';

            // prevent "division by zero" error
            if ($iBogo && $iCpuMhz) {
                $iCpuCores = $iBogo / $iCpuMhz;
            }
        }

        $aServerInfo = [
            'Server OS'     => @php_uname('s'),
            'VM'            => $this->_getVirtualizationSystem(),
            'PHP'           => $this->_getPhpVersion(),
            'MySQL'         => $this->_getMySqlServerInfo(),
            'Apache'        => $this->_getApacheVersion(),
            'Disk total'    => $this->_getDiskTotalSpace(),
            'Disk free'     => $this->_getDiskFreeSpace(),
            'Memory total'  => $iMemTotal,
            'Memory free'   => $iMemFree,
            'CPU Model'     => $sCpuModel,
            'CPU frequency' => $sCpuFreq,
            'CPU cores'     => round($iCpuCores, 0),
        ];

        return $aServerInfo;
    }

    /**
     * Returns Apache version
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getApacheVersion" in next major
     */
    protected function _getApacheVersion() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (function_exists('apache_get_version')) {
            $sReturn = apache_get_version();
        } else {
            $sReturn = $_SERVER['SERVER_SOFTWARE'];
        }

        return $sReturn;
    }

    /**
     * Tries to find out which VM is used
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVirtualizationSystem" in next major
     */
    protected function _getVirtualizationSystem() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSystemType = '';

        if ($this->isExecAllowed()) {
            //VMWare
            @$sDeviceList = $this->_getDeviceList('vmware');
            if ($sDeviceList) {
                $sSystemType = 'VMWare';
                unset($sDeviceList);
            }

            //VirtualBox
            @$sDeviceList = $this->_getDeviceList('VirtualBox');
            if ($sDeviceList) {
                $sSystemType = 'VirtualBox';
                unset($sDeviceList);
            }
        }

        return $sSystemType;
    }

    /**
     * Determines, whether the exec() command is allowed or not.
     *
     * @return boolean
     */
    public function isExecAllowed()
    {
        return function_exists('exec');
    }

    /**
     * Finds the list of system devices for given system type
     *
     * @param string $sSystemType System type.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDeviceList" in next major
     */
    protected function _getDeviceList($sSystemType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return exec('lspci | grep -i ' . $sSystemType);
    }

    /**
     * Returns amount of CPU units.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCpuAmount" in next major
     */
    protected function _getCpuAmount() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // cat /proc/cpuinfo | grep "processor" | sort -u | cut -d: -f2');
        return exec('cat /proc/cpuinfo | grep "physical id" | sort | uniq | wc -l');
    }

    /**
     * Returns CPU speed in Mhz
     *
     * @return float
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCpuMhz" in next major
     */
    protected function _getCpuMhz() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return round(exec('cat /proc/cpuinfo | grep "MHz" | sort -u | cut -d: -f2'), 0);
    }

    /**
     * Returns BogoMIPS evaluation of processor
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getBogoMips" in next major
     */
    protected function _getBogoMips() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return exec('cat /proc/cpuinfo | grep "bogomips" | sort -u | cut -d: -f2');
    }

    /**
     * Returns total amount of memory
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMemoryTotal" in next major
     */
    protected function _getMemoryTotal() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return exec('cat /proc/meminfo | grep "MemTotal" | sort -u | cut -d: -f2');
    }

    /**
     * Returns amount of free memory
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMemoryFree" in next major
     */
    protected function _getMemoryFree() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return exec('cat /proc/meminfo | grep "MemFree" | sort -u | cut -d: -f2');
    }

    /**
     * Returns CPU model information
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCpuModel" in next major
     */
    protected function _getCpuModel() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return exec('cat /proc/cpuinfo | grep "model name" | sort -u | cut -d: -f2');
    }

    /**
     * Returns total disk space
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDiskTotalSpace" in next major
     */
    protected function _getDiskTotalSpace() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return round(disk_total_space('/') / 1024 / 1024, 0) . ' GiB';
    }

    /**
     * Returns free disk space
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDiskFreeSpace" in next major
     */
    protected function _getDiskFreeSpace() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return round(disk_free_space('/') / 1024 / 1024, 0) . ' GiB';
    }

    /**
     * Returns PHP version
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getPhpVersion" in next major
     */
    protected function _getPhpVersion() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return phpversion();
    }

    /**
     * Returns MySQL server Information
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMySqlServerInfo" in next major
     */
    protected function _getMySqlServerInfo() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC)->getRow("SHOW VARIABLES LIKE 'version'");

        return $aResult['Value'];
    }
}
