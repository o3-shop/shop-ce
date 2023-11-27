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

use \OxidEsales\Eshop\Core\Registry;
use \OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * @covers \OxidEsales\Eshop\Core\Service\ApplicationServerExporter
 */
class ApplicationServerExporterTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    /**
     * @param array $activeServers            An array of application servers.
     * @param int   $count                    Expected count of application servers.
     * @param array $expectedServerCollection Expected output.
     *
     * @dataProvider dataProviderForExportApplicationServerList
     */
    public function testExport($activeServers, $count, $expectedServerCollection)
    {
        $this->markTestSkipped('Review with D.S. Only EE?');
        $service = $this->getApplicationServerServiceMock($activeServers);
        $exporter = oxNew(\OxidEsales\Eshop\Core\Service\ApplicationServerExporter::class, $service);

        $appServers = $exporter->exportAppServerList();

        $this->assertCount($count, $appServers);

        $this->assertEquals($expectedServerCollection, $appServers[0]);
    }

    /**
     * Data provider for the test method testExport.
     */
    public function dataProviderForExportApplicationServerList()
    {
        $server = oxNew(\OxidEsales\Eshop\Core\DataObject\ApplicationServer::class);
        $server->setId('serverNameHash1');
        $server->setTimestamp('createdTimestamp');
        $server->setIp('127.0.0.1');
        $server->setLastFrontendUsage('frontendUsageTimestamp');

        $activeServers = array($server);
        $activeServers2 = array($server, $server);

        $expectedServerCollection = array(
            'id'                => 'serverNameHash1',
            'ip'                => '127.0.0.1',
            'lastFrontendUsage' => 'frontendUsageTimestamp',
            'lastAdminUsage'    => ''
        );

        return [
            [false, 0, null],
            [[], 0, null],
            [$activeServers, 1, $expectedServerCollection],
            [$activeServers2, 2, $expectedServerCollection],
        ];
    }

    /**
     * @param array $appServerList An array of application servers to return.
     *
     * @return \OxidEsales\Eshop\Core\Service\ApplicationServerService
     */
    private function getApplicationServerServiceMock($appServerList)
    {
        $appServer = $this->getMockBuilder('\OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface')->getMock();
        $appServer->expects($this->any())->method('loadActiveAppServerList')->will($this->returnValue($appServerList));

        return $appServer;
    }
}
