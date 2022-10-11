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

$sMetadataVersion = '1.0';

$aModule = [
    'id' => 'with_everything',
    'title' => 'some-test-title',
    'description' => 'some-test-description',
    'thumbnail' => 'picture.png',
    'version' => '1.0',
    'author' => 'OXID eSales AG',
    'extend' => [
        'oxarticle' => 'with_everything/myarticle',
        'oxuser' => 'with_everything/myuser',
        'oxorder' => 'with_everything/myorder1',
    ],
    'blocks' => [
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'basket_btn_next_top',
            'file' => '/views/blocks/page/checkout/myexpresscheckout.tpl',
        ],
        [
            'theme' => 'shop_theme_id',
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => '/views/blocks/page/checkout/mypaymentselector.tpl',
        ],
    ],
    'templates' => [
        'order_special.tpl' => 'with_everything/views/admin/tpl/order_special.tpl',
        'user_connections.tpl' => 'with_everything/views/tpl/user_connections.tpl',
        'shop_theme_id' => [
            '01.tpl' => '01.theme.ext.tpl',
            '02.tpl' => '02.theme.ext.tpl',
        ],
    ],
    'files' => [
        'myexception' => 'with_everything/core/exception/myexception.php',
        'myconnection' => 'with_everything/core/exception/myconnection.php',
    ],
    'settings' => [
        [
            'group' => 'my_checkconfirm',
            'name' => 'blCheckConfirm',
            'type' => 'bool',
            'value' => true,
        ],
        [
            'group' => 'my_displayname',
            'name' => 'sDisplayName',
            'type' => 'str',
            'value' => 'Some name',
        ],
    ],
];
