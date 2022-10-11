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

use Exception;

/**
 * Directory reader.
 * Performs reading of file list of one shop directory
 *
 */
class FileCollector
{
    /**
     * base directory
     *
     * @var string
     */
    protected $_sBaseDirectory;

    /**
     * array of collected files
     *
     * @var array
     */
    protected $_aFiles;

    /**
     * Setter for working directory
     *
     * @param string $sDir Directory
     */
    public function setBaseDirectory($sDir)
    {
        if (!empty($sDir)) {
            $this->_sBaseDirectory = $sDir;
        }
    }

    /**
     * get collection files
     *
     * @return mixed
     */
    public function getFiles()
    {
        return $this->_aFiles;
    }

    /**
     * Add one file to collection if it exists
     *
     * @param string $sFile file name to add to collection
     *
     * @throws Exception
     * @return null
     */
    public function addFile($sFile)
    {
        if (empty($sFile)) {
            throw new Exception('Parameter $sFile is empty!');
        }

        if (empty($this->_sBaseDirectory)) {
            throw new Exception('Base directory is not set, please use setter setBaseDirectory!');
        }

        if (is_file($this->_sBaseDirectory . $sFile)) {
            $this->_aFiles[] = $sFile;

            return true;
        }

        return false;
    }


    /**
     * browse all folders and sub-folders after files which have given extensions
     *
     * @param string  $sFolder     which is explored
     * @param array   $aExtensions list of extensions to scan - if empty all files are taken
     * @param boolean $blRecursive should directories be checked in recursive manner
     *
     * @throws exception
     * @return null
     */
    public function addDirectoryFiles($sFolder, $aExtensions = [], $blRecursive = false)
    {
        if (empty($sFolder)) {
            throw new Exception('Parameter $sFolder is empty!');
        }

        if (empty($this->_sBaseDirectory)) {
            throw new Exception('Base directory is not set, please use setter setBaseDirectory!');
        }

        $aCurrentList = [];

        if (!is_dir($this->_sBaseDirectory . $sFolder)) {
            return;
        }

        $handle = opendir($this->_sBaseDirectory . $sFolder);

        while ($sFile = readdir($handle)) {
            if ($sFile != "." && $sFile != "..") {
                if (is_dir($this->_sBaseDirectory . $sFolder . $sFile)) {
                    if ($blRecursive) {
                        $aResultList = $this->addDirectoryFiles($sFolder . $sFile . '/', $aExtensions, $blRecursive);

                        if (is_array($aResultList)) {
                            $aCurrentList = array_merge($aCurrentList, $aResultList);
                        }
                    }
                } else {
                    $sExt = substr(strrchr($sFile, '.'), 1);

                    if (
                        (!empty($aExtensions) && is_array($aExtensions) && in_array($sExt, $aExtensions)) ||
                        (empty($aExtensions))
                    ) {
                        $this->addFile($sFolder . $sFile);
                    }
                }
            }
        }
        closedir($handle);
    }
}
