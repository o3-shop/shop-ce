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
    'testcatdiscount' =>
        [
            'oxdiscount' => [
                'OXID' => 'testcatdiscount',
                'OXSHOPID' => 1,
                'OXACTIVE' => 1,
                'OXTITLE' => 'discount for category [DE] šÄßüл',
                'OXTITLE_1' => 'discount for category [EN] šÄßüл',
                'OXAMOUNT' => 1,
                'OXAMOUNTTO' => 999999,
                'OXPRICETO' => 0,
                'OXPRICE' => 0,
                'OXADDSUMTYPE' => 'abs',
                'OXADDSUM' => 5,
                'OXITMARTID' => '',
                'OXITMAMOUNT' => 0,
                'OXITMMULTIPLE' => 0,
                'OXSORT' => 100
            ],
            'oxobject2discount' => [
                [
                    'OXID' => 'fa647a823ce118996.58546955',
                    'OXDISCOUNTID' => 'testcatdiscount',
                    'OXOBJECTID' => 'a7c40f631fc920687.20179984',
                    'OXTYPE' => 'oxcountry'
                ],
                [
                    'OXID' => 'fa647a823d5079104.99115703',
                    'OXDISCOUNTID' => 'testcatdiscount',
                    'OXOBJECTID' => 'testcategory0',
                    'OXTYPE' => 'oxcategories'
                ]
            ]
        ]
];
