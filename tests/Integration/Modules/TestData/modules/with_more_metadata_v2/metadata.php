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
$aModule = array(
    'id'           => 'with_more_metadata_v2',
    'title'        => 'Test extending 1 shop class',
    'description'  => 'Module testing extending 1 shop class',
    'thumbnail'    => 'picture.png',
    'version'      => '1.0',
    'author'       => 'OXID eSales AG',
    'extend'       => ['oxarticle' => 'with_more_metadata_v2/myarticle'],
    'templates' => array(
        'order_special.tpl'      => 'with_more_metadata_v2/views/admin/tpl/order_special.tpl',
        'user_connections.tpl'   => 'with_more_metadata_v2/views/tpl/user_connections.tpl',
    ),
    'controllers'  => [
        'with_more_metadata_v2_mymodulecontroller' => 'OxidEsales\EshopCommunity\Tests\Integration\Modules\testData\modules\with_more_metadata_v2\MyModuleController',
        'with_more_metadata_v2_myothermodulecontroller' => 'OxidEsales\EshopCommunity\Tests\Integration\Modules\testData\modules\with_more_metadata_v2\MyOtherModuleController'
    ]
);
