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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Module\ModuleSmartyPluginDirectories;

/**
 * Class ModuleSmartyPluginDirectoriesTest
 */
class ModuleSmartyPluginDirectoriesTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    /** @var string In order to make it simple, use the same path for all modules */
    private $fullPathToModule = '/var/www/myshop/modules/oe/mymodule';

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleSmartyPluginDirectories::add()
     */
    public function testAdd()
    {
        $directories = $this->getDirectoriesAndModuleId(0)['directories'];
        $moduleId = $this->getDirectoriesAndModuleId(0)['id'];

        $moduleSmartyPluginDirectories = $this->getModuleSmartyPluginDirectories();

        $moduleSmartyPluginDirectories->add(
            $directories,
            $moduleId
        );

        $this->assertEquals(
            [$moduleId => $directories],
            $moduleSmartyPluginDirectories->getWithRelativePath(),
            'The method add does not add the module smarty plugin directories correctly.'
        );
    }

    /**
     * The method ModuleSmartyPluginDirectories::add() appends the smarty plugin directories to the
     * existing ones. This means the order of modules being activated matters!
     */
    public function testAddAppendsNewDirectoriesAfterExistingOnes()
    {
        $directoriesModule1 = $this->getDirectoriesAndModuleId(0)['directories'];
        $moduleIdModule1 = $this->getDirectoriesAndModuleId(0)['id'];

        $directoriesModule2 = $this->getDirectoriesAndModuleId(1)['directories'];
        $moduleIdModule2 = $this->getDirectoriesAndModuleId(1)['id'];

        $moduleSmartyPluginDirectories = $this->getModuleSmartyPluginDirectories();

        $moduleSmartyPluginDirectories->add(
            $directoriesModule1,
            $moduleIdModule1
        );

        $moduleSmartyPluginDirectories->add(
            $directoriesModule2,
            $moduleIdModule2
        );

        $this->assertEquals(
            [
                $moduleIdModule1 => $directoriesModule1,
                $moduleIdModule2 => $directoriesModule2
            ],
            $moduleSmartyPluginDirectories->getWithRelativePath(),
            'The method add does not append the module smarty plugin directories correctly.'
        );
    }


    /**
     * ModuleSmartyPluginDirectories::remove() removes all module smarty plugin directories
     */
    public function testRemove()
    {
        $directoriesModule1 = $this->getDirectoriesAndModuleId(0)['directories'];
        $moduleIdModule1 = $this->getDirectoriesAndModuleId(0)['id'];

        $directoriesModule2 = $this->getDirectoriesAndModuleId(1)['directories'];
        $moduleIdModule2 = $this->getDirectoriesAndModuleId(1)['id'];

        $directoriesModule3 = $this->getDirectoriesAndModuleId(2)['directories'];
        $moduleIdModule3 = $this->getDirectoriesAndModuleId(2)['id'];

        $moduleSmartyPluginDirectories = $this->getModuleSmartyPluginDirectories();

        $moduleSmartyPluginDirectories->add(
            $directoriesModule1,
            $moduleIdModule1
        );

        $moduleSmartyPluginDirectories->add(
            $directoriesModule2,
            $moduleIdModule2
        );

        $moduleSmartyPluginDirectories->add(
            $directoriesModule3,
            $moduleIdModule3
        );

        $moduleSmartyPluginDirectories->remove($moduleIdModule2);

        $this->assertEquals(
            [
                $moduleIdModule1 => $directoriesModule1,
                $moduleIdModule3 => $directoriesModule3
            ],
            $moduleSmartyPluginDirectories->getWithRelativePath(),
            'The method add does not delete the module smarty plugin directories correctly.'
        );
    }

    /**
     * ModuleSmartyPluginDirectories::get() returns an ordered module depending on the activation order of
     * modules.
     */
    public function testGetWithAbsolutePath()
    {
        $directoriesModule1 = $this->getDirectoriesAndModuleId(0)['directories'];
        $moduleIdModule1 = $this->getDirectoriesAndModuleId(0)['id'];

        $moduleSmartyPluginDirectories = $this->getModuleSmartyPluginDirectories();

        $moduleSmartyPluginDirectories->add(
            $directoriesModule1,
            $moduleIdModule1
        );

        $expectedModuleSmartyPluginDirectoryFullPaths = [];
        foreach ($directoriesModule1 as $directory) {
            $expectedModuleSmartyPluginDirectoryFullPaths[] = $this->fullPathToModule . DIRECTORY_SEPARATOR . $directory;
        }

        $this->assertEquals(
            $expectedModuleSmartyPluginDirectoryFullPaths,
            $moduleSmartyPluginDirectories->getWithFullPath()
        );
    }

    public function testGetWithRelativePath()
    {
        $directoriesModule = ['/Smarty/Plugin1', 'Smarty/Plugin2/'];

        $moduleSmartyPluginDirectories = $this->getModuleSmartyPluginDirectories();
        $moduleSmartyPluginDirectories->add(
            $directoriesModule,
            'moduleId'
        );

        $this->assertEquals(
            [
                'moduleId' => $directoriesModule,
            ],
            $moduleSmartyPluginDirectories->getWithRelativePath()
        );
    }

    public function testDirectoriesSetter()
    {
        $moduleDirectories = [
            'moduleId' => [
                '/Smarty/Plugin1',
                'Smarty/Plugin2/',
            ],
            'anotherModuleId' => [
                '/Smarty/Plugin',
                'Smarty/Plugin/OxidEshopPackage/',
            ]
        ];

        $moduleSmartyPluginDirectories = $this->getModuleSmartyPluginDirectories();
        $moduleSmartyPluginDirectories->set($moduleDirectories);

        $this->assertEquals(
            $moduleDirectories,
            $moduleSmartyPluginDirectories->getWithRelativePath()
        );
    }


    /**
     * @return ModuleSmartyPluginDirectories
     */
    private function getModuleSmartyPluginDirectories()
    {
        $moduleStub = $this->getMock(Module::class);
        $moduleStub->method('getModuleFullPath')
            ->will($this->returnValue($this->fullPathToModule));
        return new ModuleSmartyPluginDirectories($moduleStub);
    }

    /**
     * @param int $moduleNumber
     * @return array
     */
    private function getDirectoriesAndModuleId($moduleNumber)
    {
        $modules = [
            [
                'directories' => ['/Smarty/Plugin1', 'Smarty/Plugin2/'],
                'id' => 'oemodule1'
            ],
            [
                'directories' => [['/Smarty/PluginModule2']],
                'id' => 'oemodule2'
            ],
            [
                'directories' => ['Smarty/AnotherDir1', 'Smarty/AnotherDir2', 'Smarty/AnotherDir3'],
                'id' => 'oemodule3'
            ]
        ];
        return $modules[$moduleNumber];
    }
}
