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

use OxidEsales\Eshop\Core\Module\Module;

class ModuleActivationTest extends BaseModuleTestCase
{
    /**
     * @return array
     */
    public function providerModuleActivation()
    {
        return [
            $this->caseSixModulesPreparedReactivatedWithEverything(),
            $this->caseTwoModulesPreparedReactivatedWithEverything(),
            $this->caseFourModulesPreparedReactivatedExtendingThreeClassesWithOneExtension(),
            $this->caseEightModulesPreparedReactivatedNoExtending(),
            $this->caseTwoModulesPreparedReactivatedWithTwoFiles(),
            $this->caseTwoModulesPreparedReactivatedWithTwoSettings(),
            $this->caseTwoModulesPreparedReactivatedWithTwoTemplates(),
        ];
    }

    /**
     * Tests if module was activated.
     *
     * @dataProvider providerModuleActivation
     *
     * @param array  $aInstallModules
     * @param string $sModule
     * @param array  $aResultToAsserts
     */
    public function testModuleActivation($aInstallModules, $sModule, $aResultToAsserts)
    {
        foreach ($aInstallModules as $moduleId) {
            $this->installAndActivateModule($moduleId);
        }

        $oModule = oxNew(Module::class);
        $oModule->load($sModule);
        $this->deactivateModule($oModule);
        $this->installAndActivateModule($oModule->getId());

        $this->runAsserts($aResultToAsserts);
    }

    /**
     * Data provider case with 6 modules prepared and with_everything module reactivated
     *
     * @return array
     */
    protected function caseSixModulesPreparedReactivatedWithEverything()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files',
                'with_everything', 'extending_3_blocks', 'with_events',
            ],

            // module that will be reactivated
            'with_everything',

            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_bottom', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                ],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class   => 'oeTest/extending_1_class/myorder&with_everything/myorder1',
                    \OxidEsales\Eshop\Application\Model\Article::class => 'with_everything/myarticle',
                    \OxidEsales\Eshop\Application\Model\User::class    => 'with_everything/myuser',
                ],
                'files'           => [
                    'with_2_files'    => [
                        'myexception'  => 'with_2_files/core/exception/myexception.php',
                        'myconnection' => 'with_2_files/core/exception/myconnection.php',
                    ],
                    'with_everything' => [
                        'myexception'  => 'with_everything/core/exception/myexception.php',
                        'myconnection' => 'with_everything/core/exception/myconnection.php',
                    ],
                    'with_events'     => [
                        'myevents' => 'with_events/files/myevents.php',
                    ],
                ],
                'settings'        => [
                    ['group' => 'my_checkconfirm', 'name' => 'blCheckConfirm', 'type' => 'bool', 'value' => 'true'],
                    ['group' => 'my_displayname', 'name' => 'sDisplayName', 'type' => 'str', 'value' => 'Some name'],
                ],
                'disabledModules' => [],
                'templates'       => [
                    'with_2_templates' => [
                        'order_special.tpl'    => 'with_2_templates/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl' => 'with_2_templates/views/tpl/user_connections.tpl',
                    ],
                    'with_everything'  => [
                        'order_special.tpl'    => 'with_everything/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl' => 'with_everything/views/tpl/user_connections.tpl',
                    ],
                ],
                'versions'        => [
                    'extending_1_class'  => '1.0',
                    'with_2_templates'   => '1.0',
                    'with_2_files'       => '1.0',
                    'extending_3_blocks' => '1.0',
                    'with_events'        => '1.0',
                    'with_everything'    => '1.0',
                ],
            ],
        ];
    }

    /**
     * Data provider case with 2 modules prepared and with_everything module reactivated
     *
     * @return array
     */
    private function caseTwoModulesPreparedReactivatedWithEverything()
    {
        return [
            // modules to be activated during test preparation
            [
                'with_everything', 'no_extending',
            ],

            // module that will be reactivated
            'with_everything',

            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                ],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Article::class => 'with_everything/myarticle',
                    \OxidEsales\Eshop\Application\Model\Order::class   => 'with_everything/myorder1',
                    \OxidEsales\Eshop\Application\Model\User::class    => 'with_everything/myuser',
                ],
                'files'           => [
                    'with_everything' => [
                        'myexception'  => 'with_everything/core/exception/myexception.php',
                        'myconnection' => 'with_everything/core/exception/myconnection.php',
                    ],
                ],
                'settings'        => [
                    ['group' => 'my_checkconfirm', 'name' => 'blCheckConfirm', 'type' => 'bool', 'value' => 'true'],
                    ['group' => 'my_displayname', 'name' => 'sDisplayName', 'type' => 'str', 'value' => 'Some name'],
                ],
                'disabledModules' => [],
                'templates'       => [
                    'with_everything' => [
                        'order_special.tpl'    => 'with_everything/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl' => 'with_everything/views/tpl/user_connections.tpl',
                    ],
                ],
                'versions'        => [
                    'no_extending'    => '1.0',
                    'with_everything' => '1.0',
                ],
            ],
        ];
    }

    /**
     * Data provider case with 4 modules prepared and extending_3_classes_with_1_extension module reactivated
     *
     * @return array
     */
    private function caseFourModulesPreparedReactivatedExtendingThreeClassesWithOneExtension()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'extending_3_classes_with_1_extension',
                'extending_3_classes', 'extending_1_class_3_extensions',
            ],

            // module that will be reactivated
            'extending_1_class_3_extensions',

            // environment asserts
            [
                'blocks'          => [],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class   => 'oeTest/extending_1_class/myorder&extending_3_classes_with_1_extension/mybaseclass&extending_3_classes/myorder&oeTest/extending_1_class_3_extensions/myorder1',
                    \OxidEsales\Eshop\Application\Model\Article::class => 'extending_3_classes_with_1_extension/mybaseclass&extending_3_classes/myarticle',
                    \OxidEsales\Eshop\Application\Model\User::class    => 'extending_3_classes_with_1_extension/mybaseclass&extending_3_classes/myuser',
                ],
                'files'           => [],
                'settings'        => [],
                'disabledModules' => [],
                'templates'       => [],
                'versions'        => [
                    'extending_3_classes_with_1_extension' => '1.0',
                    'extending_1_class'                    => '1.0',
                    'extending_3_classes'                  => '1.0',
                    'extending_1_class_3_extensions'       => '1.0',
                ],
            ],
        ];
    }

    /**
     * Data provider case with 8 modules prepared and no_extending module reactivated
     *
     * @return array
     */
    private function caseEightModulesPreparedReactivatedNoExtending()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files', 'with_2_settings',
                'no_extending', 'extending_3_blocks', 'with_everything', 'with_events',
            ],

            // module that will be reactivated
            'no_extending',

            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_bottom', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                ],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class   => 'oeTest/extending_1_class/myorder&with_everything/myorder1',
                    \OxidEsales\Eshop\Application\Model\Article::class => 'with_everything/myarticle',
                    \OxidEsales\Eshop\Application\Model\User::class    => 'with_everything/myuser',
                ],
                'files'           => [
                    'with_2_files'    => [
                        'myexception'  => 'with_2_files/core/exception/myexception.php',
                        'myconnection' => 'with_2_files/core/exception/myconnection.php',
                    ],
                    'with_everything' => [
                        'myexception'  => 'with_everything/core/exception/myexception.php',
                        'myconnection' => 'with_everything/core/exception/myconnection.php',
                    ],
                    'with_events'     => [
                        'myevents' => 'with_events/files/myevents.php',
                    ],
                ],
                'settings'        => [
                    ['group' => 'my_checkconfirm', 'name' => 'blCheckConfirm', 'type' => 'bool', 'value' => 'true'],
                    ['group' => 'my_displayname', 'name' => 'sDisplayName', 'type' => 'str', 'value' => 'Some name'],
                    ['group' => 'my_checkconfirm', 'name' => 'blCheckConfirm', 'type' => 'bool', 'value' => 'true'],
                    ['group' => 'my_displayname', 'name' => 'sDisplayName', 'type' => 'str', 'value' => 'Some name'],
                ],
                'disabledModules' => [],
                'templates'       => [
                    'with_2_templates' => [
                        'order_special.tpl'    => 'with_2_templates/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl' => 'with_2_templates/views/tpl/user_connections.tpl',
                    ],
                    'with_everything'  => [
                        'order_special.tpl'    => 'with_everything/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl' => 'with_everything/views/tpl/user_connections.tpl',
                    ],
                ],
                'versions'        => [
                    'extending_1_class'  => '1.0',
                    'with_2_templates'   => '1.0',
                    'with_2_settings'    => '1.0',
                    'with_2_files'       => '1.0',
                    'extending_3_blocks' => '1.0',
                    'no_extending'       => '1.0',
                    'with_events'        => '1.0',
                    'with_everything'    => '1.0',
                ],
            ],
        ];
    }

    /**
     * Data provider case with 2 modules prepared and with_2_files module reactivated
     *
     * @return array
     */
    private function caseTwoModulesPreparedReactivatedWithTwoFiles()
    {
        return [
            // modules to be activated during test preparation
            [
                'with_2_files', 'no_extending',
            ],

            // module that will be reactivated
            'with_2_files',

            // environment asserts
            [
                'blocks'          => [],
                'extend'          => [],
                'files'           => [
                    'with_2_files' => [
                        'myexception'  => 'with_2_files/core/exception/myexception.php',
                        'myconnection' => 'with_2_files/core/exception/myconnection.php',
                    ],
                ],
                'settings'        => [],
                'disabledModules' => [],
                'templates'       => [],
                'versions'        => [
                    'no_extending' => '1.0',
                    'with_2_files' => '1.0',
                ],
            ],
        ];
    }

    /**
     * Data provider case with 2 modules prepared and with_2_settings module reactivated
     *
     * @return array
     */
    private function caseTwoModulesPreparedReactivatedWithTwoSettings()
    {
        return [
            // modules to be activated during test preparation
            [
                'with_2_settings', 'no_extending',
            ],

            // module that will be reactivated
            'with_2_settings',

            // environment asserts
            [
                'blocks'          => [],
                'extend'          => [],
                'files'           => [],
                'settings'        => [
                    ['group' => 'my_checkconfirm', 'name' => 'blCheckConfirm', 'type' => 'bool', 'value' => 'true'],
                    ['group' => 'my_displayname', 'name' => 'sDisplayName', 'type' => 'str', 'value' => 'Some name'],
                ],
                'disabledModules' => [],
                'templates'       => [],
                'versions'        => [
                    'no_extending'    => '1.0',
                    'with_2_settings' => '1.0',
                ],
            ],
        ];
    }

    /**
     * Data provider case with 2 modules prepared and with_2_templates module reactivated
     *
     * @return array
     */
    private function caseTwoModulesPreparedReactivatedWithTwoTemplates()
    {
        return [
            // modules to be activated during test preparation
            [
                'with_2_templates', 'no_extending',
            ],

            // module that will be reactivated
            'with_2_templates',

            // environment asserts
            [
                'blocks'          => [],
                'extend'          => [],
                'files'           => [],
                'settings'        => [],
                'disabledModules' => [],
                'templates'       => [
                    'with_2_templates' => [
                        'order_special.tpl'    => 'with_2_templates/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl' => 'with_2_templates/views/tpl/user_connections.tpl',
                    ],
                ],
                'versions'        => [
                    'no_extending'     => '1.0',
                    'with_2_templates' => '1.0',
                ],
            ],
        ];
    }
}
