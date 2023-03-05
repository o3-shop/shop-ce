<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Database\Logger;

use OxidEsales\EshopCommunity\Internal\Framework\Database\Logger\QueryFilter;

class QueryFilterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @return array
     */
    public function providerTestFiltering()
    {
        $data = [
            [
                "select * from oxarticles",
                [],
                false
            ],
            [
                "delete * from oxarticles",
                [],
                true
            ],
            [
                "insert into oxarticles values ('some values')",
                [],
                true
            ],
            [
                "update oxarticles set oxtitle = 'other title' where oxid = '_someid' ",
                [],
                true
            ],
            [
                "UPDATE oxarticles set oxtitle = 'other title' where oxid = '_someid' ",
                [],
                true
            ],
            [
                "update oxarticles set oxtitle = 'other title' where oxid = '_someid' ",
                [
                    'oxarticles'
                ],
                false
            ],
            [
                "yadda yadda yadda insert into blabla ",
                [
                    'ox'
                ],
                true
            ],
            [
                "yadda oxyadda yadda insert into oxblabla ",
                [
                    'ox'
                ],
                false
            ],
            [
                "yadda yadda yadda oxsession blabla ",
                [],
                false
            ],
            [
                "yadda yadda yadda oxcache blabla ",
                [],
                false
            ],
        ];

        return $data;
    }

    /**
     * @param string $query
     * @param array  $skipLogTags
     * @param bool   $expected
     *
     * @dataProvider providerTestFiltering
     */
    public function testFiltering(string $query, array $skipLogTags, bool $expected)
    {
        $queryFilter = new QueryFilter();

        $this->assertEquals($expected, $queryFilter->shouldLogQuery($query, $skipLogTags));
    }
}
