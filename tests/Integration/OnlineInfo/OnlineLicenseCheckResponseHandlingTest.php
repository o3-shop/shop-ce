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
namespace OxidEsales\EshopCommunity\Tests\Integration\OnlineInfo;

/**
 * Class Integration_OnlineInfo_FrontendServersInformationStoringTest
 *
 * @covers \OxidEsales\EshopCommunity\Core\OnlineServerEmailBuilder
 * @covers \OxidEsales\EshopCommunity\Core\SimpleXml
 * @covers \OxidEsales\EshopCommunity\Core\OnlineLicenseCheckCaller
 * @covers \OxidEsales\EshopCommunity\Core\UserCounter
 * @covers \OxidEsales\EshopCommunity\Core\OnlineLicenseCheck
 */
class OnlineLicenseCheckResponseHandlingTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    public function testRequestHandlingWithPositiveResponse()
    {
        $config = $this->getConfig();
        $config->setConfigParam('blShopStopped', false);
        $config->setConfigParam('sShopVar', '');

        $xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml .= '<olc>';
        $xml .=   '<code>0</code>';
        $xml .=   '<message>ACK</message>';
        $xml .= '</olc>'."\n";

        $curlMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\Curl::class)
            ->setMethods(['execute','getStatusCode'])
            ->getMock();
        $curlMock->expects($this->any())->method('execute')->will($this->returnValue($xml));
        $curlMock->expects($this->any())->method('getStatusCode')->will($this->returnValue(200));

        $emailBuilder = oxNew(\OxidEsales\Eshop\Core\OnlineServerEmailBuilder::class);

        $simpleXml = oxNew(\OxidEsales\Eshop\Core\SimpleXml::class);
        $licenseCaller = oxNew(\OxidEsales\Eshop\Core\OnlineLicenseCheckCaller::class, $curlMock, $emailBuilder, $simpleXml);

        $userCounter = oxNew(\OxidEsales\Eshop\Core\UserCounter::class);
        $licenseCheck = oxNew(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class, $licenseCaller, $userCounter);

        $licenseCheck->validateShopSerials();

        $this->assertFalse($config->getConfigParam('blShopStopped'));
        $this->assertEquals('', $config->getConfigParam('sShopVar'));
    }

    public function testRequestHandlingWithNegativeResponse()
    {
        if ($this->getTestConfig()->getShopEdition() !== 'CE') {
            $this->markTestSkipped('This test is for Community edition only.');
        }

        $config = $this->getConfig();
        $config->setConfigParam('blShopStopped', false);
        $config->setConfigParam('sShopVar', '');

        $xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml .= '<olc>';
        $xml .=   '<code>1</code>';
        $xml .=   '<message>NACK</message>';
        $xml .= '</olc>'."\n";

        $curlMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\Curl::class)
            ->setMethods(['execute','getStatusCode'])
            ->getMock();
        $curlMock->expects($this->any())->method('execute')->will($this->returnValue($xml));
        $curlMock->expects($this->any())->method('getStatusCode')->will($this->returnValue(200));

        $emailBuilder = oxNew(\OxidEsales\Eshop\Core\OnlineServerEmailBuilder::class);
        $simpleXml = oxNew(\OxidEsales\Eshop\Core\SimpleXml::class);
        $licenseCaller= oxNew(\OxidEsales\Eshop\Core\OnlineLicenseCheckCaller::class, $curlMock, $emailBuilder, $simpleXml);

        $userCounter = oxNew(\OxidEsales\Eshop\Core\UserCounter::class);
        $licenseCheck = oxNew(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class, $licenseCaller, $userCounter);

        $licenseCheck->validateShopSerials();

        $this->assertFalse($config->getConfigParam('blShopStopped'));
        $this->assertNotEquals('unlc', $config->getConfigParam('sShopVar'));
    }

    public function testRequestHandlingWithInvalidResponse()
    {
        $config = $this->getConfig();
        $config->setConfigParam('blShopStopped', false);
        $config->setConfigParam('sShopVar', '');

        $xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml .= 'Some random XML'."\n";

        $curlMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\Curl::class)
            ->setMethods(['execute','getStatusCode'])
            ->getMock();
        $curlMock->expects($this->any())->method('execute')->will($this->returnValue($xml));
        $curlMock->expects($this->any())->method('getStatusCode')->will($this->returnValue(200));

        $emailBuilder = oxNew(\OxidEsales\Eshop\Core\OnlineServerEmailBuilder::class);
        $simpleXml = oxNew(\OxidEsales\Eshop\Core\SimpleXml::class);
        $licenseCaller = oxNew(\OxidEsales\Eshop\Core\OnlineLicenseCheckCaller::class, $curlMock, $emailBuilder, $simpleXml);

        $userCounter = oxNew(\OxidEsales\Eshop\Core\UserCounter::class);
        $licenseCheck = oxNew(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class, $licenseCaller, $userCounter);

        $licenseCheck->validateShopSerials();

        $this->assertFalse($config->getConfigParam('blShopStopped'));
        $this->assertEquals('', $config->getConfigParam('sShopVar'));
    }
}
