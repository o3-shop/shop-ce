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
 * Module information
 */
$aModule = [
    'id'           => 'test1',
    'title'        => 'Test module #1',
    'description'  => 'Appends "+ test1 to content title"',
    'thumbnail'    => 'module.png',
    'version'      => '1.0',
    'author'       => 'OXID',
    'extend'      => [
        'content' => 'test1/controllers/test1content',
    ],
    'settings' => [
        ['group' => 'settingsEmpty', 'name' => 'testEmptyBoolConfig',     'type' => 'bool',     'value' => 'false'],
        ['group' => 'settingsEmpty', 'name' => 'testEmptyStrConfig',      'type' => 'str',      'value' => ''],
        ['group' => 'settingsEmpty', 'name' => 'testEmptyArrConfig',      'type' => 'arr',      'value' => ''],
        ['group' => 'settingsEmpty', 'name' => 'testEmptyAArrConfig',     'type' => 'aarr',     'value' => ''],
        ['group' => 'settingsEmpty', 'name' => 'testEmptySelectConfig',   'type' => 'select',   'value' => '', 'constrains' => '0|1|2'],
        ['group' => 'settingsEmpty', 'name' => 'testEmptyPasswordConfig', 'type' => 'password', 'value' => ''],

        ['group' => 'settingsFilled', 'name' => 'testFilledBoolConfig',     'type' => 'bool',     'value' => 'true'],
        ['group' => 'settingsFilled', 'name' => 'testFilledStrConfig',      'type' => 'str',      'value' => 'testStr'],
        ['group' => 'settingsFilled', 'name' => 'testFilledArrConfig',      'type' => 'arr',      'value' => ['option1', 'option2']],
        ['group' => 'settingsFilled', 'name' => 'testFilledAArrConfig',     'type' => 'aarr',     'value' => ['key1' => 'option1', 'key2' => 'option2']],
        ['group' => 'settingsFilled', 'name' => 'testFilledSelectConfig',   'type' => 'select',   'value' => '2', 'constrains' => '0|1|2', 'position' => 3],
        ['group' => 'settingsFilled', 'name' => 'testFilledPasswordConfig', 'type' => 'password', 'value' => 'testPassword'],
    ],
];
