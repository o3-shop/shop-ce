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

namespace OxidEsales\EshopCommunity\Core\Autoload;

use OxidEsales\Eshop\Core\Registry;

/**
 * @deprecated 6.6 Will be removed completely
 *
 * Autoloader for module classes and extensions.
 *
 * @internal Do not make a module extension for this class.
 */
class ModuleAutoload
{
    /** @var array Classes, for which extension class chain was created. */
    public $triedClasses = [];

    /**
     * @var null|ModuleAutoload A singleton instance of this class or a sub class of this class
     */
    private static $instance = null;

    /**
     * ModuleAutoload constructor.
     *
     * Make constructor protected to ensure Singleton pattern
     */
    protected function __construct()
    {
    }

    /**
     * Magic clone method.
     *
     * Make method private to ensure Singleton pattern
     */
    private function __clone()
    {
    }

    /**
     * Tries to autoload given class. If class was not found in module files array,
     * checks module extensions.
     *
     * @param string $class Class name.
     *
     * @return bool
     */
    public static function autoload($class)
    {
        /**
         * Classes from unified namespace canot be loaded by this auto loader.
         * Do not try to load them in order to avoid strange errors in edge cases.
         */
        if (false !== strpos($class, 'OxidEsales\Eshop\\')) {
            return false;
        }
        $instance = static::getInstance();

        $class = strtolower(basename($class));

        if ($classPath = $instance->getFilePath($class)) {
            include $classPath;
        } else {
            $class = preg_replace('/_parent$/i', '', $class);

            if (!in_array($class, $instance->triedClasses)) {
                $instance->triedClasses[] = $class;
                $instance->createExtensionClassChain($class);
            }
        }
    }

    /**
     * Returns the singleton instance of this class or of a sub class of this class.
     *
     * @return ModuleAutoload The singleton instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Tries to find file path for given class. Returns empty string on path not found.
     *
     * @param string $class
     *
     * @return string
     */
    protected function getFilePath($class)
    {
        $filePath = '';

        $moduleFiles = Registry::getUtilsObject()->getModuleVar('aModuleFiles');
        if (is_array($moduleFiles)) {
            $basePath = getShopBasePath();
            foreach ($moduleFiles as $moduleId => $classPaths) {
                if (array_key_exists($class, $classPaths)) {
                    $moduleFilePath = $basePath . 'modules/' . $classPaths[$class];
                    if (file_exists($moduleFilePath)) {
                        $filePath = $moduleFilePath;
                    }
                }
            }
        }

        return $filePath;
    }

    /**
     * When module is extending other module's extension (module class, which is extending shop class),
     * this class comes to autoload and class chain has to be created.
     *
     * @param string $class
     */
    protected function createExtensionClassChain($class)
    {
        $utilsObject = Registry::getUtilsObject();

        $extensions = $utilsObject->getModuleVar('aModules');
        if (is_array($extensions)) {
            $class = preg_quote($class, '/');

            foreach ($extensions as $parentClass => $extensionPath) {
                if (preg_match('/\b' . $class . '($|\&)/i', $extensionPath)) {
                    $utilsObject->getClassName($parentClass);
                    break;
                }
            }
        }
    }
}
