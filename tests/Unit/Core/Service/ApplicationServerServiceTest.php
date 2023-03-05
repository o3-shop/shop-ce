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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

/**
 * @covers \OxidEsales\Eshop\Core\Service\ApplicationServerService
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
        $appServerDao = $this->getApplicationServerDaoMock("findAll", ['foundAppServer']);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals(array('foundAppServer'), $service->loadAppServerList());
    }

    public function testDeleteAppServer()
    {
        $id = 'testId';

        $appServerDao = $this->getApplicationServerDaoMock("delete", $id);

        $service = $this->getApplicationServerService($appServerDao);

        $service->deleteAppServerById($id);
    }

    public function testLoadAppServer()
    {
        $id = 'testId';

        $appServerDao = $this->getApplicationServerDaoMock("findAppServer", $id);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals($id, $service->loadAppServer($id));
    }

    public function testLoadAppServerDoesNotExists()
    {
        $this->expectException(\OxidEsales\Eshop\Core\Exception\NoResultException::class);
        $id = 'testId';

        $appServerDao = $this->getApplicationServerDaoMock("findAppServer", null);

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

        $appServerDao = $this->getApplicationServerDaoMock("findAll", [$server]);

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

        $appServerDao = $this->getApplicationServerDaoMock("findAll", [$server]);

        $service = $this->getApplicationServerService($appServerDao);

        $this->assertEquals([], $service->loadActiveAppServerList());
    }

    public function testLoadActiveAppServerListIfNoServersFound()
    {
        $appServerDao = $this->getApplicationServerDaoMock("findAll", []);

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
