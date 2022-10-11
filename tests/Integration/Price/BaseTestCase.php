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
namespace OxidEsales\EshopCommunity\Tests\Integration\Price;

require_once __DIR__. '/BasketConstruct.php';

/**
 * Base test case for price calculation tests.
 */
abstract class BaseTestCase extends \OxidTestCase
{
    /**
     * Returns test cases from specified paths.
     *
     * @param array|string $directoriesToScan directory name
     * @param array        $testCases         of specified test cases
     *
     * @return array
     */
    protected function getTestCases($directoriesToScan, $testCases = array())
    {
        $directoriesToScan = (array) $directoriesToScan;
        $allFiles = array();
        foreach ($directoriesToScan as $directory) {
            $directory = __DIR__ . "/$directory/";
            $files = $testCases ? $this->getTestCasesFiles($testCases, $directory) : $this->collectFilesFromPath($directory);
            $allFiles = array_merge($allFiles, $files);
        }

        return $this->collectDataFromFiles($allFiles);
    }

    /**
     * @param string $path
     * @param string $collector
     *
     * @return array
     */
    private function collectFilesFromPath($path, $collector = "*.php")
    {
        $files = glob($path . $collector, GLOB_NOSORT);
        $directories = glob($path.'*', GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $files = array_merge($files, $this->collectFilesFromPath($directory));
        }

        return $files;
    }

    /**
     * @param array  $testCases
     * @param string $basePath
     *
     * @return array
     */
    private function getTestCasesFiles($testCases, $basePath)
    {
        $files = array();
        foreach ($testCases as $sTestCase) {
            $file = $basePath . $sTestCase;
            if (file_exists($file)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    private function collectDataFromFiles($files)
    {
        $testCaseFiles = array();
        foreach ($files as $filePath) {
            $aData = null;
            include $filePath;
            if ($aData) {
                $testCaseFiles[$filePath] = array($aData);
            }
        }

        return $testCaseFiles;
    }
}
