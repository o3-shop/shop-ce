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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use \oxRegistry;

/**
 * Testing links class
 */
class LinksTest extends \OxidTestCase
{

    /**
     * Test get link list.
     *
     * @return null
     */
    public function testGetLinksList()
    {
        $oLinks = $this->getProxyClass('links');
        $oLink = $oLinks->getLinksList()->current();
        $this->assertEquals('http://www.o3-shop.com', $oLink->oxlinks__oxurl->value);
    }

    /**
     * Testing Links::getBreadCrumb()
     *
     * @return null
     */
    public function testGetBreadCrumb()
    {
        $oLinks = oxNew('Links');
        $aResult = array();
        $aResults = array();

        $aResult["title"] = oxRegistry::getLang()->translateString('LINKS', oxRegistry::getLang()->getBaseLanguage(), false);
        $aResult["link"] = $oLinks->getLink();

        $aResults[] = $aResult;

        $this->assertEquals($aResults, $oLinks->getBreadCrumb());
    }
}
