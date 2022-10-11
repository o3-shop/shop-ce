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

namespace OxidEsales\EshopCommunity\Tests\Acceptance;

abstract class FrontendTestCase extends AcceptanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->callShopSC("oxConfig", null, null, array(
            "iTopNaviCatCount" => array(
                "type" => "str",
                "value" => '3',
                "module" => "theme:azure"
            ),
            "aNrofCatArticles" => array(
                "type" => "arr",
                "value" => 'a:6:{i:0;s:2:"10";i:1;s:2:"20";i:2;s:2:"50";i:3;s:3:"100";i:4;s:1:"2";i:5;s:1:"1";}',
                "module" => "theme:azure"
            ),
            "aNrofCatArticlesInGrid" => array(
                "type" => "arr",
                "value" => 'a:4:{i:0;s:2:"12";i:1;s:2:"16";i:2;s:2:"24";i:3;s:2:"32";}',
                "module" => "theme:azure"
            )
        ));
    }
}
