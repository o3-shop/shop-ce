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
namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use oxDynImgGenerator;

/**
 * Tests for Dynamic Image generation
 */
class DynImgGeneratorTest extends \OxidTestCase
{
    /**
     * When a non-existent image is requested a jpeg image named nopic.jpeg is returned instead,
     * Test for the proper HTTP status code
     */
    public function testRequestNonExistentImageReturnsProperHttpStatusCode()
    {
        $shopUrl = $this->getConfig()->getShopUrl(1);
        $command = 'curl -I -s ' . $shopUrl . '/out/pictures/generated/product/1/87_87_75/wrongname.JPEG';
        $response = shell_exec($command);

        $this->assertNotNull($response, 'This command failed to execute: ' . $command);
        $this->assertStringContainsString('HTTP/1.1 404 Not Found', $response, 'When an image file is not found the HTTP status code 404 is returned');
    }

    /**
     * When a non-existent image is requested a jpeg image named nopic.jpeg is returned instead,
     * Test for the proper content type header.
     */
    public function testRequestNonExistentImageReturnsProperContentHeader()
    {
        $shopUrl = $this->getConfig()->getShopUrl(1);
        $command = 'curl -I -s ' . $shopUrl . '/out/pictures/generated/product/1/87_87_75/wrongname.png';
        $response = shell_exec($command);

        $this->assertNotNull($response, 'This command failed to execute: ' . $command);
        $this->assertStringContainsString(strtolower('Content-Type: image/jpeg;'), strtolower($response), '');
    }
}
