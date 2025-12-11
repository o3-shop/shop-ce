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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Autoload;

use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Class UnifiedNamespaceCompletenessTest
 *
 * This test ensures that all classes referenced in the codebase with the
 * OxidEsales\Eshop namespace have corresponding entries in the
 * UnifiedNameSpaceClassMap.php file.
 *
 * This prevents the issue where new classes are added but not registered
 * in the unified namespace mapping, causing "class not found" errors.
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Core\Autoload
 */
class UnifiedNamespaceCompletenessTest extends UnitTestCase
{
    /**
     * Test that all OxidEsales\Eshop classes used in the codebase
     * are present in the UnifiedNameSpaceClassMap.
     */
    public function testAllUsedClassesAreMappedInUnifiedNamespace()
    {
        $sourceDir = $this->getSourceDirectory();
        $classMap = $this->getUnifiedNamespaceClassMap();
        
        $usedClasses = $this->findAllUsedEshopClasses($sourceDir);
        $missingClasses = [];
        
        foreach ($usedClasses as $className) {
            if (!isset($classMap[$className])) {
                $missingClasses[] = $className;
            }
        }
        
        $this->assertEmpty(
            $missingClasses,
            "The following classes are used in the codebase but missing from UnifiedNameSpaceClassMap.php:\n" .
            implode("\n", $missingClasses) . "\n\n" .
            "Please add these classes to source/Core/Autoload/UnifiedNameSpaceClassMap.php"
        );
    }
    
    /**
     * Test that RightsRolesElement and RightsRolesElementsList are mapped.
     * This is a specific regression test for the bug that was fixed.
     */
    public function testRightsRolesClassesAreMapped()
    {
        $classMap = $this->getUnifiedNamespaceClassMap();
        
        $requiredClasses = [
            'OxidEsales\Eshop\Application\Model\RightsRoles',
            'OxidEsales\Eshop\Application\Model\RightsRolesElement',
            'OxidEsales\Eshop\Application\Model\RightsRolesElementsList',
        ];
        
        foreach ($requiredClasses as $className) {
            $this->assertArrayHasKey(
                $className,
                $classMap,
                "Class $className must be present in UnifiedNameSpaceClassMap.php"
            );
            
            $this->assertArrayHasKey(
                'editionClassName',
                $classMap[$className],
                "Class $className must have 'editionClassName' defined"
            );
        }
    }
    
    /**
     * Test that the unified namespace classes can be instantiated.
     */
    public function testRightsRolesClassesCanBeInstantiated()
    {
        // Test that the classes can be loaded via the unified namespace
        $this->assertTrue(
            class_exists('OxidEsales\Eshop\Application\Model\RightsRoles'),
            'RightsRoles class should exist in unified namespace'
        );
        
        $this->assertTrue(
            class_exists('OxidEsales\Eshop\Application\Model\RightsRolesElement'),
            'RightsRolesElement class should exist in unified namespace'
        );
        
        $this->assertTrue(
            class_exists('OxidEsales\Eshop\Application\Model\RightsRolesElementsList'),
            'RightsRolesElementsList class should exist in unified namespace'
        );
    }
    
    /**
     * Test that RightsRolesElement constants are accessible.
     */
    public function testRightsRolesElementConstants()
    {
        $className = 'OxidEsales\Eshop\Application\Model\RightsRolesElement';
        
        $this->assertTrue(defined("$className::TYPE_HIDDEN"));
        $this->assertTrue(defined("$className::TYPE_READONLY"));
        $this->assertTrue(defined("$className::TYPE_EDITABLE"));
        
        $this->assertEquals(0, constant("$className::TYPE_HIDDEN"));
        $this->assertEquals(1, constant("$className::TYPE_READONLY"));
        $this->assertEquals(2, constant("$className::TYPE_EDITABLE"));
    }
    
    /**
     * Get the source directory path.
     *
     * @return string
     */
    protected function getSourceDirectory()
    {
        return dirname(__DIR__, 4) . '/source';
    }
    
    /**
     * Get the unified namespace class map.
     *
     * @return array
     */
    protected function getUnifiedNamespaceClassMap()
    {
        $classMapFile = $this->getSourceDirectory() . '/Core/Autoload/UnifiedNameSpaceClassMap.php';
        
        $this->assertFileExists($classMapFile, "UnifiedNameSpaceClassMap.php must exist");
        
        $classMap = include $classMapFile;
        
        $this->assertIsArray($classMap, "UnifiedNameSpaceClassMap.php must return an array");
        
        return $classMap;
    }
    
    /**
     * Find all classes in the OxidEsales\Eshop namespace that are used in the codebase.
     *
     * @param string $sourceDir
     * @return array
     */
    protected function findAllUsedEshopClasses($sourceDir)
    {
        $classes = [];
        
        // Find all PHP files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            $content = file_get_contents($file->getPathname());
            
            // Find all "use OxidEsales\Eshop\..." statements
            preg_match_all(
                '/use\s+(OxidEsales\\\\Eshop\\\\[^;]+);/i',
                $content,
                $matches
            );
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $className) {
                    $classes[$className] = true;
                }
            }
            
            // Also find oxNew('OxidEsales\Eshop\...') calls
            preg_match_all(
                '/oxNew\s*\(\s*[\'"]?(OxidEsales\\\\Eshop\\\\[^\'")\s]+)[\'"]?\s*\)/i',
                $content,
                $matches
            );
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $className) {
                    // Normalize the class name (remove extra backslashes)
                    $className = str_replace('\\\\', '\\', $className);
                    $classes[$className] = true;
                }
            }
        }
        
        return array_keys($classes);
    }
}
