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

namespace OxidEsales\EshopCommunity\Core\FileSystem;

use InvalidArgumentException;
use RuntimeException;

/**
 * Wrapper for actions related to file system.
 *
 * @internal Do not make a module extension for this class.
 */
class FileSystem
{
    /**
     * Connect all parameters with backslash to single path.
     * Ensure that no double backslash appears if parameter already ends with backslash.
     *
     * @return string
     */
    public function combinePaths()
    {
        $pathElements = func_get_args();
        foreach ($pathElements as $key => $pathElement) {
            $pathElements[$key] = rtrim($pathElement, DIRECTORY_SEPARATOR);
        }

        return implode(DIRECTORY_SEPARATOR, $pathElements);
    }

    /**
     * Check if file exists and is readable
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function isReadable($filePath)
    {
        return (is_file($filePath) && is_readable($filePath));
    }

    /**
     * Creates a directory if it does not already exist.
     * Ensures the resulting path stays within the allowed base directory.
     *
     * The base directory defines the security boundary — the resolved target path
     * must be located within it, otherwise an InvalidArgumentException is thrown.
     * This prevents path traversal attacks (e.g., via "../" segments or symlinks).
     *
     * The base directory must already exist in the filesystem. It is resolved via
     * realpath() to its canonical form before comparison.
     *
     * Typical base directories in the O3-Shop context:
     *
     *   - INSTALLATION_ROOT_PATH Project root (where composer.json and vendor/ live)
     *   - OX_BASE_PATH           Shop source directory (source/)
     *   - $config->getConfigParam('sShopDir')    Same as OX_BASE_PATH, with trailing slash
     *   - $config->getConfigParam('sCompileDir') Tmp/cache/Smarty compilation directory
     *
     * @param string $path    The directory path to create.
     * @param string $baseDir The allowed root directory that acts as a security boundary.
     *                        Must already exist. The target path must resolve to a
     *                        location within this directory.
     * @param int    $mode    The permissions for the directory (default: 0755).
     *
     * @return bool True if the directory exists or was successfully created.
     *
     * @throws InvalidArgumentException If the path is empty, the base directory
     *                                   does not exist, or the resolved path
     *                                   escapes the base directory.
     * @throws RuntimeException         If the path exists but is not a directory,
     *                                   or if creation fails.
     */
    public function createDirIfNotExists(string $path, string $baseDir, int $mode = 0755): bool
    {
        $this->validatePath($path);

        if (is_dir($path)) {
            return true;
        }

        $this->assertNotExistingFile($path);

        $normalised = $this->normalizePath($path);
        $this->assertWithinBaseDir($normalised, $baseDir);
        $this->makeDirectory($normalised, $mode);

        return true;
    }

    /**
     * @param string $path
     *
     * @throws InvalidArgumentException If the path is empty.
     */
    private function validatePath(string $path): void
    {
        if (trim($path) === '') {
            throw new InvalidArgumentException('Directory path must not be empty.');
        }
    }

    /**
     * @param string $path
     *
     * @throws RuntimeException If the path exists but is not a directory.
     */
    private function assertNotExistingFile(string $path): void
    {
        if (file_exists($path)) {
            throw new RuntimeException(
                sprintf('Path "%s" exists but is not a directory.', $path)
            );
        }
    }

    /**
     * Normalizes directory separators and strips trailing separators.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    /**
     * Resolves the target path against its deepest existing ancestor and
     * verifies it stays within the allowed base directory.
     *
     * @param string $path
     * @param string $baseDir
     *
     * @throws InvalidArgumentException If the base directory does not exist,
     *                                   the path cannot be resolved, or the
     *                                   resolved path escapes the base directory.
     */
    private function assertWithinBaseDir(string $path, string $baseDir): void
    {
        $resolvedBase = realpath($baseDir);
        if ($resolvedBase === false) {
            throw new InvalidArgumentException(
                sprintf('Base directory "%s" does not exist.', $baseDir)
            );
        }
        $resolvedBase = rtrim($resolvedBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $resolvedPath = $this->resolveNonExistentPath($path);

        if (strpos($resolvedPath . DIRECTORY_SEPARATOR, $resolvedBase) !== 0) {
            throw new InvalidArgumentException(
                sprintf('Path "%s" is outside the allowed base directory "%s".', $path, $baseDir)
            );
        }
    }

    /**
     * Resolves a path that does not yet exist by walking up to the deepest
     * existing ancestor, calling realpath() on that, and re-appending the
     * remaining segments.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws InvalidArgumentException If no existing ancestor can be resolved.
     */
    private function resolveNonExistentPath(string $path): string
    {
        $existing = $path;
        $tail = '';

        while (!file_exists($existing)) {
            $tail = DIRECTORY_SEPARATOR . basename($existing) . $tail;
            $parent = dirname($existing);
            if ($parent === $existing) {
                break;
            }
            $existing = $parent;
        }

        $resolvedExisting = realpath($existing);
        if ($resolvedExisting === false) {
            throw new InvalidArgumentException(
                sprintf('Cannot resolve path "%s".', $path)
            );
        }

        return $resolvedExisting . $tail;
    }

    /**
     * Creates the directory recursively.
     *
     * Handles the race condition where another process creates the directory
     * between our existence check and the mkdir call.
     *
     * @param string $path
     * @param int    $mode
     *
     * @throws RuntimeException If the directory could not be created.
     */
    private function makeDirectory(string $path, int $mode): void
    {
        clearstatcache(true, $path);

        if (!@mkdir($path, $mode, true) && !is_dir($path)) {
            $lastError = error_get_last();
            $message = $lastError['message'] ?? 'unknown error';

            throw new RuntimeException(
                sprintf('Directory "%s" could not be created: %s', $path, $message)
            );
        }
    }
}
