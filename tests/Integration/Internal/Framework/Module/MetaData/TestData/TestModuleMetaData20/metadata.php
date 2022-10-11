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

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = [
    'id'          => 'TestModuleMetaData20',
    'title'       => 'Module for testModuleMetaData20',
    'description' => [
        'de' => 'de description for testModuleMetaData20',
        'en' => 'en description for testModuleMetaData20',
    ],
    'lang'        => 'en',
    'thumbnail'   => 'picture.png',
    'version'     => '1.0',
    'author'      => 'O3-Shop',
    'url'         => 'https://www.o3-shop.com',
    'email'       => 'info@o3-shop.com',
    'extend'      => [
        \OxidEsales\Eshop\Application\Model\Payment::class => 'TestModuleMetaData20\Payment',
        'oxArticle'                                        => 'TestModuleMetaData20\Article',
    ],
    'controllers' => [
        'myvendor_mymodule_MyModuleController'      => 'TestModuleMetaData20\Controller',
        'myvendor_mymodule_MyOtherModuleController' => 'TestModuleMetaData20\OtherController',
    ],
    'templates'   => [
        'mymodule.tpl'       => 'TestModuleMetaData20/mymodule.tpl',
        'mymodule_other.tpl' => 'TestModuleMetaData20/mymodule_other.tpl'
    ],
    'blocks'      => [
        [
            'theme'    => 'theme_id',
            'template' => 'template_1.tpl',
            'block'    => 'block_1',
            'file'     => '/blocks/template_1.tpl',
            'position' => '1'
        ],
        [
            'template' => 'template_2.tpl',
            'block'    => 'block_2',
            'file'     => '/blocks/template_2.tpl',
            'position' => '2'
        ],
    ],
    'settings'    => [
        [
            'group' => 'main',
            'name' => 'setting_1',
            'type' => 'select',
            'value' => '0',
            'constraints' => '0|1|2|3',
            'position' => 3
        ],
        ['group' => 'main', 'name' => 'setting_2', 'type' => 'arr', 'value' => ['value1', 'value2']]
    ],
    'events'      => [
        'onActivate'   => 'TestModuleMetaData20\Events::onActivate',
        'onDeactivate' => 'TestModuleMetaData20\Events::onDeactivate'
    ],

];
