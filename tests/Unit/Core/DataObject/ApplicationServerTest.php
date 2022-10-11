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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\DataObject;

/**
 * @covers \OxidEsales\Eshop\Core\DataObject\ApplicationServer
 */
class ApplicationServerTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    public function testSetGetId()
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setId('ThisIsServerId');
        $this->assertSame('ThisIsServerId', $serverNode->getId());
    }

    public function testSetGetIp()
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setIp('11.11.11.11');
        $this->assertSame('11.11.11.11', $serverNode->getIp());
    }

    public function testSetGetTimeStamp()
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setTimestamp(123456789);
        $this->assertSame(123456789, $serverNode->getTimestamp());
    }

    public function testSetGetLastFrontendUsage()
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setLastFrontendUsage(123456789);
        $this->assertSame(123456789, $serverNode->getLastFrontendUsage());
    }

    public function testSetGetLastAdminUsage()
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setLastAdminUsage(123456789);
        $this->assertSame(123456789, $serverNode->getLastAdminUsage());
    }

    /**
     * @dataProvider dataProviderServerIsInUse
     *
     * @param int  $currentTime    The current timestamp.
     * @param int  $serverTime     The server timestamp.
     * @param bool $expectedResult Expected result
     */
    public function testServerIsInUse($currentTime, $serverTime, $expectedResult)
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setTimestamp($serverTime);
        $this->assertSame($expectedResult, $serverNode->isInUse($currentTime));
    }

    /**
     * Data provider for the test method .
     */
    public function dataProviderServerIsInUse()
    {
        $currentTime = 1400000000;
        return array(
            array(null, $currentTime - (25 * 3600), true),         // If server timestamp is not set at all.
            array(1, $currentTime - (25 * 3600), true),            // If server timestamp is not valid.
            array($currentTime, $currentTime - (25 * 3600), false),// If server TTL has exceeded.
            array($currentTime, $currentTime - (11 * 3600), true)  // If server TTL didn't exceeded.
        );
    }

    /**
     * @dataProvider dataProviderNeedToDeleteAppServer
     *
     * @param int  $currentTime    The current timestamp.
     * @param int  $serverTime     The server timestamp.
     * @param bool $expectedResult Expected result
     */
    public function testNeedToDeleteAppServer($currentTime, $serverTime, $expectedResult)
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setTimestamp($serverTime);
        $this->assertSame($expectedResult, $serverNode->needToDelete($currentTime));
    }

    /**
     * Data provider for the test method .
     */
    public function dataProviderNeedToDeleteAppServer()
    {
        $currentTime = 1400000000;
        return array(
            array(null, $currentTime - (73 * 3600), false),       // Don't remove server if timestamp is not set at all.
            array(1, $currentTime - (73 * 3600), false),          // Don't remove server if timestamp is not valid.
            array($currentTime, $currentTime - (73 * 3600), true),// Remove server if its TTL has exceeded.
            array($currentTime, $currentTime - (11 * 3600), false)// Don't remove server if its TTL didn't exceeded.
        );
    }

    /**
     * @dataProvider dataProviderNeedToUpdateAppServer
     *
     * @param int  $currentTime    The current timestamp.
     * @param int  $serverTime     The server timestamp.
     * @param bool $expectedResult Expected result
     */
    public function testNeedToUpdateAppServer($currentTime, $serverTime, $expectedResult)
    {
        $serverNode = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $serverNode->setTimestamp($serverTime);
        $this->assertSame($expectedResult, $serverNode->needToUpdate($currentTime));
    }

    /**
     * Data provider for the test method .
     */
    public function dataProviderNeedToUpdateAppServer()
    {
        $currentTime = 1400000000;
        return array(
            array(null, $currentTime - (25 * 3600), true),         // Update server if server time is not set at all.
            array($currentTime, $currentTime - (25 * 3600), true), // Time when server information must be updated.
            array($currentTime, $currentTime - (24 * 3600), true), // Exact time when server information must be updated.
            array($currentTime, $currentTime - (11 * 3600), false),// Time when server information is up to date.
            array($currentTime, $currentTime, false),              // When node time is the same as current time.
            array($currentTime, $currentTime + (11 * 3600), true)  // Update server if server time is not valid.
        );
    }
}
