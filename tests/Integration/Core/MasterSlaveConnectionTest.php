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

use oxDb;
use OxidEsales\EshopCommunity\Core\DatabaseProvider;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Class MasterSlaveConnectionTest
 *
 * @covers \OxidEsales\EshopCommunity\Core\DatabaseProvider
 */
class MasterSlaveConnectionTest extends UnitTestCase
{
    /** @var mixed Backing up for earlier value of database link object */
    private $dbObjectBackup = null;

    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbObjectBackup = $this->getProtectedClassProperty(oxDb::getInstance(), 'db');

        $this->setProtectedClassProperty(oxDb::getInstance(), 'db', null);
        $this->assertNull($this->getProtectedClassProperty(oxDb::getInstance(), 'db'));
    }

    /**
     * Executed after test is down.
     */
    protected function tearDown(): void
    {
        oxDb::getDb()->closeConnection();

        $this->setProtectedClassProperty(oxDb::getInstance(), 'db', $this->dbObjectBackup);

        oxDb::getDb()->closeConnection();

        parent::tearDown();
    }

    /**
     * Test case that we have no master slave setup.
     */
    public function testGetDb()
    {
        if ('EE' == $this->getTestConfig()->getShopEdition()) {
            $this->markTestSkipped('Test is for CE/PE only.');
        }

        $connection = oxDb::getDb();
        $this->assertTrue(is_a($connection, 'OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database'));

        $dbConnection = $this->getProtectedClassProperty($connection, 'connection');
        $this->assertTrue(is_a($dbConnection, 'Doctrine\DBAL\Connection'));
        $this->assertFalse(is_a($dbConnection, 'Doctrine\DBAL\Connections\MasterSlaveConnection'));
        $this->assertSame($this->getConfig()->getConfigParam('dbHost'), $dbConnection->getHost());
    }

    /**
     * Test case that we have no master slave setup.
     */
    public function testGetMasterNoMasterSlavesetup(): void
    {
        if ('EE' == $this->getTestConfig()->getShopEdition()) {
            $this->markTestSkipped('Test is for CE/PE only.');
        }

        $connection = oxDb::getMaster();
        $this->assertTrue(is_a($connection, 'OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database'));

        $dbConnection = $this->getProtectedClassProperty($connection, 'connection');
        $this->assertTrue(is_a($dbConnection, 'Doctrine\DBAL\Connection'));
        $this->assertFalse(is_a($dbConnection, 'Doctrine\DBAL\Connections\MasterSlaveConnection'));
        $this->assertSame($this->getConfig()->getConfigParam('dbHost'), $dbConnection->getHost());
    }

    /**
     * Test case that we have no master slave setup and force master.
     */
    public function testForceMasterNoMasterSlavesetup(): void
    {
        if ('EE' == $this->getTestConfig()->getShopEdition()) {
            $this->markTestSkipped('Test is for CE/PE only.');
        }

        $connection = oxDb::getDb();
        $connection->forceMasterConnection();
        $this->assertTrue(is_a($connection, 'OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database'));

        $dbConnection = $this->getProtectedClassProperty($connection, 'connection');
        $this->assertTrue(is_a($dbConnection, 'Doctrine\DBAL\Connection'));
        $this->assertFalse(is_a($dbConnection, 'Doctrine\DBAL\Connections\MasterSlaveConnection'));
        $this->assertSame($this->getConfig()->getConfigParam('dbHost'), $dbConnection->getHost());
    }

    /**
     * Test case that we have no master slave setup and force slave.
     */
    public function testForceSlaveNoMasterSlavesetup(): void
    {
        if ('EE' == $this->getTestConfig()->getShopEdition()) {
            $this->markTestSkipped('Test is for CE/PE only.');
        }

        $connection = oxDb::getDb();
        $connection->forceSlaveConnection();
        $this->assertTrue(is_a($connection, 'OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database'));

        $dbConnection = $this->getProtectedClassProperty($connection, 'connection');
        $this->assertTrue(is_a($dbConnection, 'Doctrine\DBAL\Connection'));
        $this->assertFalse(is_a($dbConnection, 'Doctrine\DBAL\Connections\MasterSlaveConnection'));
        $this->assertSame($this->getConfig()->getConfigParam('dbHost'), $dbConnection->getHost());
    }
}
