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
$sMetadataVersion = '2.1';

/**
 * Module information
 */
$aModule = [
    'id'                      => 'TestModuleMetaData21',
    'title'                   => 'Module for testModuleMetaData21',
    'description'             => [
        'de' => 'de description for testModuleMetaData21',
        'en' => 'en description for testModuleMetaData21',
    ],
    'lang'                    => 'en',
    'thumbnail'               => 'picture.png',
    'version'                 => '1.0',
    'author'                  => 'O3-Shop',
    'url'                     => 'https://www.o3-shop.com',
    'email'                   => 'info@o3-shop.com',
    'extend'                  => [
        \OxidEsales\Eshop\Application\Model\Payment::class => 'TestModuleMetaData21\Payment',
        'oxArticle'                                        => 'TestModuleMetaData21\Article'
    ],
    'controllers'             => [
        'myvendor_mymodule_MyModuleController'      => 'TestModuleMetaData21\Controller',
        'myvendor_mymodule_MyOtherModuleController' => 'TestModuleMetaData21\OtherController',
    ],
    'templates'               => [
        'mymodule.tpl'       => 'TestModuleMetaData21/mymodule.tpl',
        'mymodule_other.tpl' => 'TestModuleMetaData21/mymodule_other.tpl'
    ],
    'blocks'                  => [
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
    'settings' => [
        [
            'group' => 'main',
            'name' => 'setting_1',
            'type' => 'select',
            'value' => '0',
            'constraints' => '0|1|2|3',
            'position' => 3
        ],
        ['group' => 'main', 'name' => 'setting_2', 'type' => 'password', 'value' => 'changeMe']
    ],
    'events'                  => [
        'onActivate'   => 'TestModuleMetaData21\Events::onActivate',
        'onDeactivate' => 'TestModuleMetaData21\Events::onDeactivate'
    ],
    'smartyPluginDirectories' => [
        'Smarty/PluginDirectory'
    ],
];
