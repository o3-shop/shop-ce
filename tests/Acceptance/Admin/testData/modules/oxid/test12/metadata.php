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
$sMetadataVersion = '1.1';

/**
 * Module information
 */
$aModule = [
    'id'           => 'test12',
    'title'        => 'Test module #12',
    'description'  => 'Test module for oxajax container-class resolution',
    'thumbnail'    => 'module.png',
    'version'      => '1.0.0',
    'author'       => 'OXID eSales',
    'url'          => 'http://www.oxid-esales.com',
    'email'        => 'info@oxid-esales.com',
    'files'   => [
        'test_12_ajax_controller_ajax' => 'oxid/test12/controllers/test_12_ajax_controller_ajax.php',
        'test_12_ajax_controller' => 'oxid/test12/controllers/test_12_ajax_controller.php'
    ],
    'templates'   =>  ['test_12_ajax_controller.tpl' => 'oxid/test12/views/tpl/test_12_ajax_controller.tpl',
                       'test_12_popup.tpl' => 'oxid/test12/views/tpl/test_12_popup.tpl']
];
