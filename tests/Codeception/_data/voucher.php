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

return [
    'testvoucher4' =>
        [
            'oxvoucherseries' => [
                'OXID' => 'testvoucher4',
                'OXSHOPID' => 1,
                'OXSERIENR' => '4 Coupon šÄßüл',
                'OXSERIEDESCRIPTION' => '4 Coupon šÄßüл',
                'OXDISCOUNT' => 50.00,
                'OXDISCOUNTTYPE' => 'percent',
                'OXBEGINDATE' => '2008-01-01 00:00:00',
                'OXENDDATE' => date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)),
                'OXALLOWSAMESERIES' => 0,
                'OXALLOWOTHERSERIES' => 0,
                'OXALLOWUSEANOTHER' => 0,
                'OXMINIMUMVALUE' => 45.00,
                'OXCALCULATEONCE' => 1
            ],
            'oxvouchers' => [
                [
                    'OXDATEUSED' => '0000-00-00',
                    'OXRESERVED' => 0,
                    'OXVOUCHERNR' => '123123',
                    'OXVOUCHERSERIEID' => 'testvoucher4',
                    'OXID' => 'testcoucher011'
                ]
            ]
        ]
];
