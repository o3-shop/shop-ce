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

namespace OxidEsales\EshopCommunity\Tests\Integration\Modules;

class ModuleIsActiveTest extends BaseModuleTestCase
{
    /**
     * @return array
     */
    public function providerModuleIsActive()
    {
        return [
            [
                ['extending_1_class', 'with_2_templates', 'with_everything'],
                ['extending_1_class', 'with_everything'],
                [
                    'active'    => ['with_2_templates'],
                    'notActive' => ['extending_1_class', 'with_everything'],
                ],
            ],
            [
                ['extending_1_class', 'with_2_templates', 'with_everything'],
                [],
                [
                    'active'    => ['extending_1_class', 'with_2_templates', 'with_everything'],
                    'notActive' => [],
                ],
            ],

            [
                ['extending_1_class', 'extending_1_class_3_extensions', 'no_extending', 'with_2_templates', 'with_everything'],
                ['extending_1_class', 'extending_1_class_3_extensions', 'no_extending', 'with_2_templates', 'with_everything'],
                [
                    'active'    => [],
                    'notActive' => ['extending_1_class', 'extending_1_class_3_extensions', 'no_extending', 'with_2_templates', 'with_everything'],
                ],
            ],

            [
                ['extending_1_class', 'extending_1_class_3_extensions', 'no_extending', 'with_2_templates', 'with_everything'],
                ['extending_1_class', 'extending_1_class_3_extensions', 'no_extending', 'with_2_templates', 'with_everything'],
                [
                    'active'    => [],
                    'notActive' => ['extending_1_class', 'extending_1_class_3_extensions', 'no_extending', 'with_2_templates', 'with_everything'],
                ],
            ],
            [
                ['no_extending'],
                [],
                [
                    'active'    => ['no_extending'],
                    'notActive' => [],
                ],
            ],
            [
                ['no_extending'],
                ['no_extending'],
                [
                    'active'    => [],
                    'notActive' => ['no_extending'],
                ],
            ],
        ];
    }

    /**
     * Tests if module was activated.
     *
     * @dataProvider providerModuleIsActive
     *
     * @param array $aInstallModules
     * @param array $aDeactivateModules
     * @param array $aResultToAssert
     */
    public function testIsActive($aInstallModules, $aDeactivateModules, $aResultToAssert)
    {
        foreach ($aInstallModules as $moduleId) {
            $this->installAndActivateModule($moduleId);
        }

        //deactivation
        $oModule = oxNew('oxModule');

        foreach ($aDeactivateModules as $sModule) {
            $this->deactivateModule($oModule, $sModule);
        }

        //assertion
        foreach ($aResultToAssert['active'] as $sModule) {
            $oModule->load($sModule);
            $this->assertTrue($oModule->isActive());
        }

        foreach ($aResultToAssert['notActive'] as $sModule) {
            $oModule->load($sModule);
            $this->assertFalse($oModule->isActive());
        }
    }
}
