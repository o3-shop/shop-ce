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

use OxidTestCase;
use oxTestModules;

/**
 * Testing moredetails class
 */
class MoredetailsTest extends OxidTestCase
{

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        oxTestModules::addFunction('oxSeoEncoderManufacturer', '_saveToDb', '{return null;}');
    }

    /**
     * Test get product id's.
     *
     * @return null
     */
    public function testGetProductId()
    {
        $oMoreDetails = $this->getProxyClass('moredetails');
        $this->setRequestParameter('anid', '2000');
        $oMoreDetails->init();

        $this->assertEquals('2000', $oMoreDetails->getProductId());
    }

    /**
     * Test get product.
     *
     * @return null
     */
    public function testGetProduct()
    {
        $oMoreDetails = $this->getProxyClass('moredetails');
        $this->setRequestParameter('anid', '2000');
        $oMoreDetails->init();

        $this->assertEquals('2000', $oMoreDetails->getProduct()->getId());
    }

    /**
     * Test get active picture id.
     *
     * @return null
     */
    public function testGetActPictureId()
    {
        $this->markTestSkipped('Review with D.S.');

        $oMoreDetails = $this->getProxyClass('moredetails');
        $this->setRequestParameter('anid', '096a1b0849d5ffa4dd48cd388902420b');
        $oMoreDetails->init();

        $this->assertEquals('1', $oMoreDetails->getActPictureId());
    }

    /**
     * Test get product zoom pictures.
     *
     * @return null
     */
    public function testGetArtZoomPics()
    {
        $this->markTestSkipped('Review with D.S.');

        $oMoreDetails = $this->getProxyClass('moredetails');
        $this->setRequestParameter('anid', '096a1b0849d5ffa4dd48cd388902420b');
        $oMoreDetails->init();
        $aZoom = $oMoreDetails->getArtZoomPics();

        $this->assertEquals('front_z1(1).jpg', basename($aZoom[1]['file']));
    }
}
