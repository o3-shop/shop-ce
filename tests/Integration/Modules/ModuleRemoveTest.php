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
use oxModuleList;
use PHPUnit\Framework\MockObject\MockObject;

class ModuleRemoveTest extends BaseModuleTestCase
{
    /**
     * @return array
     */
    public function providerModuleDeactivation()
    {
        return [
            $this->caseSevenModulesPreparedRemovedOneExtensionWithEverything(),
            $this->caseSevenModulesPreparedRemovedAllExtensionWithEverything(),
            $this->caseSevenModulesPreparedRemovedOneExtensionWithMetadataV2(),
        ];
    }

    /**
     * Test check shop environment after module deactivation
     *
     * @dataProvider providerModuleDeactivation
     *
     * @param array $aInstallModules
     * @param array $aRemovedExtensions
     * @param array $aResultToAssert
     */
    public function testModuleRemove($aInstallModules, $aRemovedExtensions, $aResultToAssert)
    {
        foreach ($aInstallModules as $id) {
            $this->installAndActivateModule($id);
        }

        /** @var oxModuleList|MockObject $oModuleList */
        $oModuleList = $this->getMock(\OxidEsales\Eshop\Core\Module\ModuleList::class, ['getDeletedExtensions']);
        $oModuleList->expects($this->any())->method('getDeletedExtensions')->will($this->returnValue($aRemovedExtensions));

        $oModuleList->cleanup();
        $this->runAsserts($aResultToAssert);
    }

    /**
     * Test check shop environment after module deactivation in subshop.
     *
     * @group quarantine
     *
     * @dataProvider providerModuleDeactivation
     *
     * @param array $aInstallModules
     * @param array $aRemovedExtensions
     * @param array $aResultToAssert
     */
    public function testModuleRemoveInSubShop($aInstallModules, $aRemovedExtensions, $aResultToAssert)
    {
        if ($this->getTestConfig()->getShopEdition() != 'EE') {
            $this->markTestSkipped('This test case is only actual when SubShops are available.');
        }

        $this->prepareProjectConfigurationWitSubshops();

        foreach ($aInstallModules as $id) {
            $this->installAndActivateModule($id, 1);
        }

        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\UtilsObject::class, null);

        $oEnvironment = new Environment();
        $oEnvironment->setShopId(2);
        $_POST['shp'] = 2;

        foreach ($aInstallModules as $id) {
            $this->installAndActivateModule($id, 2);
        }

        /** @var oxModuleList|MockObject $oModuleList */
        $oModuleList = $this->getMock(\OxidEsales\Eshop\Core\Module\ModuleList::class, ['getDeletedExtensions']);
        $oModuleList->expects($this->any())->method('getDeletedExtensions')->will($this->returnValue($aRemovedExtensions));

        $oModuleList->cleanup();

        //Assert on subshop
        $this->runAsserts($aResultToAssert);

        $this->markTestIncomplete('Skipped till cleanup for subshops will be fixed');

        //Assert on main shop
        $oEnvironment->setShopId(1);
        $this->runAsserts($aResultToAssert);
    }

    /**
     * @return array
     */
    private function caseSevenModulesPreparedRemovedOneExtensionWithEverything()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files', 'with_2_settings',
                'extending_3_blocks', 'with_everything', 'with_events',
            ],

            // extensions that will be removed
            [
                'with_everything' => [
                    'extensions' => [
                        'oxuser' => 'with_everything/myuser',
                    ],
                ],
            ],

            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_bottom', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                ],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class => 'oeTest/extending_1_class/myorder',
                ],
                'files'           => [
                    'with_2_files' => [
                        'myexception'  => 'with_2_files/core/exception/myexception.php',
                        'myconnection' => 'with_2_files/core/exception/myconnection.php',
                    ],
                    'with_events'  => [
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
                ],
                'versions'        => [
                    'extending_1_class'  => '1.0',
                    'with_2_templates'   => '1.0',
                    'with_2_settings'    => '1.0',
                    'with_2_files'       => '1.0',
                    'extending_3_blocks' => '1.0',
                    'with_events'        => '1.0',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function caseSevenModulesPreparedRemovedAllExtensionWithEverything()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files', 'with_2_settings',
                'extending_3_blocks', 'with_everything', 'with_events',
            ],

            // extensions that will be removed
            [
                'with_everything' => [
                    'extensions' => [
                        'oxarticle' => 'with_everything/myarticle',
                        'oxorder'   => 'with_everything/myorder1',
                        'oxuser'    => 'with_everything/myuser',
                    ],
                ],
            ],

            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_bottom', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                ],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class => 'oeTest/extending_1_class/myorder',
                ],
                'files'           => [
                    'with_2_files' => [
                        'myexception'  => 'with_2_files/core/exception/myexception.php',
                        'myconnection' => 'with_2_files/core/exception/myconnection.php',
                    ],
                    'with_events'  => [
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
                ],
                'versions'        => [
                    'extending_1_class'  => '1.0',
                    'with_2_templates'   => '1.0',
                    'with_2_settings'    => '1.0',
                    'with_2_files'       => '1.0',
                    'extending_3_blocks' => '1.0',
                    'with_events'        => '1.0',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function caseSevenModulesPreparedRemovedOneExtensionWithMetadataV2()
    {
        return [
            // modules to be activated during test preparation
            [
                'extending_1_class', 'with_2_templates', 'with_2_files', 'with_2_settings',
                'extending_3_blocks', 'with_metadata_v2', 'with_more_metadata_v2', 'with_events',
            ],

            // extensions that will be removed
            [
                'with_more_metadata_v2' => [
                    'extensions' => [
                        'oxarticle' => 'with_more_metadata_v2/myarticle',
                    ],
                ],
            ],
            // environment asserts
            [
                'blocks'          => [
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_top', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/basket.tpl', 'block' => 'basket_btn_next_bottom', 'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl'],
                    ['template' => 'page/checkout/payment.tpl', 'block' => 'select_payment', 'file' => '/views/blocks/page/checkout/mypaymentselector.tpl'],
                ],
                'extend'          => [
                    \OxidEsales\Eshop\Application\Model\Order::class   => 'oeTest/extending_1_class/myorder',
                    \OxidEsales\Eshop\Application\Model\Article::class =>'with_metadata_v2/myarticle',
                ],
                'files'           => [
                    'with_2_files' => [
                        'myexception'  => 'with_2_files/core/exception/myexception.php',
                        'myconnection' => 'with_2_files/core/exception/myconnection.php',
                    ],
                    'with_events'  => [
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
                    'with_metadata_v2' => [
                        'order_special.tpl'      => 'with_metadata_v2/views/admin/tpl/order_special.tpl',
                        'user_connections.tpl'   => 'with_metadata_v2/views/tpl/user_connections.tpl',
                    ],
                ],
                'versions'        => [
                    'extending_1_class'  => '1.0',
                    'with_2_templates'   => '1.0',
                    'with_2_settings'    => '1.0',
                    'with_2_files'       => '1.0',
                    'extending_3_blocks' => '1.0',
                    'with_metadata_v2'   => '1.0',
                    'with_events'        => '1.0',
                ],
                'controllers'  => [
                    'with_metadata_v2' => [
                        'with_metadata_v2_mymodulecontroller' => 'OxidEsales\EshopCommunity\Tests\Integration\Modules\testData\modules\with_metadata_v2\MyModuleController',
                        'with_metadata_v2_myothermodulecontroller' => 'OxidEsales\EshopCommunity\Tests\Integration\Modules\testData\modules\with_metadata_v2\MyOtherModuleController',
                    ],
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
