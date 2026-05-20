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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Service;

/**
 * @covers \OxidEsales\EshopCommunity\Core\Service\ApplicationServerService
 */
class ApplicationServerServiceTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute("DELETE FROM oxconfig WHERE oxvarname like 'aServersData_%'");
    }

    public function testLoadAppServerList()
    {
        $appServerDao = $this->getApplicationServerDaoMock('findAll', ['foundAppServer']);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals(['foundAppServer'], $service->loadAppServerList());
    }

    public function testDeleteAppServer()
    {
        $id = 'testId';

        $appServerDao = $this->getApplicationServerDaoMock('delete', $id);

        $service = $this->getApplicationServerService($appServerDao);

        $service->deleteAppServerById($id);
    }

    public function testLoadAppServer()
    {
        $id = 'testId';

        $appServerDao = $this->getApplicationServerDaoMock('findAppServer', $id);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals($id, $service->loadAppServer($id));
    }

    public function testLoadAppServerDoesNotExists()
    {
        $this->expectException(\OxidEsales\Eshop\Core\Exception\NoResultException::class);
        $id = 'testId';

        $appServerDao = $this->getApplicationServerDaoMock('findAppServer', null);

        $service = $this->getApplicationServerService($appServerDao);

        $service->loadAppServer($id);
    }

    public function testSaveAppServerIfExists()
    {
        $id = 'testId';

        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAppServer', 'update'])
            ->getMock();
        $appServerDao->expects($this->once())->method('findAppServer')->will($this->returnValue($id));
        $appServerDao->expects($this->once())->method('update')->will($this->returnValue($id));

        $server = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $server->setId($id);

        $service = $this->getApplicationServerService($appServerDao);
        $service->saveAppServer($server);
    }

    public function testSaveAppServerNewElement()
    {
        $id = 'testId';

        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAppServer', 'insert'])
            ->getMock();
        $appServerDao->expects($this->once())->method('findAppServer')->will($this->returnValue(null));
        $appServerDao->expects($this->once())->method('insert')->will($this->returnValue($id));

        $server = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $server->setId($id);

        $service = $this->getApplicationServerService($appServerDao);

        $service->saveAppServer($server);
    }

    public function testLoadActiveAppServerListIfServerIsValid()
    {
        $currentTime = \OxidEsales\Eshop\Core\Registry::getUtilsDate()->getTime();

        $server = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $server->setId('serverNameHash1');
        $server->setTimestamp($currentTime - (11 * 3600));
        $server->setIp('127.0.0.1');
        $server->setLastAdminUsage('adminUsageTimestamp');

        $appServerDao = $this->getApplicationServerDaoMock('findAll', [$server]);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals(['serverNameHash1' => $server], $service->loadActiveAppServerList());
    }

    public function testLoadActiveAppServerListIfServerIsNotValid()
    {
        $currentTime = \OxidEsales\Eshop\Core\Registry::getUtilsDate()->getTime();

        $server = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $server->setId('serverNameHash1');
        $server->setTimestamp($currentTime - (25 * 3600));
        $server->setIp('127.0.0.1');
        $server->setLastAdminUsage('adminUsageTimestamp');

        $appServerDao = $this->getApplicationServerDaoMock('findAll', [$server]);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals([], $service->loadActiveAppServerList());
    }

    public function testLoadActiveAppServerListIfNoServersFound()
    {
        $appServerDao = $this->getApplicationServerDaoMock('findAll', []);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals([], $service->loadActiveAppServerList());
    }

    public function testUpdateAppServerInformationNewAppServer()
    {
        $id = 'testId';

        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->expects($this->once())->method('findAppServer')->will($this->returnValue(null));
        $appServerDao->expects($this->once())->method('findAll')->will($this->returnValue([]));
        $appServerDao->expects($this->once())->method('save')->will($this->returnValue($id));

        $utilsServer = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsServer::class)
            ->setMethods(['getServerNodeId', 'getServerIp'])
            ->getMock();
        $utilsServer->expects($this->any())->method('getServerNodeId')->will($this->returnValue('serverNameHash2'));
        $utilsServer->expects($this->any())->method('getServerIp')->will($this->returnValue('127.0.0.1'));

        $currentTime = \OxidEsales\Eshop\Core\Registry::getUtilsDate()->getTime();
        $service = oxNew(\OxidEsales\Eshop\Core\Service\ApplicationServerService::class, $appServerDao, $utilsServer, $currentTime);
        $service->updateAppServerInformationInFrontend();
    }

    public function testUpdateAppServerInformationInAdminWritesAdminUsage(): void
    {
        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->expects($this->once())->method('findAppServer')->willReturn(null);
        $appServerDao->expects($this->once())->method('findAll')->willReturn([]);
        $captured = null;
        $appServerDao->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($appServer) use (&$captured) {
                $captured = $appServer;
            });

        $utilsServer = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsServer::class)
            ->setMethods(['getServerNodeId', 'getServerIp'])
            ->getMock();
        $utilsServer->method('getServerNodeId')->willReturn('admin-node');
        $utilsServer->method('getServerIp')->willReturn('10.0.0.1');

        $currentTime = 17_000_000_00;
        $service = new \OxidEsales\EshopCommunity\Core\Service\ApplicationServerService(
            $appServerDao,
            $utilsServer,
            $currentTime
        );
        $service->updateAppServerInformationInAdmin();

        $this->assertNotNull($captured);
        $this->assertSame('admin-node', $captured->getId());
        $this->assertSame('10.0.0.1', $captured->getIp());
        $this->assertSame($currentTime, $captured->getLastAdminUsage());
        // Frontend timestamp must NOT have been touched by the admin path.
        $this->assertEmpty($captured->getLastFrontendUsage() ?? '');
    }

    public function testUpdateAppServerInformationUpdatesExistingServerOnAdminCall(): void
    {
        $existingServer = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $existingServer->setId('node-7');
        $existingServer->setIp('10.0.0.7');
        $existingServer->setTimestamp(0); // forces needToUpdate() to return true

        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->method('findAppServer')->willReturn($existingServer);
        $appServerDao->method('findAll')->willReturn([]);
        $captured = null;
        $appServerDao->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($appServer) use (&$captured) {
                $captured = $appServer;
            });

        $utilsServer = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsServer::class)
            ->setMethods(['getServerNodeId', 'getServerIp'])
            ->getMock();
        $utilsServer->method('getServerNodeId')->willReturn('node-7');
        $utilsServer->method('getServerIp')->willReturn('10.0.0.7');

        $currentTime = 17_000_000_00;
        $service = new \OxidEsales\EshopCommunity\Core\Service\ApplicationServerService(
            $appServerDao,
            $utilsServer,
            $currentTime
        );
        $service->updateAppServerInformationInAdmin();

        $this->assertSame($existingServer, $captured, 'Update branch must save the existing server, not a fresh one.');
        $this->assertSame($currentTime, $existingServer->getLastAdminUsage());
        $this->assertSame($currentTime, $existingServer->getTimestamp());
    }

    public function testUpdateAppServerInformationRollsBackOnException(): void
    {
        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->expects($this->once())->method('startTransaction');
        $appServerDao->expects($this->once())->method('rollbackTransaction');
        $appServerDao->expects($this->never())->method('commitTransaction');
        $appServerDao->method('findAppServer')->willThrowException(new \RuntimeException('DB unreachable'));

        $utilsServer = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsServer::class)
            ->setMethods(['getServerNodeId', 'getServerIp'])
            ->getMock();
        $utilsServer->method('getServerNodeId')->willReturn('node-x');
        $utilsServer->method('getServerIp')->willReturn('10.0.0.99');

        $service = new \OxidEsales\EshopCommunity\Core\Service\ApplicationServerService(
            $appServerDao,
            $utilsServer,
            17_000_000_00
        );

        $this->expectException(\RuntimeException::class);
        $service->updateAppServerInformation(false);
    }

    public function testUpdateAppServerInformationCommitsOnSuccess(): void
    {
        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->expects($this->once())->method('startTransaction');
        $appServerDao->expects($this->once())->method('commitTransaction');
        $appServerDao->expects($this->never())->method('rollbackTransaction');
        // Existing server that doesn't need updating → straight to commit.
        $existingServer = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $existingServer->setId('node-fresh');
        $existingServer->setTimestamp(time()); // very recent → needToUpdate() false
        $appServerDao->method('findAppServer')->willReturn($existingServer);

        $utilsServer = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsServer::class)
            ->setMethods(['getServerNodeId', 'getServerIp'])
            ->getMock();
        $utilsServer->method('getServerNodeId')->willReturn('node-fresh');

        $service = new \OxidEsales\EshopCommunity\Core\Service\ApplicationServerService(
            $appServerDao,
            $utilsServer,
            time()
        );
        $service->updateAppServerInformation(true);
    }

    public function testUpdateAppServerInformationCleansUpStaleServers(): void
    {
        // A second server is stale and should be deleted during cleanup.
        $staleServer = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $staleServer->setId('stale-server');
        $staleServer->setTimestamp(0); // way too old → needToDelete() true
        $staleServer->setLastAdminUsage(0);
        $staleServer->setLastFrontendUsage(0);

        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->method('findAppServer')->willReturn(null);
        $appServerDao->method('findAll')->willReturn(['stale-server' => $staleServer]);
        $appServerDao->expects($this->once())
            ->method('delete')
            ->with('stale-server');

        $utilsServer = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsServer::class)
            ->setMethods(['getServerNodeId', 'getServerIp'])
            ->getMock();
        $utilsServer->method('getServerNodeId')->willReturn('node-new');
        $utilsServer->method('getServerIp')->willReturn('10.0.0.1');

        $currentTime = time();
        $service = new \OxidEsales\EshopCommunity\Core\Service\ApplicationServerService(
            $appServerDao,
            $utilsServer,
            $currentTime
        );
        $service->updateAppServerInformationInFrontend();
    }

    private function getApplicationServerDaoMock($methodToMock, $expectedReturnValue)
    {
        $appServerDao = $this->getMockBuilder(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appServerDao->expects($this->once())->method($methodToMock)->will($this->returnValue($expectedReturnValue));

        return $appServerDao;
    }

    private function getApplicationServerService($appServerDao)
    {
        $utilsServer = oxNew(\OxidEsales\Eshop\Core\UtilsServer::class);
        $currentTime = \OxidEsales\Eshop\Core\Registry::getUtilsDate()->getTime();

        return oxNew(
            \OxidEsales\Eshop\Core\Service\ApplicationServerService::class,
            $appServerDao,
            $utilsServer,
            $currentTime
        );
    }
}
