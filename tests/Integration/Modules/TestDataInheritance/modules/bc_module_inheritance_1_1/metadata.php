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
$aModule = array(
    'id'           => 'bc_module_inheritance_1_1', // maybe find a better name for that
    'title'        => 'Test backwards compatible PHP class inheritance 1.1',
    'description'  => 'Both module class and shop class use the old notation without namespaces',
    'thumbnail'    => 'picture.png',
    'version'      => '1.0',
    'author'       => 'OXID eSales AG',
    'files'       => array(
        'vendor_1_module_1_myclass' => 'oeTest/bc_module_inheritance_1_1/vendor_1_module_1_myclass.php',
        'vendor_1_module_1_anotherclass' => 'oeTest/bc_module_inheritance_1_1/vendor_1_module_1_anotherclass.php',
        'vendor_1_module_1_onemoreclass' => 'oeTest/bc_module_inheritance_1_1/vendor_1_module_1_onemoreclass.php'
    )
);
