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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;

class ModuleActivationFirstTest extends BaseModuleTestCase
{
    /**
     * @return array
     */
    public function providerModuleActivation()
    {
        return [
            $this->caseFiveModulesPreparedActivatedWithEverything(),
            $this->caseOneModulePreparedActivatedWithEverything(),
            $this->caseThreeModulesPreparedActivatedExtendingThreeClassesWithOneExtension(),
            $this->caseSevenModulesPreparedActivatedNoExtending(),
            $this->caseOneModulePreparedActivatedWithTwoFiles(),
            $this->caseOneModulePreparedActivatedWithTwoSettings(),
            $this->caseOneModulePreparedActivatedWithTwoTemplates(),
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

        $this->installAndActivateModule($sModule);

        $this->runAsserts($aResultToAsserts);
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
    public function testModuleActivationInMainShopDidNotActivatedInSubShop($aInstallModules, $sModule, $aResultToAsserts)
    {
        if ($this->getTestConfig()->getShopEdition() != 'EE') {
            $this->markTestSkipped('This test case is only actual when SubShops are available.');
        }

        $this->prepareProjectConfigurationWitSubshops();

        foreach ($aInstallModules as $moduleId) {
            $this->installAndActivateModule($moduleId);
        }

        foreach ($aInstallModules as $moduleId) {
            $this->installAndActivateModule($moduleId, 2);
        }

        $this->installAndActivateModule($sModule, 1);
        $this->installAndActivateModule($sModule, 2);

        $this->deactivateModule(oxNew('oxModule'), $sModule);

        $environment = new Environment();
        $environment->setShopId(2);

        $this->runAsserts($aResultToAsserts);
    }

    /**
     * Data provider case with 5 modules prepared and with_everything module activated
     *
     * @return array
     */
    protected function caseFiveModulesPreparedActivatedWithEverything()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files',
                'extending_3_blocks', 'with_events',
            ],

            // module that will be activated
            'with_everything',

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
     * Data provider case with 1 module prepared and with_everything module activated
     *
     * @return array
     */
    private function caseOneModulePreparedActivatedWithEverything()
    {
        return [
            // modules to be activated during test preparation
            [
                'no_extending',
            ],

            // module that will be activated
            'with_everything',

            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
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
     * Data provider case with 3 modules prepared and extending_3_classes_with_1_extension module activated
     *
     * @return array
     */
    private function caseThreeModulesPreparedActivatedExtendingThreeClassesWithOneExtension()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class',
                'extending_3_classes_with_1_extension', 'extending_3_classes',
            ],

            // module that will be activated
            'extending_1_class_3_extensions',

            // environment asserts
            [
                'blocks'          => [],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class   => '' .
                                   'oeTest/extending_1_class/myorder&extending_3_classes_with_1_extension/mybaseclass&extending_3_classes/myorder&oeTest/extending_1_class_3_extensions/myorder1',
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
     * Data provider case with 7 modules prepared and no_extending module activated
     *
     * @return array
     */
    private function caseSevenModulesPreparedActivatedNoExtending()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files', 'with_2_settings',
                'extending_3_blocks', 'with_everything', 'with_events',
            ],

            // module that will be activated
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
     * Data provider case with 1 module prepared and with_2_files module activated
     *
     * @return array
     */
    private function caseOneModulePreparedActivatedWithTwoFiles()
    {
        return [
            // modules to be activated during test preparation
            [
                'no_extending',
            ],

            // module that will be activated
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
     * Data provider case with 1 module prepared and with_2_settings module activated
     *
     * @return array
     */
    private function caseOneModulePreparedActivatedWithTwoSettings()
    {
        return [
            // modules to be activated during test preparation
            [
                'no_extending',
            ],

            // module that will be activated
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
     * Data provider case with 1 module prepared and with_2_templates module activated
     *
     * @return array
     */
    private function caseOneModulePreparedActivatedWithTwoTemplates()
    {
        return [
            // modules to be activated during test preparation
            [
                'no_extending',
            ],

            // module that will be activated
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

    private function prepareProjectConfigurationWitSubshops()
    {
        $projectConfigurationDao = $this->container->get(ProjectConfigurationDaoInterface::class);
        $projectConfiguration = $projectConfigurationDao->getConfiguration();

        $projectConfiguration->addShopConfiguration(2, new ShopConfiguration());

        $projectConfigurationDao->save($projectConfiguration);
    }
}
